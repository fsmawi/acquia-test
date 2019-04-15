<?php

namespace Acquia\Wip\Implementation;

/**
 * Encapsulates version information for a Wip instance or class.
 */
class WipVersionElement {

  /**
   * The fully-qualified class name.
   *
   * @var string
   */
  private $className = NULL;

  /**
   * The short class name.
   *
   * @var string
   */
  private $shortClassName = NULL;

  /**
   * The version associated with the class.
   *
   * @var int
   */
  private $versionNumber = 0;

  /**
   * Initializes a new instance with the specified properties.
   *
   * @param string $class_name
   *   The fully-qualified class name.
   * @param int $version
   *   The version associated with the class.
   */
  public function __construct($class_name, $version) {
    $this->setClassName($class_name);
    $this->setVersionNumber($version);
  }

  /**
   * Sets the class name for this version element.
   *
   * @param string $class_name
   *   The fully-qualified class name.
   */
  public function setClassName($class_name) {
    if (!is_string($class_name) || empty($class_name)) {
      throw new \InvalidArgumentException('The "class_name" parameter must be a non-empty string.');
    }
    $class = new \ReflectionClass($class_name);
    if (FALSE === $class) {
      throw new \InvalidArgumentException(sprintf('Failed to find class "%s".', $class_name));
    }
    $required_interface = 'Acquia\Wip\WipInterface';
    if (!class_implements($class_name, $required_interface)) {
      throw new \InvalidArgumentException(
        sprintf(
          'The class "%s" does not implement required interface "%s".',
          $class_name,
          $required_interface
        )
      );
    }

    $this->className = $class->getName();
    $this->shortClassName = $class->getShortName();
  }

  /**
   * Gets the fully-qualified class name.
   *
   * @return string
   *   The class name.
   */
  public function getClassName() {
    return $this->className;
  }

  /**
   * Gets the short class name.
   *
   * @return string
   *   The class name.
   */
  public function getShortClassName() {
    return $this->shortClassName;
  }

  /**
   * Sets the version.
   *
   * @param int $version
   *   The version.
   */
  public function setVersionNumber($version) {
    if (!is_int($version) || $version <= 0) {
      throw new \InvalidArgumentException('The "version" parameter must be a non-zero positive integer.');
    }
    $this->versionNumber = $version;
  }

  /**
   * Gets the version.
   *
   * @return int
   *   The version.
   */
  public function getVersionNumber() {
    return $this->versionNumber;
  }

  /**
   * Indicates whether this version element matches the specified class.
   *
   * @param string $class_name
   *   The fully-qualified or short class name.
   *
   * @return bool
   *   TRUE if this version element matches the class; FALSE otherwise.
   */
  public function matchesClass($class_name) {
    $result = FALSE;
    if ($class_name === $this->getClassName() || $class_name === $this->getShortClassName()) {
      $result = TRUE;
    }
    return $result;
  }

}
