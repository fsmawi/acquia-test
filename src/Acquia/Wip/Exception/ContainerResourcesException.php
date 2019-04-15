<?php

namespace Acquia\Wip\Exception;

/**
 * Defines an exception to handle a lack of container resources.
 */
class ContainerResourcesException extends WipException {

  /**
   * The resource type.
   *
   * @var string
   */
  protected $type;

  /**
   * Returns the type of resource that we are wating on.
   *
   * @return string
   *   The resource type.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Constructs an instance of the class.
   *
   * @param string $type
   *   The type of resource that we are waiting on.
   * @param string $message
   *   (optional) The Exception message to throw.
   * @param int $code
   *   (optional) The Exception code.
   * @param \Exception $previous
   *   (optional) The previous exception used for the exception chaining.
   */
  public function __construct($type, $message = '', $code = 0, Exception $previous = NULL) {
    $this->type = $type;

    if (empty($message)) {
      $message = sprintf('Waiting for ECS resources: %s', $type);
    }

    parent::__construct($message, $code, $previous);
  }

}
