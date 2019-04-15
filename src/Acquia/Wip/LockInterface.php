<?php

namespace Acquia\Wip;

/**
 * Interface used to define a basic lock management object.
 */
interface LockInterface {

  /**
   * Acquires a lock for a given name.
   *
   * @param string $name
   *   The name of the lock to attempt to acquire.
   * @param int $timeout
   *   The duration the lock will be held.
   *
   * @return bool
   *   TRUE if the lock could be successfully acquired.
   */
  public function acquire($name, $timeout = NULL);

  /**
   * Releases a lock for a given name.
   *
   * @param string $name
   *   The name of the lock to release.
   *
   * @return bool
   *   TRUE if the lock was released; FALSE otherwise.
   */
  public function release($name);

  /**
   * Determines if a lock is free.
   *
   * Note that the result of acquire() must still be checked.  isFree only
   * indicates that a lock is free at the time of checking, but as it does not
   * acquire the lock, this still allows another process to grab the lock
   * between the initial call to isFree and actually acquiring the lock.
   *
   * @param string $name
   *   The name of the lock to check.
   *
   * @return bool
   *   TRUE if the lock is available; FALSE otherwise.
   */
  public function isFree($name);

  /**
   * Determines whether we still hold a lock that was previously acquired.
   *
   * Use this only to verify that a lock that was previously acquired by a
   * process was not for some reason acquired by another process (which could
   * happen if the lock timed-out, or if the lock was forcibly broken, or if the
   * lock service was relaunched).
   *
   * @param string $name
   *   The name of the lock to check.
   *
   * @return bool
   *   TRUE if the lock is still held by the process that acquired it, otherwise
   *   FALSE.
   */
  public function isMine($name);

}
