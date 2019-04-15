<?php

namespace Acquia\WipService\Symfony\Component\HttpKernel\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * HTTP exception that is thrown when a service is unavailable.
 */
class ServiceUnavailableHttpException extends HttpException {

  /**
   * Creates a new instance of ServiceUnavailableHttpException.
   *
   * @param array $headers
   *   The HTTP headers to set.
   * @param string|null $message
   *   The message to send.
   * @param \Exception|null $previous
   *   The previous exception used for the exception chaining.
   * @param int $code
   *   The exception code.
   */
  public function __construct($headers = array(), $message = NULL, \Exception $previous = NULL, $code = 0) {
    parent::__construct(503, $message, $previous, $headers, $code);
  }

}
