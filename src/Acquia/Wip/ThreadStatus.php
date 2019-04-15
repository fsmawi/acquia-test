<?php

namespace Acquia\Wip;

/**
 * The ThreadStatus identifies the current status of the associated thread.
 */
class ThreadStatus {

  /**
   * Thread has been reserved, but is not yet executing.
   */
  const RESERVED = 1;

  /**
   * Thread has been claimed and is running.
   */
  const RUNNING = 2;

  /**
   * Thread has finished running.
   */
  const FINISHED = 3;

  /**
   * The current thread status.
   *
   * @var int
   */
  private $value = self::RESERVED;

  /**
   * The set of valid values a ThreadStatus instance can hold.
   *
   * @var int[]
   */
  private static $validValues = array(
    self::RESERVED,
    self::RUNNING,
    self::FINISHED,
  );

  /**
   * Creates a new instance of ThreadStatus with the specified value.
   *
   * @param int $value
   *   The ThreadStatus value.
   */
  public function __construct($value = self::RESERVED) {
    if (!self::isValid($value)) {
      throw new \InvalidArgumentException(sprintf('Value "%s" does not represent a valid ThreadStatus value.', $value));
    }
    $this->value = $value;
  }

  /**
   * Returns the value of this ThreadStatus instance.
   *
   * @return int
   *   The current value of this ThreadStatus.
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
   *   TRUE if the specified status is a valid ThreadStatus value; FALSE
   *   otherwise.
   */
  public static function isValid($value) {
    return is_int($value) && in_array($value, self::$validValues);
  }

}
