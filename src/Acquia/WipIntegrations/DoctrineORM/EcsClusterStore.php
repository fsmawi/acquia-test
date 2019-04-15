<?php

namespace Acquia\WipIntegrations\DoctrineORM;

use Acquia\WipIntegrations\DoctrineORM\Entities\EcsClusterStoreEntry;
use Acquia\WipInterface\EcsClusterStoreInterface;
use Acquia\WipService\App;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Storage functions for ECS cluster metadata.
 */
class EcsClusterStore implements EcsClusterStoreInterface {

  /**
   * The name of the entity.
   */
  const ENTITY_NAME = 'Acquia\WipIntegrations\DoctrineORM\Entities\EcsClusterStoreEntry';

  /**
   * The entity manager.
   *
   * @var EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Creates a new instance of EcsClusterStore.
   */
  public function __construct() {
    $this->entityManager = App::getEntityManager();
  }

  /**
   * {@inheritdoc}
   */
  public function load($name) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    return $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->find($name);
  }

  /**
   * {@inheritdoc}
   */
  public function loadAll() {
    $result = [];

    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();
    $entries = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->findBy(array());
    foreach ($entries as $entry) {
      $result[] = $entry;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function save($name, $key_id, $secret, $region, $cluster) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    $cluster_data = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->find($name);
    if (!$cluster_data) {
      $cluster_data = new EcsClusterStoreEntry();
      $cluster_data->setName($name);
    }
    $cluster_data->setAwsAccessKeyId($key_id);
    $cluster_data->setAwsSecretAccessKey($secret);
    $cluster_data->setRegion($region);
    $cluster_data->setCluster($cluster);

    $this->entityManager->persist($cluster_data);
    $this->entityManager->flush();
  }

  /**
   * {@inheritdoc}
   */
  public function delete($name) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    $cluster = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->find($name);
    if ($cluster) {
      $this->entityManager->remove($cluster);
      $this->entityManager->flush();
    }
  }

}
