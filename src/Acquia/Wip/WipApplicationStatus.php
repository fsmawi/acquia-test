<?php

namespace Acquia\Wip;

/**
 * The WipApplicationStatus identifies the status of the associated application.
 */
class WipApplicationStatus {

  /**
   * Constant for applications that are disabled.
   */
  const DISABLED = 0;

  /**
   * Constant for applications that are enabled.
   */
  const ENABLED = 1;

  /**
   * The current application status.
   *
   * @var int
   */
  private $value = self::DISABLED;

  /**
   * The set of valid values a WipApplicationStatus instance can hold.
   *
   * @var int[]
   */
  private static $validValues = array(
    self::DISABLED,
    self::ENABLED,
  );

  /**
   * Creates a new instance of WipApplicationStatus with the specified value.
   *
   * @param int $value
   *   The WipApplicationStatus value.
   */
  public function __construct($value = self::DISABLED) {
    if (!self::isValid($value)) {
      throw new \InvalidArgumentException(
        sprintf('Value "%s" does not represent a valid WipApplicationStatus value.', $value)
      );
    }
    $this->value = $value;
  }

  /**
   * Returns the value of this WipApplicationStatus instance.
   *
   * @return int
   *   The current value of this WipApplicationStatus.
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
   *   TRUE if the specified status is a valid WipApplicationStatus value; FALSE
   *   otherwise.
   */
  public static function isValid($value) {
    return is_int($value) && in_array($value, self::$validValues);
  }

}
