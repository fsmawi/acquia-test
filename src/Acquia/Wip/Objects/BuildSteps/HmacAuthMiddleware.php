<?php

namespace Acquia\Wip\Objects\BuildSteps;

use Acquia\Hmac\Exception\MalformedResponseException;
use Acquia\Hmac\KeyInterface;
use Acquia\Hmac\RequestSigner;
use Acquia\Hmac\ResponseAuthenticator;
use GuzzleHttp\Exception\RequestException;
use Guzzle\Http\Exception\BadResponseException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Provides an Acquia HMAC v2 signature for Guzzle requests.
 *
 * This is a near clone of Acquia\Hmac\Guzzle\HmacAuthMiddleware that does not
 * throw exceptions for unsigned responses with a status of 400 or greater.
 * This is done to better expose connection problems in the system. The only
 * other changes are code style fixes per the ManySite team standards.
 */
class HmacAuthMiddleware {
  /**
   * Missing summary.
   *
   * @var \Acquia\Hmac\KeyInterface
   *  The key with which to sign requests and responses.
   */
  protected $key;

  /**
   * Missing summary.
   *
   * @var \Acquia\Hmac\RequestSignerInterface
   */
  protected $requestSigner;

  /**
   * Missing summary.
   *
   * @var array
   */
  protected $customHeaders = [];

  /**
   * Missing summary.
   *
   * @param \Acquia\Hmac\KeyInterface $key
   *   The key to sign with.
   * @param string $realm
   *   The realm.
   * @param array $customHeaders
   *   A list of custom signed headers.
   */
  public function __construct(KeyInterface $key, $realm = 'Acquia', array $customHeaders = []) {
    $this->key = $key;
    $this->customHeaders = $customHeaders;
    $this->requestSigner = new RequestSigner($key, $realm);
  }

  /**
   * Called when the middleware is handled.
   *
   * @param callable $handler
   *   The handler.
   *
   * @return \Closure
   *   The middleware.
   */
  public function __invoke(callable $handler) {
    return function ($request, array $options) use ($handler) {

      $request = $this->signRequest($request);

      $promise = function (ResponseInterface $response) use ($request) {

        $authenticator = new ResponseAuthenticator($request, $this->key);

        $response_valid = 'false';
        try {
          if ($response->getStatusCode() <= 400) {
            $authenticator->isAuthentic($response);
            $response_valid = 'true';
          }
        } catch (\Exception $e) {
          // Intentionally empty.
        }
        return $response->withHeader('X-SERVER-AUTHORIZATION-HMAC-VALID', $response_valid);
      };

      return $handler($request, $options)->then($promise);
    };
  }

  /**
   * Signs the request with the appropriate headers.
   *
   * @param \Psr\Http\Message\RequestInterface $request
   *   The request to sign.
   *
   * @return \Psr\Http\Message\RequestInterface
   *   The signed request.
   */
  public function signRequest(RequestInterface $request) {
    return $this->requestSigner->signRequest($request, $this->customHeaders);
  }

}
