<?php

namespace Acquia\Wip;

/**
 * The TaskExitStatus identifies the exit status of the associated task.
 */
class TaskExitStatus {

  /**
   * Constant for tasks that have not yet finished.
   */
  const NOT_FINISHED = 0;

  /**
   * Constant for tasks that completed with warnings.
   */
  const WARNING = 1;

  /**
   * Constant for tasks that failed due to a system error.
   */
  const ERROR_SYSTEM = 2;

  /**
   * Constant for tasks that were terminated.
   */
  const TERMINATED = 3;

  /**
   * Constant for tasks that successfully ran without a warning.
   */
  const COMPLETED = 4;

  /**
   * Constant for tasks that failed due to a user error.
   */
  const ERROR_USER = 5;

  /**
   * The task exit status.
   *
   * @var int
   */
  private $value = self::NOT_FINISHED;

  /**
   * The set of valid values a TaskExitStatus instance can hold.
   *
   * @var int[]
   */
  private static $validValues = array(
    self::NOT_FINISHED,
    self::WARNING,
    self::ERROR_USER,
    self::ERROR_SYSTEM,
    self::TERMINATED,
    self::COMPLETED,
  );

  /**
   * The human-readable labels for each task exit status value.
   *
   * @var string[]
   */
  private static $labels = array(
    self::NOT_FINISHED => 'Not finished',
    self::WARNING => 'Warning',
    self::ERROR_USER => 'User error',
    self::ERROR_SYSTEM => 'System error',
    self::TERMINATED => 'Terminated',
    self::COMPLETED => 'Completed',
  );

  /**
   * Creates a new instance of TaskExitStatus with the specified value.
   *
   * @param int $value
   *   The TaskExitStatus value.
   */
  public function __construct($value = self::NOT_FINISHED) {
    if (!self::isValid($value)) {
      throw new \InvalidArgumentException(
        sprintf('Value "%s" does not represent a valid TaskExitStatus value.', $value)
      );
    }
    $this->value = $value;
  }

  /**
   * Returns the value of this TaskExitStatus instance.
   *
   * @return int
   *   The current value of this TaskExitStatus.
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Returns a list of valid TaskExitStatus values.
   *
   * @return int[]
   *   An array of all possible TaskExitStatus values.
   */
  public static function getValues() {
    return self::$validValues;
  }

  /**
   * Indicates whether the specified status value is valid.
   *
   * @param int $value
   *   The status to verify.
   *
   * @return bool
   *   TRUE if the specified status is a valid TaskExitStatus value; FALSE
   *   otherwise.
   */
  public static function isValid($value) {
    return is_int($value) && in_array($value, self::$validValues);
  }

  /**
   * Converts a valid IteratorStatus to a valid TaskExitStatus value.
   *
   * @param IteratorStatus $status
   *   A valid IteratorStatus.
   *
   * @return int
   *   The TaskExitStatus value corresponding to the IteratorStatus input.
   */
  public static function fromIteratorStatus(IteratorStatus $status) {
    $value = $status->getValue();
    if (!IteratorStatus::isValid($value)) {
      throw new \InvalidArgumentException(
        sprintf('Value "%s" does not represent a valid IteratorStatus value.', $value)
      );
    }
    $result = NULL;
    switch ($value) {
      case IteratorStatus::ERROR_USER:
        $result = self::ERROR_USER;
        break;

      case IteratorStatus::ERROR_SYSTEM:
        $result = self::ERROR_SYSTEM;
        break;

      case IteratorStatus::TERMINATED:
        $result = self::TERMINATED;
        break;

      case IteratorStatus::WARNING:
        $result = self::WARNING;
        break;

      case IteratorStatus::OK:
        $result = self::COMPLETED;
        break;
    }
    return $result;
  }

  /**
   * Indicates whether the specified status value represents an error.
   *
   * @param int $value
   *   The value to verify.
   *
   * @return bool
   *   Whether the exit code indicates an error.
   */
  public static function isError($value) {
    return in_array($value, array(self::ERROR_USER, self::ERROR_SYSTEM));
  }

  /**
   * Indicates whether the specified status value represents a termination.
   *
   * @param int $value
   *   The value to verify.
   *
   * @return bool
   *   Whether the exit code indicates a termination.
   */
  public static function isTerminated($value) {
    return $value === self::TERMINATED;
  }

  /**
   * Returns the human-readable label for a specified status value.
   *
   * @param int $status
   *   The task exit status value.
   *
   * @return string
   *   The human-readable label for the specified status value.
   */
  public static function getLabel($status) {
    if (!self::isValid($status)) {
      throw new \InvalidArgumentException(
        sprintf('Invalid exit status value specified: %s', var_export($status, TRUE))
      );
    }
    return self::$labels[$status];
  }

}
