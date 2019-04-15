<?php

namespace Acquia\Wip\Storage;

use Acquia\Wip\WipModuleTaskInterface;

/**
 * Provides a base class to test task storage.
 *
 * @copydetails WipModuleTaskStoreInterface
 */
class BasicWipModuleTaskStore implements WipModuleTaskStoreInterface {

  /**
   * Storage implementation as an array.
   *
   * @var WipModuleTaskInterface[]
   */
  private $tasks = array();

  /**
   * {@inheritdoc}
   */
  public function get($name) {
    $this->validateName($name);
    $name = trim($name);
    $exists = isset($this->tasks[$name]);

    return $exists ? unserialize($this->tasks[$name]) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function save(WipModuleTaskInterface $task) {
    $this->tasks[$task->getName()] = serialize($task);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($name) {
    $this->validateName($name);
    $name = trim($name);
    if (isset($this->tasks[$name])) {
      unset($this->tasks[$name]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTasksByModuleName($module_name) {
    $result = array();
    $this->validateName($module_name, 'module_name');
    $module_name = trim($module_name);
    foreach ($this->tasks as $task_name => $serialized) {
      $task = unserialize($serialized);
      if ($task->getModuleName() === $module_name) {
        $result[] = $task;
      }
    }
    return $result;
  }

  /**
   * Validates that the specified name makes sense.
   *
   * @param string $name
   *   The name.
   * @param string $parameter_name
   *   The name of the parameter.
   *
   * @throws \InvalidArgumentException
   *   If the name is empty or not a string.
   */
  private function validateName($name, $parameter_name = 'name') {
    if (!is_string($name) || trim($name) == FALSE) {
      throw new \InvalidArgumentException(sprintf('The "%s" parameter must be a non-empty string.', $parameter_name));
    }
  }

}
