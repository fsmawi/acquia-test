<?php

namespace Acquia\Wip\Exception;

/**
 * Defines an exception to provide information about a failed parse step.
 */
class WipParseException extends WipException {

  /**
   * The line number within the state table where the parse error occurred.
   *
   * @var int
   */
  private $stateTableLineNumber = 0;

  /**
   * Creates a new instance of WipParseException.
   *
   * @param string $message
   *   The exception message.
   * @param int $line_number
   *   The line number in the state table where the parse error occurred.
   * @param int $code
   *   Optional. The error code.
   * @param \Exception $previous
   *   Optional. The previous exception.
   */
  public function __construct($message, $line_number, $code = 0, \Exception $previous = NULL) {
    if (!is_int($line_number) || $line_number <= 0) {
      throw new \InvalidArgumentException(
        'The "line_number" argument must be an integer that is larger than 0.'
      );
    }
    $message = sprintf('%s on line %d', $message, $line_number);
    parent::__construct($message, $code, $previous);
    $this->stateTableLineNumber = $line_number;
  }

  /**
   * Returns the state table line number where the parse issue occurred.
   *
   * @return int
   *   The line number.
   */
  public function getStateTableLineNumber() {
    return $this->stateTableLineNumber;
  }

}
