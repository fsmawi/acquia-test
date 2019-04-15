<?php

namespace Acquia\Wip\Storage;

use Acquia\Wip\Exception\NoTaskException;
use Acquia\Wip\Task;
use Acquia\Wip\TaskInterface;

/**
 * The WipPoolStoreInterface handles Wip task storage related tasks.
 *
 * The WipPoolStoreInterface provides access point to save (insert or update),
 * delete or get Wip task related data.
 */
interface WipPoolStoreInterface {

  /**
   * Saves the Wip task data.
   *
   * @param TaskInterface $task
   *   The Wip task object.
   */
  public function save(TaskInterface $task);

  /**
   * Gets the data for a Wip task object.
   *
   * @param int $id
   *   The Wip task's ID to be loaded.
   * @param string $uuid
   *   The UUID of the user associated with the task.
   *
   * @return Task
   *   The Wip task's data or FALSE if it's not found.
   */
  public function get($id, $uuid = NULL);

  /**
   * Gets the total number of pool entries matching a query.
   *
   * @param int $status
   *   Optional. The status of the tasks to fetch.
   * @param int $parent
   *   Optional. The parent WIP object of the tasks to fetch.
   * @param string $group_name
   *   Optional. A group name to filter tasks by.
   * @param bool $paused
   *   Optional. Filter by paused status.
   * @param int $priority
   *   Optional. Filter tasks by priority.
   * @param string $uuid
   *   Optional. Filter tasks by user UUID.
   * @param string $exit_status
   *   Optional. Filter tasks by exit status.
   * @param string $start_time
   *   Optional. Filter tasks by start time.
   * @param string $end_time
   *   Optional. Filter tasks by end time.
   * @param bool $is_terminating
   *   Optional. Filter tasks by whether the task is marked as terminating.
   * @param string $client_job_id
   *   Optional. Filter tasks by a corresponding client job ID.
   *
   * @return int
   *   The total number of pool entries matching the query.
   *
   * @throws \InvalidArgumentException
   *   When arguments fail validation.
   */
  public function count(
    $status = NULL,
    $parent = NULL,
    $group_name = NULL,
    $paused = NULL,
    $priority = NULL,
    $uuid = NULL,
    $exit_status = NULL,
    $start_time = NULL,
    $end_time = NULL,
    $is_terminating = NULL,
    $client_job_id = NULL
  );

  /**
   * Fetches tasks from the pool.
   *
   * @param int $offset
   *   Optional. The offset into the result set.
   * @param int $count
   *   Optional. The maximum number of results to return. If not provided, up
   *   to 20 messages will be returned.
   * @param string $sort_order
   *   Optional. The order of the returned results. Defaults to ascending order.
   * @param int $status
   *   Optional. The status of the tasks to fetch.
   * @param int $parent
   *   Optional. The parent WIP object of the tasks to fetch.
   * @param string $group_name
   *   Optional. A group name to filter tasks by.
   * @param bool $paused
   *   Optional. Filter by paused status.
   * @param int $priority
   *   Optional. Filter tasks by priority.
   * @param string $uuid
   *   Optional. Filter tasks by user UUID.
   * @param int $created_before
   *   Optional. Filter tasks by created date.
   * @param bool $is_terminating
   *   Optional. Filter tasks by whether the task is marked as terminating.
   * @param string $client_job_id
   *   Optional. Filter tasks by a corresponding client job ID.
   *
   * @return TaskInterface[]
   *   The tasks.
   *
   * @throws \InvalidArgumentException
   *   When arguments fail validation.
   */
  public function load(
    $offset = 0,
    $count = 20,
    $sort_order = 'ASC',
    $status = NULL,
    $parent = NULL,
    $group_name = NULL,
    $paused = NULL,
    $priority = NULL,
    $uuid = NULL,
    $created_before = NULL,
    $is_terminating = NULL,
    $client_job_id = NULL
  );

  /**
   * Retrieves a list of valid, completed Wip IDs from a given range.
   *
   * If no stop is given, the query extends to the end of all valid,
   * completed Wip IDs.
   *
   * @param int $start
   *   The inclusive start of the ID range.
   * @param int $stop
   *   Optional. The inclusive stop of the ID range.
   *
   * @return int[]
   *   The range of valid Wip IDs requested.
   */
  public function loadCompletedIdRange($start, $stop = NULL);

  /**
   * Gets the next Wip task object.
   *
   * @param int $count
   *   Optional. The number of tasks being requested. Usually this would be set
   *   to match the number of available execution threads.
   *
   * @return TaskInterface[]
   *   An array of task metadata in the order the tasks should be executed.
   */
  public function getNextTasks($count = 1);

  /**
   * Removes the stored data for a Wip task object.
   *
   * @param TaskInterface $task
   *   The Wip task that is being deleted.
   */
  public function remove(TaskInterface $task);

  /**
   * Prunes wip pool items for specific objects.
   *
   * @param int[] $object_ids
   *   List of object ids.
   */
  public function pruneObjects(array $object_ids);

  /**
   * Retrieve a list of wip ids in completed status.
   *
   * @param string $group_name
   *   The group name.
   * @param int $created_before
   *   Unix timestamp.
   * @param int $limit
   *   The maximum number of records to return.
   *
   * @return int[]
   *   List of ids retrieved.
   */
  public function getCompletedIds($group_name, $created_before, $limit = 100);

  /**
   * Gets the children task's ids.
   *
   * @param int $id
   *   The parent's Wip task's ID whose children's task id will be returned.
   *
   * @return array
   *   A numeric array of task ids.
   *
   * @throws \InvalidArgumentException
   *   If the parent task id is not a positive integer.
   */
  public function getChildrenTaskIds($id);

  /**
   * Hook function that triggers when a task starts actual processing.
   *
   * @param TaskInterface $task
   *   A Task object that is beginning processing.
   *
   * @throws NoTaskException
   *   If the task ID has not been set.
   */
  public function startProgress(TaskInterface $task);

  /**
   * Hook function that triggers when a task finishes a processing run.
   *
   * @param TaskInterface $task
   *   A Task object that has just completed processing.
   */
  public function stopProgress(TaskInterface $task);

  /**
   * Queries for processing tasks.
   *
   * @return TaskInterface[]
   *   An array of processing tasks.
   */
  public function findProcessingTasks();

  /**
   * Deletes all group concurrency items for which the task has completed.
   */
  public function cleanupConcurrency();

  /**
   * Pauses the specified task.
   *
   * @param int $task_id
   *   The Wip task ID associated with the task that will be paused.
   *
   * @return bool
   *   TRUE if the Wip task is paused; FALSE otherwise.
   *
   * @throws \DomainException
   *   If the Wip task cannot be found.
   */
  public function pauseTask($task_id);

  /**
   * Resumes the specified task.
   *
   * @param int $task_id
   *   The Wip task ID associated with the task that will be resumed.
   *
   * @return bool
   *   TRUE if the Wip task is not paused; FALSE otherwise.
   *
   * @throws \DomainException
   *   If the Wip task cannot be found.
   */
  public function resumeTask($task_id);

}
