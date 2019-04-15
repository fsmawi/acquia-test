<?php

namespace Acquia\Wip\Lock;

use Acquia\Wip\Exception\RowLockException;
use Acquia\Wip\LockInterface;

/**
 * Provides a lock while performing an operation on an object.
 *
 * Ensures that no other process can update an object while another process
 * is performing operations on it.
 */
interface RowLockInterface extends LockInterface {

  /**
   * Performs an operation with an atomic lock.
   *
   * This function will attempt to ensure that it obtains a lock and performs
   * its updates.
   *
   * @param mixed $class
   *   The class being processed.
   * @param string $method
   *   The method being called.
   * @param array $parameters
   *   The parameters for the method.
   *
   * @return mixed
   *   The return value of the method being called.
   *
   * @throws RowLockException
   *   If the lock could not be acquired.
   * @throws \Exception
   *   If the specified method throws an exception.
   */
  public function runAtomic($class, $method, $parameters = array());

  /**
   * Determines if we have a lock on an item.
   *
   * @return bool
   *   Do we have a lock.
   */
  public function hasLock();

  /**
   * Sets the lock timeout.
   *
   * @param int $seconds
   *   The number of seconds before the lock times out.
   *
   * @return $this
   *   This RowLockInterface instance.
   */
  public function setTimeout($seconds);

}
