<?php

namespace Acquia\Wip\Iterators\SimulationIterator;

/**
 * A runnable state table script for simulation testing.
 */
class StateTableScript {
  /**
   * Array mapping state names to corresponding ScriptTestSequence objects.
   *
   * @var ScriptTestSequence[]
   */
  private $stateMapping = NULL;

  /**
   * The constructor.
   */
  public function __construct() {
    $this->stateMapping = array();
  }

  /**
   * Gets the next transition from the simulation.
   *
   * @param string $state
   *   The state for which the transition value should be returned.  If the
   *   state does not match that expected in the instructions a mismatch is
   *   detected and an exception will be thrown.
   *
   * @return string
   *   The transition value.
   *
   * @throws \RuntimeException
   *   Thrown when the script does not match the state sequence in
   *   ScriptTestSequence.
   *
   * @throws \InvalidArgumentException
   *   Thrown when an empty state is passed in, or the state is not valid.
   */
  public function getNextTransitionValue($state) {
    if (empty($state)) {
      throw new \InvalidArgumentException('Invalid state in argument.');
    } elseif (empty($this->stateMapping[$state])) {
      throw new \InvalidArgumentException('Invalid state in argument- state not found in table.');
    } else {
      $next_value = $this->stateMapping[$state]->pop();
      return $next_value;
    }
  }

  /**
   * Adds a ScriptTestSequence to the given state.
   *
   * @param string $state
   *   Name of the state to add the sequence to.
   * @param ScriptTestSequence $sequence
   *   The ScriptTestSequence object containing the sequence.
   *
   * @throws \InvalidArgumentException
   *   Thrown if invalid or empty state (not a string) or sequence object are
   *   provided.
   */
  public function addStateSequence($state, ScriptTestSequence $sequence) {
    if (empty($state) || empty($sequence)) {
      throw new \InvalidArgumentException('Invalid state and/or sequence object in arguments.');
    } elseif (!is_string($state)) {
      throw new \InvalidArgumentException('The state must be a string.');
    } else {
      if (empty($this->stateMapping[$state])) {
        $this->stateMapping[$state] = new ScriptTestSequence();
      }

      $this->stateMapping[$state] = $sequence;
    }
  }

  /**
   * Adds a state value.
   *
   * @param string $state
   *   Name of the state to add the sequence to.
   * @param mixed $value
   *   Either an array or a string of value(s) to be added.
   *
   * @throws \InvalidArgumentException
   *   Thrown if a non-string state is provided.
   */
  public function addStateValue($state, $value) {
    if (empty($state)) {
      throw new \InvalidArgumentException('Invalid state in argument.');
    } elseif (!is_string($state)) {
      throw new \InvalidArgumentException('The state must be a string.');
    } else {
      if (empty($this->stateMapping[$state])) {
        $this->stateMapping[$state] = new ScriptTestSequence();
      }

      $this->stateMapping[$state]->addValue($value);
    }
  }

  /**
   * Returns the ScriptTestSequence for the given state.
   *
   * @param string $state
   *   Name of the state to get the sequence for.
   *
   * @return ScriptTestSequence sequence
   *   The corresponding sequence for the given state, could be empty or null.
   *
   * @throws \InvalidArgumentException
   *   Thrown if invalid or empty state is provided.
   */
  public function getStateSequence($state) {
    if (empty($state)) {
      throw new \InvalidArgumentException('Invalid state in argument.');
    } else {
      $sequence = $this->stateMapping[$state];
      return $sequence;
    }
  }

}
