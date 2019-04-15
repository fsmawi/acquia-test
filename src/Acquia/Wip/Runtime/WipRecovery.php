<?php

namespace Acquia\Wip\Runtime;

use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\DependencyManager;
use Acquia\Wip\DependencyManagerInterface;
use Acquia\Wip\Environment;
use Acquia\Wip\Exception\HasRowLockException;
use Acquia\Wip\Exception\NoThreadException;
use Acquia\Wip\Exception\RowLockException;
use Acquia\Wip\Lock\WipPoolRowLock;
use Acquia\Wip\Storage\ThreadStoreInterface;
use Acquia\Wip\Storage\WipPoolStoreInterface;
use Acquia\Wip\Storage\WipStoreInterface;
use Acquia\Wip\Task;
use Acquia\Wip\TaskExitStatus;
use Acquia\Wip\TaskInterface;
use Acquia\Wip\TaskStatus;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipLogLevel;

/**
 * Determines if Threads and Tasks are in an inconsistent state in the database, and optionally fixes them.
 */
class WipRecovery implements DependencyManagedInterface {

  /**
   * After which, it is assumed the thread and task may have gone awry.
   */
  const CLEAN_UP_THRESHOLD = 120;

  /**
   * After which, it is assumed the thread and task has definitely gone wrong.
   */
  const FAIL_THRESHOLD = 300;

  /**
   * This WipFactory resource name.
   */
  const RESOURCE_NAME = 'acquia.wip.recovery';

  /**
   * Booleans constants of clean up actions for readability.
   */
  const TASK_WAIT = TRUE;
  const TASK_NO_WAIT = FALSE;
  const TASK_FAIL = TRUE;
  const TASK_NO_FAIL = FALSE;
  const THREAD_DELETE = TRUE;
  const THREAD_NO_DELETE = FALSE;
  const PROCESS_KILL = TRUE;
  const PROCESS_NO_KILL = FALSE;

  /**
   * An instance of DependencyManager.
   *
   * @var DependencyManagerInterface
   */
  protected $dependencyManager = NULL;

  /**
   * An instance of WipPoolStore.
   *
   * @var WipPoolStoreInterface
   */
  protected $wipPoolStore = NULL;

  /**
   * An instance of ThreadStore.
   *
   * @var ThreadStoreInterface
   */
  protected $threadStore = NULL;

  /**
   * The wip logger.
   *
   * @var WipLogInterface
   */
  protected $logger = NULL;

  /**
   * An instance of WipStore.
   *
   * @var WipStoreInterface
   */
  protected $wipStore = NULL;

  /**
   * The IDs of servers for narrowing the recovery.
   *
   * @var int[]
   */
  protected $serverIds = array();

  /**
   * Thread IDs and corresponding conditions that need to be fixed.
   *
   * @var DatabaseRecovery[]
   */
  protected $inconsistentThreads = array();

  /**
   * Task IDs and corresponding conditions that need to be fixed.
   *
   * @var DatabaseRecovery[]
   */
  protected $inconsistentTasks = array();

  /**
   * The output format.
   *
   * @var string
   */
  private $format = 'json';

  /**
   * The metric utility.
   *
   * @var MetricsUtility
   */
  private $metric;

  /**
   * Initializes the WipRecovery object.
   *
   * @param int[] $server_ids
   *   IDs of servers on which to narrow the search for inconsistent threads.
   * @param string $format
   *   The format of the report, either 'text' or 'json'.
   *
   * @throws \Acquia\Wip\Exception\DependencyMissingException
   * @throws \Acquia\Wip\Exception\DependencyTypeException
   */
  public function __construct($server_ids = [], $format = 'json') {
    $this->setFormat($format);
    $this->serverIds = $server_ids;
    $this->dependencyManager = new DependencyManager();
    $this->dependencyManager->addDependencies($this->getDependencies());
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.wiplog' => 'Acquia\Wip\WipLogInterface',
      'acquia.wip.storage.thread' => 'Acquia\Wip\Storage\ThreadStoreInterface',
      'acquia.wip.storage.wippool' => 'Acquia\Wip\Storage\WipPoolStoreInterface',
      'acquia.wip.storage.wip' => 'Acquia\Wip\Storage\WipStoreInterface',
      'acquia.wip.lock.rowlock.wippool' => 'Acquia\Wip\Lock\RowLockInterface',
    );
  }

  /**
   * Gets the metrics utility.
   *
   * @return Client
   *   The http client.
   */
  public function getMetricsUtility() {
    return !is_null($this->metric) ? $this->metric : new MetricsUtility(new Client());
  }

  /**
   * Sets the metrics utility.
   *
   * @param MetricsUtility $metric
   *    The metrics utility.
   */
  public function setMetricsUtility(MetricsUtility $metric) {
    $this->metric = $metric;
  }

  /**
   * Retrieves the log dependency.
   *
   * @return WipLogInterface
   *   The WipLog instance.
   */
  private function getLogger() {
    if ($this->logger === NULL) {
      $this->logger = $this->dependencyManager->getDependency('acquia.wip.wiplog');
    }
    return $this->logger;
  }

  /**
   * Retrieves the WipPoolStore dependency.
   *
   * @return WipPoolStoreInterface
   *   The WipPoolStore instance.
   */
  private function getWipPoolStore() {
    if ($this->wipPoolStore === NULL) {
      $this->wipPoolStore = $this->dependencyManager->getDependency('acquia.wip.storage.wippool');
    }
    return $this->wipPoolStore;
  }

  /**
   * Retrieves the WipStore.
   *
   * @return WipStoreInterface
   *   The WipStore instance.
   */
  private function getWipStore() {
    if ($this->wipStore === NULL) {
      $this->wipStore = $this->dependencyManager->getDependency('acquia.wip.storage.wip');
    }
    return $this->wipStore;
  }

  /**
   * Retrieves the ThreadStore dependency.
   *
   * @return ThreadStoreInterface
   *   The ThreadStore instance.
   */
  private function getThreadStore() {
    if ($this->threadStore === NULL) {
      $this->threadStore = $this->dependencyManager->getDependency('acquia.wip.storage.thread');
    }
    return $this->threadStore;
  }

  /**
   * Adds a DatabaseRecovery object to the array for an inconsistent thread.
   *
   * @param int $thread_id
   *   The ID of the thread.
   * @param bool $delete
   *   Whether to delete the thread.
   * @param bool $kill
   *   Whether to kill the thread's process.
   * @param string $reason
   *   The description of the inconsistency.
   */
  private function addInconsistentThread($thread_id, $delete, $kill, $reason) {
    $this->inconsistentThreads[$thread_id] = new DatabaseRecovery($reason, $delete, $kill);
  }

  /**
   * Adds a DatabaseRecovery object to the array of inconsistent tasks.
   *
   * @param int $task_id
   *   The ID of the task.
   * @param bool $wait
   *   Whether to set the task's run status to waiting.
   * @param bool $fail
   *   Whether to fail the task.
   * @param string $reason
   *   A description of the inconsistency.
   */
  private function addInconsistentTask($task_id, $wait, $fail, $reason) {
    $this->inconsistentTasks[$task_id] = new DatabaseRecovery($reason, FALSE, FALSE, $wait, $fail);
  }

  /**
   * Determines the interval after which to start checking for inconsistencies.
   *
   * @return int
   *   The duration after which to start checking for inconsistencies
   */
  private function getCleanupThreshold() {
    return WipFactory::getInt('$acquia.wip.database.cleanup.threshold ', self::CLEAN_UP_THRESHOLD);
  }

  /**
   * Determines the interval after which to fail in progress work.
   *
   * @return int
   *   The duration of time after which to fail work.
   */
  private function getFailThreshold() {
    return WipFactory::getInt('$acquia.wip.database.cleanup.fail ', self::FAIL_THRESHOLD);
  }

  /**
   * Determines inconsistencies of threads and tasks in the database.
   */
  public function evaluate() {
    $this->evaluateProcessingTasks();
    $this->evaluateRunningThreads();
  }

  /**
   * Assesses all processing tasks for inconsistencies.
   */
  private function evaluateProcessingTasks() {
    /** @var TaskInterface[] $processing_tasks */
    $processing_tasks = $this->getWipPoolStore()->findProcessingTasks();
    foreach ($processing_tasks as $processing_task) {
      $task_id = $processing_task->getId();
      // Processing tasks should have a running thread with running process.
      try {
        WipPoolRowLock::getWipPoolRowLock($task_id, NULL, $this->dependencyManager)
          ->setTimeout(0)
          ->runAtomic(
            $this,
            'checkForBrokenTask',
            array($processing_task)
          );
      } catch (RowLockException $e) {
        // Failed to acquire the row lock, which means a separate database
        // connection is actively updating the row. This task is likely not
        // orphaned so ignore it.
      }
    }
  }

  /**
   * Determines if the task is inconsistent and how to clean up the database.
   *
   * @param Task $processing_task
   *   A task that is marked processing in the database.
   *
   * @throws HasRowLockException
   *   If the WipPool row lock is not held by this process at the time of the
   *   method call.
   */
  public function checkForBrokenTask(Task $processing_task) {
    if (!WipPoolRowLock::getWipPoolRowLock(
      $processing_task->getId(),
      NULL,
      $this->dependencyManager
    )->hasLock()
    ) {
      throw new HasRowLockException(
        sprintf('%s must be called with the WipPool row lock.', __METHOD__)
      );
    }

    // Some time may have passed since the task was initially queried.
    $task_id = $processing_task->getId();
    $updated_task = $this->getWipPoolStore()->get($task_id);
    if (FALSE !== $updated_task && $updated_task->getStatus() === TaskStatus::PROCESSING) {
      $wip = $this->getWipStore()->get($task_id);
      try {
        $thread = $this->getThreadStore()->getThreadByTask($processing_task);
        if (time() - $thread->getCreated() < $this->getCleanupThreshold()) {
          return;
        }
        $thread_id = $thread->getId();
        $completed = FALSE;
        if ($process = $thread->getProcess()) {
          $completed = $process->hasCompleted($this->getLogger());
        }
        if ($wip === FALSE) {
          // WIP is corrupt.
          $fail_task = self::TASK_FAIL;
          $task_reason = sprintf('Failing task %d because its WIP is corrupt', $task_id);
          if (!$completed) {
            $kill_process = self::PROCESS_KILL;
            $thread_reason = sprintf(
              'Deleting thread %d and killing its process because its WIP is corrupt',
              $thread_id
            );
          } else {
            $kill_process = self::PROCESS_NO_KILL;
            $thread_reason = sprintf('Deleting thread %d because its WIP is corrupt', $thread_id);
          }

          $this->addInconsistentTask($task_id, self::TASK_NO_WAIT, $fail_task, $task_reason);
          $this->addInconsistentThread($thread_id, self::THREAD_DELETE, $kill_process, $thread_reason);
        }

        // WIP exists.
        if (!$completed) {
          $claim_time = $updated_task->getClaimedTimestamp();
          if (time() - $claim_time >= $this->getFailThreshold()) {
            $this->addInconsistentTask(
              $task_id,
              self::TASK_WAIT,
              self::TASK_FAIL,
              sprintf(
                'Failing task %d because it has been running too long on a single thread, and likely defunct',
                $task_id
              )
            );
            $this->addInconsistentThread(
              $thread_id,
              self::THREAD_DELETE,
              self::PROCESS_KILL,
              sprintf(
                'Deleting thread %d because it has been running too long and is likely defunct',
                $thread_id
              )
            );
          }
          return;
        }
        $updated = $this->getWipStore()->getTimestampByWipId($task_id);
        $created = $thread->getCreated();
        // Check if WIP was updated after the thread was created.
        if ($updated > $created) {
          // The Wip was updated.
          $this->addInconsistentTask(
            $task_id,
            self::TASK_WAIT,
            self::TASK_NO_FAIL,
            sprintf(
              'Updating task %d because there was no update after the end of the process execution',
              $task_id
            )
          );
          $this->addInconsistentThread(
            $thread_id,
            self::THREAD_DELETE,
            self::PROCESS_NO_KILL,
            sprintf(
              'Deleting thread %d because it was not updated after the WIP was updated at the end of process execution',
              $thread_id
            )
          );
        } else {
          // The Wip was not updated.
          $this->addInconsistentTask(
            $task_id,
            self::TASK_WAIT,
            self::TASK_FAIL,
            sprintf(
              'Failing task %d, if possible, or setting back to WAITING because it ran without updating its WIP',
              $task_id
            )
          );
          $this->addInconsistentThread(
            $thread_id,
            self::THREAD_DELETE,
            self::PROCESS_NO_KILL,
            sprintf(
              'Deleting thread %d because it ran without updating its WIP',
              $thread_id
            )
          );
        }
      } catch (NoThreadException $e) {
        if ($wip === FALSE) {
          $this->addInconsistentTask(
            $task_id,
            self::TASK_NO_WAIT,
            self::TASK_WAIT,
            sprintf(
              'Failing task %d because it was running without a thread and its WIP is corrupt',
              $task_id
            )
          );
        } else {
          $this->addInconsistentTask(
            $task_id,
            self::TASK_WAIT,
            self::TASK_NO_FAIL,
            sprintf(
              'Resetting task %d to WAITING because it was processing without a running thread',
              $task_id
            )
          );
        }
      }
    }
  }

  /**
   * Assesses all running threads for inconsistencies.
   */
  private function evaluateRunningThreads() {
    /** @var Thread[] $running_threads */
    $running_threads = $this->getThreadStore()->getRunningThreads($this->serverIds);
    foreach ($running_threads as $running_thread) {
      $thread_id = $running_thread->getId();
      $task_id = $running_thread->getWipId();

      try {
        WipPoolRowLock::getWipPoolRowLock($task_id, NULL, $this->dependencyManager)
          ->setTimeout(0)
          ->runAtomic(
            $this,
            'checkForBrokenThread',
            array($thread_id, $task_id)
          );
      } catch (RowLockException $e) {
        // Failed to acquire the row lock, which means a separate database
        // connection is actively updating the row. This task is likely not
        // orphaned so ignore it.
      }
    }
  }

  /**
   * Determines if the thread is inconsistent and how to clean up the database.
   *
   * @param int $thread_id
   *   The ID of the thread.
   * @param int $task_id
   *   The ID of the task.
   *
   * @throws HasRowLockException
   *   If the WipPool row lock is not held by this process at the time of the
   *   method call.
   */
  public function checkForBrokenThread($thread_id, $task_id) {
    if (!WipPoolRowLock::getWipPoolRowLock($task_id, NULL, $this->dependencyManager)->hasLock()) {
      throw new HasRowLockException(
        sprintf('%s must be called with the WipPool row lock.', __METHOD__)
      );
    }
    $updated_thread = $this->getThreadStore()->get($thread_id);
    if ($updated_thread === NULL) {
      return;
    }
    if (time() - $updated_thread->getCreated() < $this->getCleanupThreshold()) {
      return;
    }
    $updated_wip_id = $updated_thread->getWipId();
    if ($updated_wip_id !== $task_id) {
      // Something is awry. Skip this thread.
      return;
    }
    $updated_task = $this->getWipPoolStore()->get($task_id);
    if ($updated_task === FALSE) {
      $this->addInconsistentThread(
        $thread_id,
        self::THREAD_DELETE,
        self::PROCESS_NO_KILL,
        sprintf(
          'Deleting thread %d because its task does not exist in the wip_pool',
          $thread_id
        )
      );
      return;
    }
    $completed = FALSE;
    if ($process = $updated_thread->getProcess()) {
      $completed = $process->hasCompleted($this->getLogger());
    }
    $status = $updated_task->getStatus();
    $wip = $this->getWipStore()->get($task_id);
    if ($wip === FALSE) {
      // The Wip is corrupt.
      $kill_process = self::PROCESS_NO_KILL;
      $thread_reason = sprintf('Deleting thread %d because its task is not processing', $thread_id);
      if (!$completed) {
        $kill_process = self::PROCESS_KILL;
        $thread_reason =
          sprintf('Deleting thread %d and killing its process because its task is not processing', $thread_id);
      }

      if (TaskStatus::WAITING === $status) {
        $this->addInconsistentTask(
          $task_id,
          self::TASK_NO_WAIT,
          self::TASK_FAIL,
          sprintf(
            'Failing out task %d because its WIP is corrupt',
            $task_id
          )
        );
      }
      $this->addInconsistentThread($thread_id, self::THREAD_DELETE, $kill_process, $thread_reason);
    } else {
      // The Wip is valid.
      if (TaskStatus::PROCESSING === $status && !$completed) {
        $claim_time = $updated_task->getClaimedTimestamp();
        if (time() - $claim_time >= $this->getFailThreshold()) {
          $this->addInconsistentTask(
            $task_id,
            self::TASK_WAIT,
            self::TASK_FAIL,
            sprintf(
              'Failing task %d because it has been running too long on a single thread, and likely defunct',
              $task_id
            )
          );
          $this->addInconsistentThread(
            $thread_id,
            self::THREAD_DELETE,
            self::PROCESS_KILL,
            sprintf(
              'Deleting thread %d because it has been running too long and is likely defunct',
              $thread_id
            )
          );
        }
        return;
      }
      $updated = $this->getWipStore()->getTimestampByWipId($task_id);
      $created = $updated_thread->getCreated();
      // Check if the Wip was updated after the thread was created.
      if (TaskStatus::WAITING === $status && $updated <= $created) {
        $wait = self::TASK_NO_WAIT;
        if ($completed) {
          $wait = self::TASK_WAIT;
        }
        $this->addInconsistentTask(
          $task_id,
          $wait,
          self::TASK_FAIL,
          sprintf(
            "Failing out task %d because it's waiting but it also has a running thread",
            $task_id
          )
        );
      }

      $kill_process = self::PROCESS_NO_KILL;
      if (!$completed) {
        // The process is still running.
        $kill_process = self::PROCESS_KILL;
      }
      if (TaskStatus::PROCESSING === $status) {
        $thread_reason = sprintf(
          'Deleting thread %d because it was not updated after the process finished',
          $thread_id
        );
      } else {
        $thread_reason = sprintf('Deleting thread %d because its task is not processing', $thread_id);
      }
      $this->addInconsistentThread($thread_id, self::THREAD_DELETE, $kill_process, $thread_reason);
    }
  }

  /**
   * Formats the output describing database inconsistencies.
   *
   * @return string
   *   The formatted report of database inconsistencies.
   */
  public function report() {
    $format = $this->getFormat();
    switch ($format) {
      case 'json':
        $report = new \stdClass();
        $report->serverIds = $this->serverIds;
        $report->inconsistentTasks = $this->inconsistentTasks;
        $report->inconsistentThreads = $this->inconsistentThreads;
        $message = json_encode($report);
        break;

      case 'text':
        $server_ids = implode(', ', $this->serverIds);
        if (empty($server_ids)) {
          $server_ids = 'ALL';
        }
        $threads = '';
        /** @var DatabaseRecovery $databaseRecovery */
        foreach ($this->inconsistentThreads as $id => $databaseRecovery) {
          $threads .= $databaseRecovery->getReason();
        }
        $tasks = '';
        foreach ($this->inconsistentTasks as $id => $databaseRecovery) {
          $tasks .= $databaseRecovery->getReason();
        }
        $message = <<<EOT
For threads with servers IDs among ($server_ids):
$threads
$tasks

Note: Timeout is ignored on paused tasks.
EOT;
        break;

      default:
        throw new \DomainException(sprintf('Invalid format "%s".', $format));
    }
    return $message;
  }

  /**
   * Fixes the database inconsistencies previously evaluated.
   */
  public function fix() {
    // Make sure the server store is updated before updating tasks and threads.
    $environment = Environment::getRuntimeEnvironment();
    $command_path = $environment->getDocrootDir() . '/../bin/wipctl';
    $command = sprintf('%s server update', $command_path);
    exec($command, $output, $server_exit_code);

    /** @var DatabaseRecovery $databaseRecovery */
    foreach ($this->inconsistentTasks as $task_id => $databaseRecovery) {
      try {
        WipPoolRowLock::getWipPoolRowLock($task_id, NULL, $this->dependencyManager)
          ->setTimeout(0)
          ->runAtomic(
            $this,
            'fixTask',
            array($task_id, $databaseRecovery)
          );
        $format = '***INTERNAL SYSTEM FAILURE*** %s';
        $message = sprintf($format, $databaseRecovery->getReason());
        $this->getLogger()->log(WipLogLevel::FATAL, $message, $task_id);
      } catch (RowLockException $e) {
        // Failed to acquire the wip_pool row lock.
        $format = <<<EOT
***INTERNAL SYSTEM FAILURE*** %s
Update failure: %s
EOT;
        $message = sprintf(
          $format,
          $databaseRecovery->getReason(),
          $e->getMessage()
        );
        $this->getLogger()->log(WipLogLevel::FATAL, $message, $task_id);
      }
    }

    /** @var DatabaseRecovery $databaseRecovery */
    foreach ($this->inconsistentThreads as $thread_id => $databaseRecovery) {
      $this->fixThread($thread_id, $databaseRecovery);
      $format = '***INTERNAL SYSTEM FAILURE*** %s.';
      $message = sprintf($format, $databaseRecovery->getReason());
      $this->getLogger()->log(WipLogLevel::FATAL, $message, 0);
    }
  }

  /**
   * Corrects the inconsistent fields for the given task ID in the database.
   *
   * Note that this must be run atomically.
   *
   * @param int $task_id
   *   The ID of an inconsistent Task.
   * @param DatabaseRecovery $database_recovery
   *   The object with database clean-up details.
   *
   * @throws HasRowLockException
   *   If the WipPool row lock is not held by this process at the time of the
   *   method call.
   */
  public function fixTask($task_id, DatabaseRecovery $database_recovery) {
    if (!WipPoolRowLock::getWipPoolRowLock($task_id, NULL, $this->dependencyManager)->hasLock()) {
      throw new HasRowLockException(
        sprintf('%s must be called with the WipPool row lock.', __METHOD__)
      );
    }

    $task = $this->getWipPoolStore()->get($task_id);
    if (FALSE !== $task) {
      $abort_on_failure = WipFactory::getBool('$acquia.wip.dispatch.abort_on_failure', FALSE);

      if ($database_recovery->getFailOutTask() && $abort_on_failure) {
        $task->setStatus(TaskStatus::COMPLETE);
        $task->setExitStatus(TaskExitStatus::ERROR_SYSTEM);
        $task->setExitMessage('Failure due to fatal system error. Please try again.');
        $task->setCompletedTimestamp(time());
        $this->getWipPoolStore()->save($task);
        try {
          $wip = $this->getWipStore()->get($task_id);
          // Send User error metric.
          $this->getMetricsUtility()->sendMetric('count', 'wip.system.job_status.system_error', 1);

          // Send MTD system failure metric.
          $this->getMetricsUtility()->sendMtdSystemFailure();

          $wip->onStatusChange($task);
        } catch (\Exception $e) {
          // If the Wip object is missing or corrupt, there's not much else to do.
          $message = sprintf(
            'The Pipeline service may not have been updated about task %s status: %s',
            $task_id,
            $e->getMessage()
          );
          $this->getLogger()->log(WipLogLevel::ERROR, $message, $task_id);
        }
      } elseif ($database_recovery->getTaskToWaiting() ||
        ($database_recovery->getTaskToWaiting() && $database_recovery->getFailOutTask() && !$abort_on_failure)) {
        $task->setStatus(TaskStatus::WAITING);
        $task->setClaimedTimestamp(0);
        $task->setWakeTimestamp(time());
        $this->getWipPoolStore()->save($task);
      }
    }
  }

  /**
   * Corrects the inconsistent fields for the given thread ID in the database.
   *
   * Note that this must be run atomically.
   *
   * @param int $thread_id
   *   The ID of an inconsistent Thread.
   * @param DatabaseRecovery $database_recovery
   *   The object with database clean-up details.
   */
  private function fixThread($thread_id, DatabaseRecovery $database_recovery) {
    $thread = $this->getThreadStore()->get($thread_id);
    if (NULL !== $thread) {
      if ($database_recovery->getKillProcess()) {
        $process = $thread->getProcess();
        if ($process !== NULL) {
          $process->forceFail($database_recovery->getReason(), $this->getLogger());
          $thread->setProcess($process);
        }
      }
      if ($database_recovery->getDeleteThread()) {
        $this->getThreadStore()->remove($thread);
      }
    }
  }

  /**
   * Sets the format, either 'text' or 'json'.
   *
   * @param string $format
   *   The format.
   */
  private function setFormat($format) {
    if ('text' !== $format && 'json' !== $format) {
      throw new \InvalidArgumentException(
        sprintf('Invalid format "%s" - only "text" and "json" are allowed.', $format)
      );
    }
    $this->format = $format;
  }

  /**
   * Returns the format.
   *
   * @return string
   *   The output format, either 'text' or 'json'.
   */
  private function getFormat() {
    return $this->format;
  }

  /**
   * Resets class variables.
   */
  public function clear() {
    $this->inconsistentTasks = [];
    $this->inconsistentThreads = [];
    $this->serverIds = [];
  }

}
