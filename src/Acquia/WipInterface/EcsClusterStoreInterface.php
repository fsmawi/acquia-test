<?php

namespace Acquia\WipInterface;

use Acquia\WipIntegrations\DoctrineORM\Entities\EcsClusterStoreEntry;

/**
 * Defines an interface for ECS cluster record storage.
 */
interface EcsClusterStoreInterface {

  /**
   * Load a cluster record for a given name.
   *
   * @param string $name
   *   The name of the ECS cluster record.
   *
   * @return EcsClusterStoreEntry
   *   The ECS cluster record.
   */
  public function load($name);

  /**
   * Loads all cluster records.
   *
   * @return EcsClusterStoreEntry[]
   *   The ECS cluster record.
   */
  public function loadAll();

  /**
   * Saves an ECS cluster record.
   *
   * @param string $name
   *   The name that will be used to refer to the cluster record.
   * @param string $key_id
   *   The AWS key ID.
   * @param string $secret
   *   The AWS secret.
   * @param string $region
   *   The AWS region name.
   * @param string $cluster
   *   The ECS cluster name.
   */
  public function save($name, $key_id, $secret, $region, $cluster);

  /**
   * Deletes an ECS cluster record.
   *
   * @param string $name
   *   The name of the ECS cluster record.
   */
  public function delete($name);

}
