<?php

namespace Acquia\WipIntegrations\DoctrineORM\Entities;

// @codingStandardsIgnoreStart
/**
 * Defines an entity for storing signal callbacks.
 *
 * @Entity
 * @Table(name="signal_callbacks", options={"engine"="InnoDB"}, indexes={
 *   @Index(name="uuid_idx", columns={"uuid"}),
 *   @Index(name="wip_id_idx", columns={"wip_id"})
 * })
 */
class SignalCallbackStoreEntry {

  //@codingStandardsIgnoreEnd
  /**
   * The universally unique identifier of the signal callback.
   *
   * @var string
   *
   * @Id @Column(type="string", length=255)
   */
  private $uuid;

  /**
   * The Wip object ID the signal callback is related to.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true}, name="wip_id")
   */
  private $wipId;

  /**
   * The type of signal callback.
   *
   * @var int
   *
   * @Column(type="smallint", options={"unsigned"=true})
   */
  private $type;

  /**
   * Gets the UUID.
   *
   * @return string
   *   The UUID.
   */
  public function getUuid() {
    return $this->uuid;
  }

  /**
   * Sets the UUID.
   *
   * @param string $uuid
   *   The UUID.
   */
  public function setUuid($uuid) {
    $this->uuid = $uuid;
  }

  /**
   * Gets the Wip object ID.
   *
   * @return int
   *   The Wip object ID.
   */
  public function getWipId() {
    return $this->wipId;
  }

  /**
   * Sets the Wip object ID.
   *
   * @param int $wip_id
   *   The Wip object ID.
   */
  public function setWipId($wip_id) {
    $this->wipId = $wip_id;
  }

  /**
   * Gets the signal type.
   *
   * @return int
   *   The signal type.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Sets the signal type.
   *
   * @param int $type
   *   The signal type.
   */
  public function setType($type) {
    $this->type = $type;
  }

}
