<?php

namespace Acquia\Wip\State;

/**
 * Defines the monitor daemon pause state.
 */
class MonitorDaemonPause {

  /**
   * The variable name used for storing monitor daemon pause state.
   */
  const STATE_NAME = 'wip.pool.pause.monitordaemon';

  /**
   * The monitor daemon pause value indicating no pause.
   */
  const OFF = 'off';

  /**
   * The monitor daemon pause value indicating paused.
   */
  const ON = 'on';

  /**
   * An array of valid modes for global pause.
   *
   * @var string[]
   */
  private static $validModes = array(
    self::OFF,
    self::ON,
  );

  /**
   * The default value for monitor daemon pause.
   *
   * @var string
   */
  public static $defaultValue = self::OFF;

  /**
   * Returns whether the given value is valid for monitor daemon pause.
   *
   * @param string $value
   *   The value to validate.
   *
   * @return bool
   *   If the given value is valid for monitor daemon pause.
   */
  public static function isValidValue($value) {
    $valid = FALSE;
    if (is_string($value)) {
      $valid = in_array($value, self::$validModes, TRUE);
    }
    return $valid;
  }

}
