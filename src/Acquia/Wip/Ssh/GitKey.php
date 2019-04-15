<?php

namespace Acquia\Wip\Ssh;

use Acquia\Wip\Security\EncryptTrait;

/**
 * Stores information about an SSH key used for access to git repositories.
 */
class GitKey {

  use EncryptTrait;

  /**
   * The name assigned to this key.
   *
   * @var string
   */
  private $name = NULL;

  /**
   * The filename associated with the private key.
   *
   * @var string
   */
  private $keyFilename = NULL;

  /**
   * The filename associated with the git wrapper script.
   *
   * @var string
   */
  private $wrapperFilename = NULL;

  /**
   * The SSH private key.
   *
   * @var null
   */
  private $secretKey = NULL;

  /**
   * Initializes a new instance.
   *
   * @param string $name
   *   Optional. The name associated with the SSH key.
   * @param string $private_key_filename
   *   Optional. The filename in which the key will be stored.
   * @param string $wrapper_name
   *   Optional. The filename that will be used for the git wrapper.
   * @param string $key
   *   Optional. The private SSH key.
   */
  public function __construct($name = NULL, $private_key_filename = NULL, $wrapper_name = NULL, $key = NULL) {
    if (NULL !== $name) {
      $this->setName($name);
    }
    if (NULL !== $private_key_filename) {
      $this->setPrivateKeyFilename($private_key_filename);
    }
    if (NULL !== $wrapper_name) {
      $this->setWrapperFilename($wrapper_name);
    }
    if (NULL !== $key) {
      $this->setKey($key);
    }
  }

  /**
   * Sets the name of this key.
   *
   * @param string $name
   *   The name.
   */
  public function setName($name) {
    if (empty($name) || !is_string($name)) {
      throw new \InvalidArgumentException('The "name" parameter must be a non-empty string.');
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
   * Sets the filename associated with the private key.
   *
   * Note that the filename does not include the path. The key must be a file
   * in the ~/.ssh directory.
   *
   * @param string $filename
   *   The name of the file containing the private SSH key.
   */
  public function setPrivateKeyFilename($filename) {
    if (empty($filename) || !is_string($filename)) {
      throw new \InvalidArgumentException('The "filename" parameter must be a non-empty string.');
    }
    $this->keyFilename = $filename;
  }

  /**
   * Gets the filename associated with the private key.
   *
   * @return string
   *   The name of the file containing the private SSH key.
   */
  public function getPrivateKeyFilename() {
    return $this->keyFilename;
  }

  /**
   * Sets the filename associated with the git wrapper script.
   *
   * The wrapper script is used to apply a specific SSH key with a git command.
   *
   * @param string $filename
   *   The wrapper script filename.
   */
  public function setWrapperFilename($filename) {
    if (empty($filename) || !is_string($filename)) {
      throw new \InvalidArgumentException('The "filename" parameter must be a non-empty string.');
    }
    $this->wrapperFilename = $filename;
  }

  /**
   * Gets the filename associated with the git wrapper script.
   *
   * @return string
   *   The wrapper script filename.
   */
  public function getWrapperFilename() {
    return $this->wrapperFilename;
  }

  /**
   * Sets the private SSH key.
   *
   * @param string $key
   *   The SSH private key.
   */
  public function setKey($key) {
    if (empty($key) || !is_string($key)) {
      throw new \InvalidArgumentException('The "key" parameter must be a non-empty string.');
    }
    $this->secretKey = $this->encrypt($key);
  }

  /**
   * Gets the private SSH key.
   *
   * @return string
   *   The SSH private key.
   */
  public function getKey() {
    $result = $this->secretKey;
    if (!empty($result)) {
      $result = $this->decrypt($result);
    }
    return $result;
  }

  /**
   * Removes the SSH key from this instance.
   */
  public function forgetKey() {
    $this->secretKey = NULL;
  }

}
