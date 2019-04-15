<?php

namespace Acquia\Wip\AcquiaCloud\DataTypes;

use Acquia\Cloud\Api\Response\Site;
use Acquia\Wip\AcquiaCloud\AcquiaCloudDataType;

/**
 * Contains fields that describe a hosting site.
 */
class AcquiaCloudSiteInfo extends AcquiaCloudDataType implements \JsonSerializable {

  /**
   * The site name.
   *
   * @var string
   */
  private $name;

  /**
   * The type of VCS, 'git' or 'svn'.
   *
   * @var string
   */
  private $vcsType;

  /**
   * The VCS url.
   *
   * @var string
   */
  private $vcsUrl;

  /**
   * Flag indicating whether the site is in production mode.
   *
   * @var bool
   */
  private $productionMode;

  /**
   * The Unix username.
   *
   * @var string
   */
  private $unixUsername;

  /**
   * The unique ID assigned to the site.
   *
   * @var string
   */
  private $uuid;

  /**
   * Initializes a new instance of AcquiaCloudSiteInfo with the specified site.
   *
   * @param Site $site
   *   The site.
   */
  public function __construct(Site $site) {
    $this->setName($site->name());
    $this->setVcsType($site->vcsType());
    $this->setVcsUrl($site->vcsUrl());
    $this->setProductionMode($site->productionMode());
    $this->setUnixUsername($site->unixUsername());
    $this->setUuid($site->uuid());
  }

  /**
   * Gets the site name.
   *
   * @return string
   *   The site name.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Sets the site name.
   *
   * @param string $name
   *   The site name.
   */
  private function setName($name) {
    if (!is_string($name) || empty($name)) {
      throw new \InvalidArgumentException('The name parameter must be a non-empty string.');
    }
    $this->name = $name;
  }

  /**
   * Gets the VCS type.
   *
   * @return string
   *   The VCS type.  Either 'git' or 'svn'.
   */
  public function getVcsType() {
    return $this->vcsType;
  }

  /**
   * Sets the VCS type.
   *
   * @param string $vcs_type
   *   The VCS type.
   */
  private function setVcsType($vcs_type) {
    if (!is_string($vcs_type) || empty($vcs_type)) {
      throw new \InvalidArgumentException('The vcs_type parameter must be a non-empty string.');
    }
    $this->vcsType = $vcs_type;
  }

  /**
   * Gets the VCS URL.
   *
   * @return string
   *   The VCS URL.
   */
  public function getVcsUrl() {
    return $this->vcsUrl;
  }

  /**
   * Sets the VCS URL.
   *
   * @param string $vcs_url
   *   The VCS URL.
   */
  private function setVcsUrl($vcs_url) {
    if (!is_string($vcs_url) || empty($vcs_url)) {
      throw new \InvalidArgumentException('The vcs_url parameter must be a non-empty string.');
    }
    $this->vcsUrl = $vcs_url;
  }

  /**
   * Indicates whether the site is in production mode.
   *
   * @return bool
   *   TRUE if the site is in production mode; FALSE otherwise.
   */
  public function isProductionMode() {
    return $this->productionMode;
  }

  /**
   * Sets whether the site is in production mode or not.
   *
   * @param bool $production_mode
   *   TRUE if the site is in production mode; FALSE otherwise.
   */
  private function setProductionMode($production_mode) {
    if (!is_bool($production_mode)) {
      throw new \InvalidArgumentException('The production_mode parameter must be a boolean value.');
    }
    $this->productionMode = $production_mode;
  }

  /**
   * Gets the Unix username.
   *
   * @return string
   *   The Unix username.
   */
  public function getUnixUsername() {
    return $this->unixUsername;
  }

  /**
   * Sets the Unix username.
   *
   * @param string $unix_username
   *   The Unix username.
   */
  private function setUnixUsername($unix_username) {
    if (!is_string($unix_username) || empty($unix_username)) {
      throw new \InvalidArgumentException('The unix_username parameter must be a non-empty string.');
    }
    $this->unixUsername = $unix_username;
  }

  /**
   * Gets the site's unique ID.
   *
   * @return string
   *   The uuid.
   */
  public function getUuid() {
    return $this->uuid;
  }

  /**
   * Sets the site's unique ID.
   *
   * @param string $uuid
   *   The uuid.
   */
  private function setUuid($uuid) {
    if (!is_string($uuid) || empty($uuid)) {
      throw new \InvalidArgumentException('The uuid parameter must be a non-empty string.');
    }
    $this->uuid = $uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize() {
    $result = array(
      'name' => $this->name,
      'vcs_type' => $this->vcsType,
      'vcs_url' => $this->vcsUrl,
      'production_mode' => $this->productionMode,
      'unix_user' => $this->unixUsername,
      'uuid' => $this->uuid,
    );
    return (object) $result;
  }

}
