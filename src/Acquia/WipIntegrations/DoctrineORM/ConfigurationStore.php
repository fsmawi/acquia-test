<?php

namespace Acquia\WipIntegrations\DoctrineORM;

use Acquia\WipIntegrations\DoctrineORM\Entities\ConfigurationStoreEntry;
use Acquia\WipService\App;
use Acquia\Wip\Storage\ConfigurationStoreInterface;
use Doctrine\ORM\EntityManagerInterface;
use Silex\Application;

/**
 * Provides CRUD features for configuration data using Doctrine ORM.
 *
 * @copydetails ConfigurationStoreInterface
 */
class ConfigurationStore implements ConfigurationStoreInterface {

  /**
   * The name of the entity.
   */
  const ENTITY_NAME = 'Acquia\WipIntegrations\DoctrineORM\Entities\ConfigurationStoreEntry';

  /**
   * The entity manager.
   *
   * @var EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Creates a new instance of ConfigurationStore.
   */
  public function __construct() {
    $this->entityManager = App::getEntityManager();
  }

  /**
   * {@inheritdoc}
   */
  public function get($key, $default = NULL) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    /** @var ConfigurationStoreEntry $value */
    $value = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->find($key);

    if ($value) {
      return unserialize($value->getValue());
    } else {
      return $default;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    $config = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->find($key);
    if (!$config) {
      $config = new ConfigurationStoreEntry();
      $config->setName($key);
    }
    $config->setValue(serialize($value));

    $this->entityManager->persist($config);
    $this->entityManager->flush();
  }

  /**
   * {@inheritdoc}
   */
  public function delete($key) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    $config = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->find($key);
    if ($config) {
      $this->entityManager->remove($config);
      $this->entityManager->flush();
    }
  }

}
