<?php

namespace Acquia\Wip\Security;

/**
 * A simple form of authentication that uses a username and password.
 */
class SimpleAuthentication implements AuthenticationInterface {

  /**
   * The account ID.
   *
   * @var string
   */
  private $accountId = NULL;

  /**
   * The password.
   *
   * @var string
   */
  private $secret = NULL;

  /**
   * {@inheritdoc}
   */
  public function getAccountId() {
    return $this->accountId;
  }

  /**
   * {@inheritdoc}
   */
  public function getSecret() {
    return $this->secret;
  }

  /**
   * Sets the account ID.
   *
   * @param string $account_id
   *   The account ID.
   */
  public function setAccountId($account_id) {
    if (!is_string($account_id)) {
      throw new \InvalidArgumentException('The account_id parameter must be a string.');
    }
    $this->accountId = $account_id;
  }

  /**
   * Sets the account secret.
   *
   * @param string $secret
   *   The secret.
   */
  public function setSecret($secret) {
    if (!is_string($secret)) {
      throw new \InvalidArgumentException('The secret parameter must be a string.');
    }
    $this->secret = $secret;
  }

}
