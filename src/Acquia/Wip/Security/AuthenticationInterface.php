<?php

namespace Acquia\Wip\Security;

/**
 * This interface provides a modular authentication mechanism.
 */
interface AuthenticationInterface {

  /**
   * Gets the ID of the account that will be used.
   *
   * When using username / password, this would be the username.
   *
   * @return string
   *   The account ID.
   */
  public function getAccountId();

  /**
   * Gets the secret associated with the account.
   *
   * When using username / password for authentication, this would be the
   * password.
   *
   * @return string
   *   The account secret.
   */
  public function getSecret();

}
