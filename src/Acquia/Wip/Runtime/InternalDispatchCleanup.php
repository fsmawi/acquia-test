<?php

namespace Acquia\Wip\Runtime;

use Acquia\WipIntegrations\DoctrineORM\SignalStore;
use Acquia\Wip\DependencyManager;
use Acquia\Wip\DependencyManagerInterface;
use Acquia\Wip\Exception\HasRowLockException;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\Lock\WipPoolRowLock;
use Acquia\Wip\Signal\SshCompleteSignal;
use Acquia\Wip\Storage\BasicWipPoolStore;
use Acquia\Wip\Storage\ThreadStoreInterface;
use Acquia\Wip\Storage\WipStoreInterface;
use Acquia\Wip\TaskExitStatus;
use Acquia\Wip\TaskStatus;
use Acquia\Wip\Utility\MetricsUtility;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipLogLevel;
use GuzzleHttp\Client;

/**
 * Responsible for cleaning up after an abnormal internal dispatch.
 *
 * The dispatch can fail if the process experiences a fatal PHP error.
 */
class InternalDispatchCleanup {

  /**
   * The dependency manager.
   *
   * @var DependencyManagerInterface
   */
  private $dependencyManager;

  /**
   * The logger.
   *
   * @var WipLogInterface
   */
  private $wipLog;

  /**
   * The WipStore instance.
   *
   * @var WipStoreInterface
   */
  private $objectStore;

  /**
   * The task ID.
   *
   * @var int
   */
  private $taskId;

  /**
   * The metric utility.
   *
   * @var MetricsUtility
   */
  private $metric;

  /**
   * InternalDispatchCleanup constructor.
   */
  public function __construct() {
    $this->dependencyManager = new DependencyManager();
    $this->dependencyManager->addDependencies($this->getDependencies());
    $this->storage = $this->dependencyManager->getDependency('acquia.wip.storage.signal');
    $this->wipLog = WipLog::getWipLog($this->dependencyManager);
    $this->objectStore = $this->dependencyManager->getDependency('acquia.wip.storage.wip');
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      SignalStore::RESOURCE_NAME => '\Acquia\Wip\Storage\SignalStoreInterface',
      WipLog::RESOURCE_NAME => '\Acquia\Wip\WipLogInterface',
      BasicWipPoolStore::RESOURCE_NAME => 'Acquia\Wip\Storage\WipPoolStoreInterface',
      'acquia.wip.storage.thread' => '\Acquia\Wip\Storage\ThreadStoreInterface',
      'acquia.wip.storage.wip' => '\Acquia\Wip\Storage\WipStoreInterface',
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
   * Attempts to clean up the task, if needed.
   *
   * @param SshCompleteSignal $signal
   *   The signal representing the completion of an asynchronous task dispatch
   *   SSH call.
   */
  public function handleSystemSignal(SshCompleteSignal $signal) {
    $wip_log = WipLog::getWipLog();
    // Special handling for internal signals. These represent completed
    // dispatch processes and are used to ensure that the threads and tasks
    // are closed out appropriately.
    // This is a system signal; handle it without storing.
    $signal_data = $signal->getData();
    if (empty($signal_data->threadId) || empty($signal_data->taskId)
        || !is_numeric($signal_data->threadId) || !is_numeric($signal_data->taskId)) {
      // Not enough information to work with.
      WipLog::getWipLog()->log(
        WipLogLevel::FATAL,
        'The call to handleMissingSignal is missing the thread or taskID.'
      );
      return;
    }
    if (!isset($signal_data->result) || !isset($signal_data->result->exitCode)) {
      $wip_log->log(
        WipLogLevel::FATAL,
        'The call to handleSystemSignal is missing the result.'
      );
    }
    $thread_id = intval($signal_data->threadId);
    $task_id = intval($signal_data->taskId);
    $exit_code = intval($signal_data->result->exitCode);
    if ($thread_id > 0 && $task_id > 0) {
      $transcript = $this->getExecutionTranscript($signal_data);
      if ($this->traceIsAbnormal($transcript) || $exit_code !== 0) {
        $this->wipLog->log(
          WipLogLevel::FATAL,
          sprintf(
            "Exit code: %d (type: '%s')\n%s",
            $signal->getExitCode(),
            gettype($signal->getExitCode()),
            print_r($signal_data, TRUE)
          ),
          $task_id
        );
        $message = sprintf('INTERNAL: WIP dispatch completed for task %d on thread %d.', $task_id, $thread_id);
        $printable_transcript = $this->transcriptToString($transcript);
        $process_result = $this->getResultFromSignalData($signal_data);
        $printable_transcript .= sprintf(
          "\n\nstdout:%s\nstderr:\n%s",
          $process_result->stdout,
          $process_result->stderr
        );
        if ($this->cleanupIsPossible($transcript, $task_id, $signal_data)) {
          $message .= " Cleaning up task after abnormal thread completion.";
          $this->abnormalTranscriptCleanup(
            'atomicCleanupTask',
            WipLogLevel::ERROR,
            $message,
            $task_id,
            $thread_id,
            $printable_transcript
          );
        } else {
          $message .= " Unable to clean up task after abnormal thread completion.";
          $this->abnormalTranscriptCleanup(
            'atomicFailTask',
            WipLogLevel::FATAL,
            $message,
            $task_id,
            $thread_id,
            $printable_transcript
          );
        }
      }
    }
  }

  /**
   * Cleans up the task after an abnormal transcript is identified.
   *
   * @param string $method
   *   The cleanup method to call atomically.
   * @param int $log_level
   *   The log level.
   * @param string $message
   *   The log message.
   * @param int $task_id
   *   The task ID.
   * @param int $thread_id
   *   The thread ID.
   * @param string $printable_transcript
   *   The transcript in a form suitable for logging.
   *
   * @return mixed
   *   The result of running the method atomically.
   */
  private function abnormalTranscriptCleanup(
    $method,
    $log_level,
    $message,
    $task_id,
    $thread_id,
    $printable_transcript) {
    $this->wipLog->log($log_level, $message, $task_id);
    return WipPoolRowLock::getWipPoolRowLock($task_id)
      ->setTimeout(30)
      ->runAtomic(
        $this,
        $method,
        [$task_id, $thread_id, $printable_transcript]
      );
  }

  /**
   * Ensures the task and thread have been properly put away upon completion.
   *
   * @param int $task_id
   *   The task ID.
   * @param int $thread_id
   *   The thread ID.
   * @param string $printable_transcript
   *   The transcript in a form suitable for logging.
   *
   * @throws HasRowLockException
   *   If the WipPoolRowLock is not being held by this process at the time of
   *   the method call.
   */
  public function atomicCleanupTask($task_id, $thread_id, $printable_transcript) {
    if (!WipPoolRowLock::getWipPoolRowLock($task_id)->hasLock()) {
      throw new HasRowLockException(
        sprintf('%s must be called after the WipPoolRowLock has been acquired.', __METHOD__)
      );
    }
    if (!is_int($task_id) || $task_id <= 0) {
      throw new \InvalidArgumentException('The "task_id" parameter must be a positive integer.');
    }
    if (!is_int($thread_id)) {
      throw new \InvalidArgumentException('The "thread_id" parameter must be a positive integer.');
    }
    if (!is_string($printable_transcript)) {
      throw new \InvalidArgumentException('The "printable_transcript" parameter must be a string.');
    }
    $message = '';
    $pool_store = BasicWipPoolStore::getWipPoolStore($this->dependencyManager);
    $task = $pool_store->get($task_id);
    if (!empty($task) && $task->getStatus() === TaskStatus::PROCESSING) {
      // Reset the wip_pool run_status.
      $task->setStatus(TaskStatus::WAITING);
      $pool_store->save($task);
      $message .= sprintf('Task %d was left in progress. ', $task_id);
    }
    /** @var ThreadStoreInterface $thread_store */
    $thread_store = $this->dependencyManager->getDependency('acquia.wip.storage.thread');
    $thread = $thread_store->get($thread_id);
    if (!empty($thread)) {
      // Delete the thread.
      $thread_store->remove($thread);
      $message .= sprintf('Thread %d was not removed.', $thread_id);
    }
    if (empty($message)) {
      $message = 'No cleanup required';
      $log_level = WipLogLevel::TRACE;
    } else {
      $log_level = WipLogLevel::ERROR;
    }
    $this->wipLog->log(
      $log_level,
      sprintf(
        "Received system signal callback for task %d with an abnormal transcript: %s\nTranscript:\n%s",
        $task_id,
        $message,
        $printable_transcript
      ),
      $task_id
    );
  }

  /**
   * Fails the specified task.
   *
   * This has to happen if the executable fails after it has been processed
   * but before it has saved the object back to the wip_store.
   *
   * @param int $task_id
   *   The task ID.
   * @param int $thread_id
   *   The thread ID.
   * @param string $printable_transcript
   *   The transcript in a form suitable for logging.
   *
   * @throws HasRowLockException
   *   If the WipPoolRowLock is not being held by this process at the time of
   *   the method call.
   */
  public function atomicFailTask($task_id, $thread_id, $printable_transcript) {
    if (!WipPoolRowLock::getWipPoolRowLock($task_id)->hasLock()) {
      throw new HasRowLockException(
        sprintf('%s must be called after the WipPoolRowLock has been acquired.', __METHOD__)
      );
    }
    if (!is_int($task_id) || $task_id <= 0) {
      throw new \InvalidArgumentException('The "task_id" parameter must be a positive integer.');
    }
    if (!is_int($thread_id)) {
      throw new \InvalidArgumentException('The "thread_id" parameter must be a positive integer.');
    }
    if (!is_string($printable_transcript)) {
      throw new \InvalidArgumentException('The "printable_transcript" parameter must be a string.');
    }
    $pool_store = BasicWipPoolStore::getWipPoolStore($this->dependencyManager);
    $task = $pool_store->get($task_id);
    if (!empty($task) && $task->getStatus() !== TaskStatus::COMPLETE) {
      // Reset the wip_pool run_status.
      $task->setStatus(TaskStatus::COMPLETE);
      $task->setExitStatus(TaskExitStatus::ERROR_SYSTEM);
      $message = 'Failure due to fatal system error. Please try again.';
      $task->setExitMessage($message);
      $task->setCompletedTimestamp(time());
      $pool_store->save($task);
      $this->wipLog->log(
        WipLogLevel::FATAL,
        sprintf(
          'Unable to clean up after executable on thread %d failed.',
          $thread_id
        ),
        $task_id
      );
      $this->wipLog->log(WipLogLevel::FATAL, $message, $task_id, TRUE);
      try {
        // Send User error metric.
        $this->getMetricsUtility()->sendMetric('count', 'wip.system.job_status.system_error', 1);

        // Send MTD system failure metric.
        $this->getMetricsUtility()->sendMtdSystemFailure();

        $wip = $this->objectStore->get($task_id);
        $wip->getWip()->onStatusChange($task);
      } catch (\Exception $e) {
        // If the Wip object is missing or corrupt, there's not much else to do.
        $message = sprintf(
          'The Pipeline service may not have been updated about task %s status: %s',
          $task_id,
          $e->getMessage()
        );
        $this->wipLog->log(WipLogLevel::ERROR, $message, $task_id);
      }
    }
    /** @var ThreadStoreInterface $thread_store */
    $thread_store = $this->dependencyManager->getDependency('acquia.wip.storage.thread');
    $thread = $thread_store->get($thread_id);
    if (!empty($thread)) {
      // Delete the thread.
      $thread_store->remove($thread);
    }
    $message = <<<EOT
Received system signal callback for task ${task_id} with an abnormal transcript. Unable to clean up and retry.
Transcript:
${printable_transcript}
EOT;

    $this->wipLog->log(
      WipLogLevel::FATAL,
      $message,
      $task_id
    );
  }

  /**
   * Determines whether the trace is normal.
   *
   * This method looks at all of the anticipated phases to detect missing
   * components.
   *
   * @param array $transcript
   *   The execution transcript.
   *
   * @return bool
   *   TRUE if the trace is abnormal; FALSE otherwise.
   */
  private function traceIsAbnormal($transcript) {
    $result = FALSE;
    $phases = array(
      ExecutionTranscriptElement::PROCESS_PARENT,
      ExecutionTranscriptElement::PROCESS_CHILD,
      ExecutionTranscriptElement::PROCESS_TASK,
      ExecutionTranscriptElement::PROCESS_CLEANUP,
      ExecutionTranscriptElement::PROCESS_THREAD_CLEANUP,
    );
    foreach ($phases as $phase) {
      if (!$this->phaseSuccessful($phase, $transcript)) {
        $result = TRUE;
        break;
      }
    }
    return $result;
  }

  /**
   * Determines if system thread clean up is possible.
   *
   * @param array $transcript
   *   The transcript from the executable.
   * @param int $task_id
   *   The task ID.
   * @param object $signal_data
   *   The signal data.
   *
   * @return bool
   *   TRUE if it is possible to clean up; FALSE otherwise.
   */
  private function cleanupIsPossible($transcript, $task_id, $signal_data) {
    // The only case in which cleanup is not possible is that in which the task
    // started but failed to check the modified Wip object back into the
    // wip_store table.
    $result = TRUE;
    if ($this->phaseStarted(ExecutionTranscriptElement::PROCESS_TASK, $transcript) &&
      !$this->phaseCompleted(ExecutionTranscriptElement::PROCESS_TASK, $transcript)) {
      $result = FALSE;

      // Check to see whether the object has been updated in the wip_store.
      if (isset($signal_data->wipStoreTimestamp) && is_numeric($signal_data->wipStoreTimestamp)) {
        $object_timestamp = $this->objectStore->getTimestampByWipId($task_id);
        if ($object_timestamp > $signal_data->wipStoreTimestamp) {
          // The object has been updated.
          $result = TRUE;
        } else {
          // This is definitely an error. If the task cleanup is performed there
          // is a good chance one or more states will be executed multiple times.
          // The behavior under these conditions is a matter of policy. Would it
          // be preferable that a task fail? Or that a task retry after a
          // catastrophic failure that results in the object not being
          // serialized?
          $abort_on_fail = WipFactory::getBool('$acquia.wip.dispatch.abort_on_failure', FALSE);
          if ($abort_on_fail) {
            $result = FALSE;
            $this->wipLog->log(
              WipLogLevel::FATAL,
              'Task execution failed during the processing phase. No cleanup will be attempted.',
              $task_id
            );
          } else {
            $result = TRUE;
            $this->wipLog->log(
              WipLogLevel::ERROR,
              'Task execution failed during the processing phase. Task states may be executed again.',
              $task_id
            );
          }
        }
      }
    }
    return $result;
  }

  /**
   * Indicates whether the specified phase was successful.
   *
   * Successful means the phase was both started and completed.
   *
   * @param string $phase
   *   The phase.
   * @param array $transcript
   *   The transcript indicating which phases were executed.
   *
   * @return bool
   *   TRUE if the specified phase was successful; FALSE otherwise.
   */
  private function phaseSuccessful($phase, $transcript) {
    return $this->phaseStarted($phase, $transcript) && $this->phaseCompleted($phase, $transcript);
  }

  /**
   * Indicates whether the specified phase was started.
   *
   * @param string $phase
   *   The phase.
   * @param array $transcript
   *   The transcript indicating which phases were executed.
   *
   * @return bool
   *   TRUE if the specified phase was started; FALSE otherwise.
   */
  private function phaseStarted($phase, $transcript) {
    return $this->phaseIncludesAction(
      $phase,
      ExecutionTranscriptElement::START,
      $transcript
    );
  }

  /**
   * Indicates whether the specified phase was completed.
   *
   * @param string $phase
   *   The phase.
   * @param array $transcript
   *   The transcript indicating which phases were executed.
   *
   * @return bool
   *   TRUE if the specified phase was completed; FALSE otherwise.
   */
  private function phaseCompleted($phase, $transcript) {
    return $this->phaseIncludesAction(
      $phase,
      ExecutionTranscriptElement::COMPLETE,
      $transcript
    );
  }

  /**
   * Indicates whether the specified phase encountered the specified action.
   *
   * @param string $phase
   *   The phase.
   * @param string $action
   *   The action, either ExecutionTranscriptElement::START or
   *   ExecutionTranscriptElement::COMPLETE.
   * @param array $transcript
   *   The transcript indicating which phases were executed.
   *
   * @return bool
   *   TRUE if the specified phase was completed; FALSE otherwise.
   */
  private function phaseIncludesAction($phase, $action, $transcript) {
    $result = FALSE;
    if (isset($transcript[$phase])) {
      /** @var ExecutionTranscriptElement[] $action_transcript */
      $action_transcript = $transcript[$phase];
      foreach ($action_transcript as $element) {
        if ($element->getAction() === $action) {
          $result = TRUE;
          break;
        }
      }
    }
    return $result;
  }

  /**
   * Retrieves the execution transcript from the specified signal data.
   *
   * The transcript indicates which phases of execution were started and
   * completed. This information is used to determine whether cleanup is
   * possible.
   *
   * The transcript is passed from the WipExecCommand's stdout and encoded in
   * a way that makes it possible to separate from other output. The transcript
   * is expressed in stdout as an ordered list of phases that were started and
   * completed within WipExecCommand.
   *
   * @param object $signal_data
   *   The signal data.
   *
   * @return array
   *   An array containing the transcript.
   */
  private function getExecutionTranscript($signal_data) {
    $result = array();
    $signal_result = $this->getResultFromSignalData($signal_data);
    if (!empty($signal_result->stdout) && is_string($signal_result->stdout)) {
      $match_count = preg_match_all(
        '/^<([a-z_]+) ([A-Z_]+) \[([0-9]+\.[0-9]+)\]>$/m',
        $signal_result->stdout,
        $matches
      );
      if ($match_count > 0) {
        for ($i = 0; $i < $match_count; $i++) {
          $phase = $matches[1][$i];
          $action = $matches[2][$i];
          $time = floatval($matches[3][$i]);
          if (!isset($result[$phase])) {
            $result[$phase] = array();
          }
          $element = new ExecutionTranscriptElement($phase, $action, $time);
          $result[$phase][] = $element;
        }
      }
    }
    return $result;
  }

  /**
   * Renders the specified transcript in a form suitable for logging.
   *
   * @param array $transcript
   *   The transcript.
   *
   * @return string
   *   The rendered transcript.
   */
  private function transcriptToString($transcript) {
    $result = array();
    $sorted = $this->getSortedExecutionElements($transcript);
    /** @var ExecutionTranscriptElement $element */
    foreach ($sorted as $element) {
      $result[] = sprintf(
        "%0.3f - %s %s",
        $element->getTimestamp(),
        $element->getPhase(),
        $element->getAction()
      );
    }
    return implode("\n", $result);
  }

  /**
   * Gets the ExecutionTranscriptElements in the order they occurred.
   *
   * @param array $transcript
   *   The transcript.
   *
   * @return ExecutionTranscriptElement[]
   *   The ordered transcript elements.
   */
  private function getSortedExecutionElements($transcript) {
    $result = array();
    foreach ($transcript as $phase => $elements) {
      $result = array_merge($result, $elements);
    }
    usort($result, array('Acquia\Wip\Runtime\ExecutionTranscriptElement', 'sortByTime'));
    return $result;
  }

  /**
   * Gets the SSH result from the specified signal data.
   *
   * @param object $signal_data
   *   The data from the SshCompleteSignal.
   *
   * @return object
   *   The object containing the result of the SSH call.
   */
  private function getResultFromSignalData($signal_data) {
    $result = NULL;
    if (!empty($signal_data->result) && is_object($signal_data->result)) {
      $result = $signal_data->result;
    }
    return $result;
  }

}
