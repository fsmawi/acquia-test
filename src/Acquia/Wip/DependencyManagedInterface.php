<?php

namespace Acquia\Wip;

/**
 * Identifies object dependencies to aid in failing quickly if they are not met.
 *
 * Defines methods that must be implemented for an object to require
 * dependencies where initial implementations are supplied by a
 * DependencyManagerInterface implementation, which allows swapping of
 * instances on demand.
 */
interface DependencyManagedInterface {

  /**
   * Gets the known dependencies of a class.
   *
   * @return array
   *   A list of dependencies with the addressable names as array keys, values
   *   are the interface type that must be implemented. The interface type may
   *   be a concrete class name, if necessary.  Supplying an empty interface
   *   type (eg. "" or FALSE) disables type checking for that object (not
   *   recommended, but can be useful if there is no interface).
   */
  public function getDependencies();

}
