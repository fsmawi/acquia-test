<?php

namespace Acquia\WipIntegrations\DoctrineORM\Entities;

/**
 * Defines an entity for storing state data.
 *
 * @Entity @Table(name="state", options={"engine"="InnoDB"})
 */
class StateStoreEntry {

  /**
   * The unique name of the record.
   *
   * @var string
   *
   * @Id @Column(type="string", length=255, options={"unique"=true})
   */
  private $name;

  /**
   * The state value.
   *
   * @var string
   *
   * @Column(type="text")
   */
  private $value;

  /**
   * The changed timestamp.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true})
   */
  private $changed;

  /**
   * Gets the state value.
   *
   * @return mixed
   *   The state value.
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Sets the state value.
   *
   * @param mixed $value
   *   The state value.
   */
  public function setValue($value) {
    $this->value = $value;
  }

  /**
   * Gets the unique name.
   *
   * @return string
   *   The unique name.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Sets the unique name.
   *
   * @param string $name
   *   The unique name.
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * Gets the changed timestamp.
   *
   * @return mixed
   *   The changed timestamp.
   */
  public function getChanged() {
    return $this->changed;
  }

  /**
   * Sets the changed timestamp.
   *
   * @param mixed $changed
   *   The changed timestamp.
   */
  public function setChanged($changed) {
    $this->changed = $changed;
  }

}
