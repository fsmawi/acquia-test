<?php

namespace Acquia\Wip;

/**
 * The TaskStatus identifies the current status of the associated task.
 */
class TaskStatus {

  /**
   * Constant for tasks that are currently being added and not ready to start.
   */
  const NOT_READY = 99;

  /**
   * Constant for tasks that have not been started yet.
   */
  const NOT_STARTED = 0;

  /**
   * Constant for tasks that have started but are not currently processing.
   */
  const WAITING = 1;

  /**
   * Constant for tasks that have started and are currently processing.
   */
  const PROCESSING = 2;

  /**
   * Constant for tasks that have finished processing.
   */
  const COMPLETE = 3;

  /**
   * Constant for tasks that have completed processing and have been restarted.
   */
  const RESTARTED = 4;

  /**
   * The current task status.
   *
   * @var int
   */
  private $value = self::NOT_STARTED;

  /**
   * The set of valid values a TaskStatus instance can hold.
   *
   * @var int[]
   */
  private static $validValues = array(
    self::NOT_READY,
    self::NOT_STARTED,
    self::WAITING,
    self::PROCESSING,
    self::COMPLETE,
    self::RESTARTED,
  );

  /**
   * The human-readable labels for each task status value.
   *
   * @var string[]
   */
  private static $labels = array(
    self::NOT_READY => 'Not ready',
    self::NOT_STARTED => 'Not started',
    self::WAITING => 'Waiting',
    self::PROCESSING => 'Processing',
    self::COMPLETE => 'Completed',
    self::RESTARTED => 'Restarted',
  );

  /**
   * Creates a new instance of TaskStatus with the specified value.
   *
   * @param int $value
   *   The TaskStatus value.
   */
  public function __construct($value = self::NOT_STARTED) {
    if (!self::isValid($value)) {
      throw new \InvalidArgumentException(sprintf('Value "%s" does not represent a valid TaskStatus value.', $value));
    }
    $this->value = $value;
  }

  /**
   * Returns the value of this TaskStatus instance.
   *
   * @return int
   *   The current value of this TaskStatus.
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Returns a list of valid task status values.
   *
   * @return int[]
   *   An array of all possible task status values.
   */
  public static function getValues() {
    return self::$validValues;
  }

  /**
   * Returns the human-readable label for a specified status value.
   *
   * @param int $status
   *   The task status value.
   *
   * @return string
   *   The human-readable label for the specified status value.
   */
  public static function getLabel($status) {
    if (!self::isValid($status)) {
      throw new \InvalidArgumentException(sprintf('Invalid status value specified: %s', var_export($status, TRUE)));
    }
    return self::$labels[$status];
  }

  /**
   * Indicates whether the specified status value is valid.
   *
   * @param int $value
   *   The status to verify.
   *
   * @return bool
   *   TRUE if the specified status is a valid TaskStatus value; FALSE
   *   otherwise.
   */
  public static function isValid($value) {
    return is_int($value) && in_array($value, self::$validValues);
  }

}
