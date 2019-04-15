<?php

namespace Acquia\WipService\Metrics;

use Acquia\Cromwell\RequestTimer;
use Acquia\Wip\WipFactory;
use Silex\Application;
use Silex\Route;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Teapot\StatusCode;

/**
 * Defines a service provider for the Cromwell service client.
 *
 * The Cromwell service is used for gathering timing data for HTTP requests.
 */
class CromwellProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(Application $app) {
    $app['cromwell.options'] = [];
    $app['cromwell'] = $app->share(function ($app) {
      $options = $app['cromwell.options'] = array_replace([
        'host' => '127.0.0.1',
        'environment' => 'production',
        'port' => 9090,
        'manual_flush' => TRUE,
      ], $app['cromwell.options']);

      if (!isset($options['product'])) {
        error_log('Must provide "product" in cromwell.options, not registering service');
        return;
      }

      return new CromwellIntegrationClient(
        $options['product'],
        $options['environment'],
        $options['host'],
        $options
      );
    });

    $app['cromwell.request_timer'] = $app->share(function ($app) {
      if ($app['cromwell']) {
        return new RequestTimer($app['cromwell']);
      }
    });

    $app->before(function (Request $request, Application $app) {
      if ($app['cromwell.request_timer']) {
        $app['cromwell.request_timer']->start();
      }
    }, Application::EARLY_EVENT);

    $app->finish(function (Request $request, Response $response, Application $app) {
      if ($app['cromwell']) {
        $timer = $app['cromwell.request_timer'];
        // Mark Sonnabaum's CromwellProvider uses RequestTimer's
        // routePatternFromSymphonyRoute function to parse out a metric name in
        // the format of "HTTP_METHOD request_uri". Wip-service's routes are set
        // up differently from what routePatternFromSymphonyRoute expects, so
        // using it will create metric names in the format of "ServiceName /"
        // instead. To make sure that Cromwell gets the expected metric format,
        // we get the HTTP method from $request object and route path from
        // route system and avoid using routePatternFromSymphonyRoute here.
        /** @var Route $route */
        $route = $app['routes']->get($request->get("_route"));
        if (isset($route)) {
          $route_path = $route->getPath();

          // Cromwell asks for dynamic path sections to be prefixed with:
          // * /v1/tasks/:id
          // * /v1/tasks/:id/pause
          // Silex params are wrapped in curly brackets /v1/tasks/{id}.
          $cromwell_path = str_replace(array('{', '}'), array(':', ''), $route_path);
          $route_pattern = sprintf('%s %s', $request->getMethod(), $cromwell_path);

          $timer->stop($route_pattern);
          $app['cromwell']->flush();
        } else {
          $log = WipFactory::getBool('$acquia.log_cromwell', TRUE);
          // We intercept OPTIONS request early, and don't have explict routes defined. Don't
          // log these requests in Cromwell. Tests also do not log errors.
          if ($log && $response->getStatusCode() !== StatusCode::NO_CONTENT) {
            error_log('Could not find route for this request.');
          }
        }
      }
    });
  }

  /**
   * {@inheritdoc}
   */
  public function boot(Application $app) {
  }

}
