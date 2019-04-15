<?php

namespace Acquia\Wip;

use Acquia\Wip\Runtime\WipPoolInterface;
use Acquia\Wip\Signal\WipCompleteSignal;

/**
 * Represents a running Wip task.
 */
class WipTaskProcess extends WipProcess implements WipTaskProcessInterface, DependencyManagedInterface {

  /**
   * The DependencyManager instance.
   *
   * @var DependencyManager
   */
  public $dependencyManager;

  /**
   * Creates an instance of WipTaskProcess initialized from the specified Task.
   *
   * @param TaskInterface $task
   *   The task instance.
   *
   * @throws \Exception
   *   If dependencies are not met or if a the specified task is complete.
   */
  public function __construct(TaskInterface $task) {
    parent::__construct();
    $this->setPid($task->getId());
    $this->dependencyManager = new DependencyManager();
    $this->dependencyManager->addDependencies($this->getDependencies());
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.pool' => 'Acquia\Wip\Runtime\WipPool',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getResult(WipLogInterface $wip_log, $fetch = FALSE) {
    $has_completed = $this->hasCompleted($wip_log);
    $result = NULL;
    if ($has_completed) {
      $result = parent::getResult($wip_log);
      if (empty($result) && $fetch) {
        $task = $this->getTask();
        if (!empty($task) && $task->getStatus() === TaskStatus::COMPLETE) {
          $result = WipTaskResult::fromTask($task);
          $this->setResult($result);

          $log_level = WipLogLevel::WARN;
          if ($result->isSuccess()) {
            $log_level = WipLogLevel::INFO;
          }
          try {
            $time = $result->getRuntime();
            $wip_log->multiLog(
              $this->getPid(),
              $log_level,
              sprintf('Get result of asynchronous Wip task - %s completed in %d seconds', $task->getName(), $time),
              WipLogLevel::DEBUG,
              sprintf(' - exit: %d', $result->getExitCode())
            );
          } catch (\Exception $e) {
            // This can happen if the start/end times are not set.
            $wip_log->multiLog(
              $this->getPid(),
              $log_level,
              sprintf(
                'Get result of asynchronous Wip task - %s completed but could not determine the run time.',
                $task->getName()
              ),
              WipLogLevel::DEBUG,
              sprintf(' - exit: %d', $result->getExitCode())
            );
          }
        }
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function setResult(WipResultInterface $result) {
    if (!$result instanceof WipTaskResultInterface) {
      throw new \InvalidArgumentException('The result parameter must implement interface WipTaskResultInterface.');
    }
    parent::setResult($result);
    $this->verifyStartTime();
  }

  /**
   * {@inheritdoc}
   */
  public function getUniqueId() {
    return WipTaskResult::createUniqueId($this->getPid());
  }

  /**
   * {@inheritdoc}
   */
  public function kill(WipLogInterface $logger) {
    $result = NULL;
    if (!$this->hasCompleted($logger)) {
      $task = $this->getTask();
      $wip_result = new WipTaskResult();
      if (!empty($task)) {
        // @todo: Implement terminate.
        // Termination can take some time because the task has to wake and the
        // appropriate lifecycle methods on the Wip task must be called.  Return
        // TRUE here indicating the task has completed.
        $exit_message = sprintf('Wip task %d terminated.', $this->getPid());
        $result = TRUE;
      } else {
        // The task has been removed from the database.
        $exit_message = sprintf('Unable to load Wip task %d from the database.', $this->getPid());
      }
      $this->setExitMessage($exit_message);
      $this->setExitCode(TaskExitStatus::TERMINATED);
      $wip_result->populateFromProcess($this);
      $this->setResult($wip_result);
    }
    return $result ? $result : $this->hasCompleted($logger);
  }

  /**
   * {@inheritdoc}
   */
  public function hasCompleted(WipLogInterface $logger) {
    $result = parent::hasCompleted($logger);
    if (!$result) {
      $task = $this->getTask();
      if ($task->getStatus() === TaskStatus::COMPLETE) {
        $result = TRUE;
      }
    }
    return $result;
  }

  /**
   * Gets the WipPool instance to use.
   *
   * @return WipPoolInterface
   *   The WipPool.
   *
   * @throws \Acquia\Wip\Exception\DependencyMissingException
   *   If the WipPool could not be found.
   */
  protected function getWipPool() {
    return $this->dependencyManager->getDependency('acquia.wip.pool');
  }

  /**
   * Sets the start time of this process instance if not already set.
   */
  private function verifyStartTime() {
    try {
      $this->getStartTime();
    } catch (\RuntimeException $e) {
      try {
        $task = $this->getTask();
        $this->setStartTime($task->getStartTimestamp());
      } catch (\Exception $e) {
        // Don't worry; we will simply get it next time.
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTask() {
    return $this->getWipPool()->getTask($this->getPid());
  }

  /**
   * Creates an instance of WipTaskResult from the specified signal.
   *
   * @param WipCompleteSignal $signal
   *   The signal.
   * @param WipLogInterface $logger
   *   The logger.
   *
   * @return WipTaskResultInterface
   *   The SshResult instance.
   */
  public function getResultFromSignal(WipCompleteSignal $signal, WipLogInterface $logger) {
    $result = WipTaskResult::fromObject($signal->getData());
    $result->populateFromProcess($this);
    $result->setSignal($signal);
    $this->setResult($result);
    return $result;
  }

}
