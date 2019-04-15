<?php

namespace Acquia\Wip\State;

/**
 * Defines the group pause state.
 */
class GroupPause {

  /**
   * The variable name used for storing global hard pause state.
   */
  const HARD_STATE_NAME = 'wip.pool.group.hard.pause';

  /**
   * The variable name used for storing global soft pause state.
   */
  const SOFT_STATE_NAME = 'wip.pool.group.soft.pause';

  /**
   * The default value for group pause.
   *
   * @var string
   */
  public static $defaultValue = array();

  /**
   * Indicates whether the given value is valid for group pause.
   *
   * @param string $value
   *   The value to validate.
   *
   * @return bool
   *   If the given value is valid for group pause.
   */
  public static function isValidValue($value) {
    if ($value === NULL || !is_string($value)) {
      return FALSE;
    }
    $groups = explode(',', $value);
    return $groups !== NULL && !empty($groups);
  }

}
