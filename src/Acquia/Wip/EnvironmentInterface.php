<?php

namespace Acquia\Wip;

use Acquia\Wip\AcquiaCloud\CloudCredentials;
use Acquia\Wip\Objects\Site;

/**
 * The EnvironmentInterface encapsulates sitegroup and environment data.
 *
 * This is used in the Ssh layer to identify which server to run on and what
 * hosting sitegroup and environment to use.
 */
interface EnvironmentInterface {

  /**
   * Returns the hosting sitegroup.
   *
   * @return string
   *   The hosting sitegroup name.
   */
  public function getSitegroup();

  /**
   * Sets the hosting sitegroup.
   *
   * @param string $sitegroup
   *   The hosting site group.
   *
   * @throws \InvalidArgumentException
   *   If the sitegroup argument is not a non-empty string.
   */
  public function setSitegroup($sitegroup);

  /**
   * Returns the hosting realm associated with the site group.
   *
   * @return string
   *   The hosting realm.
   */
  public function getRealm();

  /**
   * Sets the hosting realm associated with the site group.
   *
   * @param string $realm
   *   The hosting realm.
   */
  public function setRealm($realm);

  /**
   * Returns the hosting site group with the realm prepended.
   *
   * @return string
   *   The fully qualified hosting site group.
   */
  public function getFullyQualifiedSitegroup();

  /**
   * Returns the hosting environment name.
   *
   * @return string
   *   The environment name.
   */
  public function getEnvironmentName();

  /**
   * Sets the hosting environment name.
   *
   * @param string $environment_name
   *   The hosting environment name.
   *
   * @throws \InvalidArgumentException
   *   If the environment_name argument is not a non-empty string.
   */
  public function setEnvironmentName($environment_name);

  /**
   * Returns an array of servers associated with this environment.
   *
   * @return string[]
   *   The set of servers associated with this environment.
   */
  public function getServers();

  /**
   * Sets the servers associated with this environment.
   *
   * @param string[] $servers
   *   The set of servers associated with this environment.
   *
   * @throws \InvalidArgumentException
   *   If the servers argument is empty, is not an array, or contains an element
   *   that is not a string or is empty.
   */
  public function setServers($servers);

  /**
   * Returns the server that will be used for ssh calls.
   *
   * @return string
   *   The server.
   */
  public function getCurrentServer();

  /**
   * Sets the server that will be used for ssh calls.
   *
   * @param string $server_name
   *   The server.
   *
   * @throws \RuntimeException
   *   If the server list has not yet been set.
   * @throws \InvalidArgumentException
   *   If the server_name is not in the server list.
   */
  public function setCurrentServer($server_name);

  /**
   * Selects a different server that will be used for ssh calls.
   *
   * @return string
   *   The newly selected server.
   *
   * @throws \RuntimeException
   *   If the server list has not yet been set.
   */
  public function selectNextServer();

  /**
   * Returns the environment for this runtime.
   *
   * @return EnvironmentInterface
   *   The environment.
   */
  public static function getRuntimeEnvironment();

  /**
   * Returns the absolute path to the docroot.
   *
   * @return string
   *   The path.
   */
  public function getDocrootDir();

  /**
   * Sets the absolute path to the docroot.
   *
   * @param string $docroot_dir
   *   The path.
   */
  public function setDocrootDir($docroot_dir);

  /**
   * Returns the absolute path to the working directory.
   *
   * @return string
   *   The path.
   */
  public function getWorkingDir();

  /**
   * Sets the absolute path to the working directory.
   *
   * @param string $working_dir
   *   The path.
   */
  public function setWorkingDir($working_dir);

  /**
   * Returns an array of primary site domain names, one for each site.
   *
   * @return string[]
   *   The domain names.
   */
  public function getPrimaryDomainNames();

  /**
   * Sets the Cloud API credentials.
   *
   * @param CloudCredentials $credentials
   *   The credentials.
   */
  public function setCloudCredentials(CloudCredentials $credentials);

  /**
   * Gets the Cloud API credentials.
   *
   * @return CloudCredentials
   *   The credentials.
   */
  public function getCloudCredentials();

  /**
   * Gets the sites associated with this Environment.
   *
   * @return Site[]
   *   The sites.
   */
  public function getSites();

  /**
   * Sets the sites associated with this Environment.
   *
   * @param Site[] $sites
   *   The sites.
   */
  public function setSites($sites);

  /**
   * Gets the user associated with this instance.
   *
   * Most of the time the user will not be available; it is only used in cases
   * in which SSH requires a user.
   *
   * @return string | null
   *   The user.
   */
  public function getUser();

  /**
   * Sets the user associated with this instance.
   *
   * This is only used in cases in which the user is required to SSH into the
   * host.
   *
   * @param string $user
   *   The user.
   */
  public function setUser($user);

  /**
   * Gets the password associated with this instance.
   *
   * This password is only available if it is required to SSH into the host.
   *
   * @return string
   *   The password.
   */
  public function getPassword();

  /**
   * Sets the password associated with this instance.
   *
   * @param string $password
   *   The password.
   */
  public function setPassword($password);

  /**
   * Retrieves the SSH key path.
   *
   * @return string
   *   The path to the private SSH key.
   *
   * @throws \DomainException
   *   If the password has not been set as the private key path.
   */
  public function getSshKeyPath();

  /**
   * Gets the port number associated with the host.
   *
   * @return int
   *   The port number.
   */
  public function getPort();

  /**
   * Sets the port number associated with the host.
   *
   * @param int $port
   *   The port number.
   */
  public function setPort($port);

}
