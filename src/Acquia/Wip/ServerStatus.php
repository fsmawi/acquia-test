<?php

namespace Acquia\Wip;

/**
 * The ServerStatus identifies the current status of the associated server.
 */
class ServerStatus {

  /**
   * Constant for server that are not available to handle Wip task processing.
   */
  const NOT_AVAILABLE = 0;

  /**
   * Constant for server that are available to handle Wip task processing.
   */
  const AVAILABLE = 1;

  /**
   * The current server status.
   *
   * @var int
   */
  private $value = self::AVAILABLE;

  /**
   * The set of valid values a ServerStatus instance can hold.
   *
   * @var int[]
   */
  private static $validValues = array(
    self::NOT_AVAILABLE,
    self::AVAILABLE,
  );

  /**
   * Creates a new instance of TaskStatus with the specified value.
   *
   * @param int $value
   *   The TaskStatus value.
   */
  public function __construct($value = self::AVAILABLE) {
    if (!self::isValid($value)) {
      throw new \InvalidArgumentException(sprintf('Value "%s" does not represent a valid ServerStatus value.', $value));
    }
    $this->value = $value;
  }

  /**
   * Returns the value of this TaskStatus instance.
   *
   * @return int
   *   The current value of this TaskStatus.
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Indicates whether the specified status value is valid.
   *
   * @param int $value
   *   The status to verify.
   *
   * @return bool
   *   TRUE if the specified status is a valid TaskStatus value; FALSE
   *   otherwise.
   */
  public static function isValid($value) {
    return is_int($value) && in_array($value, self::$validValues);
  }

}
