<?php

namespace Acquia\Wip\Security;

/**
 * Contains all of the methods that a secure object needs to implement.
 */
interface SecureTraitInterface {

  /**
   * Sets this object as being secure.
   *
   * @param bool $secure
   *   Whether or not this process is secure.
   *
   * @return static
   *   The instance that is being executed against.
   */
  public function setSecure($secure = TRUE);

  /**
   * Determines whether or not this object is secure.
   *
   * @return bool
   *   TRUE if secure.
   */
  public function isSecure();

  /**
   * Determines whether or not this object is in debug mode.
   *
   * @return bool
   *   TRUE if in debug mode.
   */
  public function isInDebugMode();

}
