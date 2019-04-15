<?php

namespace Acquia\Wip\Implementation;

/**
 * An object to hold an array of transitions for a given state.
 */
class TransitionSequence {

  /**
   * The name of the state.
   *
   * @var string
   */
  private $stateName = NULL;

  /**
   * The array of transitions for the state.
   *
   * @var string[]
   */
  private $transitionsArray = NULL;

  /**
   * The line number of the state.
   *
   * @var int
   */
  private $lineNumber = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct($name) {
    $this->stateName = $name;
    $this->transitionsArray = array();
  }

  /**
   * Adds a transition to the state.
   *
   * @param string $transition
   *   The transition.
   *
   * @throws \InvalidArgumentException
   *   If the transition is not a string.
   */
  public function addTransition($transition) {
    if (is_string($transition)) {
      array_push($this->transitionsArray, $transition);
    } else {
      throw new \InvalidArgumentException('The transition must be a string.');
    }
  }

  /**
   * Returns the array of transitions for the state.
   *
   * @return string[]
   *   The array of transitions.
   */
  public function getTransitions() {
    return $this->transitionsArray;
  }

  /**
   * Sets the name of the state.
   *
   * @param string $name
   *   The name of the state.
   *
   * @throws \InvalidArgumentException
   *   If the state name is not a string.
   */
  public function setStateName($name) {
    if (is_string($name)) {
      $this->stateName = $name;
    } else {
      throw new \InvalidArgumentException('The state name must be a string.');
    }
  }

  /**
   * Returns the name of the state.
   *
   * @return string
   *   the state name.
   */
  public function getStateName() {
    return $this->stateName;
  }

  /**
   * Sets the line number of the state.
   *
   * @param int $line_number
   *   The line number.
   *
   * @throws \InvalidArgumentException
   *   If the line number is not a positive integer.
   */
  public function setLineNumber($line_number) {
    if (is_int($line_number) && $line_number > 0) {
      $this->lineNumber = $line_number;
    } else {
      throw new \InvalidArgumentException(
        'The line number must be a positive integer.'
      );
    }
  }

  /**
   * Returns the line number of the state.
   *
   * @return int
   *   The line number.
   */
  public function getLineNumber() {
    return $this->lineNumber;
  }

}
