<?php

namespace Acquia\WipService\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Defined an exception type for validation errors during HTTP requests.
 */
class ValidationErrorException extends HttpException {

  /**
   * An array of violations that occurred during the request.
   *
   * @var array
   */
  private $violations = array();

  /**
   * Creates a new instance of ValidationErrorException.
   *
   * @param string $message
   *   The error message.
   * @param array $all_violations
   *   An array of violations that occurred during validation.
   * @param \Exception $previous
   *   The previous exception.
   * @param int $code
   *   The internal exception code.
   */
  public function __construct(
    $message,
    array $all_violations,
    $status_code = 400,
    \Exception $previous = NULL,
    array $headers = array(),
    $code = 0
  ) {
    foreach ($all_violations as $violations) {
      foreach ($violations->getIterator() as $violation) {
        $this->violations[] = $violation->getMessage();
      }
    }
    parent::__construct($status_code, $message, $previous, $headers, $code);
  }

  /**
   * Gets the list of violation messages.
   *
   * @return array
   *   An array of violation message strings.
   */
  public function getViolations() {
    return $this->violations;
  }

}
