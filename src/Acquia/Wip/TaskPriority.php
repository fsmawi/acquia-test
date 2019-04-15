<?php

namespace Acquia\Wip;

/**
 * The TaskPriority identifies the current status of the associated task.
 */
class TaskPriority {
  // Task priorities.
  const CRITICAL = 0;
  const HIGH     = 1;
  const MEDIUM   = 2;
  const LOW      = 3;

  // Task priority labels.
  const CRITICAL_LABEL = 'Critical';
  const HIGH_LABEL = 'High';
  const MEDIUM_LABEL = 'Medium';
  const LOW_LABEL = 'Low';

  /**
   * The current task priority.
   *
   * @var int
   */
  private $value = self::MEDIUM;

  /**
   * The set of valid values a TaskPriority instance can hold.
   *
   * @var int[]
   */
  private static $validValues = array(
    self::CRITICAL,
    self::HIGH,
    self::MEDIUM,
    self::LOW,
  );

  /**
   * The set of user readable TaskPriority values.
   *
   * @var string[]
   */
  private static $labels = array(
    self::CRITICAL_LABEL,
    self::HIGH_LABEL,
    self::MEDIUM_LABEL,
    self::LOW_LABEL,
  );

  /**
   * Creates a new instance of TaskPriority with the specified value.
   *
   * @param int $value
   *   The TaskPriority value.
   */
  public function __construct($value = self::MEDIUM) {
    if (!self::isValid($value)) {
      throw new \InvalidArgumentException(sprintf('Value "%s" does not represent a valid TaskPriority value.', $value));
    }
    $this->value = $value;
  }

  /**
   * Returns the value of this TaskPriority instance.
   *
   * @return int
   *   The current value of this TaskPriority.
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Returns a list of valid task priority values.
   *
   * @return int[]
   *   An array of all possible task priority values.
   */
  public static function getValues() {
    return self::$validValues;
  }

  /**
   * Indicates whether the specified priority value is valid.
   *
   * @param int $value
   *   The priority to verify.
   *
   * @return bool
   *   TRUE if the specified status is a valid TaskPriority value; FALSE
   *   otherwise.
   */
  public static function isValid($value) {
    return is_int($value) && in_array($value, self::$validValues);
  }

  /**
   * Determines whether the specified label is valid.
   *
   * The validation of the label is case-insensitive.
   *
   * @param string $label
   *   The task priority label to test.
   *
   * @return bool
   *   TRUE if the task priority label is valid; FALSE otherwise.
   *
   * @throws \InvalidArgumentException
   *   If the label argument is not a non-empty string.
   */
  public static function isValidLabel($label) {
    if (!is_string($label) || trim($label) == FALSE) {
      throw new \InvalidArgumentException(sprintf('The "label" parameter must be a non-empty string. [%s]', $label));
    }
    return in_array(self::normalizePriorityString($label), self::$labels);
  }

  /**
   * Converts the task priority label to an integer.
   *
   * @param string $priority
   *   The string representation of a task priority value.
   *
   * @return int
   *   If the given priority is a valid string, then a valid priority value, else NULL.
   */
  public static function toInt($priority) {
    if (!self::isValidLabel($priority)) {
      throw new \InvalidArgumentException(sprintf('Unknown task priority label "%s"', $priority));
    }
    return array_search(self::normalizePriorityString($priority), self::$labels);
  }

  /**
   * Converts a TaskPriority to a string representation.
   *
   * @param int $priority
   *   A task priority value.
   *
   * @return string
   *   The string representation of the given priority value.
   *
   * @throws \InvalidArgumentException
   *   If the $priority parameter is not a valid value.
   */
  public static function toString($priority) {
    if (self::isValid($priority)) {
      $result = self::$labels[$priority];
    } else {
      throw new \InvalidArgumentException(sprintf('Unknown task priority "%s"', $priority));
    }

    return $result;
  }

  /**
   * Normalizes the specified priority string for comparison.
   *
   * @param string $priority
   *   The priority string.
   *
   * @return string
   *   The normalized priority string that can be used to compare with the
   *   labels.
   */
  private static function normalizePriorityString($priority) {
    return ucfirst(strtolower(trim($priority)));
  }

}
