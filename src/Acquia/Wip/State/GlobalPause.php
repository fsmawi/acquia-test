<?php

namespace Acquia\Wip\State;

/**
 * Defines the global pause state.
 */
class GlobalPause {

  /**
   * The variable name used for storing global pause state.
   */
  const STATE_NAME = 'wip.pool.pause.global';

  /**
   * The global pause value indicating no pause.
   */
  const OFF = 'off';

  /**
   * The global pause value indicating a full pause.
   */
  const HARD_PAUSE = 'hard_pause';

  /**
   * The global pause value indicating a soft pause.
   */
  const SOFT_PAUSE = 'soft_pause';

  /**
   * An array of valid modes for global pause.
   *
   * @var string[]
   */
  private static $validModes = array(
    self::OFF,
    self::HARD_PAUSE,
    self::SOFT_PAUSE,
  );

  /**
   * The default value for global pause.
   *
   * @var string
   */
  public static $defaultValue = self::OFF;

  /**
   * Returns whether the given value is valid for global pause.
   *
   * @param string $value
   *   The value to validate.
   *
   * @return bool
   *   If the given value is valid for global pause.
   */
  public static function isValidValue($value) {
    $valid = FALSE;
    if (is_string($value)) {
      $valid = in_array($value, self::$validModes, TRUE);
    }
    return $valid;
  }

}
