<?php

namespace Acquia\Wip\Exception;

/**
 * Defines an exception type for when a Wip task has no Wip object.
 *
 * Adds getTaskId and setTaskId methods to standard WIP exceptions. These are
 * used to determine the WIP ID of the task that failed to construct as this
 * exception was thrown.
 */
class NoObjectException extends WipException {

  /**
   * Stores the Task ID that was being constructed as this exception was thrown.
   *
   * @var int
   */
  private $taskId;

  /**
   * Gets the Task ID that was being constructed as this exception was thrown.
   *
   * @return int
   *   The task ID of the object whose WIP iterator failed to construct.
   */
  public function getTaskId() {
    return $this->taskId;
  }

  /**
   * Sets the Task ID that was being constructed as this exception was thrown.
   *
   * @param int $task_id
   *   The task ID of the object whose WIP iterator failed to construct.
   */
  public function setTaskId($task_id) {
    $this->taskId = $task_id;
  }

}
