<?php

namespace Acquia\Wip\Runtime;

use Acquia\Wip\ServerStatus;

/**
 * Object that represents a server that can be used for processing tasks.
 */
class Server {

  /**
   * The ID of the server.
   *
   * @var int
   */
  private $id;

  /**
   * The hostname of the server.
   *
   * @var string
   */
  private $hostname;

  /**
   * The status of the server.
   *
   * @var int
   */
  private $status = ServerStatus::AVAILABLE;

  /**
   * The number of total threads.
   *
   * @var int
   */
  private $totalThreads = 1;

  /**
   * The number of active threads.
   *
   * @var int
   */
  private $activeThreads = 0;

  /**
   * The number of free threads.
   *
   * @var int
   */
  private $freeThreads = 1;

  /**
   * Creates a Server instance.
   *
   * @param string $hostname
   *   The hostname of the Server instance.
   */
  public function __construct($hostname) {
    $this->setHostname($hostname);
  }

  /**
   * Gets the current server's ID.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Sets the current server's ID.
   *
   * @param int $id
   *   The server ID.
   */
  public function setId($id) {
    if (!is_int($id) || $id < 0) {
      throw new \InvalidArgumentException('Invalid server ID provided.');
    }
    $this->id = $id;
  }

  /**
   * Gets the current server's hostname.
   */
  public function getHostname() {
    return $this->hostname;
  }

  /**
   * Sets the current server's hostname.
   *
   * @param string $hostname
   *   The server's hostname.
   */
  public function setHostname($hostname) {
    if (!is_string($hostname) || empty($hostname) || !($hostname = trim($hostname))) {
      throw new \InvalidArgumentException('Invalid hostname provided.');
    }
    $this->hostname = $hostname;
  }

  /**
   * Gets the current server's status.
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * Sets the current server's status.
   *
   * @param int $status
   *   The server status.
   *
   * @throws \InvalidArgumentException
   *   If the specified status is not recognized.
   */
  public function setStatus($status) {
    if (ServerStatus::isValid($status)) {
      $this->status = $status;
    } else {
      throw new \InvalidArgumentException('The status must hold a valid value.');
    }
  }

  /**
   * Gets the current server's number of threads that it may handle.
   */
  public function getTotalThreads() {
    return $this->totalThreads;
  }

  /**
   * Sets the current server's number of threads that it may handle.
   *
   * @param int $total_threads
   *   The total number of threads.
   */
  public function setTotalThreads($total_threads) {
    if (!is_int($total_threads) || $total_threads <= 0) {
      throw new \InvalidArgumentException('The total threads must be a positive integer.');
    }
    $this->totalThreads = $total_threads;
  }

  /**
   * Gets the current server's number of active threads.
   */
  public function getActiveThreads() {
    return $this->activeThreads;
  }

  /**
   * Sets the current server's number of active threads.
   *
   * @param int $active_threads
   *   The number of active threads.
   */
  public function setActiveThreads($active_threads) {
    if (!is_int($active_threads) || $active_threads < 0) {
      throw new \InvalidArgumentException('The active threads must be a non-negative integer.');
    }
    $this->activeThreads = $active_threads;
  }

  /**
   * Gets the current server's number of free threads.
   */
  public function getFreeThreads() {
    return $this->freeThreads;
  }

  /**
   * Sets the current server's number of free threads.
   *
   * @param int $free_threads
   *   The number of free threads.
   */
  public function setFreeThreads($free_threads) {
    if (!is_int($free_threads) || $free_threads < 0 || $free_threads > $this->totalThreads - $this->activeThreads) {
      throw new \InvalidArgumentException('The active threads must be a non-negative integer.');
    }
    $this->freeThreads = $free_threads;
  }

}
