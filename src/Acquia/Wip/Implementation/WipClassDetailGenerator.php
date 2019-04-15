<?php

namespace Acquia\Wip\Implementation;

use Acquia\Wip\WipInterface;

/**
 * Class WipClassDetailGenerator helps to detect wip version changes.
 *
 * @package Acquia\Wip\Implementation
 */
class WipClassDetailGenerator {

  /**
   * The class name of the class being inspected.
   *
   * @var string
   */
  private $className = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct($class_name) {
    $this->className = $class_name;
  }

  /**
   * Generates class detail.
   *
   * @return string
   *   The class detail of the associated Wip instance.
   */
  public function generate() {
    $result = array();
    if (class_exists($this->className)) {
      $wip = new $this->className();

      if (!$wip instanceof WipInterface) {
        throw new \InvalidArgumentException(sprintf('The "%s" class is not a Wip class.', $this->className));
      }
    } else {
      throw new \InvalidArgumentException(sprintf('The "%s" class does not exist.', $this->className));
    }

    $class = new \ReflectionClass($wip);
    $properties = $class->getProperties();

    $result[] = "Properties:\n----------------------------------------";
    foreach ($properties as $property) {
      $result[] = sprintf("%s: %s", $property->getName(), $this->getAccessLabel($property));
    }

    // Sorting the property information means that simply moving the instance
    // variables around in the file will not trigger a version change warning.
    sort($result);

    $result[] = '';
    $result[] = "State table:\n----------------------------------------";
    $result[] = $wip->getStateTable();
    return $result;
  }

  /**
   * Finds the access level of a property.
   *
   * @param \ReflectionProperty $property
   *   The ReflectionProperty object.
   *
   * @return string
   *   The access label.
   */
  private function getAccessLabel(\ReflectionProperty $property) {
    if ($property->isPrivate()) {
      $result = 'private';
    } elseif ($property->isProtected()) {
      $result = 'protected';
    } elseif ($property->isPublic()) {
      $result = 'public';
    }
    if ($property->isStatic()) {
      $result .= ' static';
    }
    return $result;
  }

}
