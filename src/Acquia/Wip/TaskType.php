<?php

namespace Acquia\Wip;

/**
 * Enumerates and validates all of the task types used to launch Wip tasks.
 */
class TaskType {

  /**
   * Buildsteps task type.
   */
  const BUILDSTEPS = 'buildsteps';

  /**
   * Text labels for each task type.
   *
   * @var string[]
   */
  private static $labels = array(
    self::BUILDSTEPS => 'Buildsteps',
  );

  /**
   * Identifies valid task type values.
   *
   * @var string[]
   */
  private static $validValues = array(
    self::BUILDSTEPS,
  );

  /**
   * Provides a text label for the specified task type.
   *
   * @param string $type
   *   The task type to provide a label for.
   *
   * @return string
   *   The label.
   *
   * @throws \InvalidArgumentException
   *   If the specified task type is invalid.
   */
  public static function toString($type) {
    if (!self::isValid($type)) {
      throw new \InvalidArgumentException(sprintf('Unknown task type "%s".', $type));
    }
    return self::$labels[strtolower($type)];
  }

  /**
   * Determines whether the specified task type is valid.
   *
   * @param string $type
   *   The task type to test.
   *
   * @return bool
   *   TRUE if the task type is valid; FALSE otherwise.
   *
   * @throws \InvalidArgumentException
   *   If the task type argument is not a string.
   */
  public static function isValid($type) {
    if (empty($type) || !is_string($type)) {
      throw new \InvalidArgumentException('The "type" argument must be a string.');
    }
    return in_array(strtolower($type), self::$validValues);
  }

  /**
   * Determines whether the specified task type label is valid.
   *
   * The validation of the label is case-insensitive.
   *
   * @param string $label
   *   The task label to test.
   *
   * @return bool
   *   TRUE if the task type label is valid; FALSE otherwise.
   *
   * @throws \InvalidArgumentException
   *   If the label argument is not a string.
   */
  public static function isValidLabel($label) {
    if (empty($label) || !is_string($label)) {
      throw new \InvalidArgumentException('The "label" parameter must be a string.');
    }
    return in_array($label, self::$labels);
  }

  /**
   * Returns an array containing all possible task types.
   *
   * @return array
   *   An array keyed by task types with their human-readable names as values.
   */
  public static function getAll() {
    return self::$labels;
  }

}
