<?php

namespace Acquia\Wip\Signal;

/**
 * The SignalType class identifies legal signal types.
 */
class SignalType {

  /**
   * Indicates when an asynchronous process has completed.
   */
  const COMPLETE = 1;

  /**
   * Indicates when an asynchronous process has provided data.
   */
  const DATA = 2;

  /**
   * Indicates the associated Wip object should terminate.
   */
  const TERMINATE = 3;

  /**
   * Indicates the legal signal type values and their human-readable names.
   *
   * @var array
   */
  private static $legalValues = array(
    self::COMPLETE => 'complete',
    self::DATA => 'data',
    self::TERMINATE => 'terminate',
  );

  /**
   * Indicates whether the specified type is a legal signal type.
   *
   * @param int $type
   *   The signal type ID to test.
   *
   * @return bool
   *   TRUE if the specified type is legal; FALSE otherwise.
   */
  public static function isLegal($type) {
    return in_array($type, array_keys(self::$legalValues));
  }

  /**
   * Returns a human-readable version of the specified type code.
   *
   * @param int $type
   *   The signal type.
   *
   * @return string
   *   The human-readable label.
   */
  public static function getLabel($type) {
    $result = 'unknown';
    if (self::isLegal($type)) {
      $result = self::$legalValues[$type];
    }
    return $result;
  }

}
