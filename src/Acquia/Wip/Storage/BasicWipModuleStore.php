<?php

namespace Acquia\Wip\Storage;

use Acquia\Wip\WipModule;
use Acquia\Wip\WipModuleInterface;

/**
 * Provides a base class to test Module storage.
 *
 * @copydetails WipModuleStoreInterface
 */
class BasicWipModuleStore implements WipModuleStoreInterface {

  /**
   * Storage implementation as an array.
   *
   * @var WipModule[]
   */
  private $modules = array();

  /**
   * {@inheritdoc}
   */
  public function get($name) {
    $this->validateName($name);
    $name = trim($name);
    return isset($this->modules[$name]) ? unserialize($this->modules[$name]) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function save(WipModuleInterface $module) {
    $this->modules[$module->getName()] = serialize($module);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($name) {
    $this->validateName($name);
    $name = trim($name);
    if (isset($this->modules[$name])) {
      unset($this->modules[$name]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getByTaskName($name) {
    $this->validateName($name);
    $name = trim($name);
    $result = NULL;
    foreach ($this->modules as $module_name => $serialized) {
      /** @var WipModuleInterface $module */
      $module = unserialize($serialized);
      $task = $module->getTask($name);
      if (NULL !== $task) {
        $result = $module;
        break;
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getByEnabled($enabled) {
    if (!is_bool($enabled)) {
      throw new \InvalidArgumentException('The "enabled" parameter must be a boolean.');
    }

    $enabled_modules = array();
    foreach ($this->modules as $module_name => $serialized) {
      $module = unserialize($serialized);
      if ($module->isEnabled() === $enabled) {
        $enabled_modules[] = $module;
      }
    }

    return $enabled_modules;
  }

  /**
   * {@inheritdoc}
   */
  public function getByReady($ready) {
    if (!is_bool($ready)) {
      throw new \InvalidArgumentException('The "ready" parameter must be a boolean.');
    }

    $ready_modules = array();
    foreach ($this->modules as $module_name => $serialized) {
      $module = unserialize($serialized);
      if ($module->isReady() === $ready) {
        $ready_modules[] = $module;
      }
    }

    return $ready_modules;
  }

  /**
   * Validates that the specified name makes sense.
   *
   * @param string $name
   *   The name.
   *
   * @throws \InvalidArgumentException
   *   If the name is empty or not a string.
   */
  private function validateName($name) {
    if (!is_string($name) || trim($name) == FALSE) {
      throw new \InvalidArgumentException('The "name" parameter must be a non-empty string.');
    }
  }

}
