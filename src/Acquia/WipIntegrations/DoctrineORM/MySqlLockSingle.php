<?php

namespace Acquia\WipIntegrations\DoctrineORM;

use Acquia\WipService\App;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\WipLogLevel;

/**
 * Lock in mysql that permits only a single lock per connection.
 *
 * MySQL version 5.7.5 and above fully support multiple simultaneous locks
 * per connection. This class is meant to work with versions below that for
 * which only one lock at a time is permitted on a single connection.
 *
 * For these MySQL versions, as soon as a second lock is granted, any other
 * lock associated with the same connection is automatically released.
 */
class MySqlLockSingle extends MySqlLock {

  /**
   * The set of locks that are supposed to be maintained on this connection.
   *
   * @var string[]
   */
  private $lockStack = array();

  /**
   * {@inheritdoc}
   */
  public function acquire($name, $max_acquire_delay = NULL) {
    $current_lock = $this->currentLockName();
    if (!empty($current_lock)) {
      $this->log(
        WipLogLevel::ERROR,
        sprintf(
          'Trying to acquire a second lock (%s) while holding, and therefore losing, (%s)',
          $name,
          $current_lock
        )
      );
    }

    // Note: This will release the previously held lock.
    $result = parent::acquire($name, $max_acquire_delay);
    if ($result) {
      $this->log(WipLogLevel::ALERT, sprintf('Acquired lock "%s"', $name));

      // Record this lock. If a new lock is acquired and then released, this lock
      // will automatically be re-acquired.
      $this->pushLock($name);
    } else {
      // The acquire failed, make sure we still have the previous lock.
      $this->reAcquire();
    }
    return $result;
  }

  /**
   * Re-acquires the lock at the top of the stack.
   *
   * If it isn't possible to reacquire the lock immediately, an exception will
   * be thrown.
   *
   * @throws \DomainException
   *   If the previous lock could not be re-acquired. This means that another
   *   connection has grabbed the lock, possibly indicating a conflict.
   */
  private function reAcquire() {
    $lock_name = $this->currentLockName();
    if (NULL === $lock_name) {
      return;
    }
    if (!$this->isMine($lock_name)) {
      if (!parent::acquire($lock_name, 1)) {
        $this->log(WipLogLevel::FATAL, sprintf('Failed to re-acquire lock "%s"', $lock_name));
        throw new \DomainException(sprintf('Failed to re-acquire lock "%s".', $lock_name));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function release($name) {
    if (in_array($name, $this->lockStack)) {
      $result = parent::release($name);
      $this->log(WipLogLevel::ALERT, sprintf('Released lock "%s"', $name));
      $this->removeLock($name);

      // Try to get the previous lock back.
      $this->reAcquire();
    } else {
      // The lock is not owned by this process.
      $result = FALSE;
    }
    return $result;
  }

  /**
   * Pushes the specified lock onto the stack.
   *
   * Note that only the top-most lock will be held; all others will be released
   * when a new lock is acquired.
   *
   * @param string $lock_name
   *   The name of the lock.
   */
  private function pushLock($lock_name) {
    array_push($this->lockStack, $lock_name);
  }

  /**
   * Removes the specified lock from the stack.
   *
   * @param string $lock_name
   *   The lock name.
   */
  private function removeLock($lock_name) {
    $locks = array();
    foreach ($this->lockStack as $lock) {
      if ($lock !== $lock_name) {
        $locks[] = $lock;
      }
    }
    $this->lockStack = $locks;
  }

  /**
   * Gets the name of the current lock without removing it from the stack.
   *
   * @return string
   *   The name of the current lock or NULL if no lock remains on the stack.
   */
  private function currentLockName() {
    $result = end($this->lockStack);
    if ($result === FALSE) {
      $result = NULL;
    }
    return $result;
  }

  /**
   * Logs the specified message.
   *
   * This is used only for debugging the locks, which can be quite helpful.
   *
   * @param int $level
   *   The WipLogLevel.
   * @param string $message
   *   The message to log.
   */
  private function log($level, $message) {
    // This logging should never be run in production due to the volume of log
    // messages it produces. In order to prevent this, the code must be
    // modified and the system must be running in debug mode just in case debug
    // code happens to get released.
    if (FALSE && App::getApp()['config.global']['debug']) {
      WipLog::getWipLog()->log($level, sprintf('%s - %s:%s', $message, gethostname(), getmypid()));
    }
  }

}
