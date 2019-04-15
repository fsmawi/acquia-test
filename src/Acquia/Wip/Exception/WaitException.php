<?php

namespace Acquia\Wip\Exception;

/**
 * Defines an exception type for when the process must wait.
 */
class WaitException extends WipException {

  /**
   * The number of seconds to wait.
   *
   * @var int
   */
  private $wait = 0;

  /**
   * Set the duration the process should wait.
   *
   * @param int $wait
   *   The duration to wait, measured in seconds.
   */
  public function setWait($wait) {
    if (!is_int($wait) || !$wait >= 0) {
      throw new \InvalidArgumentException('The "wait" parameter must be a positive integer.');
    }
    $this->wait = $wait;
  }

  /**
   * Indicates how long the process should wait.
   *
   * @return int
   *   The number of seconds to wait.
   */
  public function getWait() {
    return $this->wait;
  }

}
