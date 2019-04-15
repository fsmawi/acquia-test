<?php

namespace Acquia\WipService\Console;

use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Defines a service provider for the Wip console application.
 */
class WipConsoleServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(Application $app) {
    $app['console'] = function () use ($app) {
      return new WipConsoleApplication(
        $app,
        $app['console.app_directory'],
        $app['console.name'],
        $app['console.version']
      );
    };
  }

  /**
   * {@inheritdoc}
   */
  public function boot(Application $app) {
  }

}
