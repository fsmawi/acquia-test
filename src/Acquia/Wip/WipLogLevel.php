<?php

namespace Acquia\Wip;

/**
 * This class is similar to an enumeration, but has means of validation.
 */
class WipLogLevel {
  /**
   * Fatal log level.
   */
  const FATAL = 1;

  /**
   * Error log level.
   */
  const ERROR = 2;

  /**
   * Alert log level.
   */
  const ALERT = 3;

  /**
   * Warning log level.
   */
  const WARN = 4;

  /**
   * Info log level.
   */
  const INFO = 5;

  /**
   * Debug log level.
   */
  const DEBUG = 6;

  /**
   * Trace log level.
   */
  const TRACE = 7;

  /**
   * Text labels for each level.
   *
   * @var array
   */
  private static $labels = array(
    'Fatal',
    'Error',
    'Alert',
    'Warning',
    'Info',
    'Debug',
    'Trace',
  );

  /**
   * Identifies valid level values.
   *
   * @var int[]
   *   Valid level values.
   */
  private static $validValues = array(
    self::FATAL,
    self::ERROR,
    self::ALERT,
    self::WARN,
    self::INFO,
    self::DEBUG,
    self::TRACE,
  );

  /**
   * Provides a text label for the specified level.
   *
   * @param int $level
   *   The log level to provide a label for.
   *
   * @return string
   *   The label.
   *
   * @throws \InvalidArgumentException
   *   If the specified level is not valid.
   */
  public static function toString($level) {
    if (self::isValid($level)) {
      $result = self::$labels[$level - self::FATAL];
    } else {
      throw new \InvalidArgumentException(sprintf('Unknown log level "%s"', $level));
    }
    return $result;
  }

  /**
   * Determines whether the specified level is valid.
   *
   * @param int $level
   *   The log level to test.
   *
   * @return bool
   *   TRUE if the log level is valid; FALSE otherwise.
   *
   * @throws \InvalidArgumentException
   *   If the level argument is not an integer.
   */
  public static function isValid($level) {
    if (!is_int($level)) {
      throw new \InvalidArgumentException('The "level" argument must be an integer.');
    }
    return in_array($level, self::$validValues);
  }

  /**
   * Determines whether the specified label is valid.
   *
   * The validation of the label is case-insensitive.
   *
   * @param string $label
   *   The log level label to test.
   *
   * @return bool
   *   TRUE if the log level label is valid; FALSE otherwise.
   *
   * @throws \InvalidArgumentException
   *   If the label argument is not an integer.
   */
  public static function isValidLabel($label) {
    if (!is_string($label)) {
      throw new \InvalidArgumentException('The "label" argument must be a string.');
    }
    return in_array(ucfirst(strtolower(trim($label))), self::getAll());
  }

  /**
   * Returns the log level associated with the given label.
   *
   * @param string $label
   *   The log level label to convert.
   *
   * @return string
   *   The label.
   *
   * @throws \InvalidArgumentException
   *   If the specified level is not valid.
   */
  public static function toInt($label) {
    if (!self::isValidLabel($label)) {
      throw new \InvalidArgumentException(sprintf('Unknown log level label "%s"', $label));
    }
    $levels = array_flip(self::getAll());
    return $levels[ucfirst(strtolower(trim($label)))];
  }

  /**
   * Returns an array containing all possible log levels.
   *
   * @return array
   *   An array keyed by log levels with their human-readable names as values.
   */
  public static function getAll() {
    return array(
      self::FATAL => 'Fatal',
      self::ERROR => 'Error',
      self::ALERT => 'Alert',
      self::WARN  => 'Warning',
      self::INFO  => 'Info',
      self::DEBUG => 'Debug',
      self::TRACE => 'Trace',
    );
  }

}
