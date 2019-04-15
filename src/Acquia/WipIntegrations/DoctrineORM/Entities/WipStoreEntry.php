<?php

namespace Acquia\WipIntegrations\DoctrineORM\Entities;

/**
 * Defines an entity for storing Wip objects.
 *
 * @Entity @Table(name="wip_store", options={"engine"="InnoDB"})
 */
class WipStoreEntry {

  /**
   * The ID of the Wip object.
   *
   * @var int
   *
   * @Id @Column(type="integer", options={"unsigned"=true})
   */
  private $wid;

  /**
   * The serialized Wip object.
   *
   * @var string
   *
   * @Column(type="text")
   */
  private $obj;

  /**
   * The set of required include file.
   *
   * @var string
   *
   * @Column(type="text")
   */
  private $requires;

  /**
   * The time of the last update to this Wip object.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true})
   */
  private $timestamp;

  /**
   * Sets the ID of the Wip object.
   *
   * @param int $wid
   *   The ID of the Wip object.
   */
  public function setWid($wid) {
    $this->wid = $wid;
  }

  /**
   * Gets the serialized representation of the Wip object.
   *
   * @return string
   *   The Wip object.
   */
  public function getObj() {
    return $this->obj;
  }

  /**
   * Sets the serialized representation of the Wip object.
   *
   * @param string $obj
   *   The Wip object.
   */
  public function setObj($obj) {
    $this->obj = $obj;
  }

  /**
   * Gets the serialized representation of the set of required include files.
   *
   * @return string
   *   The set of required include files.
   */
  public function getRequires() {
    return $this->requires;
  }

  /**
   * Sets the serialized representation of the set of required include files.
   *
   * @param string $requires
   *   The set of required include files.
   */
  public function setRequires($requires) {
    $this->requires = $requires;
  }

  /**
   * Gets the timestamp of the last update to this Wip object.
   *
   * @return int
   *   The timestamp.
   */
  public function getTimestamp() {
    return $this->timestamp;
  }

  /**
   * Sets the timestamp of the last update to this Wip object.
   *
   * @param int $timestamp
   *   The timestamp.
   */
  public function setTimestamp($timestamp) {
    if (!is_int($timestamp) || $timestamp < 0) {
      throw new \InvalidArgumentException('The timestamp must be a positive integer.');
    }
    $this->timestamp = $timestamp;
  }

}
