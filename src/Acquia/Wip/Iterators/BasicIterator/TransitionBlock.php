<?php

namespace Acquia\Wip\Iterators\BasicIterator;

/**
 * Holds information about a state / transition combination.
 *
 * The TransitionBlock holds all the information necessary to figure out what
 * the next state would be given a particular transition value.
 */
class TransitionBlock {

  /**
   * The starting state.
   *
   * @var string
   */
  private $state = NULL;

  /**
   * The transition.
   *
   * @var string
   */
  private $transitionMethod = NULL;

  /**
   * The name of the timer associated with this transition block.
   *
   * @var string
   */
  private $timerName = NULL;

  /**
   * The set of transitions within this block.
   *
   * @var array
   */
  private $transitions = NULL;

  /**
   * The line number.
   *
   * @var int
   */
  private $lineNumber = 0;

  /**
   * Creates a new instance of TransitionBlock.
   *
   * @param string $state
   *   The state.
   * @param string $transition_method
   *   The transition.
   * @param string $timer_name
   *   The timer name associated with this transition block.
   */
  public function __construct($state, $transition_method, $timer_name = 'system') {
    $this->state = $state;
    $this->transitionMethod = $transition_method;
    if (empty($timer_name) || !is_string($timer_name)) {
      throw new \InvalidArgumentException('The timer_name parameter must be a non-empty string.');
    }
    $this->timerName = $timer_name;
    $this->transitions = array();
  }

  /**
   * Gets the state.
   *
   * @return string
   *   The state.
   */
  public function getState() {
    return $this->state;
  }

  /**
   * Gets the transition method associated with the specified value.
   *
   * @return string
   *   The transition method that calculates a value used to determine the
   *   next state.
   */
  public function getTransitionMethod() {
    return $this->transitionMethod;
  }

  /**
   * Gets the name of the timer associated with this transition block.
   *
   * @return string
   *   The timer name.
   */
  public function getTimerName() {
    return $this->timerName;
  }

  /**
   * Adds a new transition to this transition block.
   *
   * @param Transition $transition
   *   The transition to be added.
   */
  public function addTransition(Transition $transition) {
    $this->transitions[$transition->getValue()] = $transition;
  }

  /**
   * Returns the transition with the specified transition value.
   *
   * @param string $transition_value
   *   The transition value.
   *
   * @return Transition
   *   The transition associated with the specified transition value.
   */
  public function getTransition($transition_value) {
    $result = NULL;
    if (!empty($this->transitions[$transition_value])) {
      $result = $this->transitions[$transition_value];
    } elseif ($transition_value === 'terminateRequested') {
      $result = new Transition('terminateRequested', 'terminate');
    }
    return $result;
  }

  /**
   * Determines the proper Transition given the transition value.
   *
   * This is similar to getTransition, but handles wildcard transition values
   * and error values.
   *
   * @param string $transition_value
   *   The transition value used to determine the next state.
   *
   * @return Transition
   *   The next transition, or NULL if there is no specified next transition.
   */
  public function findNextTransition($transition_value) {
    $result = $this->getTransition($transition_value);
    if (empty($result)) {
      // Use the * transition if it was provided.  Do not include the !
      // transition in the * transition group.
      if ($transition_value !== '!') {
        $result = $this->getTransition('*');
      }
    }
    return $result;
  }

  /**
   * Sets the line number associated with this transition block.
   *
   * @param int $line_number
   *   The line number associated with this transition block.
   */
  public function setLineNumber($line_number) {
    $this->lineNumber = $line_number;
  }

  /**
   * Gets the line number associated with this transition block.
   *
   * @return int
   *   The line number.
   */
  public function getLineNumber() {
    return $this->lineNumber;
  }

  /**
   * Returns all possible transition values this transition block expects.
   *
   * @return string[]
   *   The transition values.
   */
  public function getAllTransitionValues() {
    return array_keys($this->transitions);
  }

}
