<?php

namespace Acquia\Wip\AcquiaCloud\DataTypes;

use Acquia\Cloud\Api\Response\Environment;
use Acquia\Wip\AcquiaCloud\AcquiaCloudDataType;

/**
 * Encapsulates data associated with a hosting environment.
 */
class AcquiaCloudEnvironmentInfo extends AcquiaCloudDataType implements \JsonSerializable {

  /**
   * The environment name.
   *
   * @var string
   */
  private $name;

  /**
   * The VCS path.
   *
   * @var string
   */
  private $vcsPath;

  /**
   * The ssh hostname.
   *
   * @var string
   */
  private $sshHost;

  /**
   * The database clusters associated with this environment.
   *
   * @var int[]
   */
  private $dbClusters;

  /**
   * The default domain for this environment.
   *
   * @var string
   */
  private $defaultDomain;

  /**
   * Indicates whether live development mode is enabled.
   *
   * @var bool
   */
  private $liveDev;

  /**
   * Instantiates this instance with information from the specified environment.
   *
   * @param Environment $env
   *   The environment.
   */
  public function __construct(Environment $env) {
    $this->setName($env->name());
    $this->setVcsPath($env->vcsPath());
    $this->setSshHost($env->sshHost());
    $this->setDbClusters($env->dbClusters());
    $this->setDefaultDomain($env->defaultDomain());
    $this->setLiveDev($env->liveDev());
  }

  /**
   * Gets the environment name.
   *
   * @return string
   *   The environment name.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Sets the environment name.
   *
   * @param string $name
   *   The environment name.
   */
  private function setName($name) {
    if (!is_string($name) || empty($name)) {
      throw new \InvalidArgumentException('The name parameter must be a non-empty string.');
    }
    $this->name = $name;
  }

  /**
   * Gets the VCS path associated with this environment.
   *
   * @return string
   *   The VCS path.
   */
  public function getVcsPath() {
    return $this->vcsPath;
  }

  /**
   * Sets the VCS path associated with this environment.
   *
   * @param string $vcs_path
   *   The VCS path.
   */
  private function setVcsPath($vcs_path) {
    if (!is_string($vcs_path) || empty($vcs_path)) {
      throw new \InvalidArgumentException('The vcs_path parameter must be a non-empty string.');
    }
    $this->vcsPath = $vcs_path;
  }

  /**
   * Gets the ssh hostname associated with this environment.
   *
   * Note: This is the fully qualified domain name.
   *
   * @return string
   *   The ssh hostname.
   */
  public function getSshHost() {
    return $this->sshHost;
  }

  /**
   * Sets the ssh hostname associated with this environment.
   *
   * @param string $ssh_host
   *   The ssh hostname.
   */
  private function setSshHost($ssh_host) {
    if (!is_string($ssh_host) || empty($ssh_host)) {
      throw new \InvalidArgumentException('The ssh_host parameter must be a non-empty string.');
    }
    $this->sshHost = $ssh_host;
  }

  /**
   * Gets the database cluster IDs associated with this environment.
   *
   * @return int[]
   *   The database cluster IDs.
   */
  public function getDbClusters() {
    return $this->dbClusters;
  }

  /**
   * Sets the database cluster IDs associated with this environment.
   *
   * @param int[] $db_clusters
   *   The database cluster IDs.
   */
  private function setDbClusters($db_clusters) {
    if (!is_array($db_clusters)) {
      throw new \InvalidArgumentException('The db_clusters parameter must be an array.');
    }
    $this->dbClusters = array_map('intval', $db_clusters);
  }

  /**
   * Gets the default domain for this environment.
   *
   * @return string
   *   The default domain.
   */
  public function getDefaultDomain() {
    return $this->defaultDomain;
  }

  /**
   * Sets the default domain for this environment.
   *
   * @param string $default_domain
   *   The default domain.
   */
  private function setDefaultDomain($default_domain) {
    if (!is_string($default_domain) || empty($default_domain)) {
      throw new \InvalidArgumentException('the default_domain parameter must be a non-empty string.');
    }
    $this->defaultDomain = $default_domain;
  }

  /**
   * Indicates whether the environment is in live development mode.
   *
   * @return bool
   *   TRUE if the environment is in live development mode; FALSE otherwise.
   */
  public function isLiveDev() {
    return $this->liveDev;
  }

  /**
   * Sets whether this environment is in live development mode.
   *
   * @param bool $live_dev
   *   TRUE if this environment is in live development mode; FALSE otherwise.
   */
  private function setLiveDev($live_dev) {
    if (!is_bool($live_dev)) {
      throw new \InvalidArgumentException('The live_dev parameter must be a boolean.');
    }
    $this->liveDev = $live_dev;
  }

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize() {
    $result = array(
      'name' => $this->name,
      'vcs_path' => $this->vcsPath,
      'ssh_host' => $this->sshHost,
      'db_clusters' => $this->dbClusters,
      'default_domain' => $this->defaultDomain,
      'live_dev' => $this->liveDev,
    );
    return (object) $result;
  }

}
