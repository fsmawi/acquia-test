<?php

namespace Acquia\Wip;

use Acquia\Wip\AcquiaCloud\AcquiaCloudProcessInterface;
use Acquia\Wip\AcquiaCloud\AcquiaCloudResultInterface;

/**
 * This interface creates an easy way to interact with the Cloud API.
 */
interface WipAcquiaCloudInterface {

  /**
   * Gets the ID of the Wip object associated with this instance.
   *
   * @return int
   *   The Wip ID.
   */
  public function getWipId();

  /**
   * Sets the ID of the Wip object associated with this instance.
   *
   * @param int $wip_id
   *   The Wip ID.
   */
  public function setWipId($wip_id);

  /**
   * Clears all AcquiaCloud results from the specified context.
   *
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the AcquiaCloud results are
   *   recorded.
   * @param WipLogInterface $logger
   *   The logger.
   */
  public function clearAcquiaCloudResults(WipContextInterface $context, WipLogInterface $logger);

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
  );

  /**
   * Adds the specified AcquiaCloud result.
   *
   * @param AcquiaCloudResultInterface $result
   *   The result.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the AcquiaCloud result will be
   *   recorded.
   */
  public function addAcquiaCloudResult(AcquiaCloudResultInterface $result, WipContextInterface $context);

  /**
   * Removes the specified AcquiaCloud result from the context.
   *
   * @param AcquiaCloudResultInterface $cloud_result
   *   The AcquiaCloud result to remove.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the AcquiaCloud result will be
   *   recorded.
   */
  public function removeAcquiaCloudResult(AcquiaCloudResultInterface $cloud_result, WipContextInterface $context);

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
  public function getAcquiaCloudResults(WipContextInterface $context);

  /**
   * Returns the AcquiaCloud result from the context with the specified ID.
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
  public function getAcquiaCloudResult($id, WipContextInterface $context);

  /**
   * Clears all AcquiaCloud processes from the specified context.
   *
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the AcquiaCloud processes are
   *   recorded.
   * @param WipLogInterface $logger
   *   The logger.
   */
  public function clearAcquiaCloudProcesses(WipContextInterface $context, WipLogInterface $logger);

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
  );

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
  public function addAcquiaCloudProcess(AcquiaCloudProcessInterface $process, WipContextInterface $context);

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
  );

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
  public function getAcquiaCloudProcesses(WipContextInterface $context);

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
  public function getAcquiaCloudProcess($id, WipContextInterface $context);

  /**
   * Returns the status of AcquiaCloud children with the specified context.
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
  public function getAcquiaCloudStatus(WipContextInterface $context, WipLogInterface $logger);

}
