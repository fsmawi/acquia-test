<?php

namespace Acquia\WipIntegrations\DoctrineORM\Entities;

/**
 * Defines an entity for storing configuration values.
 *
 * @Entity @Table(name="configuration", options={"engine"="InnoDB"})
 */
class ConfigurationStoreEntry {

  /**
   * The name of the configuration.
   *
   * @var string
   *
   * @Id @Column(type="string", length=255, options={"unique"=true})
   */
  private $name;

  /**
   * The value of the configuration.
   *
   * @var string
   *
   * @Column(type="text")
   */
  private $value;

  /**
   * Gets the value of the configuration.
   *
   * @return mixed
   *   The configuration value.
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Sets the value of the configuration.
   *
   * @param mixed $value
   *   The configuration value.
   */
  public function setValue($value) {
    $this->value = $value;
  }

  /**
   * Gets the name of the configuration.
   *
   * @return string
   *   The configuration name.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Sets the name of the configuration.
   *
   * @param string $name
   *   The configuration name.
   */
  public function setName($name) {
    $this->name = $name;
  }

}
