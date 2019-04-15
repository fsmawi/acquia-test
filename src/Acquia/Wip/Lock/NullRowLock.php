<?php

namespace Acquia\Wip\Lock;

/**
 * The RowLock implementation used for testing.
 */
class NullRowLock implements RowLockInterface {

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

  /**
   * {@inheritdoc}
   */
  public function runAtomic($class, $method, $parameters = array()) {
    $result = call_user_func_array(array($class, $method), $parameters);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function hasLock() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function setTimeout($seconds) {
    if (!is_int($seconds) || $seconds < 0) {
      throw new \InvalidArgumentException('The "seconds" parameter must be a positive integer or zero.');
    }
    $this->timeout = $seconds;
    return $this;
  }

  /**
   * Sets the prefix for the lock.
   *
   * @param string $prefix
   *   The prefix.
   */
  public function setPrefix($prefix) {
  }

  /**
   * Sets the object Id for the lock.
   *
   * @param int $object_id
   *   Object Id.
   */
  public function setObjectId($object_id) {
  }

}
