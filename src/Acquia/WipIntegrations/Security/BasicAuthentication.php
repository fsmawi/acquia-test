<?php

namespace Acquia\WipIntegrations\Security;

use Acquia\WipService\App;
use Acquia\Wip\Security\SimpleAuthentication;

/**
 * Defined an authentication implementation for HTTP Basic Authentication.
 */
class BasicAuthentication extends SimpleAuthentication {

  /**
   * {@inheritdoc}
   */
  public function getAccountId() {
    $user = parent::getAccountId();
    if (empty($user)) {
      $user = getenv('WIP_SERVICE_USERNAME');
      if (empty($user)) {
        $app = App::getApp();
        $user = $app['security.client_users']['ROLE_ADMIN']['username'];
      }
    }
    return $user;
  }

  /**
   * {@inheritdoc}
   */
  public function getSecret() {
    $secret = parent::getSecret();
    if (empty($secret)) {
      $secret = getenv('WIP_SERVICE_PASSWORD');
      if (empty($secret)) {
        $app = App::getApp();
        $secret = $app['security.client_users']['ROLE_ADMIN']['password'];
      }
    }
    return $secret;
  }

}
