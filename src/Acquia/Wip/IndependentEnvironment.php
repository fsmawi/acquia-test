<?php

namespace Acquia\Wip;

/*
 * @todo We may not need this any more. It seems the primary purpose was to have
 *   an Environment with cloud credentials, and that has been added to the
 *   interface. While adding the Cloud API capability we should explore removing
 *   this class.
 */
use Acquia\Wip\AcquiaCloud\CloudCredentials;

/**
 * Represents an environment that can be used without any external data.
 */
class IndependentEnvironment extends Environment {

  /**
   * Initializes this instance.
   *
   * @param Environment $environment
   *   The environment.
   * @param array $context
   *   The context.
   */
  public function __construct(Environment $environment, $context = array()) {
    // @todo Check everything's covered from the parent.
    $this->setSitegroup($environment->getFullyQualifiedSitegroup());
    $this->setEnvironmentName($environment->getEnvironmentName());
    if ($servers = $environment->getServers()) {
      $this->setServers($servers);
    }
    if ($current = $environment->getCurrentServer()) {
      $this->setCurrentServer($current);
    }
    $this->setDocrootDir($environment->getDocrootDir());
    $this->setWorkingDir($environment->getWorkingDir());
    $this->setSites($environment->getSites());
    $cloud_creds = $context['cloudCreds'];
    if ($cloud_creds instanceof CloudCredentials) {
      $this->setCloudCredentials($cloud_creds);
    } else {
      $cloud_credentials = new CloudCredentials(
        $cloud_creds->endpoint,
        $cloud_creds->user,
        $cloud_creds->pass,
        $environment->getFullyQualifiedSitegroup()
      );
      $this->setCloudCredentials($cloud_credentials);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    // @todo More validation?
    $cloud_creds = $this->getCloudCredentials();
    if (empty($cloud_creds)) {
      throw new \InvalidArgumentException('IndependentEnvironment::validate found no cloud credentials.');
    }
    return parent::validate();
  }

}
