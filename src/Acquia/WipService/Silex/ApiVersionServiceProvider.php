<?php

namespace Acquia\WipService\Silex;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Registers the API version based on the incoming request.
 */
class ApiVersionServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function boot(Application $app) {}

  /**
   * {@inheritdoc}
   */
  public function register(Application $app) {
    $app->before(function (Request $request) use ($app) {
      $app['api.version'] = 'v1';
      // Set the API version from the first path fragment in the request URL. For
      // example, a request for GET /v2/tasks would result in an API version of v2.
      // Default to v1 so we don't break the contract with existing clients.
      preg_match('~/(v\d+)~', $request->getRequestUri(), $matches);
      if (!empty($matches[1])) {
        $app['api.version'] = $matches[1];
      }
    });
  }

}
