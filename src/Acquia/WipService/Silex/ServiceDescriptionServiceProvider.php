<?php

namespace Acquia\WipService\Silex;

use Silex\Application;

/**
 * Registers service descriptions for each supported API version.
 */
class ServiceDescriptionServiceProvider extends ValidatedConfigServiceProvider {

  /**
   * {@inheritdoc}
   */
  public function register(Application $app) {
    $config = $this->readConfig();
    $this->validateConfig($config);
    $config = array(
      'api.versions' => array(
        $config['apiVersion'] => $config,
      ),
    );
    $this->merge($app, $config);
  }

}
