<?php

namespace Acquia\Wip;

/**
 * Indicates the completion status of an iterator.
 */
class IteratorStatus {

  const OK = 0;
  const TERMINATED = 1;
  const WARNING = 2;
  const ERROR_SYSTEM = 3;
  const ERROR_USER = 4;

  /**
   * The current status.
   *
   * @var int
   */
  private $value = IteratorStatus::OK;

  /**
   * Indicates the valid status values.
   *
   * @var array
   */
  private static $validValues = array(
    IteratorStatus::OK,
    IteratorStatus::TERMINATED,
    IteratorStatus::WARNING,
    IteratorStatus::ERROR_SYSTEM,
    IteratorStatus::ERROR_USER,
  );

  /**
   * The human-readable labels for each iterator status value.
   *
   * @var string[]
   */
  private static $labels = array(
    IteratorStatus::OK => 'OK',
    IteratorStatus::TERMINATED => 'Terminated',
    IteratorStatus::WARNING => 'Warning',
    IteratorStatus::ERROR_SYSTEM => 'System Error',
    IteratorStatus::ERROR_USER => 'User Error',
  );

  /**
   * Creates a new IteratorStatus instance set to the specified status.
   *
   * @param int $value
   *   The status.  Must be one of OK, TERMINATED, WARNING, ERROR_USER, or ERROR_SYSTEM.
   */
  public function __construct($value = IteratorStatus::OK) {
    if (!self::isValid($value)) {
      throw new \InvalidArgumentException(
        sprintf('Value "%s" does not represent a valid IteratorStatus value.', $value)
      );
    }
    $this->value = $value;
  }

  /**
   * Returns the IteratorStatus value.
   *
   * @return int
   *   The iterator status value.
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Indicates whether the specified status value is valid.
   *
   * @param int $status
   *   The status to verify.
   *
   * @return bool
   *   TRUE if the specified status is a valid IteratorStatus value;
   *   FALSE otherwise.
   */
  public static function isValid($status) {
    return in_array($status, self::$validValues);
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
   *   The iterator status value.
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
   * Converts the specified iterator status into a TaskExitStatus value.
   *
   * @param int $status
   *   The IteratorStatus code.
   *
   * @return int
   *   The TaskExitStatus value.
   */
  public static function toTaskExitStatus($status) {
    switch ($status) {
      case IteratorStatus::OK:
        $result = TaskExitStatus::COMPLETED;
        break;

      case IteratorStatus::TERMINATED:
        $result = TaskExitStatus::TERMINATED;
        break;

      case IteratorStatus::WARNING:
        $result = TaskExitStatus::WARNING;
        break;

      case IteratorStatus::ERROR_SYSTEM:
        $result = TaskExitStatus::ERROR_SYSTEM;
        break;

      case IteratorStatus::ERROR_USER:
        $result = TaskExitStatus::ERROR_USER;
        break;

      default:
        $result = TaskExitStatus::WARNING;
    }
    return $result;
  }

}
