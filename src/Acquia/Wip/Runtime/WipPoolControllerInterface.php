<?php

namespace Acquia\Wip\Runtime;

use Acquia\Wip\Task;

/**
 * The interface for controlling the Wip task pool.
 */
interface WipPoolControllerInterface {

  /**
   * Pauses all work.
   *
   * This affects all work immediately, except for tasks that are currently
   * executing. For those tasks the pause will take effect when the task is
   * stored.
   *
   * @return Task[]
   *   The WipTask objects that are currently in progress.
   */
  public function hardPauseGlobal();

  /**
   * Soft pauses everything.
   *
   * As a result of this call, all work waiting to be executed will be
   * prevented from starting and all work currently in progress will be
   * allowed to complete.
   *
   * @return Task[]
   *   The WipTask objects that are currently in progress.
   */
  public function softPauseGlobal();

  /**
   * Pauses only the items in the specified groups.
   *
   * As a result of this call, all members of the specified groups
   * waiting to be executed will be prevented from starting and all
   * members of the specified groups currently in progress will be
   * prevented from continuing their work. Note that members of these
   * groups that are currently in process will continue until they come
   * to a save point.
   *
   * @param string[] $groups
   *   The group names identifying which objects to soft pause.
   *
   * @return Task[]
   *   The WipTask objects that are currently in progress in the specified groups.
   */
  public function hardPauseGroups($groups);

  /**
   * Soft pauses only the items in the specified groups.
   *
   * As a result of this call, all members of the specified groups
   * waiting to be executed will be prevented from starting and all
   * members of the specified groups currently in progress will be
   * allowed to complete.
   *
   * @param string[] $groups
   *   The group names identifying which objects to soft pause.
   *
   * @return Task[]
   *   The WipTask objects that are currently in progress in the specified groups.
   */
  public function softPauseGroups($groups);

  /**
   * Resumes all work.
   *
   * This call effectively undoes the pause and soft pause.
   *
   * @return bool
   *   TRUE if global pause has been lifted; FALSE otherwise.
   */
  public function resumeGlobal();

  /**
   * Resumes all members of the specified groups.
   *
   * Removes the pause on all members of the specified groups.
   *
   * @param string[] $groups
   *   The names of the groups to resume.
   *
   * @return bool
   *   TRUE if the pause on the specified groups has been lifted; FALSE otherwise.
   */
  public function resumeGroups($groups);

  /**
   * Indicates whether pause is set.
   *
   * If groups are specified, this method indicates whether any members of any
   * of the specified groups are paused.
   *
   * @return bool
   *   TRUE if the system is paused or if any member of any of the specified
   *   groups is paused; FALSE otherwise.
   */
  public function isHardPausedGlobal();

  /**
   * Indicates which groups are currently paused.
   *
   * @return string[]
   *   The group names that are currently paused.
   */
  public function getHardPausedGroups();

  /**
   * Indicates whether soft pause is set.
   *
   * @return bool
   *   TRUE if the system is soft paused.
   */
  public function isSoftPausedGlobal();

  /**
   * Gets the current value of the global pause variable.
   *
   * @return string
   *   One of GlobalPause::validModes indicating the current state.
   */
  public function getGlobalPause();

  /**
   * Indicates which groups are currently soft-paused.
   *
   * @return string[]
   *   The group names that are currently soft-paused.
   */
  public function getSoftPausedGroups();

  /**
   * Pauses the specified task.
   *
   * @param int $task_id
   *   The Wip task ID representing the task to be paused.
   *
   * @return bool
   *   TRUE if the specified task was paused; FALSE otherwise.
   *
   * @throws \DomainException
   *   If the Wip task could not be found.
   */
  public function pauseTask($task_id);

  /**
   * Resumes the specified task.
   *
   * @param int $task_id
   *   The Wip task ID representing the task to be resumed.
   *
   * @return bool
   *   TRUE if the specified task was unpaused; FALSE otherwise.
   *
   * @throws \DomainException
   *   If the Wip task could not be found.
   */
  public function resumeTask($task_id);

  /**
   * Gets information about all tasks that are currently in progress.
   *
   * Tasks in progress are those in either WAITING or PROCESSING state.
   *
   * @param string $groups
   *   Optional. If provided, only tasks that are a member of one of the
   *   specified groups that are in progress will be returned.
   *
   * @return Task[]
   *   The tasks that are in progress.
   */
  public function getTasksInProgress($groups = NULL);

  /**
   * Gets information about all tasks that are in the processing state.
   *
   * @param string $groups
   *   Optional. If provided, only tasks that are a member of one of the
   *   specified groups that are in the processing state will be returned.
   *
   * @return Task[]
   *   The tasks that are in the processing state.
   */
  public function getTasksInProcessing($groups = NULL);

}
