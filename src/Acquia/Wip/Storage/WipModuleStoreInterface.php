<?php

namespace Acquia\Wip\Storage;

use Acquia\Wip\WipModuleInterface;

/**
 * The WipModuleStoreInterface provides storage for modules with task implementations.
 *
 * Each module will define one or more tasks.
 */
interface WipModuleStoreInterface {

  /**
   * Retrieves a module by name.
   *
   * @param string $name
   *   The name of the module.
   *
   * @return WipModuleInterface | NULL
   *   The WipModule that was retrieved, or NULL on failure.
   *
   * @throws \InvalidArgumentException
   *   If the name argument is not a non-empty string.
   */
  public function get($name);

  /**
   * Stores or updates a module.
   *
   * @param WipModuleInterface $module
   *   The module.
   */
  public function save(WipModuleInterface $module);

  /**
   * Removes a module from storage.
   *
   * @param string $name
   *   The name of the module.
   *
   * @throws \InvalidArgumentException
   *   If the name argument is not a non-empty string.
   */
  public function delete($name);

  /**
   * Retrieves a module by one of the task names it contains.
   *
   * @param string $name
   *   The name of a task.
   *
   * @return WipModuleInterface
   *   The WipModule that was retrieved.
   *
   * @throws \InvalidArgumentException
   *   If the name argument is not a non-empty string.
   * @throws \DomainException
   *   If the name argument does not match an installed module.
   */
  public function getByTaskName($name);

  /**
   * Retrieves modules based on the value of the enabled column.
   *
   * @param bool $enabled
   *   Whether the module is enabled.
   *
   * @return WipModuleInterface | NULL
   *   An array of WipModuleInterface objects, else NULL on failure.
   */
  public function getByEnabled($enabled);

  /**
   * Retrieves modules based on the value of the ready column.
   *
   * @param bool $ready
   *   Whether the module is ready.
   *
   * @return WipModuleInterface | NULL
   *   An array of WipModuleInterface objects, else NULL on failure.
   */
  public function getByReady($ready);

}
