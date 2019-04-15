<?php

namespace Acquia\Wip;

use Acquia\Wip\Exception\DependencyMissingException;
use Acquia\Wip\Exception\DependencyTypeException;

/**
 * The DependencyManager is an implementation of DependencyManagerInterface.
 */
class DependencyManager implements \Serializable, DependencyManagerInterface {

  /**
   * The dependencies.
   *
   * @var array
   */
  protected $dependencies = array();

  /**
   * The internal dependencies.
   *
   * @var array
   */
  protected $spec = array();

  /**
   * The names of dependencies that have already been added.
   *
   * @var string[]
   */
  protected static $visitedNames = array();

  /**
   * Clears the visited names.
   */
  public function reset() {
    self::$visitedNames = array();
  }

  /**
   * {@inheritdoc}
   */
  public function addDependencies(array $spec) {
    $this->spec = $spec;
    $this->dependencies = array();

    foreach ($this->spec as $name => $type) {
      if (!in_array($name, self::$visitedNames)) {
        $obj = WipFactory::getObject($name);
        if (!$this->checkDependency($obj, $type)) {
          throw new DependencyTypeException(
            sprintf('Dependency %s is not of expected type %s. The actual type is %s', $name, $type, get_class($obj))
          );
        }
        self::$visitedNames[] = $name;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkDependency($instance, $type) {
    if (empty($type)) {
      return TRUE;
    }
    return $instance instanceof $type;
  }

  /**
   * {@inheritdoc}
   */
  public function swapDependency($name, $object) {
    if (!isset($this->spec[$name])) {
      $message = 'Unable to swap missing dependency %s. Ensure it is included in getDependencies() in your class, and that you have called addDependencies().';
      throw new DependencyMissingException(sprintf($message, $name));
    }

    $type = $this->spec[$name];

    if (!$this->checkDependency($object, $type)) {
      $message = 'Dependency %s is not of expected type %s. The actual type is %s';
      throw new DependencyTypeException(
        sprintf($message, $name, $type, get_class($object))
      );
    }

    $this->dependencies[$name] = $object;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependency($name) {
    if (!empty($this->dependencies[$name])) {
      $result = $this->dependencies[$name];
    } else {
      try {
        $result = WipFactory::getObject($name);
      } catch (\Exception $e) {
        throw new DependencyMissingException(
          sprintf('Unable to obtain missing dependency %s', $name)
        );
      }
    }
    return $result;
  }

  /**
   * Called when this object is serialized.
   */
  public function serialize() {
    // The only thing that we can safely serialize is the dependencies spec.
    return serialize($this->spec);
  }

  /**
   * Called when this object is unserialized.
   *
   * @param string $serialized
   *   Serialized PHP data.
   */
  public function unserialize($serialized) {
    // Rebuild the dependencies from the spec on unserialize.
    $this->spec = unserialize($serialized);
    $this->addDependencies($this->spec);
  }

}
