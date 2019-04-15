<?php

namespace Acquia\Wip\Objects;

use Acquia\Wip\AcquiaCloud\AcquiaCloudInterface;
use Acquia\Wip\AcquiaCloud\CloudCredentials;
use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\DependencyManager;
use Acquia\Wip\Environment;
use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\Exception\AcquiaCloudApiException;
use Acquia\Wip\WipFactory;

/**
 * Builds a ParameterDocument from cloud credentials, vcs_uri, and vcs_path.
 */
class ParameterDocumentBuilder implements DependencyManagedInterface {

  const DEFAULT_MAX_RETRIES = 3;

  /**
   * The DependencyManager instance.
   *
   * @var DependencyManager
   */
  protected $dependencyManager;

  /**
   * The Cloud API credentials.
   *
   * @var CloudCredentials
   */
  private $credentials = NULL;

  /**
   * Builds an environment with cloud calls.
   *
   * @var boolean
   */
  private $cloudCalls = TRUE;

  /**
   * Creates a new ParameterDocumentBuilder for the specified site.
   *
   * @param CloudCredentials $credentials
   *   The credentials for the Acquia Cloud API.
   */
  public function __construct(CloudCredentials $credentials) {
    $this->setCredentials($credentials);
    $this->dependencyManager = new DependencyManager();
    $dependencies = $this->getDependencies();
    if (!empty($dependencies)) {
      $this->dependencyManager->addDependencies($dependencies);
    }
  }

  /**
   * Sets cloud calls on or off.
   *
   * @param bool $status
   *   Indicates if we are using cloud calls.
   */
  public function setCloudCalls($status) {
    $this->cloudCalls = $status;
  }

  /**
   * Gets the dependencies for the document.
   *
   * @return array
   *   An array of the dependencies.
   */
  public function getDependencies() {
    return array(
      'acquia.wip.acquiacloud.api' => 'Acquia\Wip\AcquiaCloud\AcquiaCloudInterface',
    );
  }

  /**
   * Gets the dependency manager for the class.
   *
   * @return DependencyManager
   *   The dependency manager for the class.
   */
  public function getDependencyManager() {
    return $this->dependencyManager;
  }

  /**
   * Sets the cloud credentials.
   *
   * This is required in order to use Acquia Cloud calls to populate a
   * parameter document.
   *
   * @param CloudCredentials $credentials
   *   The credentials for the Acquia Cloud API.
   */
  public function setCredentials(CloudCredentials $credentials) {
    $this->credentials = $credentials;
  }

  /**
   * Gets the cloud credentials.
   *
   * @return CloudCredentials|null
   *   The credentials or NULL if they have not been set.
   */
  public function getCredentials() {
    return $this->credentials;
  }

  /**
   * Creates the parameter document.
   *
   * @param ParameterConverterInterface[] $converters
   *   Optional. An associative array indicating any data converters that should
   *   be applied when decoding the JSON document.  The $converters array should
   *   use a key that indicates the property name, associated with a string
   *   value that indicates the class that will do the proper conversion. The
   *   specified class must implement the ParameterConverterInterface interface.
   *
   * @return ParameterDocument
   *   The parameter document.
   */
  public function build(array $converters = array()) {
    $json_document = $this->buildJson();
    return new ParameterDocument($json_document, $converters);
  }

  /**
   * Creates the parameter document in JSON format.
   *
   * @return string
   *   The parameter document in JSON format.
   *
   * @throws \DomainException
   *   If the Acquia Cloud credentials are unavailable or if the Acquia Cloud
   *   call to verify the Hosting site group fails.
   */
  public function buildJson() {
    $credentials = $this->getCredentials();
    if (empty($credentials)) {
      throw new \DomainException('The Acquia Cloud credentials have not been set.');
    }
    $document = new \stdClass();
    $site_groups = new \stdClass();
    $site_group_name = $credentials->getSitegroup();
    $site_groups->$site_group_name = $this->buildSiteGroupInfo($site_group_name);
    $document->siteGroups = $site_groups;
    return json_encode($document);
  }

  /**
   * Builds an object structure representing a site in the parameter document.
   *
   * @param string $site_group
   *   The fully qualified site group name (realm:site).
   *
   * @return object
   *   An object representing a site.
   *
   * @throws \DomainException
   *   If the specified site group cannot be verified or if environment data
   *   cannot be retrieved using the Cloud API.
   */
  private function buildSiteGroupInfo($site_group) {
    $result = new \stdClass();
    $result->multisite = FALSE;
    $result->name = $site_group;

    $result->cloudCreds = $this->buildCloudCredentials();

    if ($this->cloudCalls) {
      $environment = $this->createPartialEnvironment();
      $result->environments = $this->getEnvironments($environment);
    } else {
      $result->environments = $this->getDummyEnvironment();
    }

    // @todo - We probably don't need this in most cases.
    $all_environment_names = array_keys((array) $result->environments);
    $result->liveEnvironment = reset($all_environment_names);
    $result->updateEnvironment = end($all_environment_names);

    return $result;
  }

  /**
   * Build an environment object that will allow an environment to be built.
   *
   * @return \stdClass
   *   An object representing a site.
   */
  private function getDummyEnvironment() {
    $result = new \stdClass();
    $name = 'wip';
    $environment = new \stdClass();
    $environment->name = $name;

    // @todo - We probably don't need this in most cases.
    $environment->type = 'live_env';
    $environment->sites = array();
    $environment->servers = array();
    $server = new \stdClass();
    $server->fqdn = 'dummy host';
    $server->active = TRUE;
    $environment->servers[] = $server;

    $result->$name = $environment;
    return $result;
  }

  /**
   * Builds an object containing the cloud credentials.
   *
   * This object can be inserted into the ParameterDocument.
   *
   * @return object
   *   The cloud credentials in the form of an object.
   */
  private function buildCloudCredentials() {
    $credentials = $this->getCredentials();
    $result = new \stdClass();
    $result->endpoint = $credentials->getEndpoint();
    $result->user = $credentials->getUsername();
    $result->pass = $credentials->getPassword();
    return $result;
  }

  /**
   * Uses the Cloud API to get environment information about a site.
   *
   * @param EnvironmentInterface $environment
   *   The environment used to interact with the Cloud API.
   *
   * @return object
   *   The environment data.
   *
   * @throws AcquiaCloudApiException
   *   If the Acquia Cloud call that retrieves environment information fails.
   */
  private function getEnvironments(EnvironmentInterface $environment) {
    $result = new \stdClass();
    $cloud = $this->getAcquiaCloud($environment);

    $environments_response = $cloud->listEnvironments();
    if ($environments_response->isSuccess()) {
      $environments = $environments_response->getData();
      foreach ($environments as $environment) {
        $name = $environment->getName();
        $env = new \stdClass();
        $env->name = $name;

        // @todo - We probably don't need this in most cases.
        $env->type = 'live_env';
        $env->sites = array();
        $env->servers = array();
        $server = new \stdClass();
        $server->fqdn = $environment->getSshHost();
        $server->active = TRUE;
        $env->servers[] = $server;

        $result->$name = $env;
        return $result;
      }
    }

    throw new AcquiaCloudApiException($environments_response);
  }

  /**
   * Creates a minimal environment instance required for basic Cloud API calls.
   *
   * @return EnvironmentInterface
   *   The environment.
   */
  private function createPartialEnvironment() {
    $result = new Environment();
    $result->setCloudCredentials($this->getCredentials());
    $result->setSitegroup($this->getCredentials()->getSitegroup());
    return $result;
  }

  /**
   * Returns an instance of AcquiaCloud.
   *
   * @param EnvironmentInterface $environment
   *   The environment.
   *
   * @return AcquiaCloudInterface
   *   The AcquiaCloud instance.
   */
  public function getAcquiaCloud(EnvironmentInterface $environment) {
    /** @var AcquiaCloudInterface $result */
    $result = WipFactory::getObject('acquia.wip.acquiacloud.api');
    $result->setEnvironment($environment);
    return $result;
  }

}
