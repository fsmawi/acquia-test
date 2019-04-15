<?php

namespace Acquia\WipIntegrations\DoctrineORM\Entities;

/**
 * Defines an entity for storing Wip applications.
 *
 * @Entity @Table(name="wip_application_store", options={"engine"="InnoDB"})
 */
class WipApplicationStoreEntry {

  /**
   * The sequential ID.
   *
   * @var int
   *
   * @Id @GeneratedValue @Column(type="integer", options={"unsigned"=true})
   */
  private $id;

  /**
   * The application's handler, like CCI entry or whatever ID we get.
   *
   * @var string
   *
   * @Column(type="string", length=255, options={"unique"=true})
   */
  private $handler;

  /**
   * The status of the Wip application.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true})
   */
  private $status;

  /**
   * Gets the sequential ID.
   *
   * @return int
   *   The sequential ID.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Gets the handler.
   *
   * @return string
   *   The handler.
   */
  public function getHandler() {
    return $this->handler;
  }

  /**
   * Sets the handler.
   *
   * @param string $handler
   *   The handler.
   */
  public function setHandler($handler) {
    $this->handler = $handler;
  }

  /**
   * Gets the status of the Wip application.
   *
   * @return int
   *   The status of the Wip application.
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * Sets the status of the Wip application.
   *
   * @param int $status
   *   The status of the Wip application.
   */
  public function setStatus($status) {
    $this->status = $status;
  }

}
