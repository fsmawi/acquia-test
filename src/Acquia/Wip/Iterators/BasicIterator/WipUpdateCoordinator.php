<?php

namespace Acquia\Wip\Iterators\BasicIterator;

use Acquia\Wip\StateTableIteratorInterface;

/**
 * Provides facilities to Wip update methods.
 */
class WipUpdateCoordinator implements WipUpdateCoordinatorInterface {

  /**
   * The StateTableIterator instance.
   *
   * @var StateTableIteratorInterface
   */
  private $iterator;

  /**
   * The current state table position.
   *
   * @var string
   */
  private $currentState = NULL;

  /**
   * The state table position that should be set after the update is complete.
   *
   * @var string
   */
  private $newState = NULL;

  /**
   * Indicates whether a Wip object restart is required.
   *
   * @var bool
   */
  private $requiresRestart = FALSE;

  /**
   * Indicates whether the state table counters should be reset.
   *
   * @var bool
   */
  private $requiresCounterReset = FALSE;

  /**
   * Indicates specific state table counters that should be reset.
   *
   * @var string[]
   */
  private $resetTransitionCounters = [];

  /**
   * Initializes a new instance of WipUpdateCoordinator.
   *
   * @param StateTableIteratorInterface $iterator
   *   The iterator.
   * @param string $current_state
   *   The state that identifies where in the state table the Wip
   *   instance is currently.
   */
  public function __construct(StateTableIteratorInterface $iterator, $current_state) {
    $this->setIterator($iterator);
    $this->setCurrentState($current_state);
  }

  /**
   * {@inheritdoc}
   */
  public function hasStarted() {
    return $this->getCurrentState() !== NULL;
  }

  /**
   * Sets the iterator.
   *
   * @param StateTableIteratorInterface $iterator
   *   The iterator.
   */
  private function setIterator(StateTableIteratorInterface $iterator) {
    $this->iterator = $iterator;
  }

  /**
   * Gets the iterator.
   *
   * @return StateTableIterator
   *   The iterator.
   */
  private function getIterator() {
    return $this->iterator;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentState() {
    return $this->currentState;
  }

  /**
   * Sets the current state table position.
   *
   * @param string $state
   *   The name of the state the Wip object is currently on.
   */
  private function setCurrentState($state) {
    $this->currentState = $state;
    $this->setNewState($state);
  }

  /**
   * {@inheritdoc}
   */
  public function setNewState($state) {
    $this->newState = $state;
  }

  /**
   * Gets the state that should be set post update.
   *
   * @return string
   *   The name of the new state.
   */
  public function getNewState() {
    return $this->newState;
  }

  /**
   * {@inheritdoc}
   */
  public function restart() {
    $this->requiresRestart = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function requiresRestart() {
    return $this->requiresRestart;
  }

  /**
   * {@inheritdoc}
   */
  public function resetAllCounters() {
    $this->requiresCounterReset = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function requiresCounterReset() {
    return $this->requiresCounterReset;
  }

  /**
   * {@inheritdoc}
   */
  public function resetStateTransitionCounters($state) {
    $iterator = $this->getIterator();
    $ids = $iterator->getStateTransitionIds($state);
    $this->resetTransitionCounters = array_merge($this->resetTransitionCounters, $ids);
  }

  /**
   * {@inheritdoc}
   */
  public function resetTransitionCount($state, $transition_value) {
    $iterator = $this->getIterator();
    $id = $iterator->getTransitionId($state, $transition_value);
    $this->resetTransitionCounters[] = $id;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransitionCountersToReset() {
    return $this->resetTransitionCounters;
  }

}
