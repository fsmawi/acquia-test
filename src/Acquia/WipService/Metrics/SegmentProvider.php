<?php

namespace Acquia\WipService\Metrics;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines a service provider for the Segment service.
 */
class SegmentProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(Application $app) {
    $app['segment.options'] = [];
    $app['segment'] = $app->share(function ($app) {
      $options = $app['segment.options'] = array_replace([
        'sandbox' => TRUE,
        'project_key' => '',
        'environment' => '',
      ], $app['segment.options']);

      return new SegmentClient($options);
    });

    $app->finish(function (Request $request, Response $response, Application $app) {
      if ($app['segment']) {
        $app['segment']->flush();
      }
    });
  }

  /**
   * {@inheritdoc}
   */
  public function boot(Application $app) {
  }

}
