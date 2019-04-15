<?php

namespace Acquia\Wip\Storage;

use Acquia\Wip\WipModuleTask;
use Acquia\Wip\WipModuleTaskInterface;

/**
 * The WipModuleTaskStoreInterface provides storage for tasks for a single module.
 *
 * Each module will define one or more tasks.
 */
interface WipModuleTaskStoreInterface {

  /**
   * Retrieve a task by name.
   *
   * @param string $name
   *   The name of the task.
   *
   * @return WipModuleTaskInterface
   *   The WipModuleTaskInterface that was retrieved, or NULL on failure.
   *
   * @throws \InvalidArgumentException
   *   If the name parameter is not a non-empty string.
   */
  public function get($name);

  /**
   * Retrieve only the class names for tasks associated with a given module name.
   *
   * @param string $module_name
   *   The module name.
   *
   * @return WipModuleTask[]
   *   An array of WipModuleTask objects that were retrieved.
   *
   * @throws \InvalidArgumentException
   *   If the module name argument is not a non-empty string.
   */
  public function getTasksByModuleName($module_name);

  /**
   * Store a task.
   *
   * @param WipModuleTaskInterface $task
   *   The task.
   */
  public function save(WipModuleTaskInterface $task);

  /**
   * Removes a task from storage.
   *
   * @param string $name
   *   The name of the task.
   *
   * @throws \InvalidArgumentException
   *   If the name parameter is not a non-empty string.
   */
  public function delete($name);

}
