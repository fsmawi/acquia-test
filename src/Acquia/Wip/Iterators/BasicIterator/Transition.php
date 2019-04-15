<?php

namespace Acquia\Wip\Iterators\BasicIterator;

/**
 * A transition binds information describing a single transition.
 */
class Transition {

  /**
   * The transition value.
   *
   * @var string
   */
  private $value = NULL;

  /**
   * The state associated with the transition value.
   *
   * @var string
   */
  private $state = NULL;

  /**
   * The wait time in seconds associated with the transition.
   *
   * @var int
   */
  private $wait = 0;

  /**
   * The maximum number of executions associated with the transition.
   *
   * @var int
   */
  private $maxCount = 0;

  /**
   * Indicates whether to execute the state on the associated transition.
   *
   * @var bool
   */
  private $exec = TRUE;

  /**
   * The line number.
   *
   * @var int
   */
  private $lineNumber = 0;

  /**
   * Creates a new transition.
   *
   * @param string $value
   *   The transition value.
   * @param string $state
   *   The state associated with the transition value.
   * @param int $wait
   *   Optional. The wait time associated with the transition value.
   * @param int $max_count
   *   Optional. The maximum number of transitions for this path instance.
   * @param bool $exec
   *   Optional. Indicates whether the next state should be executed on this
   *   transition instance.
   */
  public function __construct($value, $state, $wait = 0, $max_count = 0, $exec = TRUE) {
    $this->value = $value;
    $this->state = $state;
    $this->wait = $wait;
    $this->maxCount = $max_count;
    $this->exec = $exec;
  }

  /**
   * Returns the transition value.
   *
   * @return string
   *   The transition value.
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Returns the state that will be executed upon associated transition value..
   *
   * The next state to be executed if the associated transition value is
   * provided.
   *
   * @return string
   *   The state associated with the transition value.
   */
  public function getState() {
    return $this->state;
  }

  /**
   * Returns the wait time in seconds associated with the transition.
   *
   * @return int
   *   The wait time.
   */
  public function getWait() {
    return $this->wait;
  }

  /**
   * Returns the maximum execution count for this transition.
   *
   * @return int
   *   The maximum execution count.
   */
  public function getMaxCount() {
    return $this->maxCount;
  }

  /**
   * Indicates whether this transition executes the next state.
   *
   * Having an option to not execute the target state but rather only use its
   * transitions means asynchronous operations do not need a state having the
   * sole purpose to wait for the asynchronous action to complete.
   *
   * @code
   * install:checkAsyncProcess {
   *   running install wait=30 exec=false # Does not execute the install state.
   *   success next_step
   *   fail install wait=30 max=3         # Does execute the install state.
   * }
   * @endcode
   *
   * @return bool
   *   TRUE if the next state should be executed; FALSE if it should not.
   */
  public function getExec() {
    return $this->exec;
  }

  /**
   * Sets the line number.
   *
   * @param int $line_number
   *   The line number.
   */
  public function setLineNumber($line_number) {
    if (!is_int($line_number)) {
      throw new \InvalidArgumentException('The line_number argument must be an integer.');
    }
    if ($line_number < 0) {
      throw new \InvalidArgumentException('The line_number argument must be a positive integer.');
    }
    $this->lineNumber = $line_number;
  }

  /**
   * Gets the line number.
   *
   * @return int
   *   The line number.
   */
  public function getLineNumber() {
    return $this->lineNumber;
  }

  /**
   * Returns an ID that is unique for this transition within the state table.
   *
   * @return string
   *   The unique ID.
   */
  public function getUniqueId() {
    // There is only one transition per line.
    return '' . $this->getLineNumber();
  }

}
