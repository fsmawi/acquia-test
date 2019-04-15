<?php

namespace Acquia\Wip\Implementation;

use Acquia\Wip\IteratorResultInterface;
use Acquia\Wip\IteratorStatus;

/**
 * The IteratorResult class encapsulates the result of running the iterator.
 */
class IteratorResult implements IteratorResultInterface {

  /**
   * The amount of time to wait before invoking the iterator again.
   *
   * @var int
   */
  private $waitTime = 0;

  /**
   * Indicates whether the iterator has completed its processing.
   *
   * @var bool
   */
  private $complete = FALSE;

  /**
   * Indicates the iterator completion if it has finished processing.
   *
   * @var IteratorStatus
   */
  private $status = NULL;

  /**
   * The exit message.
   *
   * @var string
   */
  private $message = NULL;

  /**
   * Creates a new instance of IteratorResult.
   *
   * @param int $wait_time
   *   Optional. The duration in seconds to delay before calling the iterator again.
   * @param bool $complete
   *   Optional. Whether the iterator is complete or not.
   * @param IteratorStatus $status
   *   Optional. The completion status.
   * @param string $message
   *   Optional. The exit message.
   */
  public function __construct($wait_time = 0, $complete = FALSE, IteratorStatus $status = NULL, $message = '') {
    $this->waitTime = $wait_time;
    $this->complete = $complete;
    if ($status === NULL) {
      $status = new IteratorStatus();
    }
    $this->status = $status;
    $this->message = $message;
  }

  /**
   * {@inheritdoc}
   */
  public function getWaitTime() {
    return $this->waitTime;
  }

  /**
   * {@inheritdoc}
   */
  public function isComplete() {
    return $this->complete;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    return $this->message;
  }

}
