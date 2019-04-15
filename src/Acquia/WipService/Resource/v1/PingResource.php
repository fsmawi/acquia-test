<?php

namespace Acquia\WipService\Resource\v1;

use Acquia\WipService\Http\HalResponse;
use Acquia\WipService\Resource\AbstractResource;
use Acquia\Wip\WipLogLevel;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a resource to check if the service is responding appropriately.
 */
class PingResource extends AbstractResource {

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array();
  }

  /**
   * Checks if the service is responding appropriately.
   *
   * @param Request $request
   *   An instance of Request representing the incoming HTTP request.
   * @param Application $app
   *   The application instance.
   *
   * @return HalResponse
   *   The Hal response instance.
   */
  public function getAction(Request $request, Application $app) {
    $this->wipLog->log(WipLogLevel::INFO, sprintf(
      'Received ping request from %s as user %s',
      $request->getClientIp(),
      $request->getUser()
    ));
    $app['segment']->track([
      'userId' => $request->getUser(),
      'event' => 'Ping',
      'context' => [
        'ip' => $request->getClientIp(),
        'userAgent' => $request->headers->get('User-Agent'),
      ],
    ]);

    // @todo Do we need any basic status checks here?
    $response = $this->generateResponse();
    return new HalResponse($app['hal']($request->getUri(), $response));
  }

  /**
   * Generates the response.
   *
   * @return array
   *   The response body.
   */
  protected function generateResponse() {
    return array(
      'server_time' => time(),
      'api_version' => $this->getApiVersion(),
      'latest' => FALSE,
    );
  }

}
