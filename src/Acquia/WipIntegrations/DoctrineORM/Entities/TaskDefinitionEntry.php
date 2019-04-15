<?php

namespace Acquia\WipIntegrations\DoctrineORM\Entities;

/**
 * Defines an entity for storing ECS task definitions.
 *
 * @Entity @Table(name="task_definition", options={"engine"="InnoDB"})
 */
class TaskDefinitionEntry {

  /**
   * The ID of the row.
   *
   * @var int
   *
   * @Id @Column(type="integer")
   *
   * @GeneratedValue
   */
  private $id;

  /**
   * The name of the task definition.
   *
   * @var string
   *
   * @Column(type="string", length=255)
   */
  private $name;

  /**
   * The AWS region in which the task is registered.
   *
   * @var string
   *
   * @Column(type="string", length=255)
   */
  private $region;

  /**
   * The revision of the task.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true})
   */
  private $revision;

  /**
   * The task definition.
   *
   * @var string
   *
   * @Column(type="text")
   */
  private $definition;

  /**
   * Gets the ID of the task definition.
   *
   * @return int
   *   The ID of the task definition.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Sets the ID of the task definition.
   *
   * @param int $id
   *   The ID of the task definition.
   */
  public function setId($id) {
    $this->id = $id;
  }

  /**
   * Gets the name of the task definition.
   *
   * @return string
   *   The name of the task definition.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Sets the name of the task definition.
   *
   * @param string $name
   *   The name of the task definition.
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * Gets the AWS region.
   *
   * @return string
   *   The AWS region.
   */
  public function getRegion() {
    return $this->region;
  }

  /**
   * Sets the AWS region.
   *
   * @param string $region
   *   The AWS region.
   */
  public function setRegion($region) {
    $this->region = $region;
  }

  /**
   * Gets the task definition.
   *
   * @return string
   *   The task definition.
   */
  public function getDefinition() {
    return $this->definition;
  }

  /**
   * Sets the task definition.
   *
   * @param string $definition
   *   The task definition.
   */
  public function setDefinition($definition) {
    $this->definition = $definition;
  }

  /**
   * Gets the revision of the task.
   *
   * @return mixed
   *   The revision of the task.
   */
  public function getRevision() {
    return $this->revision;
  }

  /**
   * Sets the revision of the task.
   *
   * @param mixed $revision
   *   The revision of the task.
   */
  public function setRevision($revision) {
    $this->revision = $revision;
  }

}
