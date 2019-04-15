<?php

namespace Acquia\WipService\Exception;

use Acquia\WipService\Http\HalResponse;
use Nocarrier\Hal;
use Teapot\StatusCode;

/**
 * Exception that provides a HalResponse.
 */
class ResponseProviderException extends \Exception {
  private $responseData;
  private $responseStatus;
  private $responseHeaders;

  /**
   * Creates a new instance of ResponseGeneratorException.
   *
   * @param string $message
   *   The exception message to throw.
   * @param int $code
   *   The exception code.
   * @param Hal $response_data
   *   The Hal response data.
   * @param int $response_status
   *   The HTTP response status code.
   * @param array $response_headers
   *   The HTTP response headers.
   * @param \Exception|null $previous
   *   The previous exception in the chain.
   */
  public function __construct(
    $message = "",
    $code = 0,
    Hal $response_data = NULL,
    $response_status = StatusCode::INTERNAL_SERVER_ERROR,
    array $response_headers = array(),
    \Exception $previous = NULL
  ) {
    \Exception::__construct($message, $code, $previous);
    $this->responseData = $response_data;
    $this->responseStatus = $response_status;
    $this->responseHeaders = $response_headers;
  }

  /**
   * Returns the Hal response.
   *
   * @return \Acquia\WipService\Http\HalResponse
   *   The Hal response.
   */
  public function getResponse() {
    return new HalResponse($this->responseData, $this->responseStatus, $this->responseHeaders);
  }

  /**
   * Returns the response data.
   *
   * @return \Nocarrier\Hal
   *   The response data.
   */
  public function getResponseData() {
    return $this->responseData;
  }

  /**
   * Set the response data.
   *
   * @param \Nocarrier\Hal|null $response_data
   *   The response data.
   */
  public function setResponseData($response_data) {
    $this->responseData = $response_data;
  }

  /**
   * Returns the response status.
   *
   * @return int
   *   The response status.
   */
  public function getResponseStatus() {
    return $this->responseStatus;
  }

  /**
   * Set the response status.
   *
   * @param int $response_status
   *   The response status.
   */
  public function setResponseStatus($response_status) {
    $this->responseStatus = $response_status;
  }

  /**
   * Returns the response headers.
   *
   * @return array
   *   The response headers.
   */
  public function getResponseHeaders() {
    return $this->responseHeaders;
  }

  /**
   * Set the response headers.
   *
   * @param array $response_headers
   *   The response headers.
   */
  public function setResponseHeaders($response_headers) {
    $this->responseHeaders = $response_headers;
  }

}
