<?php

namespace Acquia\Wip\Exception;

use Acquia\Wip\AcquiaCloud\AcquiaCloudResult;

/**
 * Defines an exception type for Acquia Cloud API errors.
 */
class AcquiaCloudApiException extends WipException {

  /**
   * An associative array of error codes and their corresponding messages.
   *
   * These error codes represent failures that are within the customer's
   * control.
   *
   * @var array
   */
  private static $customerFacingErrorCodes = array(
    401 => 'Authentication failed. Please make sure that you have logged in with the correct Acquia Cloud credentials and that you have access to the site that you are trying to build.',
    429 => 'The rate limit has been exceeded for your Acquia Cloud account. Please try again in a few minutes. If the problem persists, please contact support.',
  );

  /**
   * The default exception string.
   *
   * Applied if there are no customer-facing display messages set for the
   * specific type of error received from Cloud API.
   *
   * @var string
   */
  private static $defaultExceptionMessage = 'An error has occurred while trying to reach the Acquia Cloud API. Please try again in a few minutes. If the problem persists, please contact support.';

  /**
   * Constructs an instance of the class.
   *
   * If no arguments are given, a default exception message will be used. If
   * a message is given, no customer-facing messages will be used even if a
   * response with a customer-facing error code is provided.
   *
   * @param AcquiaCloudResult $result
   *   (optional) The result object from the failed request.
   * @param string $message
   *   (optional) The Exception message to throw.
   * @param int $code
   *   (optional) The Exception code.
   * @param \Exception $previous
   *   (optional) The previous exception used for the exception chaining.
   */
  public function __construct(AcquiaCloudResult $result = NULL, $message = '', $code = 0, \Exception $previous = NULL) {
    $exception_message = sprintf("\n%s", self::$defaultExceptionMessage);

    if (!empty($message)) {
      $exception_message = sprintf("\n%s", $message);
    } else {
      if (!is_null($result)) {
        $exit_code = $result->getExitCode();

        if (array_key_exists($exit_code, self::$customerFacingErrorCodes)) {
          $exception_message = sprintf("\n%s", self::$customerFacingErrorCodes[$exit_code]);
        }
      }
    }

    parent::__construct($exception_message, $code, $previous);
  }

  /**
   * Returns the associative array of error codes and their display messages.
   *
   * @return array
   *   The associative array of error codes and their corresponding display
   *   messages.
   */
  public static function getCustomerFacingErrorCodes() {
    return self::$customerFacingErrorCodes;
  }

  /**
   * Returns the default exception message.
   *
   * @return string
   *   The default exception message.
   */
  public static function getDefaultExceptionMessage() {
    return self::$defaultExceptionMessage;
  }

}
