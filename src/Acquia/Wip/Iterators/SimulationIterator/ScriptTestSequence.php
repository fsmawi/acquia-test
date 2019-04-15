<?php

namespace Acquia\Wip\Iterators\SimulationIterator;

/**
 * This class keeps track of the status sequences for a state to run in simulation.
 */
class ScriptTestSequence {
  /**
   * The status sequence.
   *
   * @var array
   */
  private $sequence = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->sequence = array();
  }

  /**
   * Adds value(s) to the sequence.
   *
   * Merges an array of values to the end of the current array, or adds a
   * single value to the end of the current array.
   *
   * @param mixed $value
   *   Either an array or a string of value(s) to be added.
   *
   * @throws \InvalidArgumentException
   *   Thrown if the value given is neither an array or a string.
   */
  public function addValue($value) {
    if (is_array($value)) {
      $this->sequence = array_merge($this->sequence, $value);
    } elseif (is_string($value)) {
      array_push($this->sequence, $value);
    } else {
      throw new \InvalidArgumentException('Invalid argument type for $value- must be an array or a string.');
    }
  }

  /**
   * Returns the entire sequence as an array.
   *
   * @return array|null
   *   The sequence.
   */
  public function getSequence() {
    return $this->sequence;
  }

  /**
   * Resets the sequence by removing all values.
   */
  public function reset() {
    $this->sequence = array();
  }

  /**
   * Peeks at the next value without removing it from the sequence.
   *
   * @return string|null
   *   The next value in the sequence, or null if it does not exist.
   */
  public function peek() {
    if (count($this->sequence) <= 0) {
      return NULL;
    } else {
      return $this->sequence[0];
    }
  }

  /**
   * Removes and returns the next value in the sequence.
   *
   * @return string|null
   *   The next value in the sequence, or null if it does not exist.
   */
  public function pop() {
    if (count($this->sequence) <= 0) {
      return NULL;
    } else {
      return array_shift($this->sequence);
    }
  }

}
