<?php

namespace Acquia\WipIntegrations\DoctrineORM\Entities;

// @codingStandardsIgnoreStart
/**
 * Defines an entity for storing signals.
 *
 * @Entity @Table(name="signal_store", options={"engine"="InnoDB"}, indexes={
 *   @Index(name="object_id_consumed_idx", columns={"object_id", "consumed"}),
 * })
 */
class SignalStoreEntry {

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
   * The Wip object ID.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true}, name="object_id")
   */
  private $objectId;

  /**
   * The signal type.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true})
   */
  private $type;

  /**
   * When the signal was sent.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true})
   */
  private $sent;

  /**
   * When the signal was consumed.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true})
   */
  private $consumed;

  /**
   * The signal data.
   *
   * @var string
   *
   * @Column(type="text")
   */
  private $data;

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
   * Sets the sequential ID.
   *
   * @param int $id
   *   The sequential ID.
   */
  public function setId($id) {
    $this->id = $id;
  }

  /**
   * Gets the Wip object ID.
   *
   * @return int
   *   The Wip object ID.
   */
  public function getObjectId() {
    return $this->objectId;
  }

  /**
   * Sets the Wip object ID.
   *
   * @param int $object_id
   *   The Wip object ID.
   */
  public function setObjectId($object_id) {
    $this->objectId = $object_id;
  }

  /**
   * Gets the signal type.
   *
   * @return mixed
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

  /**
   * Gets the sent time.
   *
   * @return int
   *   The sent time.
   */
  public function getSent() {
    return $this->sent;
  }

  /**
   * Sets the sent time.
   *
   * @param int $sent
   *   The sent time.
   */
  public function setSent($sent) {
    $this->sent = $sent;
  }

  /**
   * Gets the consumed time.
   *
   * @return int
   *   The consumed time.
   */
  public function getConsumed() {
    return $this->consumed;
  }

  /**
   * Sets the consumed time.
   *
   * @param int $consumed
   *   The consumed time.
   */
  public function setConsumed($consumed) {
    $this->consumed = $consumed;
  }

  /**
   * Gets the signal data.
   *
   * @return string
   *   The signal data.
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Sets the signal data.
   *
   * @param string $data
   *   The signal data.
   */
  public function setData($data) {
    $this->data = $data;
  }

}
