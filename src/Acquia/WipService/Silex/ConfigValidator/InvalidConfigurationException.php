<?php

namespace Acquia\WipService\Silex\ConfigValidator;

/**
 * Configuration exception that is thrown when the configuration is invalid.
 */
class InvalidConfigurationException extends \Exception {
  /**
   * The configuration option path, that helps to identify the location of the error.
   *
   * @var string
   */
  private $path;

  /**
   * Creates a new instance of InvalidConfigurationException.
   *
   * @param string $message
   *   The exception message to throw.
   * @param string $path
   *   The configuration option path, that helps to identify the location of the error.
   * @param int $code
   *   The exception code.
   * @param \Exception|null $previous
   *   The previous exception used for the exception chaining.
   */
  public function __construct($message = "", $path = "", $code = 0, \Exception $previous = NULL) {
    \Exception::__construct($message, $code, $previous);
    $this->setPath($path);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    if ('' === $this->getPath()) {
      return $this->getMessage();
    }

    return sprintf('%s (at %s)', $this->getMessage(), $this->getPath());
  }

  /**
   * Returns the configuration option path.
   *
   * @return string
   *   The configuration option path.
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * Set the configuration option path.
   *
   * @param string $path
   *   The configuration option path.
   */
  public function setPath($path) {
    $this->path = $path;
  }

}
