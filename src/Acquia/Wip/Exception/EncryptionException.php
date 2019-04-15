<?php

namespace Acquia\Wip\Exception;

/**
 * Defines an exception type for Acquia Cloud API errors.
 */
class EncryptionException extends WipException {

  /**
   * Generic decryption error.
   */
  const TYPE_DECRYPTION_ERROR = 0;

  /**
   * Unauthorized decryption error.
   */
  const TYPE_DECRYPTION_NOT_AUTHORIZED = 1;

  /**
   * Outdated version decryption error.
   */
  const TYPE_DEPRECATED_VERSION = 2;

  /**
   * Maps types to messages.
   */
  const TYPE_MESSAGE = array(
    self::TYPE_DECRYPTION_ERROR => 'Decryption failed.',
    self::TYPE_DECRYPTION_NOT_AUTHORIZED => 'Decryption of this value is not authorized.',
    self::TYPE_DEPRECATED_VERSION =>
    'This version of encryption is no longer supported, please re-encrypt your values.',
  );

  /**
   * Indicates the nature of the exception.
   *
   * @var int
   */
  private $exceptionType;

  /**
   * Constructs an instance of the class.
   *
   * @param int $type
   *   (optional) The Exception type.
   * @param \Exception $previous
   *   (optional) The previous exception used for the exception chaining.
   */
  public function __construct($type = 0, \Exception $previous = NULL) {
    $this->exceptionType = $type;

    parent::__construct(self::TYPE_MESSAGE[$type], 0, $previous);
  }

  /**
   * Gets the exception type.
   *
   * @return int
   *   The type of exception.
   */
  public function getExceptionType() {
    return $this->exceptionType;
  }

}
