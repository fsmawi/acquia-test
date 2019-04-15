<?php

namespace Acquia\Wip;

use Acquia\Wip\Exception\DependencyTypeException;

/**
 * Defines methods required for any DependencyManager implementation.
 */
interface DependencyManagerInterface {

  /**
   * Adds the initial dependencies to the DependencyManager.
   *
   * @param array $spec
   *   An array whose keys are names of dependencies or services specified in
   *   the WipFactory config file.  Values are an interface or class type that
   *   should match any instance supplied.  Instances will be type-checked on
   *   any attempt to add or swap them.
   *
   * @throws DependencyTypeException
   *   If any instance does not match the specified type.
   */
  public function addDependencies(array $spec);

  /**
   * Checks a dependency against a declared interface or class type.
   *
   * @param mixed $instance
   *   The dependency or service to check.
   * @param object $type
   *   The fully-qualified type name to check against.
   */
  public function checkDependency($instance, $type);

  /**
   * Replaces a dependency with another concrete instance.
   *
   * @param string $name
   *   The declared name of the dependency or service to replace.
   * @param object $object
   *   The instance to use to replace the declared dependency.
   */
  public function swapDependency($name, $object);

  /**
   * Retrieves a previously stored dependency by name.
   *
   * @param string $name
   *   The name corresponding to a known dependency.
   *
   * @return object
   *   An instance of the requested dependency.
   */
  public function getDependency($name);

}
