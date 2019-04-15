<?php

namespace Acquia\Wip\Implementation;

use Acquia\Wip\AcquiaCloud\AcquiaCloudProcessInterface;
use Acquia\Wip\AcquiaCloud\AcquiaCloudResultInterface;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudTaskResult;
use Acquia\Wip\ServiceApi;
use Acquia\Wip\WipAcquiaCloudInterface;
use Acquia\Wip\WipContextInterface;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipLogLevel;

/**
 * Makes interacting with the Cloud API within a Wip object easy.
 *
 * NOTE: The CloudAPI currently has no callback mechanism, so there is no
 * signal processing in this class.
 */
class AcquiaCloudApi extends ServiceApi implements WipAcquiaCloudInterface {

  /**
   * The prefix used for data collection.
   */
  const DATA_PREFIX = 'cloud';

  /**
   * The ID of the Wip object associated with this instance.
   *
   * @var int
   */
  private $wipId;

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
   * Clears all AcquiaCloud results from the specified context.
   *
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the AcquiaCloud results are
   *   recorded.
   * @param WipLogInterface $logger
   *   The logger.
   */
  public function clearAcquiaCloudResults(
    WipContextInterface $context,
    WipLogInterface $logger
  ) {
    $this->clearAcquiaCloudProcesses($context, $logger);
    if (isset($context->acquiaCloud)) {
      unset($context->acquiaCloud);
    }
  }

  /**
   * Sets the specified Wip result as the only result in the context.
   *
   * @param AcquiaCloudResultInterface $result
   *   The result.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where AcquiaCloud results are recorded.
   * @param WipLogInterface $logger
   *   The logger.
   */
  public function setAcquiaCloudResult(
    AcquiaCloudResultInterface $result,
    WipContextInterface $context,
    WipLogInterface $logger
  ) {
    $this->clearAcquiaCloudResults($context, $logger);
    $this->addAcquiaCloudResult($result, $context);
  }

  /**
   * Adds the specified AcquiaCloud result.
   *
   * @param AcquiaCloudResultInterface $result
   *   The result.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the AcquiaCloud result will be
   *   recorded.
   */
  public function addAcquiaCloudResult(
    AcquiaCloudResultInterface $result,
    WipContextInterface $context
  ) {
    if (!isset($context->acquiaCloud)) {
      $context->acquiaCloud = new \stdClass();
    }
    if (!isset($context->acquiaCloud->results) || !is_array($context->acquiaCloud->results)) {
      $context->acquiaCloud->results = array();
    }
    $unique_id = $result->getUniqueId();
    if (!in_array($unique_id, $context->acquiaCloud->results)) {
      $context->acquiaCloud->results[$unique_id] = $result;
    }
  }

  /**
   * Removes the specified AcquiaCloud result from the context.
   *
   * @param AcquiaCloudResultInterface $cloud_result
   *   The AcquiaCloud result to remove.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the AcquiaCloud result will be
   *   recorded.
   */
  public function removeAcquiaCloudResult(
    AcquiaCloudResultInterface $cloud_result,
    WipContextInterface $context
  ) {
    $unique_id = $cloud_result->getUniqueId();
    $result = $this->getAcquiaCloudResult($cloud_result->getUniqueId(), $context);
    if (!empty($result) && !empty($context->acquiaCloud) && !empty($context->acquiaCloud->results)) {
      unset($context->acquiaCloud->results[$unique_id]);
    }
  }

  /**
   * Returns the set of AcquiaCloud results in the specified context.
   *
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the AcquiaCloud results are
   *   recorded.
   *
   * @return AcquiaCloudResultInterface[]
   *   The array of results.
   */
  public function getAcquiaCloudResults(WipContextInterface $context) {
    $result = array();
    if (isset($context->acquiaCloud) &&
      isset($context->acquiaCloud->results) && is_array($context->acquiaCloud->results)
    ) {
      $result = $context->acquiaCloud->results;
    }
    return $result;
  }

  /**
   * Returns the AcquiaCloud result from the specified context with the ID.
   *
   * @param int $id
   *   The Wip Task ID.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the AcquiaCloud results are
   *   recorded.
   *
   * @return AcquiaCloudResultInterface
   *   The AcquiaCloudResult.
   */
  public function getAcquiaCloudResult($id, WipContextInterface $context) {
    $result = NULL;
    if (isset($context->acquiaCloud) &&
      isset($context->acquiaCloud->results) && is_array($context->acquiaCloud->results)
    ) {
      if (isset($context->acquiaCloud->results[$id])) {
        $result = $context->acquiaCloud->results[$id];
      }
    }
    return $result;
  }

  /**
   * Clears all AcquiaCloud processes from the specified context.
   *
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the AcquiaCloud processes are
   *   recorded.
   * @param WipLogInterface $logger
   *   The logger.
   */
  public function clearAcquiaCloudProcesses(
    WipContextInterface $context,
    WipLogInterface $logger
  ) {
    if (isset($context->acquiaCloud)) {
      unset($context->acquiaCloud);
    }
  }

  /**
   * Sets the specified AcquiaCloud process as the only process in the context.
   *
   * @param AcquiaCloudProcessInterface $process
   *   The process.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the AcquiaCloud processes are
   *   recorded.
   * @param WipLogInterface $logger
   *   The logger.
   */
  public function setAcquiaCloudProcess(
    AcquiaCloudProcessInterface $process,
    WipContextInterface $context,
    WipLogInterface $logger
  ) {
    $this->clearAcquiaCloudProcesses($context, $logger);
    $this->addAcquiaCloudProcess($process, $context);
  }

  /**
   * Adds the specified Acquia process.
   *
   * As the processes complete, they will be removed and converted into results.
   *
   * @param AcquiaCloudProcessInterface $process
   *   The AcquiaCloud process.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the process will be recorded.
   */
  public function addAcquiaCloudProcess(
    AcquiaCloudProcessInterface $process,
    WipContextInterface $context
  ) {
    if (!isset($context->acquiaCloud)) {
      $context->acquiaCloud = new \stdClass();
    }
    if (!isset($context->acquiaCloud->processes) || !is_array($context->acquiaCloud->processes)) {
      $context->acquiaCloud->processes = array();
    }
    $unique_id = $process->getUniqueId();
    if (!isset($context->acquiaCloud->processes[$unique_id])) {
      $context->acquiaCloud->processes[$unique_id] = $process;
    }
  }

  /**
   * Removes the specified AcquiaCloud process from the context.
   *
   * @param AcquiaCloudProcessInterface $process
   *   The process to remove.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the process is recorded.
   * @param WipLogInterface $logger
   *   The logger.
   */
  public function removeAcquiaCloudProcess(
    AcquiaCloudProcessInterface $process,
    WipContextInterface $context,
    WipLogInterface $logger
  ) {
    $process = $this->getAcquiaCloudProcess($process->getUniqueId(), $context);
    if (!empty($process) && $process instanceof AcquiaCloudProcessInterface) {
      if (!empty($context->acquiaCloud) && !empty($context->acquiaCloud->processes)) {
        unset($context->acquiaCloud->processes[$process->getUniqueId()]);
      }
    }
  }

  /**
   * Returns the set of AcquiaCloud processes in the specified context.
   *
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the AcquiaCloud processes are
   *   recorded.
   *
   * @return AcquiaCloudProcessInterface[]
   *   The array of processes.
   */
  public function getAcquiaCloudProcesses(WipContextInterface $context) {
    $result = array();
    if (isset($context->acquiaCloud) &&
      isset($context->acquiaCloud->processes) && is_array($context->acquiaCloud->processes)
    ) {
      $result = $context->acquiaCloud->processes;
    }
    return $result;
  }

  /**
   * Returns the AcquiaCloud process in the context associated with the ID.
   *
   * @param int $id
   *   The acquiaCloud process ID.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the processes are recorded.
   *
   * @return AcquiaCloudProcessInterface
   *   The AcquiaCloud process.
   */
  public function getAcquiaCloudProcess($id, WipContextInterface $context) {
    $result = NULL;
    if (isset($context->acquiaCloud) &&
      isset($context->acquiaCloud->processes) && is_array($context->acquiaCloud->processes)
    ) {
      if (isset($context->acquiaCloud->processes[$id])) {
        $result = $context->acquiaCloud->processes[$id];
      }
    }
    return $result;
  }

  /**
   * Returns the status of AcquiaCloud children associated with the context.
   *
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the AcquiaCloud results are
   *   stored.
   * @param WipLogInterface $logger
   *   The logger.
   *
   * @return string
   *   'success' - All tasks have completed successfully.
   *   'wait' - One or more tasks are still running.
   *   'uninitialized' - No AcquiaCloud objects have been added to the context.
   *   'fail' - At least one task failed.
   */
  public function getAcquiaCloudStatus(
    WipContextInterface $context,
    WipLogInterface $logger
  ) {
    $result = 'uninitialized';
    // Processing signals will automatically convert any completed process
    // objects into result objects.
    $context->processSignals();
    // Verify all processes have completed.
    $processes = $this->getAcquiaCloudProcesses($context);
    foreach ($processes as $id => $process) {
      if ($process instanceof AcquiaCloudProcessInterface) {
        if (!$process->hasCompleted($logger)) {
          if (!$this->runningTooLong($process, $logger)) {
            $result = 'wait';
            break;
          }
          // Fail this process out; it has taken too long.
          $process->forceFail('The process has been running too long.', $logger);
        }
        $task_result = $process->getTaskInfo($logger);
        try {
          $task_result->setEndtime(time());
        } catch (\Exception $e) {
          // The end time has already been set.
        }
        if ($task_result->isSuccess()) {
          $environment = $task_result->getEnvironment();
          if (!empty($environment)) {
            $run_time = $task_result->getData()
              ->getCompleted() - $task_result->getData()->getStarted();
            $this->recordProcessRuntime($this->getDataName($task_result), $environment->getSitegroup(), $run_time);
          }
        }
        // This process completed; convert it to a result.
        $this->addAcquiaCloudResult($task_result, $context);
        $this->removeAcquiaCloudProcess($process, $context, $logger);
        $time = $task_result->getRuntime();
        if ($task_result->isSuccess()) {
          $log_level = WipLogLevel::INFO;
          $task = $task_result->getData();
          $logger->multiLog(
            $context->getObjectId(),
            $log_level,
            sprintf(
              'Requested the result of Acquia Cloud task - %s [%s] completed in %s seconds',
              $task->getId(),
              $task->getDescription(),
              $time
            ),
            WipLogLevel::DEBUG,
            sprintf(' - exit: %s', $task->getState())
          );
        } else {
          $log_level = WipLogLevel::WARN;
          $logger->log(
            $log_level,
            sprintf(
              'Request to get the result of Acquia Cloud task %s failed: %s',
              $task_result->getPid(),
              $task_result->getExitMessage()
            ),
            $context->getObjectId()
          );
        }
      }
    }
    // Have all of the processes completed?
    $processes = $this->getAcquiaCloudProcesses($context);
    if (count($processes) == 0) {
      // Inspect all results. Note this only happens if there are no processes
      // still running.
      $task_results = $this->getAcquiaCloudResults($context);
      if (count($task_results) > 0) {
        $result = 'success';
        foreach ($task_results as $id => $task_result) {
          if ($task_result instanceof AcquiaCloudResultInterface) {
            if (!$task_result->isSuccess()) {
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
   * Determines whether the specified process has been running too long.
   *
   * @param AcquiaCloudProcessInterface $process
   *   The process.
   * @param WipLogInterface $logger
   *   The logger.
   *
   * @return bool
   *   TRUE if the process has been running too long; FALSE otherwise.
   */
  private function runningTooLong(
    AcquiaCloudProcessInterface $process,
    WipLogInterface $logger
  ) {
    $result = FALSE;
    $task_result = $process->getTaskInfo($logger);
    if ($task_result->isSuccess()) {
      $task_info = $task_result->getData();
      $result = $this->hasProcessRunTooLong(
        $this->getDataName($task_result),
        $process->getEnvironment()->getSitegroup(),
        $task_info->getStarted()
      );
    }
    return $result;
  }

  /**
   * Returns the name used to record and query runtime data.
   *
   * @param AcquiaCloudTaskResult $task_result
   *   The task result.
   *
   * @return string
   *   The data name.
   */
  private function getDataName(AcquiaCloudTaskResult $task_result) {
    $result = 'taskFailure';
    if ($task_result->isSuccess()) {
      $result = sprintf(
        '%s-%s',
        self::DATA_PREFIX,
        $task_result->getData()->getQueue()
      );
    }
    return $result;
  }

}
