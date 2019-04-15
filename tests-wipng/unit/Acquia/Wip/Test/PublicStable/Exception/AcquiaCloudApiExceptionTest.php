<?php

namespace Acquia\Wip\Test\PublicStable\Exception;

use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudStringArrayResult;
use Acquia\Wip\Exception\AcquiaCloudApiException;

/**
 * Missing summary.
 */
class AcquiaCloudApiExceptionTest extends \PHPUnit_Framework_TestCase {

  /**
   * Tests that the default message is returned by default.
   *
   * @group Exceptions
   */
  public function testDefaultMessage() {
    $exception = new AcquiaCloudApiException();
    $this->assertEquals(AcquiaCloudApiException::getDefaultExceptionMessage(), trim($exception->getMessage()));
  }

  /**
   * Tests that customer-facing errors trigger the correct exception messages.
   *
   * A specific exception message should only be provided if the failure
   * response used to construct the exception contains a customer-facing
   * error code.
   *
   * @group Exceptions
   */
  public function testConstructorWithCustomerFacingCode() {
    $exit_code_mapping = AcquiaCloudApiException::getCustomerFacingErrorCodes();
    foreach ($exit_code_mapping as $exit_code => $message) {
      $failure_response = $this->getResponse($exit_code);

      $exception = new AcquiaCloudApiException($failure_response);
      $this->assertEquals($message, trim($exception->getMessage()));
    }
  }

  /**
   * Tests that non-customer-facing errors trigger the default message.
   *
   * The default exception message should be provided if the failure response
   * used to construct the exception does not contain a customer-facing error
   * code.
   *
   * @param int $exit_code
   *   The exit code to test.
   *
   * @dataProvider nonCustomerFacingErrorCodeProvider
   *
   * @group Exceptions
   */
  public function testConstructorWithoutCustomerFacingCode($exit_code) {
    $failure_response = $this->getResponse($exit_code);

    $exception = new AcquiaCloudApiException($failure_response);
    $this->assertEquals(AcquiaCloudApiException::getDefaultExceptionMessage(), trim($exception->getMessage()));
  }

  /**
   * Tests that the default exception message can be overridden.
   *
   * The default exception message should be overridden if an override
   * message is passed into the exception's constructor.
   *
   * @group Exceptions
   */
  public function testConstructorWithMessage() {
    $failure_response = $this->getResponse(500);

    $exception = new AcquiaCloudApiException($failure_response, 'Override message.');
    $this->assertEquals("Override message.", trim($exception->getMessage()));
  }

  /**
   * Creates a Response object.
   *
   * @param int $exit_code
   *   (optional) The exit code.
   *
   * @return AcquiaCloudStringArrayResult
   *   The Response object.
   */
  private function getResponse($exit_code = 500) {
    $failure_response = new AcquiaCloudStringArrayResult();
    $failure_response->setExitCode($exit_code);

    return $failure_response;
  }

  /**
   * Provides non-customer-facing error codes for testing.
   *
   * @return array
   *   The data for testing.
   */
  public function nonCustomerFacingErrorCodeProvider() {
    return array(
      array(500),
      array(111),
      array(0),
      array(418),
    );
  }

}
