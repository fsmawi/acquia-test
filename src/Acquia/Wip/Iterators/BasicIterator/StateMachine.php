<?php

namespace Acquia\Wip\Iterators\BasicIterator;

use Acquia\Wip\Exception\MissingTransitionBlockException;

/**
 * Contains the internal representation of the parsed state table.
 *
 * The StateMachine provides an interface for building and querying a state
 * table that has been processed.
 */
class StateMachine {

  /**
   * The first state in the finite state machine.
   *
   * @var string
   */
  private $startState = NULL;

  /**
   * The collection of transition blocks in this finite state machine.
   *
   * @var TransitionBlock[]
   */
  private $transitionBlocks = array();

  /**
   * Adds the specified transition block to the state machine.
   *
   * @param TransitionBlock $transition_block
   *   The transition block.
   */
  public function addTransitionBlock(TransitionBlock $transition_block) {
    if (empty($this->startState)) {
      $this->startState = $transition_block->getState();
    }
    $this->transitionBlocks[$transition_block->getState()] = $transition_block;
  }

  /**
   * Returns the transition block associated with the specified state.
   *
   * @param string $state
   *   The state name.
   *
   * @return TransitionBlock
   *   The transition block that corresponds with the specified state.
   *
   * @throws \InvalidArgumentException
   *   If the specified state name is not a string.
   * @throws MissingTransitionBlockException
   *   If the specified block does not exist.
   */
  public function getTransitionBlock($state) {
    if (empty($state)) {
      throw new \InvalidArgumentException('The state argument must not be empty.');
    }
    if (!is_string($state)) {
      throw new \InvalidArgumentException('The state argument must be a string.');
    }
    if (empty($this->transitionBlocks[$state])) {
      $e = new MissingTransitionBlockException(sprintf('There is no transition block for state "%s"', $state));
      $e->setBlock($state);
      throw $e;
    }
    return $this->transitionBlocks[$state];
  }

  /**
   * Returns the state where the state machine begins.
   *
   * @return string
   *   The start state.
   */
  public function getStartState() {
    if (empty($this->startState)) {
      throw new \RuntimeException('Cannot get the start state until transition blocks have been added.');
    }
    return $this->startState;
  }

  /**
   * Gets the list of all state names.
   *
   * @return string[]
   *   All state names.
   */
  public function getAllStates() {
    return array_keys($this->transitionBlocks);
  }

  /**
   * Gets the list of all transition names.
   *
   * @return string[]
   *   All transition method names.
   */
  public function getAllTransitions() {
    $result = array();
    foreach ($this->transitionBlocks as $transition_block) {
      $transition = $transition_block->getTransitionMethod();
      if (!in_array($transition, $result)) {
        $result[] = $transition;
      }
    }
    return $result;
  }

}
