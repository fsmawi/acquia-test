<?php

namespace Acquia\Wip\Implementation;

use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\DependencyManager;
use Acquia\Wip\Exception\DependencyTypeException;
use Acquia\Wip\Exception\NoTaskException;
use Acquia\Wip\Exception\RowLockException;
use Acquia\Wip\Lock\WipPoolRowLock;
use Acquia\Wip\Runtime\WipPoolInterface;
use Acquia\Wip\ServiceApi;
use Acquia\Wip\Signal\SignalInterface;
use Acquia\Wip\Signal\SignalType;
use Acquia\Wip\Signal\WipCallback;
use Acquia\Wip\Signal\WipCompleteSignal;
use Acquia\Wip\Signal\WipSignalInterface;
use Acquia\Wip\Storage\SignalStoreInterface;
use Acquia\Wip\Storage\WipPoolStoreInterface;
use Acquia\Wip\TaskPriority;
use Acquia\Wip\TaskStatus;
use Acquia\Wip\WipContextInterface;
use Acquia\Wip\WipInterface;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipLogLevel;
use Acquia\Wip\WipTaskInterface;
use Acquia\Wip\WipTaskProcess;
use Acquia\Wip\WipTaskProcessInterface;
use Acquia\Wip\WipTaskResultInterface;

/**
 * This is the API used by Wip objects to interact with other Wip objects.
 */
class WipTaskApi extends ServiceApi implements WipTaskInterface, DependencyManagedInterface {

  /**
   * The DependencyManager instance.
   *
   * @var DependencyManager
   */
  public $dependencyManager;
  /**
   * The ID of the Wip object associated with this instance.
   *
   * @var int
   */
  private $wipId;

  /**
   * Initializes this instance.
   *
   * @throws DependencyTypeException
   *   If any dependencies are not satisfied.
   */
  public function __construct() {
    $this->dependencyManager = new DependencyManager();
    $this->dependencyManager->addDependencies($this->getDependencies());
  }

  /**
   * Gets the ID of the Wip object associated with this instance.
   *
   * @return int
   *   The Wip ID.
   */
  public function getWipId() {
    return $this->wipId;
  }

  /**
   * Sets the ID of the Wip object associated with this instance.
   *
   * @param int $wip_id
   *   The Wip ID.
   */
  public function setWipId($wip_id) {
    if (!is_int($wip_id) || $wip_id <= 0) {
      throw new \InvalidArgumentException('The wip_id argument must be a positive integer.');
    }
    $this->wipId = $wip_id;
  }

  /**
   * {@inheritdoc}
   */
  public function addChild(
    WipInterface $child,
    WipContextInterface $context,
    WipInterface $parent = NULL,
    TaskPriority $priority = NULL,
    $send_signal = TRUE
  ) {
    if (empty($priority)) {
      $priority = new TaskPriority();
    }
    $wip_pool = $this->getWipPool();
    if (!empty($parent)) {
      try {
        $child->setId($parent->getId());
      } catch (\Exception $e) {
        // Don't panic.
      }
      if ($send_signal) {
        $child->addCallback(new WipCallback($parent->getId()));
      }
    }
    $task = $wip_pool->addTask($child, $priority);
    $wip_process = new WipTaskProcess($task);
    $this->addWipTaskProcess($wip_process, $context);
    return $wip_process;
  }

  /**
   * {@inheritdoc}
   */
  public function restartTask($task_id, WipContextInterface $context, WipLogInterface $logger) {
    return WipPoolRowLock::getWipPoolRowLock($task_id, NULL, $this->dependencyManager)
      ->setTimeout(30)
      ->runAtomic($this, 'atomicRestartTask', [$task_id, $context, $logger]);
  }

  /**
   * Does the actual work for the restartTask method.
   *
   * This method verifies the appropriate lock has been acquired before
   * restarting the task. It is critical that the sequence of loading the task,
   * setting it to be restarted, and saving it be done in an atomic fashion so
   * that other processes do not interfere.
   *
   * @param int $task_id
   *   The task ID.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the child Wip IDs are stored.
   * @param WipLogInterface $logger
   *   The WipLogInterface instance.
   *
   * @return WipTaskProcessInterface
   *   The WipTaskProcess instance representing the restarted task.
   *
   * @throws NoTaskException
   *   If there is no task associated with the specified task ID.
   * @throws \Exception
   *   If the task has not completed; a task cannot be restarted unless it is
   *   in a completed state.
   * @throws RowLockException
   *   If the wip_pool row update lock has not been acquired.
   */
  public function atomicRestartTask($task_id, WipContextInterface $context, WipLogInterface $logger) {
    if (!WipPoolRowLock::getWipPoolRowLock($task_id, NULL, $this->dependencyManager)->hasLock()) {
      throw new RowLockException(
        'The wip_pool row update lock must be acquired before calling atomicRestartTask.'
      );
    }
    $result = NULL;
    if (!is_int($task_id)) {
      throw new \InvalidArgumentException('The task_id argument must be an integer.');
    }
    $wip_pool = $this->getWipPool();
    $task = $wip_pool->getTask($task_id);
    if (!empty($task)) {
      // Convert all completed processes to results.
      $this->getWipTaskStatus($context, $logger);
      if ($task->getStatus() === TaskStatus::COMPLETE) {
        // The task cannot be restarted.
        throw new \RuntimeException(
          sprintf('Task %d cannot be restarted; it has a status of %d', $task_id, $task->getStatus())
        );
      }
      // If there is an associated result object, delete it.
      $result = $this->getWipTaskResult($task_id, $context);
      if (!empty($result)) {
        $this->removeWipTaskResult($result, $context);
      }
      $wip_pool->restartTask($task);
      $process = new WipTaskProcess($task);
      $this->addWipTaskProcess($process, $context);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function clearWipTaskResults(WipContextInterface $context, WipLogInterface $logger) {
    $this->clearWipTaskProcesses($context, $logger);
    if (isset($context->wip)) {
      unset($context->wip);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setWipTaskResult(
    WipTaskResultInterface $result,
    WipContextInterface $context,
    WipLogInterface $logger
  ) {
    $this->clearWipTaskResults($context, $logger);
    $this->addWipTaskResult($result, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function addWipTaskResult(WipTaskResultInterface $result, WipContextInterface $context) {
    if (!isset($context->wip)) {
      $context->wip = new \stdClass();
    }
    if (!isset($context->wip->results) || !is_array($context->wip->results)) {
      $context->wip->results = array();
    }
    $unique_id = $result->getUniqueId();
    if (!in_array($unique_id, $context->wip->results)) {
      $context->wip->results[$unique_id] = $result;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getWipTaskResults(WipContextInterface $context) {
    $result = array();
    if (isset($context->wip) && isset($context->wip->results) && is_array($context->wip->results)) {
      $result = $context->wip->results;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getWipTaskResult($id, WipContextInterface $context) {
    $result = NULL;
    if (isset($context->wip) && isset($context->wip->results) && is_array($context->wip->results)) {
      if (array_key_exists($id, $context->wip->results)) {
        $result = $context->wip->results[$id];
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function removeWipTaskResult(WipTaskResultInterface $result, WipContextInterface $context) {
    $unique_id = $result->getUniqueId();
    $result = $this->getWipTaskResult($result->getUniqueId(), $context);
    if (!empty($result) && !empty($context->wip) && !empty($context->wip->results)) {
      unset($context->wip->results[$unique_id]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clearWipTaskProcesses(WipContextInterface $context, WipLogInterface $logger) {
    if (isset($context->wip) && isset($context->wip->processes)) {
      if (is_array($context->wip->processes)) {
        foreach ($context->wip->processes as $process) {
          // Be sure server side resources are released.
          if ($process instanceof WipTaskProcessInterface) {
            if (!$process->hasCompleted($logger)) {
              $process->kill($logger);
            }
          }
        }
      }
    }
    if (isset($context->wip)) {
      unset($context->wip);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setWipTaskProcess(
    WipTaskProcessInterface $process,
    WipContextInterface $context,
    WipLogInterface $logger
  ) {
    $this->clearWipTaskProcesses($context, $logger);
    $this->addWipTaskProcess($process, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function addWipTaskProcess(WipTaskProcessInterface $process, WipContextInterface $context) {
    if (!isset($context->wip)) {
      $context->wip = new \stdClass();
    }
    if (!isset($context->wip->processes) || !is_array($context->wip->processes)) {
      $context->wip->processes = array();
    }
    $unique_id = $process->getUniqueId();
    if (!in_array($unique_id, $context->wip->processes)) {
      $context->wip->processes[$unique_id] = $process;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function removeWipTaskProcess(
    WipTaskProcessInterface $process,
    WipContextInterface $context,
    WipLogInterface $logger
  ) {
    $process = $this->getWipTaskProcess($process->getUniqueId(), $context);
    if (!empty($process) && $process instanceof WipTaskProcessInterface) {
      // Be sure server side resources are released.
      if (!$process->hasCompleted($logger)) {
        $process->kill($logger);
      }
      if (!empty($context->wip) && !empty($context->wip->processes)) {
        unset($context->wip->processes[$process->getUniqueId()]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getWipTaskProcesses(WipContextInterface $context) {
    $result = array();
    if (isset($context->wip) && isset($context->wip->processes) && is_array($context->wip->processes)) {
      $result = $context->wip->processes;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getWipTaskProcess($id, WipContextInterface $context) {
    $result = NULL;
    if (isset($context->wip) && isset($context->wip->processes) && is_array($context->wip->processes)) {
      if (array_key_exists($id, $context->wip->processes)) {
        $result = $context->wip->processes[$id];
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getWipTaskStatus(WipContextInterface $context, WipLogInterface $logger) {
    $result = 'uninitialized';
    // Processing signals will automatically convert any completed process
    // objects into result objects.
    $context->processSignals();
    // Verify all processes have completed.
    $processes = $this->getWipTaskProcesses($context);
    foreach ($processes as $id => $process) {
      if ($process instanceof WipTaskProcessInterface) {
        if (!$process->hasCompleted($logger)) {
          if (!$this->runningTooLong($process, $logger)) {
            $result = 'wait';
            break;
          }
          // Fail the process out; it has taken too long.
          // @todo: Are we going to force fail a Wip task?
          // $process->forceFail($logger);
        }
        // This process completed; convert it to a result.
        /** @var WipTaskResultInterface $wip_result */
        $wip_result = $process->getResult($logger, TRUE);
        if (empty($wip_result)) {
          $result = 'fail';
          break;
        }
        if ($wip_result->isSuccess()) {
          // @todo: Record the run length of all successful tasks.
          $environment = $wip_result->getEnvironment();
          if (!empty($environment)) {
            $run_time = $wip_result->getRuntime();
            $this->recordProcessRuntime('wip', $environment->getSitegroup(), $run_time);
          }
        }
        $this->addWipTaskResult($wip_result, $context);
        $this->removeWipTaskProcess($process, $context, $logger);
        $log_level = WipLogLevel::WARN;
        if ($wip_result->isSuccess()) {
          $log_level = WipLogLevel::INFO;
        }
        $task = $process->getTask();
        try {
          $time = $wip_result->getRuntime();
          $logger->multiLog(
            $context->getObjectId(),
            $log_level,
            sprintf(
              'Requested the result of asynchronous Wip task - %s completed in %d seconds',
              $task->getName(),
              $time
            ),
            WipLogLevel::DEBUG,
            sprintf(' - exit: %s', $wip_result->getExitCode())
          );
        } catch (\Exception $e) {
          // This can happen if the start and/or end time were not set.
          $logger->multiLog(
            $context->getObjectId(),
            $log_level,
            sprintf(
              'Requested the result of asynchronous Wip task - %s completed',
              $task->getName()
            ),
            WipLogLevel::DEBUG,
            sprintf(' - exit: %s', $wip_result->getExitCode())
          );
        }
      }
    }
    // Have all of the processes completed?
    $processes = $this->getWipTaskProcesses($context);
    if (count($processes) == 0) {
      // Inspect all results. Note this only happens if there are no processes
      // still running.
      $wip_results = $this->getWipTaskResults($context);
      if (count($wip_results) > 0) {
        $result = 'success';
        foreach ($wip_results as $id => $wip_result) {
          if ($wip_result instanceof WipTaskResultInterface) {
            if (!$wip_result->isSuccess()) {
              $result = 'fail';
              break;
            }
          }
        }
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getSignals() {
    $signal_store = $this->getSignalStore();
    return $signal_store->loadAllActive($this->wipId);
  }

  /**
   * {@inheritdoc}
   */
  public function consumeSignal(SignalInterface $signal) {
    $signal_store = $this->getSignalStore();
    $signal_store->consume($signal);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteSignal(SignalInterface $signal) {
    $signal_store = $this->getSignalStore();
    $signal_store->delete($signal);
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.pool' => 'Acquia\Wip\Runtime\WipPool',
      'acquia.wip.storage.wippool' => 'Acquia\Wip\Storage\WipPoolStoreInterface',
      'acquia.wip.storage.signal' => 'Acquia\Wip\Storage\SignalStoreInterface',
    );
  }

  /**
   * Gets the WipPool instance to use.
   *
   * @return WipPoolInterface
   *   The WipPool instance.
   *
   * @throws \Acquia\Wip\Exception\DependencyMissingException
   *   If the WipPool could not be found.
   */
  protected function getWipPool() {
    return $this->dependencyManager->getDependency('acquia.wip.pool');
  }

  /**
   * Gets the WipPool storage instance to use.
   *
   * @return WipPoolStoreInterface
   *   The WipPool storage.
   *
   * @throws \Acquia\Wip\Exception\DependencyMissingException
   *   If the WipPoolStoreInterface implementation could not be found.
   */
  protected function getWipPoolStore() {
    return $this->dependencyManager->getDependency('acquia.wip.storage.wippool');
  }

  /**
   * Gets the signal storage instance to use.
   *
   * @return SignalStoreInterface
   *   The signal storage.
   *
   * @throws \Acquia\Wip\Exception\DependencyMissingException
   *   If the SignalStoreInterface implementation could not be found.
   */
  private function getSignalStore() {
    return $this->dependencyManager->getDependency('acquia.wip.storage.signal');
  }

  /**
   * {@inheritdoc}
   */
  public function processSignal(WipSignalInterface $signal, WipContextInterface $context, WipLogInterface $logger) {
    $result = 0;
    if ($signal instanceof WipCompleteSignal && $signal->getType() === SignalType::COMPLETE) {
      $result += $this->processCompletionSignal($signal, $context, $logger);
    }
    return $result;
  }

  /**
   * Processes the specified WipCompleteSignal instance.
   *
   * @param WipCompleteSignal $signal
   *   The signal.
   * @param WipContextInterface $context
   *   The context.
   * @param WipLogInterface $logger
   *   The logger.
   *
   * @return int
   *   0 if the specified signal was not processed; 1 otherwise.
   */
  private function processCompletionSignal(
    WipCompleteSignal $signal,
    WipContextInterface $context,
    WipLogInterface $logger
  ) {
    $result = 0;
    $process_id = $signal->getProcessId();
    $process = $this->getWipTaskProcess($process_id, $context);
    if (!empty($process) && $process instanceof WipTaskProcessInterface) {
      // This process completed; convert it to a result.
      /** @var WipTaskResultinterface $wip_result */
      $wip_result = $process->getResultFromSignal($signal, $logger);
      $this->addWipTaskResult($wip_result, $context);
      $wip_result->setSignal($signal);
      $this->removeWipTaskProcess($process, $context, $logger);
      $result = 1;
      $task = $process->getTask();
      $log_level = WipLogLevel::WARN;
      if ($wip_result->isSuccess()) {
        $log_level = WipLogLevel::INFO;
      }
      $time = $wip_result->getRuntime();
      $logger->multiLog(
        $context->getObjectId(),
        $log_level,
        sprintf(
          'Signaled result of asynchronous Wip task - %s completed in %s seconds',
          $task->getName(),
          $time
        ),
        WipLogLevel::DEBUG,
        sprintf(' - exit: %s', $wip_result->getExitCode())
      );
      $signal_store = $this->getSignalStore();
      $signal_store->consume($signal);
    }
    return $result;
  }

  /**
   * Determines whether the specified process has been running too long.
   *
   * @param WipTaskProcessInterface $process
   *   The process.
   * @param WipLogInterface $logger
   *   The logger.
   *
   * @return bool
   *   TRUE if the process has been running too long; FALSE otherwise.
   */
  private function runningTooLong(WipTaskProcessInterface $process, WipLogInterface $logger) {
    // @todo: For now we will not force-kill Wip objects.
    $result = FALSE;
    return $result;
  }

}
