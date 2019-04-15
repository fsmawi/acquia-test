<?php

namespace Acquia\Wip\Lock;

use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\DependencyManager;
use Acquia\Wip\Exception\RowLockException;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\LockInterface;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipLogLevel;

/**
 * Provides a base implementation for locks.
 */
abstract class RowLock implements RowLockInterface, DependencyManagedInterface {

  /**
   * Row id.
   *
   * @var int
   */
  protected $rowId;

  /**
   * Object id.
   *
   * @var int
   */
  protected $objectId;

  /**
   * The wip logger.
   *
   * @var WipLogInterface
   */
  protected $logger;

  /**
   * Lock prefix.
   *
   * @var string
   */
  protected $lockPrefix = '';

  /**
   * Lock timeout in seconds.
   *
   * @var int
   */
  protected $timeout = 2;

  /**
   * Sleep in micro seconds.
   *
   * @var int
   */
  protected $sleep = 25000;

  /**
   * An instance of DependencyManager.
   *
   * @var DependencyManager
   */
  public $dependencyManager;

  /**
   * Delay in micro seconds.
   *
   * @var int
   */
  protected $delay = 6000000;

  /**
   * Creates a new instance of RowLock.
   */
  public function __construct() {
    $this->dependencyManager = new DependencyManager();
    $this->dependencyManager->addDependencies($this->getDependencies());
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
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.wiplog' => 'Acquia\Wip\WipLogInterface',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function runAtomic($class, $method, $parameters = array()) {
    /** @var \Exception $exception */
    $exception = NULL;
    $result = NULL;
    $key = $this->getKey();
    $lock_manager = $this->getLockImplementation();
    $acquired_lock = $lock_manager->acquire($key, $this->timeout);
    try {
      if ($acquired_lock) {
        $result = call_user_func_array(array($class, $method), $parameters);
      }
    } catch (\Exception $e) {
      $exception = $e;
    } finally {
      if ($acquired_lock) {
        $lock_manager->release($key);
      } else {
        $message = sprintf('Failed to lock %s before calling %s from %s', $key, $method, __METHOD__);
        throw new RowLockException($message);
      }
    }
    if (NULL !== $exception) {
      throw $exception;
    }
    return $result;
  }

  /**
   * Log a message.
   *
   * A row ID and an object ID are not necessarily the same, so if only
   * row ID is known, use an object ID of 0 for logging.
   *
   * @param int $level
   *   The level of log message.
   * @param string $message
   *   The message to log.
   */
  protected function log($level, $message) {
    if (!isset($this->logger)) {
      /* @var WipLogInterface */
      $this->logger = $this->dependencyManager->getDependency('acquia.wip.wiplog');
    }
    $object_id = 0;
    if (!empty($this->objectId)) {
      $object_id = $this->objectId;
    }
    $this->logger->log($level, $message, $object_id);
  }

  /**
   * Get the key for the lock.
   *
   * @return string
   *   The key for the lock.
   */
  protected function getKey() {
    return $this->lockPrefix . $this->rowId;
  }

  /**
   * {@inheritdoc}
   */
  public function hasLock() {
    /** @var LockInterface $lock_manager */
    $lock_manager = $this->dependencyManager->getDependency('acquia.wip.lock.rowlock');
    return $lock_manager->isMine($this->getKey());
  }

  /**
   * Sets the prefix for the lock.
   *
   * @param string $prefix
   *   The prefix.
   */
  public function setPrefix($prefix) {
    if (!empty($prefix)) {
      $this->lockPrefix = $prefix;
    }
  }

  /**
   * Sets the row Id for the lock.
   *
   * @param int $row_id
   *   Row Id.
   */
  public function setRowId($row_id) {
    if ($row_id !== NULL) {
      $this->rowId = $row_id;
    }
  }

  /**
   * Sets the object Id and row Id for the lock.
   *
   * When the object Id is known, it has the same value as the row Id.
   *
   * @param int $object_id
   *   Object Id.
   */
  public function setObjectId($object_id) {
    if ($object_id !== NULL) {
      $this->objectId = $object_id;
      $this->rowId = $object_id;
    }
  }

  /**
   * Acquires a lock for a given name.
   *
   * @param string $name
   *   Optional. The name of the lock to attempt to acquire. If not provided
   *   the row ID and prefix will be used to construct the name.
   * @param int $timeout
   *   The duration the lock will be held.
   *
   * @return bool
   *   TRUE if the lock could be successfully acquired.
   */
  public function acquire($name = NULL, $timeout = NULL) {
    $key = empty($name) ? $this->getKey() : $name;
    $lock_manager = $this->getLockImplementation();
    $result = $lock_manager->acquire($key, $timeout);
    return $result;
  }

  /**
   * Releases a lock for a given name.
   *
   * @param string $name
   *   Optional. The name of the lock to release. If not provided, the row
   *   ID and prefix will be used to construct the name.
   *
   * @return bool
   *   TRUE if the lock was released; FALSE otherwise.
   */
  public function release($name = NULL) {
    $key = empty($name) ? $this->getKey() : $name;
    $lock_manager = $this->getLockImplementation();
    return $lock_manager->release($key);
  }

  /**
   * Determines if a lock is free.
   *
   * Note that the result of acquire() must still be checked.  isFree only
   * indicates that a lock is free at the time of checking, but as it does not
   * acquire the lock, this still allows another process to grab the lock
   * between the initial call to isFree and actually acquiring the lock.
   *
   * @param string $name
   *   Optional. The name of the lock to check. If not provided, the prefix and
   *   row ID will be used to construct the name.
   *
   * @return bool
   *   TRUE if the lock is available; FALSE otherwise.
   */
  public function isFree($name = NULL) {
    $key = empty($name) ? $this->getKey() : $name;
    $lock_manager = $this->getLockImplementation();
    return $lock_manager->isFree($key);
  }

  /**
   * Determines whether we still hold a lock that was previously acquired.
   *
   * Use this only to verify that a lock that was previously acquired by a
   * process was not for some reason acquired by another process (which could
   * happen if the lock timed-out, or if the lock was forcibly broken, or if the
   * lock service was relaunched).
   *
   * @param string $name
   *   Optional. The name of the lock to check. If not provided, the row ID
   *   and prefix will be used to construct the name.
   *
   * @return bool
   *   TRUE if the lock is still held by the process that acquired it, otherwise
   *   FALSE.
   */
  public function isMine($name = NULL) {
    $key = empty($name) ? $this->getKey() : $name;
    $lock_manager = $this->getLockImplementation();
    return $lock_manager->isMine($key);
  }

  /**
   * Gets the lock implementation.
   *
   * @return LockInterface
   *   The underlying lock implementation.
   */
  protected function getLockImplementation() {
    /** @var LockInterface $lock_manager */
    return $this->dependencyManager->getDependency('acquia.wip.lock.rowlock');
  }

}
