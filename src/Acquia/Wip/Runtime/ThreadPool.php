<?php

namespace Acquia\Wip\Runtime;

use Acquia\WipIntegrations\DoctrineORM\ServerStore;
use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\DependencyManager;
use Acquia\Wip\Environment;
use Acquia\Wip\Exception\InvalidOperationException;
use Acquia\Wip\Exception\NoObjectException;
use Acquia\Wip\Exception\NoTaskException;
use Acquia\Wip\Exception\NoThreadException;
use Acquia\Wip\Exception\NoWorkerServersException;
use Acquia\Wip\Exception\RowLockException;
use Acquia\Wip\Exception\TaskHasRunningThreadException;
use Acquia\Wip\Exception\TaskNotWaitingException;
use Acquia\Wip\Exception\ThreadIncompleteException;
use Acquia\Wip\Exception\WaitException;
use Acquia\Wip\Lock\WipPoolRowLock;
use Acquia\Wip\Notification\NotificationInterface;
use Acquia\Wip\Notification\NotificationSeverity;
use Acquia\Wip\State\MonitorDaemonPause;
use Acquia\Wip\Storage\BasicWipPoolStore;
use Acquia\Wip\Storage\ServerStoreInterface;
use Acquia\Wip\Storage\StateStoreInterface;
use Acquia\Wip\Storage\ThreadStoreInterface;
use Acquia\Wip\Storage\WipPoolStoreInterface;
use Acquia\Wip\Task;
use Acquia\Wip\TaskExitStatus;
use Acquia\Wip\TaskInterface;
use Acquia\Wip\TaskStatus;
use Acquia\Wip\ThreadPoolDetailInterface;
use Acquia\Wip\ThreadStatus;
use Acquia\Wip\Utility\MetricsUtility;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipLogLevel;
use GuzzleHttp\Client;

/**
 * Manages the distribution of tasks to available servers.
 */
class ThreadPool implements ThreadPoolInterface, DependencyManagedInterface {

  /**
   * The delay used when the maximum tasks in progress limit is reached.
   */
  const MAXIMUM_WORK_IN_PROGRESS_DELAY = 5;

  /**
   * The default delay before checking whether the system has resumed.
   *
   * This is the default number of seconds to wait when the system is paused
   * before determining whether it is still paused.
   */
  const DEFAULT_RESUME_DELAY = 5;

  /**
   * The default delay before checking for tasks after no tasks are available.
   *
   * This is the default number of seconds to wait after the system finds no
   * tasks to process before checking again.
   */
  const DEFAULT_TASKS_NOT_AVAILABLE_DELAY = 5;

  /**
   * The dependency manager.
   *
   * @var DependencyManager
   */
  public $dependencyManager;

  /**
   * Number of seconds that processing should continue for in one chunk.
   *
   * Defaults to the equivalent of 3 minutes.
   *
   * @var int
   */
  static protected $timeLimit = 180;

  /**
   * Set to TRUE to indicate this process should quit ASAP.
   *
   * @var bool
   */
  private $quit = FALSE;

  /**
   * A directory prefix for dispatching commands over Ssh.
   *
   * @var string
   */
  private $directoryPrefix = '';

  /**
   * A callback function used to check if processing can proceed.
   *
   * @var callable
   */
  private $statusCheckCallback;

  /**
   * The Unix timestamp indicating the approximate time this process started.
   *
   * @var int
   */
  private $startTime = 0;

  /**
   * The ProcessDetail class for this instance.
   *
   * @var ThreadPoolProcessDetail
   */
  private $processDetail;

  /**
   * The metric utility.
   *
   * @var MetricsUtility
   */
  private $metric;

  /**
   * Creates a new ThreadPool instance.
   */
  public function __construct() {
    $this->dependencyManager = new DependencyManager();
    $this->dependencyManager->addDependencies($this->getDependencies());
    $this->initializeStartTime();
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.storage.thread' => 'Acquia\Wip\Storage\ThreadStoreInterface',
      'acquia.wip.storage.server' => 'Acquia\Wip\Storage\ServerStoreInterface',
      'acquia.wip.storage.wippool' => 'Acquia\Wip\Storage\WipPoolStoreInterface',
      'acquia.wip.pool' => 'Acquia\Wip\Runtime\WipPoolInterface',
      'acquia.wip.wiplog' => 'Acquia\Wip\WipLogInterface',
      'acquia.wip.notification' => 'Acquia\Wip\Notification\NotificationInterface',
      'acquia.wip.lock.rowlock.wippool' => 'Acquia\Wip\Lock\RowLockInterface',
      WipPoolController::RESOURCE_NAME => 'Acquia\Wip\Runtime\WipPoolControllerInterface',
      'acquia.wip.storage.state' => 'Acquia\Wip\Storage\StateStoreInterface',
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
   * Sets the time limit for processing tasks.
   *
   * @param int $limit
   *   Time limit for processing tasks.
   */
  public function setTimeLimit($limit) {
    static::$timeLimit = $limit;
  }

  /**
   * Returns the time limit for each run of thread pool processing.
   *
   * @return int
   *   The time limit for each run of thread pool processing.
   */
  public function getTimeLimit() {
    return static::$timeLimit;
  }

  /**
   * Returns an instance of WipPoolInterface.
   *
   * @return WipPoolInterface
   *   The WipPoolInterface instance.
   */
  public function getWipPool() {
    return $this->dependencyManager->getDependency('acquia.wip.pool');
  }

  /**
   * Returns an instance of WipLogInterface.
   *
   * @return WipLogInterface
   *   The WipLogInterface instance.
   */
  public function getWipLog() {
    return $this->dependencyManager->getDependency('acquia.wip.wiplog');
  }

  /**
   * Returns an instance of NotificationInterface.
   *
   * @return NotificationInterface
   *   The NotificationInterface instance.
   */
  public function getNotifier() {
    return $this->dependencyManager->getDependency('acquia.wip.notification');
  }

  /**
   * Creates a new log entry.
   *
   * @param int $level
   *   Log level.
   * @param string $message
   *   Information to be logged.
   * @param int $object_id
   *   Optional. The Wip object ID. If provided the log message will be
   *   associated with the specified Wip object.
   * @param bool $user_readable
   *   Optional. If TRUE, the log message will be available to the user.
   */
  private function log($level, $message, $object_id = NULL, $user_readable = FALSE) {
    /** @var WipLogInterface $logger */
    $logger = $this->dependencyManager->getDependency('acquia.wip.wiplog');
    $logger->log($level, $message, $object_id, $user_readable);
  }

  /**
   * Indicates whether this process is out of time and should be shut down.
   *
   * @return bool
   *   TRUE if this process is out of time; FALSE otherwise.
   */
  private function outOfTime() {
    $start_time = $this->getStartTime();
    return (time() >= $start_time + $this->getTimeLimit());
  }

  /**
   * Called when this instance is instantiated, recording the start time.
   */
  private function initializeStartTime() {
    if (empty($this->startTime)) {
      $this->startTime = time();
    }
  }

  /**
   * Gets the start time.
   *
   * @return int
   *   The Unix timestamp indicating when this process started.
   */
  private function getStartTime() {
    return $this->startTime;
  }

  /**
   * Indicates whether this process should be shut down.
   *
   * @return bool
   *   TRUE if this process should exit; FALSE otherwise.
   */
  private function shouldQuit() {
    return ($this->quit || $this->outOfTime() || $this->isPaused() === TRUE);
  }

  /**
   * Gets whether the monitor daemon is paused.
   *
   * @return bool
   *   Whether the monitor daemon is paused.
   */
  private function isPaused() {
    $pause = $this->dependencyManager->getDependency('acquia.wip.storage.state')->get(
      MonitorDaemonPause::STATE_NAME,
      MonitorDaemonPause::$defaultValue
    );
    return $pause === MonitorDaemonPause::ON;
  }

  /**
   * Iterates through tasks assigning each to an available server thread.
   */
  public function process() {
    $start_time = microtime(TRUE);
    $this->processDetail = new ThreadPoolProcessDetail(getmypid());
    $no_thread_count = 0;
    $no_thread_warning_threshold = WipFactory::getInt('$acquia.wip.threadpool.threshold', 50);
    $wip_pool_controller = WipPoolController::getWipPoolController($this->dependencyManager);

    // Clean up group concurrency. This will result in entries associated with
    // completed or deleted Wip objects being removed from the database. Doing
    // this cleanup here means that every time the process starts the cleanup
    // will occur, guaranteeing that a particular Wip group will not be blocked
    // for longer than the timeLimit value.
    $this->getWipPool()->cleanupConcurrency();

    while (!$this->shouldQuit()) {
      $task = NULL;
      $thread = NULL;
      // Configurable callback that can be used to check the system status is ok
      // before proceeding.
      if (!$this->systemStatusOk()) {
        $this->log(WipLogLevel::FATAL, sprintf('System status check error: %s:%s', gethostname(), getmypid()));
        throw new \RuntimeException('System status check indicated a system error. Aborting.');
      }
      if ($wip_pool_controller->isHardPausedGlobal()) {
        $sleep_time = WipFactory::getInt(
          '$acquia.wip.threadpool.resume_delay',
          self::DEFAULT_RESUME_DELAY
        );
        $this->measuredSleep($sleep_time, $this->processDetail);
        continue;
      }

      try {
        // Get the available threads.
        $threads = $this->getAvailableThreads();
        if (count($threads) === 0) {
          // There are no available threads for processing tasks.
          if (++$no_thread_count >= $no_thread_warning_threshold) {
            $format = 'Unable to obtain a thread for %d consecutive iterations. Consider adding more resources. The threshold for this warning is currently %d.';
            $error_message = sprintf($format, $no_thread_count, $no_thread_warning_threshold);
            $this->log(WipLogLevel::ERROR, $error_message);
            // Reset the warning counter.
            $no_thread_count = 0;
          }
          $this->measuredSleep(1, $this->processDetail);
          continue;
        }
        // Reset the warning counter.
        $no_thread_count = 0;
        $this->useThreads($threads);
      } catch (NoWorkerServersException $no_workers) {
        $this->log(WipLogLevel::FATAL, 'No servers are configured. Attempting to recover...');
        $this->recoverFromServerFailure();
      } catch (\Exception $e) {
        // @todo - need to move exception handlers here or remove the try?
        // Maybe keep it for safety.
        // @todo - this is getting in the way.
        $this->log(
          WipLogLevel::FATAL,
          sprintf(
            "Caught exception in process:\n%s\n%s\n",
            $e->getMessage(),
            $e->getTraceAsString()
          )
        );
      }
    }
    $end_time = microtime(TRUE);
    $this->processDetail->setTotalRunTime($end_time - $start_time);
    $this->processDetail->report();
    // @todo - quit and restart should be managed by the console app.
  }

  /**
   * Applies the specified thread to the next task.
   *
   * If there is no task that can be executed, this method will keep trying
   * until the thread has been applied to a task or it is time to exit.
   *
   * @param Thread[] $threads
   *   The set of threads to apply to tasks.
   */
  public function useThreads($threads) {
    $start_time = microtime();
    $thread_count = count($threads);
    $thread_index = 0;
    $wip_pool = $this->getWipPool();

    $this->processDetail->addNewIterationDetails();
    $this->processDetail->getCurrentIterationDetail()->receivedThreadsToMatch($thread_count);

    // Go through the set of threads and try to apply each to a task.
    while ($thread_index < $thread_count && !$this->shouldQuit()) {
      if (empty($tasks)) {
        try {
          $tasks = $wip_pool->getNextTasks($thread_count);
        } catch (NoTaskException $e) {
          $sleep_time = WipFactory::getInt(
            '$acquia.wip.threadpool.no_task_delay',
            self::DEFAULT_TASKS_NOT_AVAILABLE_DELAY
          );
          $this->measuredSleep($sleep_time, $this->processDetail->getCurrentIterationDetail());
          $tasks = NULL;
          continue;
        }
      }
      $this->processDetail->getCurrentIterationDetail()->receivedTasksToMatch(count($tasks));
      // Note the need for an additional thread index check here. It is
      // possible to go beyond the thread count in this inner loop because the
      // getTasks(count) call provides a suggestion as to how many tasks are
      // needed. The actual number returned could be more or less than the
      // suggestion.
      for ($task_index = 0;
           $task_index < count($tasks) && $thread_index < $thread_count && !$this->shouldQuit();
           $task_index++) {
        try {
          $task = $tasks[$task_index];
        } catch (\Exception $e) {
          $this->log(
            WipLogLevel::FATAL,
            sprintf(
              "Error - useThreads() failed to index tasks[%d]: %s\ntasks: %s",
              $task_index,
              $e->getMessage(),
              print_r($tasks, TRUE)
            )
          );
          break;
        }
        $thread = $threads[$thread_index];
        if ($this->shouldQuit()) {
          break 2;
        }
        try {
          WipPoolRowLock::getWipPoolRowLock($task->getId(), NULL, $this->dependencyManager)
            ->setTimeout(0)
            ->runAtomic($this, 'atomicUseThreadOnTask', [$task->getId(), $thread]);
          $thread_index++;
          $this->processDetail->getCurrentIterationDetail()->recordSuccessfulMatch();
          continue;
        } catch (RowLockException $e) {
          // Failed to get the update lock. Move to the next task.
          $this->processDetail->getCurrentIterationDetail()->recordFailedMatch($e);
          continue;
        } catch (TaskHasRunningThreadException $e) {
          // Skip the task and reuse the thread.
          $this->processDetail->getCurrentIterationDetail()->recordFailedMatch($e);
          continue;
        } catch (NoObjectException $e) {
          // Skip this task.
          $this->processDetail->getCurrentIterationDetail()->recordFailedMatch($e);
          continue;
        } catch (TaskNotWaitingException $e) {
          // The task is not in the 'WAITING' state, so it cannot be
          // dispatched.
          $this->processDetail->getCurrentIterationDetail()->recordFailedMatch($e);
          continue;
        } catch (WaitException $e) {
          // Do not wait if this process is supposed to quit.
          $this->processDetail->getCurrentIterationDetail()->recordFailedMatch($e);
          if ($this->shouldQuit()) {
            break 2;
          }
          $this->measuredSleep($e->getWait(), $this->processDetail->getCurrentIterationDetail());

          // Given that there was a sleep, the task priorities have likely
          // changed. Start over, but use the same thread.
          $tasks = NULL;
          break;
        } catch (\Exception $e) {
          // The dispatch failed. Log and move on to the next task.
          $format = 'Failure to execute the task. %s';
          $error_message = sprintf($format, $e->getMessage());
          $this->log(WipLogLevel::FATAL, $error_message);

          // Also log a user-readable message with less detail.
          $user_message = sprintf('A fatal error occurred while processing the task. Aborting. (%s)', __METHOD__);
          $this->log(WipLogLevel::FATAL, $user_message, NULL, TRUE);
          $notifier = $this->getNotifier();
          $notifier->notifyException($e, NotificationSeverity::ERROR);
          $thread_index++;
        }
      }
      $tasks = NULL;
    }

    $end_time = microtime();
    $this->processDetail->getCurrentIterationDetail()->setTotalExecutionTime($end_time - $start_time);
  }

  /**
   * Applies a thread to the specified task.
   *
   * @param int $id
   *   The task ID.
   * @param Thread $thread
   *   The thread on which the task will be executed.
   *
   * @return bool TRUE
   *   If the task was used with the thread.
   *
   * @throws NoObjectException
   *   If the task object could not be loaded.
   * @throws RowLockException
   *   If the caller failed to acquire the wip_pool row lock for update.
   * @throws TaskNotWaitingException
   *   If the specified task is not in an appropriate run state to execute.
   * @throws TaskHasRunningThreadException
   *   If the specified thread is already in use.
   * @throws WaitException
   *   If the maximum number of tasks in progress has been reached.
   */
  public function atomicUseThreadOnTask($id, Thread $thread) {
    if (!WipPoolRowLock::getWipPoolRowLock($id, NULL, $this->dependencyManager)->hasLock()) {
      throw new RowLockException(
        sprintf(
          'Failed to get the lock for row %d before calling %s.',
          $id,
          __METHOD__
        )
      );
    }

    try {
      $wip_pool = $this->getWipPool();
      $wip_pool_store = BasicWipPoolStore::getWipPoolStore($this->dependencyManager);
      /** @var TaskInterface $task */
      $task = $wip_pool_store->get($id);
      if (FALSE === $task) {
        throw new \DomainException(sprintf('Task %d could not be loaded.', $id));
      }
      /** @var ThreadStoreInterface $thread_store */
      $thread_store = $this->dependencyManager->getDependency('acquia.wip.storage.thread');

      // Be sure the task isn't currently being executed by trying to get
      // a thread that is currently working on this task. If such a thread
      // doesn't exist, the task can be executed by the current thread.
      // Otherwise, do not attempt to execute the task.
      try {
        // This method will throw an exception if the task cannot be found.
        $thread_store->getThreadByTask($task);
        $this->log(
          WipLogLevel::INFO,
          sprintf('Preventing execution of task %d because it is still running.', $task->getId())
        );

        // A running thread was found for the task. Do not invoke the task.
        // Note that in this case the thread was not used, so it can be applied
        // to the next available task.
        throw new TaskHasRunningThreadException();
      } catch (NoThreadException $e) {
        // The task is apparently not being executed right now. Note that there
        // is locking within the WipExecCommand that prevents two copies of the
        // same task from running simultaneously.
      }

      if (in_array($task->getStatus(), [TaskStatus::NOT_STARTED, TaskStatus::RESTARTED])) {
        // This task is being started. Make sure there aren't too many
        // tasks in progress.
        $running_tasks = $wip_pool->getUnpausedTasksBeingExecutedCount();
        $max_simultaneous_tasks = $this->getMaximumWorkInProgress();
        if ($running_tasks >= $max_simultaneous_tasks) {
          // At this point there is at least one available thread and there
          // are too many tasks in progress to start a new one. Simply wait
          // for a task already in progress to need attention.
          // Note that the ordering of tasks is such that work currently in
          // progress comes first, so a new task list has to be retrieved.
          //
          // Wait a short period of time before trying to start a new task.
          $e = new WaitException();
          $e->setWait(self::MAXIMUM_WORK_IN_PROGRESS_DELAY);
          throw $e;
        }
      } elseif ($task->getStatus() !== TaskStatus::WAITING) {
        // It is unexpected to have a task in finished or processing state.
        // This can happen if two processes are working on tasks
        // simultaneously. While that should be impossible, it is not
        // because of the way older mysql versions handle locking. This
        // condition should only occur if a user invokes
        // 'wipctl process-tasks' manually while another process-tasks
        // invocation has acquired the appropriate lock.
        $message = sprintf(
          'Unable to execute task %d as it is currently in the %s state',
          $task->getId(),
          TaskStatus::getLabel($task->getStatus())
        );
        $this->log(WipLogLevel::ERROR, $message, $task->getId());
        throw new TaskNotWaitingException();
      }

      // At this point we have a task and a thread to execute it.
      $this->executeTask($task, $thread);

      // The thread has now been used; move on to the next one.
      return TRUE;
    } catch (NoObjectException $e) {
      // Something went wrong in trying to construct the WIP iterator.  This
      // probably won't recover on its own, so we need to error-out this task.
      $format = 'Failure in loading the iterator for task %d. Task status will be set to error.';
      $error_message = sprintf($format, $e->getTaskId());
      $this->log(WipLogLevel::FATAL, $error_message, $e->getTaskId());
      // Also log a user-readable message with less detail.
      $user_message = sprintf('A fatal error occurred while processing the task. Aborting. (%s)', __METHOD__);
      $this->log(WipLogLevel::FATAL, $user_message, $e->getTaskId(), TRUE);
      $notifier = $this->getNotifier();
      $notifier->notifyException($e, NotificationSeverity::ERROR, array('task_id' => $e->getTaskId()));

      // We didn't actually load a task successfully at this point, so we have
      // to load it from the ID provided by the exception.
      $task_id = $e->getTaskId();
      if ($task_id) {
        $task = $this->loadTask($task_id);
        $task->setExitStatus(TaskExitStatus::ERROR_SYSTEM);
        $task->setStatus(TaskStatus::COMPLETE);
        try {
          // Send User error metric.
          $this->getMetricsUtility()->sendMetric('count', 'wip.system.job_status.system_error', 1);

          // Send MTD system failure metric.
          $this->getMetricsUtility()->sendMtdSystemFailure();

          $this->saveTask($task);
        } catch (\Exception $e) {
          $this->log(
            WipLogLevel::FATAL,
            sprintf("Unable to save task %d:\n%s", $task_id, $e->getMessage())
          );
        }
      }
    }
    throw $e;
  }

  /**
   * Executes the specified task using the specified thread.
   *
   * @param TaskInterface $task
   *   The task to execute.
   * @param Thread $thread
   *   The thread to use when executing the task. The thread indicates which
   *   webnode the task should be delegated to.
   *
   * @throws NoObjectException
   *   If there is no object in the wip_store table that matches the specified
   *   task.
   * @throws RowLockException
   *   If the wip_pool row update lock has not been acquired.
   */
  public function executeTask(TaskInterface $task, Thread $thread) {
    $task_id = $task->getId();
    $thread_id = $thread->getId();

    if (!WipPoolRowLock::getWipPoolRowLock($task_id, WipPoolRowLock::LOCK_PREFIX_EXECUTE, $this->dependencyManager)
      ->isFree(NULL)) {
      throw new RowLockException(
        sprintf(
          'The execute lock for row %d was already in use before calling %s.',
          $task_id,
          __METHOD__
        )
      );
    }
    if (!WipPoolRowLock::getWipPoolRowLock($task_id, NULL, $this->dependencyManager)->hasLock()) {
      throw new RowLockException(
        sprintf(
          'The update lock for row %d was already in use before calling %s.',
          $task_id,
          __METHOD__
        )
      );
    }

    $notifier = $this->getNotifier();
    if (!$task->getWipIterator()) {
      $task->loadWipIterator();
    }
    try {
      $this->dispatch($thread, $task);
    } catch (\ErrorException $ssh_error) {
      $message = sprintf(
        "Experienced an error while attempting to dispatch task %d on thread %d:\n%s\n%s",
        $task_id,
        $thread_id,
        $ssh_error->getMessage(),
        $ssh_error->getTraceAsString()
      );
      $this->log(WipLogLevel::ERROR, $message, $task_id);
      // An SSH error has occurred. This can happen if the server_store entries
      // are out of date such that servers that are no longer available are
      // being used to execute tasks.
      $this->recoverFromDispatchFailure($task, $thread);
      $this->quit = TRUE;
      $thread->setStatus(ThreadStatus::FINISHED);
      $thread->setCompleted(time());
      $this->saveThread($thread);
    } catch (ThreadIncompleteException $incomplete_exception) {
      $message = sprintf(
        "Experienced an error while attempting to dispatch task %d on thread %d:\n%s\n%s",
        $task_id,
        $thread_id,
        $incomplete_exception->getMessage(),
        $incomplete_exception->getTraceAsString()
      );
      $this->log(WipLogLevel::ERROR, $message, $task_id);
      // The thread references a server ID that is no longer available. This
      // can happen if the server_store entries have been updated during
      // execution, including at least one server that was disabled during that
      // cleanup.
      $this->recoverFromDispatchFailure($task, $thread);
      $this->quit = TRUE;
      $thread->setStatus(ThreadStatus::FINISHED);
      $thread->setCompleted(time());
      $this->saveThread($thread);
    } catch (\Exception $e) {
      $format = 'Dispatch failed for task %d on thread %d. Thread will be released, and task status set to error. Message: %s';
      $error_message = sprintf($format, $task_id, $thread_id, $e->getMessage());
      $this->log(WipLogLevel::ERROR, $error_message);
      $notifier->notifyException($e, NotificationSeverity::ERROR, array('task_id' => $task_id));

      // Any exception in dispatch implies the task failed before executing.
      // We need to set the status to error-ed out and free the thread.
      $task->setExitStatus(TaskExitStatus::ERROR_SYSTEM);
      $task->setStatus(TaskStatus::COMPLETE);
      try {
        $this->saveTask($task);
      } catch (\Exception $e) {
        $this->log(
          WipLogLevel::FATAL,
          sprintf("Unable to save task %d:\n%s", $task_id, $e->getMessage())
        );
      }
      $thread->setStatus(ThreadStatus::FINISHED);
      $thread->setCompleted(time());
      $this->saveThread($thread);
    }
  }

  /**
   * Attempts to recover from a dispatch failure.
   *
   * This failure can happen when a webnode goes down or is otherwise
   * inaccessible. This method will attempt to correct the server_store table
   * and reclaim threads and tasks that are associated with the faulty server.
   *
   * @param TaskInterface $task
   *   The task that failed.
   * @param Thread $thread
   *   The thread associated with the failed task.
   */
  private function recoverFromDispatchFailure(TaskInterface $task, Thread $thread) {
    $server_store = ServerStore::getServerStore($this->dependencyManager);
    $server = $server_store->get($thread->getServerId());
    if ($server !== FALSE) {
      $host_name = $server->getHostname();
      $this->log(
        WipLogLevel::ERROR,
        sprintf(
          'Failed to execute task %d on %s. Attempting to recover...',
          $task->getId(),
          $host_name
        )
      );
    } else {
      // The server associated with the specified thread has been removed from
      // the server_store table. This does not happen without human
      // intervention because the system only disables webnodes; it does not
      // delete them. In any case, the server doesn't exist, so modify the
      // error message appropriately.
      $this->log(
        WipLogLevel::ERROR,
        sprintf(
          'Failed to execute task %d on thread %d (server id %d). Attempting to recover...',
          $task->getId(),
          $thread->getId(),
          $thread->getServerId()
        )
      );
    }

    // Attempt to fix the server store.
    $this->recoverFromServerFailure();
  }

  /**
   * Attempts to recover from the server_store having incorrect references.
   */
  private function recoverFromServerFailure() {
    $environment = Environment::getRuntimeEnvironment();
    $command_path = $environment->getDocrootDir() . '/../bin/wipctl';
    if (!file_exists($command_path)) {
      // This is not running in a hosting environment. This generally happens
      // in the unit test environment.
      $this->log(
        WipLogLevel::ERROR,
        sprintf('Failed to recover because the command "%s" does not exist.', $command_path)
      );
      return;
    }
    $command = sprintf('%s server update --format=json', $command_path);
    exec($command, $output, $server_exit_code);
    $server_result = json_decode(implode('', $output));
    if (!empty($server_result)) {
      // In case some tasks or threads were put into an inconsistent state, run the recover command.
      $command = sprintf('%s recover', $command_path);
      exec($command, $output, $server_exit_code);
    }
  }

  /**
   * Obtains an ordered list of available threads.
   *
   * @return Thread[] The set of available threads.
   *   The set of available threads.
   *
   * @throws NoWorkerServersException
   *   If there are no servers configured.
   * @throws \Acquia\Wip\Exception\DependencyMissingException
   *   If a missing dependency prevents this method from executing.
   */
  public function getAvailableThreads() {
    /** @var ThreadStoreInterface $thread_store */
    $thread_store = $this->dependencyManager->getDependency('acquia.wip.storage.thread');
    /** @var ServerStoreInterface $server_store */
    $server_store = $this->dependencyManager->getDependency('acquia.wip.storage.server');

    $servers = $server_store->getActiveServers();
    // Check if there are any worker servers configured.
    if (empty($servers)) {
      $error_message = 'There are no worker servers configured. Do any server records exist in the server_store table?';
      $this->log(WipLogLevel::FATAL, $error_message);
      throw new NoWorkerServersException($error_message);
    }

    $active_threads = $thread_store->getActiveThreads();

    return $this->getOrderedServerThreads($servers, $active_threads);
  }

  /**
   * Gets the number of tasks per thread that can execute simultaneously.
   *
   * The system can process more Wip objects at a time than the number of
   * available threads because the jobs are largely asynchronous. If this dial
   * is cranked too high, execution of a given Wip object will stall waiting
   * for an available thread.
   *
   * This value can be adjusted to account for the duty cycle of the Wip
   * objects being executed.
   *
   * @return int
   *   The maximum number of tasks per thread.
   */
  public function getMaximumSimultaneousTasksPerThread() {
    return WipFactory::getInt('$acquia.wip.threadpool.max_tasks_per_thread', 4);
  }

  /**
   * Gets the maximum number of Wip objects that can execute simultaneously.
   *
   * This represents the maximum number of Wip objects that can be in the
   * PROCESSING and WAITING states combined. A single thread can service
   * multiple Wip objects, but only to a reasonable degree. If set too high the
   * Wip objects in progress will stall waiting for an available thread,
   * causing increased execution times for each job.
   *
   * @return int
   *   The number of Wip objects that can be executed using a single thread.
   */
  public function getMaximumWorkInProgress() {
    /** @var ServerStoreInterface $server_store */
    $server_store = $this->dependencyManager->getDependency('acquia.wip.storage.server');

    $servers = $server_store->getActiveServers();
    $total_simultaneous_threads = 0;
    foreach ($servers as $server) {
      $total_simultaneous_threads += $server->getTotalThreads();
    }

    return $total_simultaneous_threads * $this->getMaximumSimultaneousTasksPerThread();
  }

  /**
   * Gets the available set of server threads ordered by ascending load.
   *
   * @param Server[] $servers
   *   An array of all known server objects.
   * @param Thread[] $active_threads
   *   An array of all threads in the RUNNING or RESERVED status.
   *
   * @return Thread[]
   *   The ordered set of threads that can be used. Using these threads in
   *   order will result in the work being evenly spread across all of the
   *   available servers.
   */
  public function getOrderedServerThreads($servers, $active_threads) {
    $result = array();

    // Make it easy to convert a server ID to a Server instance.
    $server_map = array();
    foreach ($servers as $server) {
      $server_map[$server->getId()] = $server;
    }

    // Create a summary object for each server that we can use to sort.
    $server_summaries = array();
    foreach ($servers as &$server) {
      $summary = new \stdClass();
      $server_id = $server->getId();
      $summary->serverId = $server_id;
      $summary->maxThreads = $server->getTotalThreads();
      $summary->threadsUsed = 0;
      $this->setPercentageUsed($summary);
      $server_summaries[$server_id] = $summary;
    }

    // Indicate the number of threads used on each server.
    //
    // Note that changes in the server_store table can result in a thread
    // that is not part of this calculation. That should not disrupt the
    // server thread calculation because it will certainly happen as the
    // service undergoes hardware changes to handle increasing load.
    foreach ($active_threads as $thread) {
      $server_id = $thread->getServerId();
      if (isset($server_summaries[$server_id])) {
        $server_summaries[$server_id]->threadsUsed++;
        $this->setPercentageUsed($server_summaries[$server_id]);
      }
    }

    // Sort the set of available threads.
    $done = FALSE;
    do {
      usort($server_summaries, array($this, 'sortServerSummaries'));
      $least_utilized_server = reset($server_summaries);
      if (FALSE === $least_utilized_server) {
        // No available thread.
        break;
      }
      if ($least_utilized_server->percentUsed < 100) {
        $thread = new Thread();
        $thread->setServerId($least_utilized_server->serverId);
        $result[] = $thread;
        $least_utilized_server->threadsUsed++;
        $this->setPercentageUsed($least_utilized_server);
      } else {
        $done = TRUE;
      }
    } while (!$done);
    return $result;
  }

  /**
   * Sets the percentage of threads used for the specified server summary.
   *
   * @param object $server_summary
   *   The server summary.
   */
  private function setPercentageUsed(&$server_summary) {
    $server_summary->percentUsed = intval(($server_summary->threadsUsed / $server_summary->maxThreads) * 100);
  }

  /**
   * Sort callback that orders by percentage used.
   *
   * This method is used to help sort available threads by server load such
   * that the least-loaded server will get work before the most-loaded server.
   *
   * @param object $a
   *   The first object.
   * @param object $b
   *   The second object.
   *
   * @return int
   *   Negative if the percentage used in object 'a' is less than that of
   *   object 'b'; positive if the percentage used in object 'a' is greater
   *   than that of object 'b'; or zero if they are equal.
   */
  public function sortServerSummaries($a, $b) {
    return $a->percentUsed - $b->percentUsed;
  }

  /**
   * Gets the directory prefix for dispatching commands.
   *
   * @return string
   *   The directory prefix for dispatching commands.
   */
  public function getDirectoryPrefix() {
    return $this->directoryPrefix;
  }

  /**
   * Sets the directory prefix for dispatching commands.
   *
   * @param string $directory_prefix
   *   The directory prefix for dispatching commands.
   */
  public function setDirectoryPrefix($directory_prefix) {
    $this->directoryPrefix = $directory_prefix;
  }

  /**
   * Sends a given task to a given thread.
   *
   * @param Thread $thread
   *   The thread to which to dispatch the given task.
   * @param TaskInterface $task
   *   The task to dispatch to the given thread.
   *
   * @throws RowLockException
   *   If the wip_pool row update lock has not been acquired.
   */
  public function dispatch(Thread $thread, TaskInterface $task) {
    // Access to this method must be locked, as the global resource of the next
    // available thread can only be handed to a single process once.
    $id = $task->getId();
    if (!WipPoolRowLock::getWipPoolRowLock($id, NULL, $this->dependencyManager)->hasLock()) {
      throw new RowLockException(
        sprintf(
          'Failed to get the lock for row %d before calling %s.',
          $id,
          __METHOD__
        )
      );
    }

    $thread->setWipId($task->getId());
    $thread->setStatus(ThreadStatus::RUNNING);
    $this->saveThread($thread);

    $task->setClaimedTimestamp(time());
    $task->setStatus(TaskStatus::PROCESSING);

    $this->saveTask($task);
    // Starting the task causes the wip_group_concurrency table to be updated,
    // which is essential to inform the WipPoolStore::getNextTasks() method of
    // the number of tasks currently in progress. If this is done in a separate
    // process the small delay before updating the wip_group_concurrency table
    // will often result in the group concurrency value not being honored.
    $this->startTask($task);

    // IMPORTANT NOTE: save() methods must no longer be called on either the
    // thread or the task after calling $thread->dispatch().  If this happens,
    // any saves made by the remote endpoint can be overwritten by saves on this
    // side.
    $thread->setDirectoryPrefix($this->directoryPrefix);
    $message = sprintf('INTERNAL: Dispatching task %d on thread %d.', $task->getId(), $thread->getId());
    $this->log(WipLogLevel::TRACE, $message, $task->getId());
    $thread->dispatch($task);
  }

  /**
   * Saves a Thread object to the database.
   *
   * @param Thread $thread
   *   The Thread object to save.
   */
  private function saveThread(Thread $thread) {
    /** @var ThreadStoreInterface $thread_store */
    $thread_store = $this->dependencyManager->getDependency('acquia.wip.storage.thread');
    $thread_store->save($thread);
  }

  /**
   * Saves a Task object in the WIP pool database table.
   *
   * @param TaskInterface $task
   *   The task to save.
   */
  public function saveTask(TaskInterface $task) {
    /** @var WipPoolStoreInterface $task_store */
    $task_store = $this->dependencyManager->getDependency('acquia.wip.storage.wippool');
    $task_store->save($task);
  }

  /**
   * Starts progress on a Wip task, which is only updating concurrency.
   *
   * Notes: This method must be called only when the wip_pool row lock has been
   * acquired. This method was moved from WipWorker to ThreadPool to update
   * the database more quickly, so as to prevent stale results from the
   * getNextTasks query.
   *
   * @param TaskInterface $task
   *   The task being dispatched.
   */
  public function startTask(TaskInterface $task) {
    $wip_pool = BasicWipPoolStore::getWipPoolStore($this->dependencyManager);
    $wip_pool->startProgress($task);
  }

  /**
   * Loads a Task object from the WIP pool.
   *
   * @param int $id
   *   The WIP ID of the Task to load.
   *
   * @return Task|bool
   *   A Task object corresponding to the given ID, or FALSE if not found.
   */
  private function loadTask($id) {
    /** @var WipPoolStoreInterface $task_store */
    $task_store = $this->dependencyManager->getDependency('acquia.wip.storage.wippool');
    return $task_store->get($id);
  }

  /**
   * Releases a reserved thread back to the pool.
   *
   * @param Thread $thread
   *   The thread to release.
   *
   * @throws InvalidOperationException
   *   If the thread has not been stored.
   * @throws \Acquia\Wip\Exception\DependencyMissingException
   *   If the thread dependency has not been identified.
   */
  public function releaseThread(Thread $thread) {
    if (!$thread instanceof Thread || !$thread->getId()) {
      $message = 'Only Thread objects that have been stored can be released. Ensure the passed argument is a Thread, and has an ID.';
      throw new InvalidOperationException($message);
    }

    /** @var ThreadStoreInterface $thread_store */
    $thread_store = $this->dependencyManager->getDependency('acquia.wip.storage.thread');

    $thread_store->remove($thread);
  }

  /**
   * Instructs the process loop to stop ASAP.
   */
  public function stop() {
    $this->quit = TRUE;
  }

  /**
   * Returns the value of the $quit flag.
   *
   * @return bool
   *   The value of the $quit flag.
   */
  public function getQuit() {
    return $this->quit;
  }

  /**
   * Sets a system status check callback function to use when checking status.
   *
   * @param callable $callback
   *   The callback function to call to determine system status.  This function
   *   must return TRUE to indicate status "OK", otherwise FALSE if processing
   *   should not proceed.
   */
  public function setStatusCheckCallback(callable $callback) {
    $this->statusCheckCallback = $callback;
  }

  /**
   * {@inheritdoc}
   */
  public function systemStatusOk() {
    if (!empty($this->statusCheckCallback)) {
      return call_user_func($this->statusCheckCallback);
    }
    return TRUE;
  }

  /**
   * Calls the sleep method and measures how long it actually takes.
   *
   * @param int $time
   *   The amount of time to sleep.
   * @param ThreadPoolDetailInterface $detail
   *   The Detail object that tracks this sleep.
   */
  private function measuredSleep($time, ThreadPoolDetailInterface $detail) {
    $before = microtime(TRUE);
    sleep($time);
    $after = microtime(TRUE);

    $detail->recordSleep($after - $before);
  }

}
