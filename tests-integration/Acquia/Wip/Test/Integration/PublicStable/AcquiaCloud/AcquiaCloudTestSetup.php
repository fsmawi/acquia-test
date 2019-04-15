<?php

namespace Acquia\Wip\Test\Integration\PublicStable\AcquiaCloud;

use Acquia\Cloud\Api\CloudApiClient;
use Acquia\Wip\AcquiaCloud\AcquiaCloud;
use Acquia\Wip\AcquiaCloud\CloudCredentials;
use Acquia\Wip\Environment;
use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\Implementation\SqliteWipLogStore;
use Acquia\Wip\Implementation\WipLog;

/**
 * Missing summary.
 */
class AcquiaCloudTestSetup {
  private static $hasWarned = FALSE;
  public static $environmentName = NULL;

  /**
   * Gets the cloud credentials from local environment variables.
   *
   * @return CloudCredentials
   *   The Cloud API credentials.
   */
  public static function getCreds() {
    $warn = FALSE;
    $realm = getenv('ACQUIA_CLOUD_REALM');
    if (empty($realm)) {
      $warn = TRUE;
    }
    $sitegroup = getenv('ACQUIA_CLOUD_SITEGROUP');
    if (empty($sitegroup)) {
      $warn = TRUE;
    }
    if (!empty($realm) && !empty($sitegroup)) {
      $sitegroup = sprintf('%s:%s', $realm, $sitegroup);
    }
    $environment = getenv('ACQUIA_CLOUD_ENVIRONMENT');
    if (empty($environment)) {
      $warn = TRUE;
    }
    $endpoint = getenv('ACQUIA_CLOUD_ENDPOINT');
    if (empty($endpoint)) {
      $warn = TRUE;
    }
    $user = getenv('ACQUIA_CLOUD_USER');
    if (empty($user)) {
      $warn = TRUE;
    }
    $password = getenv('ACQUIA_CLOUD_PASSWORD');
    if (empty($password)) {
      $warn = TRUE;
    }
    if ($warn && !self::$hasWarned) {
      $message = self::getEnvWarningMessage();
      printf($message);
      self::$hasWarned = TRUE;
    }
    $credentials = new CloudCredentials($endpoint, $user, $password, $sitegroup);
    return $credentials;
  }

  /**
   * Gets the message used to warn for missing ENV variables.
   *
   * @return string
   *   The message.
   */
  public static function getEnvWarningMessage() {
    return
      "NOTICE: The ACQUIA_CLOUD_SITEGROUP, ACQUIA_CLOUD_ENVIRONMENT, ACQUIA_CLOUD_ENDPOINT, ACQUIA_CLOUD_USER, ACQUIA_CLOUD_PASSWORD, and ACQUIA_CLOUD_REALM environment variables have to be set for successful tests.\n";
  }

  /**
   * Gets the configured realm for these tests.
   *
   * @return string
   *   The realm.
   */
  public static function getRealm() {
    $realm = getenv('ACQUIA_CLOUD_REALM');
    if (empty($realm)) {
      echo self::getEnvWarningMessage();
    }
    return $realm;
  }

  /**
   * Gets the configured sitegroup for these tests.
   *
   * @return string
   *   The sitegroup.
   */
  public static function getSitegroup() {
    $sitegroup = getenv('ACQUIA_CLOUD_SITEGROUP');
    if (empty($sitegroup)) {
      echo self::getEnvWarningMessage();
    }
    return $sitegroup;
  }

  /**
   * Missing summary.
   */
  public static function getEnvironment() {
    Environment::setRuntimeSitegroup('sitegroup');
    Environment::setRuntimeEnvironmentName('prod');
    if (empty($env)) {
      $env = new Environment();
    }
    $creds = self::getCreds();
    $env->setCloudCredentials($creds);
    // @todo - this has to change because the site group doesn't belong in the creds.
    $env->setSitegroup($creds->getSitegroup());
    $env->setEnvironmentName(self::getProductionEnvironmentName($env));
    $env->setServers(array('localhost'));
    $env->selectNextServer();
    return $env;
  }

  /**
   * Missing summary.
   */
  public static function getBadCredsEnvironment() {
    $env = self::getEnvironment();
    $creds = $env->getCloudCredentials();
    $wrong_password = $creds->getPassword() . 'x';
    $wrong_creds = new CloudCredentials(
      $creds->getEndpoint(),
      $creds->getUsername(),
      $wrong_password,
      $creds->getSitegroup()
    );
    $env->setCloudCredentials($wrong_creds);
    return $env;
  }

  /**
   * Missing summary.
   *
   * @param CloudCredentials $creds
   *   The Acquia Cloud credentials.
   *
   * @return CloudApiClient
   *   The client.
   */
  public static function createCloudClient(CloudCredentials $creds = NULL) {
    if (empty($creds)) {
      $creds = self::getCreds();
    }
    $array = array(
      'base_url' => $creds->getEndpoint(),
      'username' => $creds->getUsername(),
      'password' => $creds->getPassword(),
    );
    $client = CloudApiClient::factory($array);
    $client->setSslVerification(FALSE, FALSE);
    return $client;
  }

  /**
   * Missing summary.
   */
  public static function createWipLog() {
    return new WipLog(new SqliteWipLogStore());
  }

  /**
   * Gets the environment name for the production environment.
   *
   * @param EnvironmentInterface $environment
   *   The environment.
   *
   * @return string
   *   The environment name.
   */
  public static function getProductionEnvironmentName(EnvironmentInterface $environment) {
    if (empty(self::$environmentName)) {
      $wip_id = mt_rand(1, PHP_INT_MAX);
      $cloud = new AcquiaCloud($environment, self::createWipLog(), $wip_id);
      $environment_result = $cloud->listEnvironments();
      if ($environment_result->isSuccess()) {
        $environments = $environment_result->getData();
        if (count($environments) > 0) {
          self::$environmentName = $environments[0]->getName();
        }
      } else {
        // This can happen if there is no active network connection.
        self::$environmentName = 'no_network';
      }
    }
    return self::$environmentName;
  }

}
