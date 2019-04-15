<?php

namespace Acquia\WipService\Runtime;

use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\DependencyManager;
use Acquia\Wip\Storage\StateStoreInterface;

/**
 * A class for controlling persistent state values.
 */
class StateController implements DependencyManagedInterface {
  const KEY_WIP_APPLICATION_MAINTENANCE = 'wip.application.maintenance';
  const MODE_NORMAL_OPERATION = 0;
  const MODE_MAINTENANCE_FULL = 1;

  /** @var DependencyManager*/
  protected $dependencyManager;

  /**
   * Initializes this instance of WipPoolController.
   */
  public function __construct() {
    $this->dependencyManager = new DependencyManager();
    $dependencies = $this->getDependencies();
    if (!empty($dependencies)) {
      $this->dependencyManager->addDependencies($dependencies);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.storage.state' => '\Acquia\Wip\Storage\StateStoreInterface',
    );
  }

  /**
   * Gets the variable storage.
   *
   * @return StateStoreInterface
   *   The variable storage.
   */
  private function getStorage() {
    $result = $this->dependencyManager->getDependency('acquia.wip.storage.state');
    if (!$result instanceof StateStoreInterface) {
      throw new \DomainException(
        'Configuration error - the "acquia.wip.storage.state" resource should be of type StateStoreInterface.'
      );
    }
    return $result;
  }

  /**
   * Checks if the given mode string is a valid server mode.
   *
   * @param string $mode
   *   The mode to check.
   *
   * @return bool
   *   The validation result.
   */
  public static function isValidServerModeString($mode) {
    if (!is_string($mode)) {
      throw new \DomainException(
        sprintf("The server mode must be a string, %s given.", gettype($mode))
      );
    }
    return defined('self::MODE_' . $mode);
  }

  /**
   * Checks if the given value is a valid server mode.
   *
   * @param int $value
   *   The value to check.
   *
   * @return bool
   *   The validation result.
   */
  public static function isValidServerModeValue($value) {
    if (!is_int($value)) {
      throw new \DomainException(
        sprintf("The server mode value must be an integer, %s given.", gettype($value))
      );
    }

    $class = new \ReflectionClass(__CLASS__);
    $constants = $class->getConstants();

    return in_array($value, $constants);
  }

  /**
   * Translates the given server mode value to the human-readable constant name, with or without its prefix.
   *
   * @param int $value
   *   Server mode value.
   * @param bool $trim_prefix
   *   Trim the MODE_ constant prefix.
   *
   * @return string|null
   *   The corresponding constant name.
   */
  public static function getServerModeString($value, $trim_prefix = TRUE) {
    if (!is_int($value)) {
      throw new \DomainException(
        sprintf("The server mode value must be an integer, %s given.", gettype($value))
      );
    }
    if (!is_bool($trim_prefix)) {
      throw new \DomainException(
        sprintf("The trim_prefix parameter must be boolean, %s given.", gettype($trim_prefix))
      );
    }
    $class = new \ReflectionClass(__CLASS__);
    $constants = array_flip($class->getConstants());

    $result = NULL;
    if (isset($constants[$value])) {
      $result = $constants[$value];
      if ($trim_prefix) {
        $result = substr($result, 5); // Trim the MODE_ prefix.
      }
    }

    return $result;
  }

  /**
   * Get the current state of the given state name.
   *
   * @param string $name
   *   The state name to get.
   * @param mixed $default_value
   *   The default value to return, if there is no state persisted for the given name.
   *
   * @return mixed|null
   *   The state.
   */
  public function getState($name, $default_value = NULL) {
    if (!is_string($name)) {
      throw new \DomainException(
        sprintf("The name must be a string, %s given.", gettype($name))
      );
    }
    $result = $this->getStorage()->get($name);
    if ($result === NULL) {
      $result = $default_value;
    }
    return $result;
  }

  /**
   * Set the new value for the given state name.
   *
   * @param string $name
   *   The state name to set.
   * @param mixed $value
   *   The value to set.
   */
  public function setState($name, $value) {
    if (!is_string($name)) {
      throw new \DomainException(
        sprintf("The name must be a string, %s given.", gettype($name))
      );
    }
    $this->getStorage()->set($name, $value);
  }

  /**
   * Delete the saved state of the given name.
   *
   * @param string $name
   *   The state name.
   */
  public function deleteState($name) {
    if (!is_string($name)) {
      throw new \DomainException(
        sprintf("The name must be a string, %s given.", gettype($name))
      );
    }
    $this->getStorage()->delete($name);
  }

}
