<?php

namespace Acquia\Wip\Objects;

use Acquia\Wip\AcquiaCloud\CloudCredentials;

/**
 * The IndependentSite class represents a site that can be used in a WIP.
 *
 * The distinction is that this site can be used without any further data.
 */
class IndependentSite extends Site {

  /**
   * The Cloud API credentials.
   *
   * @var CloudCredentials
   */
  private $cloudCreds = NULL;

  /**
   * The name of the environment.
   *
   * @var string
   */
  private $environment = '';

  /**
   * The set of servers.
   *
   * @var string[]
   */
  private $servers = array();

  /**
   * Any additional data passed in context to this object.
   *
   * @var array
   */
  private $extra = array();

  /**
   * Creates a new instance of IndependentSite.
   *
   * @param Site $site
   *   The site.
   * @param array $context
   *   The context that contains information about the site.
   */
  public function __construct(Site $site, $context = array()) {
    // @TODO - check everything is covered from the parent.
    $this->siteGroup = $site->getSiteGroup();
    $this->dbRole = $site->getDbRole();
    $this->domains = $site->getDomains();
    $this->id = $site->getId();

    $this->cloudCreds = $context['cloudCreds'];
    unset($context['cloudCreds']);
    $this->environment = $context['environment'];
    unset($context['environment']);
    $this->servers = $context['servers'];
    unset($context['servers']);
    unset($context['siteGroup']);
    // Store anything remaining on $extra.
    $this->extra = $context;
  }

  /**
   * Validates this is a complete site description.
   *
   * @return bool
   *   TRUE if this is a complete site; FALSE otherwise.
   */
  public function validate() {
    // @TODO - more validation?

    foreach (array('environment', 'servers') as $member) {
      if (empty($this->$member)) {
        return FALSE;
      }
    }
    foreach (array('getUsername', 'getPassword', 'getEndpoint', 'getSitegroup') as $getter) {
      if (!$this->cloudCreds->$getter()) {
        return FALSE;
      }
    }

    return parent::validate();
  }

  /**
   * Returns the cloud credentials.
   *
   * @return CloudCredentials
   *   The credentials.
   */
  public function getCloudCreds() {
    return $this->cloudCreds;
  }

  /**
   * Sets the cloud credentials.
   *
   * @param CloudCredentials $cloud_creds
   *   The specified cloud credentials.
   */
  public function setCloudCreds(CloudCredentials $cloud_creds) {
    $this->cloudCreds = $cloud_creds;
  }

  /**
   * Gets the name of the environment.
   *
   * @return string
   *   The environment name.
   */
  public function getEnvironment() {
    return $this->environment;
  }

  /**
   * Sets the name of the environment.
   *
   * @param string $environment
   *   The environment name.
   */
  public function setEnvironment($environment) {
    $this->environment = $environment;
  }

}
