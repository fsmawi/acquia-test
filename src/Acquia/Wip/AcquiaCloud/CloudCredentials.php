<?php

namespace Acquia\Wip\AcquiaCloud;

use Acquia\Wip\Security\EncryptTrait;

/**
 * Encapsulates the Cloud API credentials.
 */
class CloudCredentials {

  use EncryptTrait;

  /**
   * The Cloud API endpoint.
   *
   * @var string
   */
  private $endpoint = NULL;

  /**
   * The Cloud API username.
   *
   * @var string
   */
  private $username = NULL;

  /**
   * The encrypted Cloud API password.
   *
   * @var string
   */
  private $securePassword = NULL;

  /**
   * The hosting sitegroup associated with the cloud credentials.
   *
   * @var string
   */
  private $sitegroup = NULL;

  /**
   * Creates a new instance of CloudCredentials.
   *
   * @param string $endpoint
   *   The Cloud API endpoint.
   * @param string $username
   *   The Cloud API user name.
   * @param string $password
   *   The Cloud API password.
   * @param string $sitegroup
   *   The hosting sitegroup.
   */
  public function __construct($endpoint, $username, $password, $sitegroup) {
    $this->setEndpoint($endpoint);
    $this->setUsername($username);
    $this->setPassword($password);
    $this->setSitegroup($sitegroup);
  }

  /**
   * Sets the Cloud API endpoint.
   *
   * @param string $endpoint
   *   The endpoint.
   *
   * @throws \InvalidArgumentException
   *   When the endpoint argument is empty or not a string.
   */
  protected function setEndpoint($endpoint) {
    if (empty($endpoint) || !is_string($endpoint)) {
      throw new \InvalidArgumentException('The endpoint parameter must be a non-empty string.');
    }
    $this->endpoint = $endpoint;
  }

  /**
   * Gets the Cloud API endpoint.
   *
   * @return string
   *   The endpoint.
   */
  public function getEndpoint() {
    return $this->endpoint;
  }

  /**
   * Gets the Cloud API user name.
   *
   * @param string $username
   *   The user name.
   *
   * @throws \InvalidArgumentException
   *   When the username argument is empty or not a string.
   */
  protected function setUsername($username) {
    if (empty($username) || !is_string($username)) {
      throw new \InvalidArgumentException('The username parameter must be a non-empty string.');
    }
    $this->username = $username;
  }

  /**
   * Gets the Cloud API user name.
   *
   * @return string
   *   The user name.
   */
  public function getUsername() {
    return $this->username;
  }

  /**
   * Sets the Cloud API password.
   *
   * @param string $password
   *   The password.
   *
   * @throws \InvalidArgumentException
   *   When the password argument is empty or not a string.
   */
  protected function setPassword($password) {
    if (empty($password) || !is_string($password)) {
      throw new \InvalidArgumentException('The password parameter must be a non-empty string.');
    }
    $this->securePassword = $this->encrypt($password);
  }

  /**
   * Gets the Cloud API password.
   *
   * @return string
   *   The password.
   */
  public function getPassword() {
    return $this->decrypt($this->securePassword);
  }

  /**
   * Sets the hosting sitegroup.
   *
   * @param string $sitegroup
   *   The hosting sitegroup.
   *
   * @throws \InvalidArgumentException
   *   When the sitegroup argument is empty or not a string.
   */
  protected function setSitegroup($sitegroup) {
    if (empty($sitegroup) || !is_string($sitegroup)) {
      throw new \InvalidArgumentException('The sitegroup parameter must be a non-empty string.');
    }
    $this->sitegroup = $sitegroup;
  }

  /**
   * Gets the hosting sitegroup.
   *
   * @return string
   *   The hosting sitegroup.
   */
  public function getSitegroup() {
    return $this->sitegroup;
  }

}
