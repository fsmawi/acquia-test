<?php

namespace Acquia\Wip\AcquiaCloud;

use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudTaskResult;
use Acquia\Wip\DependencyManager;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipLogLevel;
use Acquia\Wip\WipProcess;
use Acquia\Wip\WipResultInterface;
use Guzzle\Http\Exception\BadResponseException;

/**
 * Represents a running AcquiaCloud task.
 */
class AcquiaCloudProcess extends WipProcess implements AcquiaCloudProcessInterface {

  /**
   * The DependencyManager instance.
   *
   * @var DependencyManager
   */
  public $dependencyManager;

  /**
   * The message from any error that prevents the process from being invoked.
   *
   * @var string
   */
  private $error;

  /**
   * The class name of the result.
   *
   * @var string
   */
  private $resultClass = NULL;

  /**
   * Creates an instance of AcquiaCloudProcess.
   *
   * @throws \Exception
   *   If dependencies are not met or if a the specified task is complete.
   */
  public function __construct() {
    $this->setSuccessExitCodes(array(200));
  }

  /**
   * {@inheritdoc}
   */
  public function setPid($pid) {
    if (!is_int($pid)) {
      throw new \InvalidArgumentException('The pid parameter must be an integer.');
    }
    parent::setPid($pid);
  }

  /**
   * {@inheritdoc}
   */
  public function getResult(WipLogInterface $wip_log, $fetch = FALSE) {
    $result = parent::getResult($wip_log);
    if (empty($result) && $fetch) {
      $error = $this->getError();
      if (!empty($error)) {
        $this->createFailureResult($wip_log);
      } else {
        $task_result = $this->getTaskInfo($wip_log);
        $pid = $this->getPid();
        if (empty($task_result)) {
          throw new \RuntimeException("Could not get task result for task ${pid}");
        }
        $task = $task_result->getData();
        if (empty($task)) {
          throw new \RuntimeException('No task data has been set.');
        }
        if ($task->isRunning()) {
          throw new \RuntimeException('Cannot call getResult on a task that is still running.');
        }
        $completed = $task->getCompleted();
        if (is_numeric($completed)) {
          $this->setEndTime($completed);
        }
        $task_result->populateFromProcess($this);
        $result = $task_result;
        $this->setResult($result);
        $log_level = WipLogLevel::INFO;
        if (!$task->isSuccess()) {
          $log_level = WipLogLevel::ERROR;
        }
        $wip_log->log(
          $log_level,
          sprintf(
            'Get result of Hosting Wip task - %d completed in %d seconds',
            $task->getId(),
            $task->getCompleted() - $task->getStarted()
          ),
          $this->getWipId()
        );
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function setResult(WipResultInterface $result) {
    if (!$result instanceof AcquiaCloudResult) {
      throw new \InvalidArgumentException('The result parameter must be of type AcquiaCloudResult.');
    }
    parent::setResult($result);
  }

  /**
   * Indicates whether this process is still running.
   *
   * @param WipLogInterface $logger
   *   The logger.
   *
   * @return bool
   *   true if this process is still running; false otherwise.
   *
   * @deprecated
   */
  public function isRunning(WipLogInterface $logger) {
    $result = $this->hasCompleted($logger);
    if (is_bool($result)) {
      $result = !$result;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function hasCompleted(WipLogInterface $logger) {
    $result = parent::hasCompleted($logger);
    if (empty($result)) {
      $error = $this->getError();
      if (!empty($error)) {
        try {
          $this->createFailureResult($logger);
        } catch (\Exception $e) {
          // The result has already been set.
        }
        $result = TRUE;
      } else {
        $task_result = $this->getTaskInfo($logger);
        if (!empty($task_result)) {
          $task_info = $task_result->getData();
          if (!empty($task_info) && $task_info->isRunning() === FALSE) {
            $this->setResult($task_result);
            $result = TRUE;
          }
        }
      }
    }
    return $result;
  }

  /**
   * Creates a result that indicates failure.
   *
   * @param WipLogInterface $wip_log
   *   Optional.  The Wip log.
   *
   * @return AcquiaCloudTaskResult
   *   The result that indicates failure.
   */
  private function createFailureResult(WipLogInterface $wip_log = NULL) {
    $pid = NULL;
    try {
      $pid = $this->getPid();
    } catch (\Exception $e) {
      // This will happen if the process failed to start and thus has no process
      // ID.
    }
    $generate_process_id = empty($pid);
    $result = new AcquiaCloudTaskResult($generate_process_id);
    $now = time();
    try {
      $this->setStartTime($now);
    } catch (\Exception $e) {
    }
    $this->setEndTime(time());
    $result->populateFromProcess($this);
    $this->setResult($result);
    if (!empty($wip_log)) {
      $wip_log->log(
        WipLogLevel::ERROR,
        sprintf(
          'Failed Cloud API request - %s completed in %d seconds: %s',
          $result->getPid(),
          $result->getRuntime(),
          $result->getExitMessage()
        ),
        $result->getWipId()
      );
    }
    return $result;
  }

  /**
   * Gets the task info.
   *
   * @param WipLogInterface $logger
   *   The logger.
   *
   * @return AcquiaCloudTaskResult
   *   The task information.
   */
  public function getTaskInfo(WipLogInterface $logger) {
    // Get the cached result only.  If the result has not been cached, certainly
    // don't ask it to fetch the result from the Cloud API or it will end up
    // back here until it exceeds the maximum number of stack frames.
    $result = $this->getResult($logger, FALSE);
    if (empty($result) || !$result instanceof AcquiaCloudTaskResult) {
      $result = NULL;
      $pid = NULL;
      try {
        // This will throw an exception if no process ID has been set into the
        // AcquiaCloudProcess instance.  The process ID must be set at the time
        // when the AcquiaCloudProcess is created, and control should never get
        // this far without one.
        $pid = $this->getPid();
        if (!empty($pid)) {
          $cloud = new AcquiaCloud($this->getEnvironment(), $logger);
          $result = $cloud->getTaskInfo($pid, $this->getTaskInfoClass());
        }
        $result->populateFromProcess($this);
      } catch (\RuntimeException $e) {
        // The process ID is not set; this process instance was not properly
        // initialized.
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function setError($error, WipLogInterface $logger) {
    $exit_code = AcquiaCloudResult::EXIT_CODE_GENERAL_FAILURE;
    if ($error instanceof \Exception) {
      if ($error instanceof BadResponseException && ($response = $error->getResponse())) {
        $exit_code = $response->getStatusCode();
      }
      $error_message = $error->getMessage();
    } else {
      $error_message = strval($error);
    }
    try {
      $this->setExitCode($exit_code);
    } catch (\Exception $e) {
      // The exit code has already been set.
    }
    try {
      $this->setExitMessage($error_message);
    } catch (\Exception $e) {
      // The exit message has already been set.
    }
    $this->error = $error_message;
    try {
      $this->createFailureResult($logger);
    } catch (\RuntimeException $e) {
      // The result has already been set.
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getError() {
    return $this->error;
  }

  /**
   * {@inheritdoc}
   */
  public function kill(WipLogInterface $logger) {
    if (!$this->hasCompleted($logger)) {
      // There is currently no way to kill a hosting task; instead ignore the task
      // by setting a result.
      try {
        $pid = $this->getPid();
      } catch (\RuntimeException $e) {
        $pid = 'unknown';
      }
      $message = sprintf('Hosting task "%s" killed.', $pid);
      $logger->log(WipLogLevel::ALERT, $message, $this->getWipId());
      $error = new \RuntimeException($message);
      $this->setError($error, $logger);
    }
    return parent::kill($logger);
  }

  /**
   * {@inheritdoc}
   */
  public function setTaskInfoClass($class_name) {
    if (empty($class_name) || !is_string($class_name)) {
      throw new \InvalidArgumentException('The class_name parameter must be a non-empty string.');
    }
    $this->resultClass = $class_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getTaskInfoClass() {
    return $this->resultClass;
  }

}
