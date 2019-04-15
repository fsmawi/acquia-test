<?php

namespace Acquia\WipService\Resource\v2;

use Acquia\WipService\Resource\v1\PingResource as PingResourceV1;
use Silex\Application;

/**
 * Provides a resource to check if the service is responding appropriately.
 */
class PingResource extends PingResourceV1 {

  /**
   * {@inheritdoc}
   */
  protected function generateResponse(Application $app) {
    $response = parent::generateResponse();
    $response['latest'] = TRUE;
    return $response;
  }

}
