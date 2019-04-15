<?php

namespace Acquia\Wip;

use Acquia\Wip\Container\ContainerProcessInterface;
use Acquia\Wip\Container\ContainerResultInterface;
use Acquia\Wip\Signal\ContainerDataSignalInterface;
use Acquia\Wip\Signal\ContainerSignalInterface;
use Acquia\Wip\Signal\ContainerTerminatedSignalInterface;

/**
 * This interface creates an easy way to interact with containers.
 */
interface WipContainerInterface {

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
   * Clears all container results from the specified context.
   *
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the container results are
   *   recorded.
   * @param WipLogInterface $logger
   *   The logger.
   */
  public function clearContainerResults(WipContextInterface $context, WipLogInterface $logger);

  /**
   * Sets the specified container result as the only result in the context.
   *
   * @param ContainerResultInterface $result
   *   The container result.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where container results are recorded.
   * @param WipLogInterface $logger
   *   The logger.
   */
  public function setContainerResult(
    ContainerResultInterface $result,
    WipContextInterface $context,
    WipLogInterface $logger
  );

  /**
   * Adds the specified container result.
   *
   * @param ContainerResultInterface $result
   *   The container result.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the container result will be
   *   recorded.
   */
  public function addContainerResult(ContainerResultInterface $result, WipContextInterface $context);

  /**
   * Removes the specified container result from the context.
   *
   * @param ContainerResultInterface $result
   *   The container result to remove.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the container result are
   *   recorded.
   */
  public function removeContainerResult(ContainerResultInterface $result, WipContextInterface $context);

  /**
   * Returns the set of container results in the specified context.
   *
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the container results are
   *   recorded.
   *
   * @return ContainerResultInterface[]
   *   The array of results.
   */
  public function getContainerResults(WipContextInterface $context);

  /**
   * Returns the container result from the context with the specified ID.
   *
   * @param int $id
   *   The unique ID of the container process.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the container results are
   *   recorded.
   *
   * @return ContainerResultInterface
   *   The container result.
   */
  public function getContainerResult($id, WipContextInterface $context);

  /**
   * Clears all container processes from the specified context.
   *
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the container processes are
   *   recorded.
   * @param WipLogInterface $logger
   *   The logger.
   */
  public function clearContainerProcesses(WipContextInterface $context, WipLogInterface $logger);

  /**
   * Sets the specified container process as the only process in the context.
   *
   * @param ContainerProcessInterface $process
   *   The container process.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the container processes are
   *   recorded.
   * @param WipLogInterface $logger
   *   The logger.
   */
  public function setContainerProcess(
    ContainerProcessInterface $process,
    WipContextInterface $context,
    WipLogInterface $logger
  );

  /**
   * Adds the specified container process.
   *
   * As the processes complete, they will be removed and converted into results.
   *
   * @param ContainerProcessInterface $process
   *   The container process.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the process will be recorded.
   */
  public function addContainerProcess(ContainerProcessInterface $process, WipContextInterface $context);

  /**
   * Removes the specified container process from the context.
   *
   * @param ContainerProcessInterface $process
   *   The container process to remove.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the process is recorded.
   * @param WipLogInterface $logger
   *   The logger.
   */
  public function removeContainerProcess(
    ContainerProcessInterface $process,
    WipContextInterface $context,
    WipLogInterface $logger
  );

  /**
   * Returns the set of container processes in the specified context.
   *
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the container processes are
   *   recorded.
   *
   * @return ContainerProcessInterface[]
   *   The array of processes.
   */
  public function getContainerProcesses(WipContextInterface $context);

  /**
   * Returns the container process in the context associated with the ID.
   *
   * @param int $id
   *   The unique ID of the container process.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the processes are recorded.
   *
   * @return ContainerProcessInterface
   *   The container process.
   */
  public function getContainerProcess($id, WipContextInterface $context);

  /**
   * Returns the status of container children within the specified context.
   *
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the container processes and
   *   results are stored.
   * @param WipLogInterface $logger
   *   The logger.
   *
   * @return string
   *   'success' - The container task completed successfully.
   *   'wait' - The container is being started.
   *   'ready' - The container is up and ready to receive the task.
   *   'running' - The container process is still running.
   *   'uninitialized' - No container results or processes have been added.
   *   'fail' - The container process failed.
   */
  public function getContainerStatus(WipContextInterface $context, WipLogInterface $logger);

  /**
   * Processes the given container signal.
   *
   * @param ContainerSignalInterface $signal
   *   An instance of ContainerSignalInterface representing the signal.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the container results and/or
   *   processes are stored.
   * @param WipLogInterface $logger
   *   The logger.
   *
   * @return int
   *   0 if the specified signal was not processed; 1 otherwise.
   */
  public function processSignal(
    ContainerSignalInterface $signal,
    WipContextInterface $context,
    WipLogInterface $logger
  );

  /**
   * Gets the first unprocessed ContainerTerminated signal, if any.
   *
   * @param int $wip_id
   *   The ID of the Wip object associated with the signals of interest.
   *
   * @return ContainerTerminatedSignalInterface|null
   *   The signal. If there is no such signal, NULL is returned instead.
   */
  public function getContainerTerminatedSignal($wip_id);

  /**
   * Gets the first unprocessed ContainerData signal, if any.
   *
   * @param int $wip_id
   *   The ID of the Wip object associated with the signals of interest.
   *
   * @return ContainerDataSignalInterface|null
   *   The signal. If there is no such signal, NULL is returned instead.
   */
  public function getContainerDataSignal($wip_id);

}
