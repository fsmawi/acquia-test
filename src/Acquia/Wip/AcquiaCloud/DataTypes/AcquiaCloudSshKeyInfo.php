<?php

namespace Acquia\Wip\AcquiaCloud\DataTypes;

use Acquia\Cloud\Api\Response\SshKey;
use Acquia\Wip\AcquiaCloud\AcquiaCloudDataType;

/**
 * Provides access to ssh key information.
 */
class AcquiaCloudSshKeyInfo extends AcquiaCloudDataType implements \JsonSerializable {

  /**
   * The ID of this ssh key.
   *
   * @var int
   */
  private $id = NULL;

  /**
   * The name associated with this ssh key.
   *
   * @var string
   */
  private $name = NULL;

  /**
   * The public key.
   *
   * @var string
   */
  private $publicKey = NULL;

  /**
   * Indicates whether this key provides for shell access.
   *
   * @var bool
   */
  private $shellAccess = TRUE;

  /**
   * Indicates whether this key provides for VCS access.
   *
   * @var bool
   */
  private $vcsAccess = TRUE;

  /**
   * Any resources that are not available for this key.
   *
   * @var string[]
   */
  private $blacklist = array();

  /**
   * Creates a new instance of AcquiaCloudSshKeyInfo.
   *
   * @param SshKey $key
   *   The SshKey instance from the Acquia Cloud SDK that holds the ssh key
   *   data.
   */
  public function __construct(SshKey $key) {
    $this->setId(intval($key->id()));
    $this->setName($key->nickname());
    $this->setPublicKey($key->publicKey());
    $this->setHasShellAccess($key->shellAccess());
    $this->setHasVcsAccess($key->vcsAccess());
    $this->setBlacklist($key->blacklist());
  }

  /**
   * Sets the key ID used to identify this ssh key in the Cloud API.
   *
   * @param int $id
   *   The key ID.
   */
  protected function setId($id) {
    if (!is_int($id) || $id <= 0) {
      throw new \InvalidArgumentException('The id parameter must be a positive integer.');
    }
    $this->id = $id;
  }

  /**
   * Gets the key ID.
   *
   * @return int
   *   The key ID.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Sets the key name.
   *
   * @param string $name
   *   The key name.
   */
  private function setName($name) {
    if (!is_string($name) || empty($name)) {
      throw new \InvalidArgumentException('The name parameter must be a non-empty string.');
    }
    $this->name = $name;
  }

  /**
   * Gets the name of this key.
   *
   * @return string
   *   The key name.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Sets the public portion of the ssh key.
   *
   * @param string $public_key
   *   The public key.
   */
  private function setPublicKey($public_key) {
    if (!is_string($public_key) || empty($public_key)) {
      throw new \InvalidArgumentException('The public_key parameter must be a non-empty string.');
    }
    $this->publicKey = $public_key;
  }

  /**
   * Gets the public portion of the ssh key.
   *
   * @return string
   *   The public key.
   */
  public function getPublicKey() {
    return $this->publicKey;
  }

  /**
   * Configures this key for shell access.
   *
   * @param bool $has_shell_access
   *   TRUE if this key has access to the shell.
   */
  private function setHasShellAccess($has_shell_access) {
    if (!is_bool($has_shell_access)) {
      throw new \InvalidArgumentException('The has_shell_access parameter must be a boolean value.');
    }
    $this->shellAccess = $has_shell_access;
  }

  /**
   * Indicates whether this key can access the shell.
   *
   * @return bool
   *   TRUE if the key can access the shell; FALSE otherwise.
   */
  public function hasShellAccess() {
    return $this->shellAccess;
  }

  /**
   * Configures this key for VCS access.
   *
   * @param bool $has_vcs_access
   *   TRUE if this key has access to the VCS repository.
   */
  private function setHasVcsAccess($has_vcs_access) {
    if (!is_bool($has_vcs_access)) {
      throw new \InvalidArgumentException('The has_vcs_access parameter must be a boolean value.');
    }
    $this->vcsAccess = $has_vcs_access;
  }

  /**
   * Indicates whether this key can access the VCS repository.
   *
   * @return bool
   *   TRUE if the key can access the VCS repository; FALSE otherwise.
   */
  public function hasVcsAccess() {
    return $this->vcsAccess;
  }

  /**
   * Sets the blacklist applied to this key.
   *
   * @param string[] $blacklist
   *   The blacklist.
   */
  private function setBlacklist($blacklist) {
    if (!is_array($blacklist)) {
      throw new \InvalidArgumentException('The blacklist parameter must be an array.');
    }
    $this->blacklist = $blacklist;
  }

  /**
   * Gets the blacklist associated with this key.
   *
   * @return string[]
   *   The blacklist.
   */
  public function getBlacklist() {
    return $this->blacklist;
  }

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize() {
    $result = array(
      'id' => $this->id,
      'name' => $this->name,
      'public_key' => $this->publicKey,
      'shell_access' => $this->shellAccess,
      'vcs_access' => $this->vcsAccess,
      'blacklist' => $this->blacklist,
    );
    return (object) $result;
  }

}
