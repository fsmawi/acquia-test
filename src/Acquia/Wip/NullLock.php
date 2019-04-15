<?php

namespace Acquia\Wip;

/**
 * Implements Acquia\Wip\Lockinterface.
 *
 * Dummy implementation of a locking scheme - allows all locks to be acquired.
 */
class NullLock implements LockInterface {

  /**
   * {@inheritdoc}
   */
  public function acquire($name, $timeout = NULL) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function release($name) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isFree($name) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isMine($name) {
    return TRUE;
  }

}
