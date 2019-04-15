<?php

namespace Acquia\Wip\State;

/**
 * Defines maintenance mode.
 */
class Maintenance {
  /**
   * The variable name used for storing maintenance mode.
   */
  const STATE_NAME = 'wip.application.maintenance';

  /**
   * The value indicating normal operation.
   */
  const OFF = 'off';

  /**
   * The value indicating full maintenance mode.
   */
  const FULL = 'full';

  /**
   * An array of valid modes for maintenance mode.
   *
   * @var string[]
   */
  private static $validModes = array(
    self::OFF,
    self::FULL,
  );

  /**
   * The default value for maintenance mode.
   *
   * @var string
   */
  public static $defaultValue = self::OFF;

  /**
   * Indicates whether the given value is valid for maintenance mode.
   *
   * @param string $value
   *   The value to validate.
   *
   * @return bool
   *   If the given value is valid for maintenance mode.
   */
  public static function isValidValue($value) {
    $valid = FALSE;
    if (is_string($value)) {
      $valid = in_array($value, self::$validModes, TRUE);
    }
    return $valid;
  }

}
