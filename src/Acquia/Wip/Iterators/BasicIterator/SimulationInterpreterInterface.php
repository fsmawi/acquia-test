<?php

namespace Acquia\Wip\Iterators\BasicIterator;

/**
 * The interface that describes the common elements of simulations.
 */
interface SimulationInterpreterInterface {

  /**
   * Resets the interpreter to start the simulation over.
   */
  public function reset();

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
   */
  public function getNextTransitionValue($state);

}
