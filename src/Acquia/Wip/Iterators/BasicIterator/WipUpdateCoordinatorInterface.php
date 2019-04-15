<?php

namespace Acquia\Wip\Iterators\BasicIterator;

use Acquia\Wip\Exception\MissingTransitionBlockException;

/**
 * Provides facilities to Wip update methods.
 */
interface WipUpdateCoordinatorInterface {

  /**
   * Indicates whether the Wip instance has started.
   *
   * @return bool
   *   TRUE if the instance has started; FALSE otherwise.
   */
  public function hasStarted();

  /**
   * Gets the current state.
   *
   * @return string
   *   The name of the state the Wip object is currently on.
   */
  public function getCurrentState();

  /**
   * Indicates the new state that should be set after the update.
   *
   * @param string $state
   *   The new state.
   *
   * @return string
   *   The name of the new state.
   */
  public function setNewState($state);

  /**
   * Gets the state that should be set post update.
   *
   * @return string
   *   The name of the new state.
   */
  public function getNewState();

  /**
   * Sets a flag indicating the Wip object should be restarted on update.
   *
   * This effectively results in the Wip object being terminated and then
   * restarted. This is certainly the worst-case scenario because it does not
   * cause the cleanup section of the state table to be executed, possibly
   * resulting in inefficient resource use.
   */
  public function restart();

  /**
   * Indicates whether the Wip object must be restarted.
   *
   * @return bool
   *   TRUE if the Wip instance must be restarted; FALSE otherwise.
   */
  public function requiresRestart();

  /**
   * Causes all transition counters to be reset upon update.
   */
  public function resetAllCounters();

  /**
   * Indicates whether all counters should be reset upon update.
   *
   * @return bool
   *   TRUE if all counters should be reset; FALSE otherwise.
   */
  public function requiresCounterReset();

  /**
   * Causes all transition counters for the specified state to be reset.
   *
   * @param string $state
   *   The name of the state for which all transition counts will be reset.
   */
  public function resetStateTransitionCounters($state);

  /**
   * Causes the counter for the specified state and transition value to reset.
   *
   * @param string $state
   *   The name of the state associated with the transition.
   * @param string $transition_value
   *   The transition value associated with the transition.
   *
   * @throws MissingTransitionBlockException
   *   If the specified transition cannot be found.
   */
  public function resetTransitionCount($state, $transition_value);

  /**
   * Gets the IDs of all of the transition counters that will be reset.
   *
   * @return string[]
   *   The set of transition IDs to reset.
   */
  public function getTransitionCountersToReset();

}
