<?php

namespace Acquia\Wip;

use Acquia\Wip\AcquiaCloud\AcquiaCloud;
use Acquia\Wip\AcquiaCloud\CloudCredentials;
use Acquia\Wip\Exception\InvalidEnvironmentException;
use Acquia\Wip\Objects\ParameterConverterInterface;
use Acquia\Wip\Objects\Site;
use Acquia\Wip\Objects\SiteGroup;
use Acquia\Wip\Security\EncryptTrait;

/**
 * The Environment class encapsulates sitegroup and environment data.
 */
class Environment implements EnvironmentInterface, ParameterConverterInterface {

  use EncryptTrait;

  /**
   * The hosting sitegroup.
   *
   * @var string
   */
  private $sitegroup = NULL;

  /**
   * The realm associated with the hosting sitegroup.
   *
   * @var string
   */
  private $realm = NULL;

  /**
   * The hosting environment name.
   *
   * @var string
   */
  protected $environmentName = NULL;

  /**
   * The servers associated with the hosting sitegroup and environment.
   *
   * @var string[]
   */
  protected $servers = NULL;

  /**
   * The current server associated with this Environment instance.
   *
   * @var string
   */
  protected $currentServer = NULL;

  /**
   * The set of servers that have already been selected.
   *
   * This will record all servers that have been selected. Once all of the
   * servers have been selected in this Environment instance, subsequent calls
   * to selectNextServer will result in the same server list being repeated in
   * the same order to spread the workload across servers randomly rather than
   * favoring one particular server.
   *
   * @var string[]
   */
  protected $selectedServers = array();

  /**
   * This static value is used to force the sitegroup setting.
   *
   * Used during unit tests that run outside of a standard hosting environment.
   *
   * @var string
   */
  protected static $runtimeSitegroup = NULL;

  /**
   * This static value is used to force the environment name setting.
   *
   * Used during unit tests that run outside of a standard hosting environment.
   *
   * @var string
   */
  protected static $runtimeEnvironmentName = NULL;

  /**
   * The absolute path to the docroot.
   *
   * @var string
   */
  protected $docrootDir = NULL;

  /**
   * The absolute path to the working directory.
   *
   * @var string
   */
  protected $workingDir = NULL;

  /**
   * An array of sites on this environment.
   *
   * @var Site[]
   */
  protected $sites = array();

  /**
   * The cloud API credentials.
   *
   * @var CloudCredentials
   */
  private $cloudCredentials;

  /**
   * The user that is used to SSH into the host.
   *
   * @var string
   */
  private $user = NULL;

  /**
   * The password that is used to SSH into the host.
   *
   * @var string
   */
  private $securePassword = NULL;

  /**
   * The port number used for SSH connections to the host.
   *
   * @var int
   */
  private $port = 22;

  /**
   * {@inheritdoc}
   */
  public function getSitegroup() {
    return $this->sitegroup;
  }

  /**
   * {@inheritdoc}
   */
  public function setSitegroup($sitegroup) {
    if (!is_string($sitegroup) || empty($sitegroup)) {
      throw new \InvalidArgumentException('The "sitegroup" parameter must be a non-empty string.');
    }

    $sitegroup_obj = SiteGroup::separateSitegroupName($sitegroup);
    if (!empty($sitegroup_obj->realm)) {
      $this->setRealm($sitegroup_obj->realm);
    }
    $this->sitegroup = $sitegroup_obj->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getRealm() {
    return $this->realm;
  }

  /**
   * {@inheritdoc}
   */
  public function setRealm($realm) {
    if (!is_string($realm) || empty($realm)) {
      throw new \InvalidArgumentException('The "realm" parameter must be a non-empty string.');
    }
    $this->realm = $realm;
  }

  /**
   * {@inheritdoc}
   */
  public function getFullyQualifiedSitegroup() {
    $result = $this->getSitegroup();
    $realm = $this->getRealm();
    if (!empty($realm)) {
      $result = sprintf("%s:%s", $realm, $result);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnvironmentName() {
    return $this->environmentName;
  }

  /**
   * {@inheritdoc}
   */
  public function setEnvironmentName($environment_name) {
    if (!is_string($environment_name) || empty($environment_name)) {
      throw new \InvalidArgumentException('The "environment_name" parameter must be a non-empty string.');
    }
    $this->environmentName = $environment_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getServers() {
    return $this->servers;
  }

  /**
   * {@inheritdoc}
   */
  public function setServers($servers) {
    if (!is_array($servers) || empty($servers)) {
      throw new \InvalidArgumentException('The "servers" parameter must be non-empty array of strings.');
    }
    foreach ($servers as $server) {
      if (!is_string($server) || empty($server)) {
        throw new \InvalidArgumentException('Each server name in the "servers" parameter must be a non-empty string.');
      }
    }
    $this->servers = $servers;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentServer() {
    return $this->currentServer;
  }

  /**
   * {@inheritdoc}
   */
  public function setCurrentServer($server_name) {
    if (!is_array($this->servers)) {
      throw new \RuntimeException('Cannot set the current server until the server list has been set.');
    }
    if (!in_array($server_name, $this->servers)) {
      throw new \InvalidArgumentException('The "server_name" parameter is not in the list of servers.');
    }
    $this->currentServer = $server_name;
  }

  /**
   * {@inheritdoc}
   */
  public function selectNextServer() {
    $servers = $this->getServers();
    if (!is_array($servers)) {
      throw new \RuntimeException('Cannot select the next server until the server list has been set.');
    }
    $remaining_servers = array_values(array_diff($servers, $this->selectedServers));
    $server_count = count($remaining_servers);
    if ($server_count > 0) {
      $index = mt_rand(0, $server_count - 1);
      $this->selectedServers[] = $remaining_servers[$index];
      $this->setCurrentServer($remaining_servers[$index]);
    } else {
      $server = array_shift($this->selectedServers);
      array_push($this->selectedServers, $server);
      $this->setCurrentServer($server);
    }
    return $this->getCurrentServer();
  }

  /**
   * {@inheritdoc}
   */
  public static function getRuntimeEnvironment() {
    $result = new Environment();
    $result->setSitegroup(self::getRuntimeSitegroup());
    $result->setEnvironmentName(self::getRuntimeEnvironmentName());
    return $result;
  }

  /**
   * Returns the runtime sitegroup.
   *
   * Generally this will be retrieved through environment variables set in the
   * hosting runtime.
   *
   * @return string
   *   The hosting sitegroup of the runtime environment.
   */
  public static function getRuntimeSitegroup() {
    $result = self::$runtimeSitegroup;
    if (empty($result)) {
      $result = getenv('AH_SITE_GROUP');
      if (empty($result)) {
        throw new \RuntimeException(
          'The site group could not be determined as the AH_SITE_GROUP environment variable was empty.'
        );
      }
    }
    return $result;
  }

  /**
   * Returns the runtime environment name.
   *
   * Generally this will be retrieved through environment variables set in the
   * hosting runtime.
   *
   * @return string
   *   The hosting environment name of the runtime environment.
   */
  public static function getRuntimeEnvironmentName() {
    $result = self::$runtimeEnvironmentName;
    if (empty($result)) {
      $result = getenv('AH_SITE_ENVIRONMENT');
      if (empty($result)) {
        throw new \RuntimeException(
          'The site environment could not be determined as the AH_SITE_ENVIRONMENT environment variable was empty.'
        );
      }
    }
    return $result;
  }

  /**
   * Forces the runtime sitegroup to the specified value.
   *
   * This is used for unit tests that run outside of a hosting environment.
   *
   * @param string $sitegroup
   *   The sitegroup for the runtime.
   */
  public static function setRuntimeSitegroup($sitegroup) {
    self::$runtimeSitegroup = $sitegroup;
  }

  /**
   * Forces the runtime environment to the specified value.
   *
   * This is used for unit tests that run outside of a hosting environment.
   *
   * @param string $environment_name
   *   The environment for the runtime.
   */
  public static function setRuntimeEnvironmentName($environment_name) {
    self::$runtimeEnvironmentName = $environment_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getDocrootDir() {
    $result = $this->docrootDir;
    if (empty($result)) {
      $sitegroup = $this->getSitegroup();
      $environment_name = $this->getEnvironmentName();
      if (!empty($sitegroup) && !empty($environment_name)) {
        $result = sprintf('/mnt/www/html/%s.%s/docroot', $sitegroup, $environment_name);
      } else {
        throw new \RuntimeException('The sitegroup and/or environment have not been set.');
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function setDocrootDir($docroot_dir) {
    $this->docrootDir = $docroot_dir;
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkingDir() {
    $result = $this->workingDir;
    if (empty($result)) {
      $sitegroup = $this->getSitegroup();
      $environment_name = $this->getEnvironmentName();
      if (!empty($sitegroup) && !empty($environment_name)) {
        $result = sprintf('/mnt/tmp/%s.%s', $sitegroup, $environment_name);
      } else {
        throw new \RuntimeException('The sitegroup and environment have not been set.');
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function setWorkingDir($working_dir) {
    $this->workingDir = $working_dir;
  }

  /**
   * {@inheritdoc}
   */
  public static function convert($value, $context = array()) {
    $result = array();
    foreach ($value as $environment) {
      $env = new Environment();
      $env->setSitegroup($context['siteGroup']);
      $env->setEnvironmentName($environment->name);
      $env->setSites(Site::convert($environment->sites, $context));
      $servers = array();
      foreach ($environment->servers as $server_detail) {
        $servers[] = $server_detail->fqdn;
      }
      $env->setServers($servers);

      $result[$environment->name] = $env;
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function extract($keys, $context = array()) {
    if (empty($keys)) {
      $environment = new IndependentEnvironment($this, $context);
      if (!$environment->validate()) {
        throw new InvalidEnvironmentException('Environment failed validation.');
      }
      return $environment;
    }
    $site = $keys['site'];
    unset($keys['site']);
    $context = array(
      'environment' => $this->environmentName,
      'servers' => $this->servers,
    ) + $context;
    return $this->sites[$site]->extract($keys, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    // @TODO - implement
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSites() {
    return $this->sites;
  }

  /**
   * {@inheritdoc}
   */
  public function setSites($sites) {
    $this->sites = $sites;
  }

  /**
   * Returns an array of primary site domain names, one for each site.
   *
   * @return string[]
   *   The domain names.
   */
  public function getPrimaryDomainNames() {
    $result = array();
    $sites = $this->getSites();
    foreach ($sites as $site) {
      $result[] = $site->getPrimaryDomainName();
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function setCloudCredentials(CloudCredentials $credentials) {
    $this->cloudCredentials = $credentials;
  }

  /**
   * {@inheritdoc}
   */
  public function getCloudCredentials() {
    return $this->cloudCredentials;
  }

  /**
   * Creates an Environment instance from the Acquia Cloud API.
   *
   * This is a convenience function, not to be used for Site Factories.
   *
   * @param WipLogInterface $logger
   *   The logger.
   * @param int $wip_id
   *   The ID of the associated Wip task.
   * @param string $sitegroup
   *   Optional. The hosting site group name. If not provided the value will be
   *   read from the ACQUIA_CLOUD_SITEGROUP environment variable.
   * @param string $cloud_endpoint
   *   Optional. The Acquia Cloud API endpoint. If not provided the value will
   *   be read from the ACQUIA_CLOUD_ENDPOINT environment variable.
   * @param string $cloud_user
   *   Optional. The Acquia Cloud API user name. If not provided the value will
   *   be read from the ACQUIA_CLOUD_USER environment variable.
   * @param string $cloud_password
   *   Optional. The AcquiaCloud API user password. If not provided the value
   *   will be read from the ACQUIA_CLOUD_PASSWORD environment variable.
   * @param string $environment_name
   *   Optional. The hosting environment name. If not provided, the first
   *   environment name from a call to AcquiaCloud->listEnvironments() will be
   *   used.
   * @param bool $populate_servers
   *   Optional. TRUE if servers should be added to the resulting environment;
   *   FALSE otherwise. Defaults to TRUE.
   * @param string $cloud_realm
   *   Optional. The hosting realm name. If not provided, the value will be
   *   read from the ACQUIA_CLOUD_REALM environment variable.
   *
   * @return EnvironmentInterface
   *   The new Environment instance.
   */
  public static function makeEnvironment(
    WipLogInterface $logger,
    $wip_id,
    $sitegroup = NULL,
    $cloud_endpoint = NULL,
    $cloud_user = NULL,
    $cloud_password = NULL,
    $environment_name = NULL,
    $populate_servers = TRUE,
    $cloud_realm = NULL
  ) {
    if (empty($cloud_realm)) {
      $cloud_realm = getenv('ACQUIA_CLOUD_REALM');
    }
    if (empty($sitegroup)) {
      $sitegroup = getenv('ACQUIA_CLOUD_SITEGROUP');
      if (empty($sitegroup)) {
        $message = <<<EOT
The "sitegroup" parameter must be a non-empty string or the ACQUIA_CLOUD_SITEGROUP environment variable must be set.
EOT;
        throw new \InvalidArgumentException($message);
      }
      if (!empty($cloud_realm) && !AcquiaCloud::sitegroupIsFullyQualified($sitegroup)) {
        $sitegroup = sprintf('%s:%s', $cloud_realm, $sitegroup);
      }
    }
    if (empty($cloud_endpoint)) {
      $cloud_endpoint = getenv('ACQUIA_CLOUD_ENDPOINT');
      if (empty($cloud_endpoint)) {
        $message = <<<EOT
The "cloud_endpoint" parameter must be a non-empty string or the ACQUIA_CLOUD_ENDPOINT environment variable must be set.
EOT;
        throw new \InvalidArgumentException($message);
      }
    }
    if (empty($cloud_user)) {
      $cloud_user = getenv('ACQUIA_CLOUD_USER');
      if (empty($cloud_user)) {
        $message = <<<EOT
The "cloud_user" parameter must be a non-empty string or the ACQUIA_CLOUD_USER environment variable must be set.
EOT;
        throw new \InvalidArgumentException($message);
      }
    }
    if (empty($cloud_password)) {
      $cloud_password = getenv('ACQUIA_CLOUD_PASSWORD');
      if (empty($cloud_password)) {
        $message = <<<EOT
The "cloud_password" parameter must be a non-empty string or the ACQUIA_CLOUD_PASSWORD environment variable must be set.
EOT;
        throw new \InvalidArgumentException($message);
      }
    }
    if (empty($environment_name)) {
      // We won't throw an exception here if the environment variable is not
      // set, as we have one more back fill attempt, below.
      $environment_name = getenv('ACQUIA_CLOUD_ENVIRONMENT');
    }
    $env = new Environment();
    $env->setSitegroup($sitegroup);

    $credentials = new CloudCredentials($cloud_endpoint, $cloud_user, $cloud_password, $sitegroup);
    $env->setCloudCredentials($credentials);

    $cloud = new AcquiaCloud($env, $logger, $wip_id);

    if (!AcquiaCloud::sitegroupIsFullyQualified($sitegroup)) {
      // If the realm is not included, get it from the list of sites.
      $site_response = $cloud->listSites();
      if (!$site_response->isSuccess()) {
        throw new \RuntimeException('Failed to retrieve site list');
      }

      $site_list = $site_response->getData();
      $found_realm = FALSE;
      if (!empty($site_list)) {
        foreach ($site_list as $site) {
          $sitegroup_obj = SiteGroup::separateSitegroupName($site);
          if (!empty($sitegroup_obj->name) && !empty($sitegroup_obj->realm) && $sitegroup_obj->name == $sitegroup) {
            $found_realm = TRUE;
            $sitegroup = $site;
            $env->setSiteGroup($sitegroup);
            break;
          }
        }
      }

      if (!$found_realm) {
        throw new \RuntimeException(sprintf('Failed to fully qualify the sitegroup %s', $sitegroup));
      }
    }

    if (empty($environment_name)) {
      $environment_response = $cloud->listEnvironments();
      if (!$environment_response->isSuccess()) {
        throw new \InvalidArgumentException(
          sprintf('Failed to list environments: %s', $environment_response->getExitMessage())
        );
      }
      $environments = $environment_response->getData();
      if (count($environments) > 0) {
        $environment_name = $environments[0]->getName();
      }
    }
    $env->setEnvironmentName($environment_name);

    if ($populate_servers) {
      $server_response = $cloud->listServers();
      if ($server_response->isSuccess()) {
        $servers = $server_response->getData();
        $server_list = array();
        foreach ($servers as $server) {
          $services = $server->getServices();
          if (!empty($services['web'])) {
            if ($services['web']['status'] === 'online') {
              $server_list[] = $server->getFullyQualifiedDomainName();
            }
          }
        }
        $env->setServers($server_list);
        $env->selectNextServer();
      } else {
        $logger->log(
          WipLogLevel::ERROR,
          sprintf('Failed to list servers for %s.%s', $env->getFullyQualifiedSitegroup(), $env->getEnvironmentName())
        );
      }
    }

    $database_response = $cloud->listDatabaseEnvironmentInfo();
    if (!$database_response->isSuccess()) {
      throw new \RuntimeException(sprintf('Failed to list databases: %s', $database_response->getExitMessage()));
    }
    $databases = $database_response->getData();
    $domain_response = $cloud->listDomains();
    if (!$domain_response->isSuccess()) {
      throw new \RuntimeException(sprintf('Failed to list domains: %s', $database_response->getExitMessage()));
    }
    $domains = $domain_response->getData();
    $sites = array();
    $id = 0;
    foreach ($databases as $database) {
      $sites[] = new Site($sitegroup, $database->getRoleName(), $domains, ++$id);
    }
    $env->setSites($sites);

    return $env;
  }

  /**
   * {@inheritdoc}
   */
  public function getUser() {
    return $this->user;
  }

  /**
   * {@inheritdoc}
   */
  public function setUser($user) {
    if (!is_string($user) || empty($user)) {
      throw new \InvalidArgumentException('The "user" parameter must be a non-empty string.');
    }
    $this->user = $user;
  }

  /**
   * {@inheritdoc}
   */
  public function getPassword() {
    return $this->decrypt($this->securePassword);
  }

  /**
   * {@inheritdoc}
   */
  public function getSshKeyPath() {
    $result = NULL;
    $password = $this->getPassword();
    if (is_string($password)) {
      $matches = array();
      if (1 === preg_match('/ssh:([^\s]+)/', $password, $matches)) {
        // Using the SSH private key as the password. This value is not stored
        // in the Environment instance since that would be serialized in the
        // database.
        $result = trim($matches[1]);
      }
    }
    if (NULL === $result) {
      throw new \DomainException(sprintf('Cannot get the SSH key from the password %s.', $password));
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function setPassword($password) {
    if (!is_string($password) || empty($password)) {
      throw new \InvalidArgumentException('The "password" parameter must be a non-empty string.');
    }
    $this->securePassword = $this->encrypt($password);
  }

  /**
   * {@inheritdoc}
   */
  public function getPort() {
    return $this->port;
  }

  /**
   * {@inheritdoc}
   */
  public function setPort($port) {
    if (!is_int($port) || $port <= 0) {
      throw new \InvalidArgumentException('The "port" parameter must be a positive integer.');
    }
    $this->port = $port;
  }

}
