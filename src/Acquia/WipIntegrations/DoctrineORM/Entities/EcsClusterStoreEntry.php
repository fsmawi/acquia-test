<?php

namespace Acquia\WipIntegrations\DoctrineORM\Entities;

/**
 * Defines an entity for storing ECS cluster records.
 *
 * @Entity @Table(name="ecs_cluster", options={"engine"="InnoDB"})
 */
class EcsClusterStoreEntry {

  /**
   * The unique name of the ECS cluster record.
   *
   * @var string
   *
   * @Id @Column(type="string", length=255, options={"unique"=true})
   */
  private $name;

  /**
   * The AWS access key.
   *
   * @var string
   *
   * @Column(type="string", length=255, name="aws_access_key_id")
   */
  private $awsAccessKeyId;

  /**
   * The AWS secret key.
   *
   * @var string
   *
   * @Column(type="string", length=255, name="aws_secret_access_key")
   */
  private $awsSecretAccessKey;

  /**
   * The AWS region in which the cluster resides.
   *
   * @var string
   *
   * @Column(type="string", length=255)
   */
  private $region;

  /**
   * The name of the ECS cluster.
   *
   * @var string
   *
   * @Column(type="string", length=255)
   */
  private $cluster;

  /**
   * Gets the unique name of the ECS cluster record.
   *
   * @return string
   *   The unique name of the record.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Sets the unique name of the ECS cluster record.
   *
   * @param string $name
   *   The unique name of the record.
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * Gets the AWS access key.
   *
   * @return string
   *   The AWS access key.
   */
  public function getAwsAccessKeyId() {
    return $this->awsAccessKeyId;
  }

  /**
   * Sets the AWS access key.
   *
   * @param string $aws_access_key_id
   *   The AWS access key.
   */
  public function setAwsAccessKeyId($aws_access_key_id) {
    $this->awsAccessKeyId = $aws_access_key_id;
  }

  /**
   * Gets the AWS secret key.
   *
   * @return string
   *   The AWS secret key.
   */
  public function getAwsSecretAccessKey() {
    return $this->awsSecretAccessKey;
  }

  /**
   * Sets the AWS secret key.
   *
   * @param string $aws_secret_access_key
   *   The AWS secret key.
   */
  public function setAwsSecretAccessKey($aws_secret_access_key) {
    $this->awsSecretAccessKey = $aws_secret_access_key;
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
   * Gets the ECS cluster name.
   *
   * @return string
   *   The ECS cluster name.
   */
  public function getCluster() {
    return $this->cluster;
  }

  /**
   * Sets the ECS cluster name.
   *
   * @param string $cluster
   *   The ECS cluster name.
   */
  public function setCluster($cluster) {
    $this->cluster = $cluster;
  }

}
