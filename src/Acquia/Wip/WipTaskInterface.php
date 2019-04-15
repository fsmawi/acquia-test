<?php

namespace Acquia\Wip;

use Acquia\Wip\Exception\NoTaskException;
use Acquia\Wip\Signal\SignalInterface;
use Acquia\Wip\Signal\WipSignalInterface;

/**
 * The WipTaskInterface facilitates interaction between a Wip and its children.
 */
interface WipTaskInterface {

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
   * Clears all Wip results from the specified context.
   *
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the Wip results are recorded.
   * @param WipLogInterface $logger
   *   The logger.
   */
  public function clearWipTaskResults(WipContextInterface $context, WipLogInterface $logger);

  /**
   * Sets the specified Wip result as the only result in the context.
   *
   * @param WipTaskResultInterface $result
   *   The result.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where Wip results are recorded.
   * @param WipLogInterface $logger
   *   The logger.
   */
  public function setWipTaskResult(
    WipTaskResultInterface $result,
    WipContextInterface $context,
    WipLogInterface $logger
  );

  /**
   * Adds the specified Wip result.
   *
   * @param WipTaskResultInterface $result
   *   The result.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the Wip result will be recorded.
   */
  public function addWipTaskResult(WipTaskResultInterface $result, WipContextInterface $context);

  /**
   * Removes the specified Wip result from the context.
   *
   * @param WipTaskResultInterface $result
   *   The Wip result to remove.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the Wip result will be recorded.
   */
  public function removeWipTaskResult(WipTaskResultInterface $result, WipContextInterface $context);

  /**
   * Returns the set of Wip results in the specified context.
   *
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the Wip results are recorded.
   *
   * @return WipTaskResultInterface[]
   *   The array of results.
   */
  public function getWipTaskResults(WipContextInterface $context);

  /**
   * Returns the Wip result from the specified context with the specified ID.
   *
   * @param int $id
   *   The Wip Task ID.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the Wip results are recorded.
   *
   * @return WipTaskResultInterface
   *   The WipTaskResult.
   */
  public function getWipTaskResult($id, WipContextInterface $context);

  /**
   * Clears all Wip processes from the specified context.
   *
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the Wip processes are recorded.
   * @param WipLogInterface $logger
   *   The logger.
   */
  public function clearWipTaskProcesses(WipContextInterface $context, WipLogInterface $logger);

  /**
   * Sets the specified Wip process as the only process in the context.
   *
   * @param WipTaskProcessInterface $process
   *   The process.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the Wip processes are recorded.
   * @param WipLogInterface $logger
   *   The logger.
   */
  public function setWipTaskProcess(
    WipTaskProcessInterface $process,
    WipContextInterface $context,
    WipLogInterface $logger
  );

  /**
   * Adds the specified Wip process.
   *
   * As the processes complete, they will be removed and converted into results.
   *
   * @param WipTaskProcessInterface $process
   *   The Wip process.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the Wip process will be recorded.
   */
  public function addWipTaskProcess(WipTaskProcessInterface $process, WipContextInterface $context);

  /**
   * Removes the specified Wip process from the context.
   *
   * @param WipTaskProcessInterface $process
   *   The Wip process to remove.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the Wip process is recorded.
   * @param WipLogInterface $logger
   *   The logger.
   */
  public function removeWipTaskProcess(
    WipTaskProcessInterface $process,
    WipContextInterface $context,
    WipLogInterface $logger
  );

  /**
   * Returns the set of Wip processes in the specified context.
   *
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the Wip processes are recorded.
   *
   * @return WipTaskProcessInterface[]
   *   The array of processes.
   */
  public function getWipTaskProcesses(WipContextInterface $context);

  /**
   * Returns the Wip process in the specified context associated with the ID.
   *
   * @param int $id
   *   The Wip process ID.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the Wip processes are recorded.
   *
   * @return WipTaskProcessInterface
   *   The Wip process.
   */
  public function getWipTaskProcess($id, WipContextInterface $context);

  /**
   * Adds the specified child object to be executed at the specified priority.
   *
   * @param WipInterface $child
   *   The Wip object to add as a child.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the child will be recorded.
   * @param WipInterface $parent
   *   Optional. The Wip object that will be the parent object. If not provided
   *   the new Wip object will not have a parent.
   * @param TaskPriority $priority
   *   Optional. The task priority the child object will execute with.
   * @param bool $send_signal
   *   Optional. If TRUE the child Wip object will send a completion signal to
   *   the parent Wip object.
   *
   * @return WipTaskProcess
   *   The WipTaskProcess representing the running Wip process.
   */
  public function addChild(
    WipInterface $child,
    WipContextInterface $context,
    WipInterface $parent = NULL,
    TaskPriority $priority = NULL,
    $send_signal = TRUE
  );

  /**
   * Restarts the task with the specified ID.
   *
   * When the task is restarted, the specified context will be modified
   * accordingly. If there is a process associated with the context that has
   * completed, it will be converted to a result. If there is a result in the
   * context that matches the specified task ID, it will be removed. A new
   * WipTaskProcessInterface instance will be added to the specified context.
   *
   * If the task has not completed, an exception will be thrown.
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
   *
   * @throws \Exception
   *   If the task has not completed; a task cannot be restarted unless it is
   *   in a completed state.
   */
  public function restartTask($task_id, WipContextInterface $context, WipLogInterface $logger);

  /**
   * Returns the status of Wip children associated with the specified context.
   *
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the child Wip IDs are stored.
   * @param WipLogInterface $logger
   *   The logger.
   *
   * @return string
   *   'success' - All tasks have completed successfully.
   *   'wait' - One or more tasks are still running.
   *   'uninitialized' - No child Wip objects have been added to the context.
   *   'fail' - At least one task failed.
   */
  public function getWipTaskStatus(WipContextInterface $context, WipLogInterface $logger);

  /**
   * Gets all signals associated with this instance.
   *
   * @return SignalInterface[]
   *   The signals.
   */
  public function getSignals();

  /**
   * Marks the specified signal as consumed.
   *
   * @param SignalInterface $signal
   *   The signal to consume.
   */
  public function consumeSignal(SignalInterface $signal);

  /**
   * Removes the signal from the signal store.
   *
   * @param SignalInterface $signal
   *   The signal to delete.
   */
  public function deleteSignal(SignalInterface $signal);

  /**
   * Processes the specified signal.
   *
   * @param WipSignalInterface $signal
   *   The signal.
   * @param WipContextInterface $context
   *   The context.
   * @param WipLogInterface $logger
   *   The logger.
   *
   * @return int
   *   The number of signals processed.
   */
  public function processSignal(WipSignalInterface $signal, WipContextInterface $context, WipLogInterface $logger);

}
