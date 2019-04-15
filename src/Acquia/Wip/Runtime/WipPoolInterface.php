<?php

namespace Acquia\Wip\Runtime;

/**
 * There are words here as a placeholder.
 */
use Acquia\Wip\Exception\NoTaskException;
use Acquia\Wip\Exception\RowLockException;
use Acquia\Wip\TaskInterface;
use Acquia\Wip\TaskPriority;
use Acquia\Wip\WipInterface;

/**
 * The WipPoolInterface provides a basic API for managing tasks.
 *
 * Interface for classes that manage queued tasks.
 */
interface WipPoolInterface {

  /**
   * Retrieves the next task in the WIP pool.
   *
   * @param int $count
   *   Optional. The number of tasks being requested. Usually this would be set
   *   to match the number of available execution threads.
   *
   * @return TaskInterface[]
   *   The next task that should be processed.
   *
   * @throws NoTaskException
   *   If there is no task available for processing.
   */
  public function getNextTasks($count = 1);

  /**
   * Returns the Task associated with the specified task ID.
   *
   * @param int $task_id
   *   The task ID.
   *
   * @return TaskInterface
   *   The Task.
   *
   * @throws NoTaskException
   *   If there is no task associated with the specified task ID.
   */
  public function getTask($task_id);

  /**
   * Adds a new task to the WIP pool.
   *
   * @param WipInterface $wip
   *   The Wip task to add.
   * @param TaskPriority $priority
   *   Optional. Specifies the priority of the Wip task being added. If not
   *   provided, the Wip task will be added with priority TaskPriority::MEDIUM.
   * @param string $group_name
   *   Optional. The name of the group the new Wip task will be associated with.
   *   The group name is important as it affects task execution concurrency. If
   *   not specified, the group name will be retrieved from the Wip object via
   *   its getGroup() method.
   * @param int $parent_id
   *   Optional. The Wip ID identifying the parent object. If not provided, the
   *   newly added Wip object will not have a parent assigned.
   * @param string $client_job_id
   *   Optional. The id of the client job who started the task.
   *
   * @return TaskInterface
   *   The task that was added to the WIP pool.
   */
  public function addTask(
    WipInterface $wip,
    TaskPriority $priority = NULL,
    $group_name = '',
    $parent_id = NULL,
    $client_job_id = NULL
  );

  /**
   * Saves a task to the WIP pool.
   *
   * @param TaskInterface $task
   *   The task to be saved.
   *
   * @return TaskInterface
   *   The task that was added to the WIP pool.
   */
  public function saveTask(TaskInterface $task);

  /**
   * Restarts a WIP task.
   *
   * The wip_pool row update lock must be acquired before calling this method.
   *
   * @param TaskInterface $task
   *   The WIP task to restart.
   *
   * @throws RowLockException
   *   If the wip_pool update lock is not held by this process.
   */
  public function restartTask(TaskInterface $task);

  /**
   * Hook function called whenever a task begins actual processing.
   *
   * @param TaskInterface $task
   *   A Task object that is beginning processing.
   */
  public function startProgress(TaskInterface $task);

  /**
   * Hook function called whenever a task finishes a processing run.
   *
   * @param TaskInterface $task
   *   A Task object that has just completed processing.
   */
  public function stopProgress(TaskInterface $task);

  /**
   * Cleans up any outdated group concurrency entries.
   */
  public function cleanupConcurrency();

  /**
   * Gets the count of tasks currently being executed.
   *
   * The paused tasks are omitted from the count, because when tasks are paused
   * other tasks should be allowed to execute instead.
   *
   * @return int
   *   The number of tasks currently being executed.
   */
  public function getUnpausedTasksBeingExecutedCount();

}
