<?php

namespace Acquia\Wip\Signal;

/**
 * Used to signal that a task is to be terminated.
 */
class TaskTerminateSignal extends Signal {

  /**
   * TaskTerminateSignal constructor.
   *
   * @param int $task_id
   *   The task ID.
   */
  public function __construct($task_id) {
    $this->setObjectId($task_id);
  }

}
