<?php

namespace Acquia\Wip\Security;

use Acquia\Wip\WipFactory;

/**
 * A trait that provides a generic way of implying secure behavior.
 */
trait SecureTrait {

  /**
   * Whether or not this object is secure.
   *
   * @var bool
   */
  private $secure = FALSE;

  /**
   * Sets this object as being secure.
   *
   * @param bool $secure
   *   Whether or not this process is secure.
   *
   * @return WipResultInterface
   *   The WipResult object.
   */
  public function setSecure($secure = TRUE) {
    if (!is_bool($secure)) {
      throw new \InvalidArgumentException('The secure argument must be of boolean type.');
    }
    $this->secure = $secure;

    return $this;
  }

  /**
   * Determines whether or not this object is secure.
   *
   * @return bool
   *   TRUE if secure.
   */
  public function isSecure() {
    return $this->secure;
  }

  /**
   * Determines whether or not this object is in debug mode.
   *
   * @return bool
   *   TRUE if in debug mode.
   */
  public function isInDebugMode() {
    return WipFactory::getBool('$acquia.wip.secure.debug', FALSE);
  }

}
