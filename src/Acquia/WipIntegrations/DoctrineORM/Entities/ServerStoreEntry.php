<?php

namespace Acquia\WipIntegrations\DoctrineORM\Entities;

// @codingStandardsIgnoreStart
/**
 * Defines an entity for storing worker servers.
 *
 * @Entity @Table(name="server_store", options={"engine"="InnoDB"}, indexes={
 *   @Index(name="status_idx", columns={"status"}),
 * })
 */
class ServerStoreEntry {

  //@codingStandardsIgnoreEnd
  /**
   * The sequential ID.
   *
   * @var int
   *
   * @Id @GeneratedValue @Column(type="integer", options={"unsigned"=true})
   */
  private $id;

  /**
   * The server hostname.
   *
   * @var string
   *
   * @Column(type="string", length=255, options={"unique"=true})
   */
  private $hostname;

  /**
   * The maximum number of threads that are allowed to run on the server.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true}, name="total_threads")
   */
  private $totalThreads;

  /**
   * The status of the server.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true})
   */
  private $status;

  /**
   * Gets the ID.
   *
   * @return int
   *   The ID of the record.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Gets the server hostname.
   *
   * @return string
   *   The server hostname.
   */
  public function getHostname() {
    return $this->hostname;
  }

  /**
   * Gets the server hostname.
   *
   * @param string $hostname
   *   The server hostname.
   */
  public function setHostname($hostname) {
    $this->hostname = $hostname;
  }

  /**
   * Gets the total threads.
   *
   * @return int
   *   The total threads.
   */
  public function getTotalThreads() {
    return $this->totalThreads;
  }

  /**
   * Sets the total threads.
   *
   * @param int $total_threads
   *   The total threads.
   */
  public function setTotalThreads($total_threads) {
    $this->totalThreads = $total_threads;
  }

  /**
   * Gets the server status.
   *
   * @return int
   *   The server status.
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * Sets the server status.
   *
   * @param int $status
   *   The server status.
   */
  public function setStatus($status) {
    $this->status = $status;
  }

}
