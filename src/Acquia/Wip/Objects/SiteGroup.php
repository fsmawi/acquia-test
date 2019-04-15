<?php

namespace Acquia\Wip\Objects;

use Acquia\Wip\AcquiaCloud\CloudCredentials;
use Acquia\Wip\Environment;

/**
 * The SiteRelationship class represents site relationships within a sitegroup.
 */
class SiteGroup implements ParameterConverterInterface {

  /**
   * The name.
   *
   * @var string
   */
  protected $name = NULL;

  /**
   * The realm.
   *
   * @var string
   */
  protected $realm = NULL;

  /**
   * The "live" environment.
   *
   * @var string
   */
  protected $liveEnvironment = NULL;

  /**
   * The "update" environment.
   *
   * @var string
   */
  protected $updateEnvironment = NULL;

  /**
   * The list of sites.
   *
   * @var string[]
   */
  protected $sites = array();

  /**
   * The list of environments.
   *
   * @var Environment[]
   */
  protected $environments = array();

  /**
   * The cloud credentials.
   *
   * @var CloudCredentials
   */
  protected $cloudCreds = NULL;

  /**
   * The list of servers.
   *
   * @var string[]
   */
  protected $servers = array();

  /**
   * Creates a new SiteGroup object.
   *
   * @param string $name
   *   The name of the sitegroup.
   * @param string $live_environment
   *   The "live" environment.
   * @param string $update_environment
   *   The "update" environment.
   */
  public function __construct($name, $live_environment, $update_environment) {
    $this->setName($name);
    $this->liveEnvironment = $live_environment;
    $this->updateEnvironment = $update_environment;
  }

  /**
   * Converts the specified value to an appropriate object.
   *
   * @param mixed $value
   *   The value to convert.
   *
   * @return object
   *   The value converted into an appropriate object.
   */
  public static function convert($value, $context = array()) {
    $result = array();
    foreach ($value as $sitegroup) {
      // Add sitegroup-specific options to the options that get passed down.
      $context = array('siteGroup' => $sitegroup->name) + $context;
      $sg = new SiteGroup($sitegroup->name, $sitegroup->liveEnvironment, $sitegroup->updateEnvironment);
      $sg->environments = Environment::convert($sitegroup->environments, $context);
      $result[$sg->getFullyQualifiedName()] = $sg;
      if (!empty($sitegroup->cloudCreds)) {
        $creds = $sitegroup->cloudCreds;
        $sg->setCloudCreds(new CloudCredentials($creds->endpoint, $creds->user, $creds->pass, $sitegroup->name));
      }
      if (!empty($sitegroup->servers)) {
        $sg->servers = $sitegroup->servers;
      }
    }

    return $result;
  }

  /**
   * Extracts an IndependentSiteGroup object.
   *
   * @param array $keys
   *   A list of keys to help locate the environment.
   * @param array $context
   *   The context to pass to IndependentSiteGroup::__construct().
   *
   * @return \Acquia\Wip\IndependentEnvironment|IndependentSite|IndependentSiteGroup|mixed
   *   The IndependentEnvironment object.
   *
   * @throws \Acquia\Wip\Exception\InvalidEnvironmentException
   *   If there is a problem extracting the object.
   */
  public function extract($keys, $context = array()) {
    if (empty($keys)) {
      // @TODO - only propagate unknown properties on sitegroups.  No other
      // context
      return new IndependentSiteGroup($this, $context);
    }

    $environment = $keys['environment'];
    unset($keys['environment']);
    $context = array(
      'siteGroup' => $this->getFullyQualifiedName(),
      'cloudCreds' => $this->cloudCreds,
    ) + $context;
    return $this->environments[$environment]->extract($keys, $context);
  }

  /**
   * Validates the SiteGroup object.
   *
   * @return bool
   *   Whether or not the SiteGroup is valid.
   */
  public function validate() {
    // @TODO - implement
    return TRUE;
  }

  /**
   * Gets the name of the SiteGroup.
   *
   * @return string
   *   The name.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Sets the name of the hosting site group.
   *
   * If this includes the realm, that will be split out.
   *
   * @param string $name
   *   The site group, optionally including the realm name.
   */
  public function setName($name) {
    $sitegroup_obj = self::separateSitegroupName($name);
    if (!empty($sitegroup_obj->realm)) {
      $this->setRealm($sitegroup_obj->realm);
    }
    $this->name = $sitegroup_obj->name;
  }

  /**
   * Sets the realm associated with the site.
   *
   * @param string $realm
   *   The realm name.
   */
  public function setRealm($realm) {
    $this->realm = $realm;
  }

  /**
   * Returns the name of the realm, if available.
   *
   * @return string
   *   The realm name.
   */
  public function getRealm() {
    return $this->realm;
  }

  /**
   * Gets the fully qualified site group name.
   *
   * If the realm and site group name have been set, this will return
   * realm:site.
   *
   * @return string
   *   The fully qualified site group name.
   */
  public function getFullyQualifiedName() {
    $result = $this->getName();
    $realm = $this->getRealm();
    if (!empty($realm)) {
      $result = sprintf("%s:%s", $realm, $result);
    }
    return $result;
  }

  /**
   * Gets the live environment of the SiteGroup.
   *
   * @return string
   *   The environment name.
   */
  public function getLiveEnvironment() {
    return $this->liveEnvironment;
  }

  /**
   * Sets the live environment of the SiteGroup.
   *
   * @param string $live_environment
   *   The environment name.
   */
  public function setLiveEnvironment($live_environment) {
    $this->liveEnvironment = $live_environment;
  }

  /**
   * Gets the update environment of the SiteGroup.
   *
   * @return string
   *   The environment name.
   */
  public function getUpdateEnvironment() {
    return $this->updateEnvironment;
  }

  /**
   * Sets the update environment of the SiteGroup.
   *
   * @param string $update_environment
   *   The environment name.
   */
  public function setUpdateEnvironment($update_environment) {
    $this->updateEnvironment = $update_environment;
  }

  /**
   * Gets the sites in this SiteGroup.
   *
   * @return \string[]
   *   The list of sites.
   */
  public function getSites() {
    return $this->sites;
  }

  /**
   * Sets the sites in this SiteGroup.
   *
   * @param \string[] $sites
   *   The list of sites.
   */
  public function setSites($sites) {
    $this->sites = $sites;
  }

  /**
   * Gets the cloud credentials of this SiteGroup.
   *
   * @return CloudCredentials
   *   The credentials.
   */
  public function getCloudCreds() {
    return $this->cloudCreds;
  }

  /**
   * Sets the cloud credentials of this SiteGroup.
   *
   * @param CloudCredentials $cloud_creds
   *   The credentials.
   */
  public function setCloudCreds(CloudCredentials $cloud_creds) {
    $this->cloudCreds = $cloud_creds;
  }

  /**
   * Gets the servers for this SiteGroup.
   *
   * @return \string[]
   *   The server list.
   */
  public function getServers() {
    return $this->servers;
  }

  /**
   * Sets the servers for this SiteGroup.
   *
   * @param \string[] $servers
   *   The server list.
   */
  public function setServers($servers) {
    $this->servers = $servers;
  }

  /**
   * Gets the environment for the specified environment name.
   *
   * @param string $environment_name
   *   The name of the environment.
   *
   * @return Environment
   *   The environment object.
   */
  public function getEnvironment($environment_name) {
    return $this->environments[$environment_name];
  }

  /**
   * Separates the components of the specified sitegroup name.
   *
   * @param string $sitegroup_name
   *   The site group, optionally including the realm name.
   *
   * @return object
   *   An object with a 'name' field and optionally a 'realm' field, if the
   *   realm is provided in the sitegroup_name parameter.
   */
  public static function separateSitegroupName($sitegroup_name) {
    $result = new \stdClass();

    // The fully-qualified site group is of the form realm:site.  If the fully-
    // qualified sitegroup form was provided, break out the two elements.
    $realm = NULL;
    $site_name = NULL;
    $replacement_count = sscanf($sitegroup_name, '%[^:]:%s', $realm, $site_name);
    if ($replacement_count === 2) {
      $result->realm = $realm;
      $result->name = $site_name;
    } else {
      $result->name = $sitegroup_name;
    }
    return $result;
  }

}
