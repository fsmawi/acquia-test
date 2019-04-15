<?php

namespace Acquia\Wip;

/**
 * The WipTaskResult indicates the result of a completed Wip object.
 */
class WipTaskResult extends WipResult implements WipTaskResultInterface {

  /**
   * Initializes a new instance.
   */
  public function __construct() {
    parent::__construct();
    $this->setSuccessExitCodes(array(TaskExitStatus::COMPLETED, TaskExitStatus::WARNING));
  }

  /**
   * {@inheritdoc}
   */
  public function setPid($pid) {
    if (empty($pid) || !is_int($pid) || $pid < 0) {
      throw new \InvalidArgumentException('The pid parameter must be an integer.');
    }
    parent::setPid($pid);
  }

  /**
   * Creates a WipTaskResult instance from the specified Task.
   *
   * @param TaskInterface $task
   *   The Task instance.
   *
   * @return WipTaskResultInterface
   *   The WipTaskResult instance.
   */
  public static function fromTask(TaskInterface $task) {
    $result = new static();
    $wip_id = $task->getId();
    if (!empty($wip_id)) {
      $result->setPid($wip_id);
    }
    $exit_code = $task->getExitStatus();
    if (!empty($exit_code)) {
      $result->setExitCode($exit_code);
    }
    $exit_message = $task->getExitMessage();
    if (!empty($exit_message)) {
      $result->setExitMessage($exit_message);
    }
    $start_time = $task->getStartTimestamp();
    if (!empty($start_time)) {
      $result->setStartTime($start_time);
    }
    $end_time = $task->getCompletedTimestamp();
    if (!empty($end_time)) {
      $result->setEndTime($end_time);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public static function fromObject($object, WipResultInterface $wip_result = NULL) {
    $result = $wip_result;
    if (empty($result)) {
      $result = new static();
    } elseif (!$result instanceof WipTaskResultInterface) {
      throw new \InvalidArgumentException('The wip_result parameter must be an instance of WipTaskResultInterface.');
    }
    $result_object = $object;
    if (!isset($result_object->exitCode) && isset($result_object->result) && isset($result_object->result->exitCode)) {
      $result_object = $object->result;
    }
    parent::fromObject($result_object, $result);
    return $result;
  }

  /**
   * Sets the exit code associated with the Wip task.
   *
   * @param int $exit_code
   *   The exit code.
   */
  public function setExitCode($exit_code) {
    if (!TaskExitStatus::isValid($exit_code)) {
      throw new \InvalidArgumentException('The exit_code parameter must be a valid TaskExitStatus value.');
    }
    parent::setExitCode($exit_code);
  }

  /**
   * {@inheritdoc}
   */
  public function getUniqueId() {
    return self::createUniqueId($this->getPid());
  }

  /**
   * {@inheritdoc}
   */
  public static function createUniqueId($id) {
    return $id;
  }

}
