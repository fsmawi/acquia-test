<?php

namespace Acquia\WipService\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Defines an HTTP exception type for 500 Internal Server Error.
 */
class InternalServerErrorException extends HttpException {

  /**
   * Creates a new instance of InternalServerErrorException.
   *
   * @param string $message
   *   The error message.
   * @param \Exception $previous
   *   Any previous exception that caused this one to be thrown.
   * @param array $headers
   *   An array of HTTP headers.
   * @param int $code
   *   The exception code.
   */
  public function __construct($message = NULL, \Exception $previous = NULL, array $headers = array(), $code = 0) {
    parent::__construct(500, $message, $previous, $headers, $code);
  }

}
