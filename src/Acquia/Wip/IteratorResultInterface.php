<?php

namespace Acquia\Wip;

/**
 * Encapsulates the result of running the iterator.
 *
 * This interface is used to expose the condition of the iterator after its
 * moveToNextState method is called.
 */
interface IteratorResultInterface {

  /**
   * Indicates the preferred amount of delay before the iterator is invoked.
   *
   * This value is used to make the system more efficient. When waiting for an
   * asynchronous action to complete, this value is used to keep the iterator
   * from constantly polling. A signal is expected to arrive and interrupt the
   * wait time in most cases, so this should be a fail-safe poll time.
   *
   * For example, if an action is anticipated to take 10 seconds and it is an
   * asynchronous action that sends a signal when completed, it would likely
   * be prudent to establish a wait time of 60 seconds to prevent unnecessary
   * polling.
   */
  public function getWaitTime();

  /**
   * Indicates whether the iterator has completed its processing.
   *
   * If the isComplete method returns FALSE, the iterator will not be invoked
   * again unless it is restarted.
   *
   * @return bool
   *   TRUE if the iterator is complete; FALSE otherwise.
   */
  public function isComplete();

  /**
   * Indicates the completion status.
   *
   * @return IteratorStatus
   *   The iterator status.
   */
  public function getStatus();

  /**
   * The exit message.
   *
   * @return string
   *   The message.
   */
  public function getMessage();

}
