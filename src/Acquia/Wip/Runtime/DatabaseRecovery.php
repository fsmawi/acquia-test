<?php

namespace Acquia\Wip\Runtime;

/**
 * Stores data about task and thread inconsistencies in the database.
 */
class DatabaseRecovery {

  /**
   * The description of the database inconsistency.
   *
   * @var string
   */
  protected $reason = '';

  /**
   * Whether to delete the thread.
   *
   * @var bool
   */
  protected $deleteThread = FALSE;

  /**
   * Whether to kill the thread's process.
   *
   * @var bool
   */
  protected $killProcess = FALSE;

  /**
   * Whether to set the task's run status to waiting.
   *
   * @var bool
   */
  protected $taskToWaiting = FALSE;

  /**
   * Whether to fail out the task.
   *
   * @var bool
   */
  protected $failOutTask = FALSE;

  /**
   * Initiates a DatabaseRecovery object.
   *
   * @param string $reason
   *   The description of the inconsistency.
   * @param bool $delete
   *   Whether to delete the thread.
   * @param bool $kill
   *   Whether to kill the thread's process.
   * @param bool $wait
   *   Whether to set the task's run status to waiting.
   * @param bool $fail
   *   Whether to fail out the task.
   */
  public function __construct(
    $reason,
    $delete = FALSE,
    $kill = FALSE,
    $wait = FALSE,
    $fail = FALSE) {
    $this->setReason($reason);
    $this->setDeleteThread($delete);
    $this->setKillProcess($kill);
    $this->setTaskToWaiting($wait);
    $this->setFailOutTask($fail);
  }

  /**
   * Sets the description of the inconsistency.
   *
   * @param string $reason
   *   The description of the inconsistency.
   */
  public function setReason($reason) {
    if (!is_string($reason)) {
      throw new \InvalidArgumentException('The "reason" parameter must be a string.');
    }
    $this->reason = $reason;
  }

  /**
   * Retrieves the description of the inconsistency.
   *
   * @return string
   *   The description of the inconsistency.
   */
  public function getReason() {
    return $this->reason;
  }

  /**
   * Sets whether to delete the thread.
   *
   * @param bool $delete
   *   Whether to delete the thread.
   */
  public function setDeleteThread($delete) {
    if (!is_bool($delete)) {
      throw new \InvalidArgumentException('The "delete" parameter must be a boolean value.');
    }
    $this->deleteThread = $delete;
  }

  /**
   * Retrieves whether to delete the thread.
   *
   * @return bool
   *   Whether to delete the thread.
   */
  public function getDeleteThread() {
    return $this->deleteThread;
  }

  /**
   * Sets whether to kill the thread's process.
   *
   * @param bool $kill
   *   Whether to kill the thread's process.
   */
  public function setKillProcess($kill) {
    if (!is_bool($kill)) {
      throw new \InvalidArgumentException('The "kill" parameter must be a boolean value.');
    }
    $this->killProcess = $kill;
  }

  /**
   * Retrieves whether to kill the thread's process.
   *
   * @return bool
   *   Whether to kill the thread's process.
   */
  public function getKillProcess() {
    return $this->killProcess;
  }

  /**
   * Sets whether to set the task's run status to waiting.
   *
   * @param bool $wait
   *   Whether to set the task's run status to waiting.
   */
  public function setTaskToWaiting($wait) {
    if (!is_bool($wait)) {
      throw new \InvalidArgumentException('The "wait" parameter must be a boolean value.');
    }
    $this->taskToWaiting = $wait;
  }

  /**
   * Retrieves whether to set the task's run status to waiting.
   *
   * @return bool
   *   Whether to set the task's run status to waiting.
   */
  public function getTaskToWaiting() {
    return $this->taskToWaiting;
  }

  /**
   * Sets whether to fail out the task.
   *
   * @param bool $fail
   *   Whether to fail out the task.
   */
  public function setFailOutTask($fail) {
    if (!is_bool($fail)) {
      throw new \InvalidArgumentException('The "fail" parameter must be a boolean value.');
    }
    $this->failOutTask = $fail;
  }

  /**
   * Retrieves whether to fail out the task.
   *
   * @return bool
   *   Whether to fail out the task.
   */
  public function getFailOutTask() {
    return $this->failOutTask;
  }

}
