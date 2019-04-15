<?php

namespace Acquia\WipService\Test;

use Acquia\WipIntegrations\DoctrineORM\MySqlLock;

/**
 * Class LockTester.
 *
 * Utility for testing MySqlLock locks: receives signals as commands to attempt
 * to acquire or release a lock.
 */
class LockTester {

  /**
   * Timeout value to use on any lock acquire call.
   *
   * @var int
   */
  private $acquireTimeout = 0;

  /**
   * Maximum number of seconds that sleep() should run for.
   *
   * @var int
   */
  private $timeout = 0;

  /**
   * Lock name to use for all locks.
   */
  const LOCKNAME = 'testlock';

  /**
   * The lock.
   *
   * @var MySqlLock
   */
  private $lock;

  /**
   * Prefix used in log messages.
   *
   * @var string
   */
  private $prefix = '';

  /**
   * Temporary file name.
   *
   * @var string
   */
  private $tempfile = '';

  /**
   * UNIX timestamp when this process started.
   *
   * @var int
   */
  private $start = 0;

  /**
   * Missing summary.
   */
  public function __construct() {
    if (function_exists('pcntl_signal')) {
      declare(ticks = 1);
      // Signal to attempt to acquire the lock:
      pcntl_signal(SIGUSR1, array($this, 'handleSignal'));
      // Signal to attempt to release the lock:
      pcntl_signal(SIGUSR2, array($this, 'handleSignal'));
      // Signal to terminate gracefully:
      pcntl_signal(SIGABRT, array($this, 'handleSignal'));
      // Signal to check that the proc still holds the lock:
      pcntl_signal(SIGHUP, array($this, 'handleSignal'));
    } else {
      throw new \Exception('pcntl functions not available');
    }

    $this->lock = new MySqlLock();
    $this->start = time();
  }

  /**
   * Missing summary.
   */
  public function setAcquireTimeout($seconds) {
    $this->acquireTimeout = $seconds;
  }

  /**
   * Missing summary.
   */
  public function setPrefix($prefix) {
    $this->prefix = $prefix;
  }

  /**
   * Missing summary.
   */
  public function setTempFile($filename) {
    $this->tempfile = $filename;
  }

  /**
   * Sleeps for a given number of seconds - can also be interrupted and resumed.
   *
   * If a signal is received and the signal handler returns control to this
   * function with no arguments, it should continue until its allotted time has
   * run out.
   *
   * @param int $seconds
   *   The number of seconds to sleep.
   */
  public function sleep($seconds = NULL) {
    if (isset($seconds)) {
      $this->timeout = $seconds;
    }

    do {
      $remaining = $this->timeout - (time() - $this->start);
      echo "$this->prefix: $remaining \n";
      sleep(1);
    } while ($remaining > 0);
  }

  /**
   * Missing summary.
   */
  private function handleSignal($signal) {
    echo "$this->prefix: SIG: $signal\n";
    switch ($signal) {
      case SIGUSR1:
        $result = $this->lock->acquire(self::LOCKNAME, $this->acquireTimeout);
        $output = ($result ? 'ACQ' : '');
        file_put_contents($this->tempfile, "$this->prefix:$output\n", FILE_APPEND);
        echo "$this->prefix: LOCK ACQUIRE: " . var_export($result, TRUE) . "\n";
        break;

      case SIGUSR2:
        $this->lock->release(self::LOCKNAME);
        file_put_contents($this->tempfile, "$this->prefix:REL\n", FILE_APPEND);
        echo "$this->prefix: LOCK RELEASE\n";
        break;

      case SIGHUP:
        $mine = ($this->lock->isMine(self::LOCKNAME) ? 'YES' : 'NO');
        file_put_contents($this->tempfile, "$this->prefix:IS MINE:$mine\n", FILE_APPEND);
        echo "$this->prefix:IS MINE:$mine\n";
        break;

      case SIGABRT:
        echo "$this->prefix: LOCK RELEASE ALL\n";
        exit(0);
    }
  }

}
