<?php

namespace Acquia\WipIntegrations\DoctrineORM;

use Acquia\WipIntegrations\DoctrineORM\Entities\WipModuleTaskStoreEntry;
use Acquia\WipService\App;
use Acquia\Wip\DependencyManagerInterface;
use Acquia\Wip\Storage\WipModuleTaskStoreInterface;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipModuleTaskInterface;
use Doctrine\ORM\EntityManagerInterface;
use Silex\Application;

/**
 * Provides CRUD features for task storage by module using Doctrine ORM.
 *
 * @copydetails WipModuleTaskStoreInterface
 */
class WipModuleTaskStore implements WipModuleTaskStoreInterface {

  /**
   * The WipFactory resource name.
   */
  const RESOURCE_NAME = 'acquia.wip.storage.module_task';

  /**
   * The entity name.
   */
  const ENTITY_NAME = 'Acquia\WipIntegrations\DoctrineORM\Entities\WipModuleTaskStoreEntry';

  /**
   * The entity manager.
   *
   * @var EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Creates a new instance of WipModuleTaskStore.
   */
  public function __construct() {
    $this->entityManager = App::getEntityManager();
  }

  /**
   * {@inheritdoc}
   */
  public function get($name) {
    if (!is_string($name) || trim($name) == FALSE) {
      throw new \InvalidArgumentException('The "name" parameter must be a non-empty string.');
    }

    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    /** @var WipModuleTaskStoreEntry $entry */
    $entry = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->find(trim($name));

    $result = NULL;
    if ($entry) {
      $result = $entry->toWipModuleTask();
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function save(WipModuleTaskInterface $task) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    /** @var WipModuleTaskStoreEntry $entry */
    $entry = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->find($task->getName());

    if (!$entry) {
      $entry = WipModuleTaskStoreEntry::fromWipModuleTask($task);
    }

    $this->entityManager->persist($entry);
    $this->entityManager->flush();
  }

  /**
   * {@inheritdoc}
   */
  public function delete($name) {
    if (!is_string($name) || trim($name) == FALSE) {
      throw new \InvalidArgumentException('The "name" parameter must be a non-empty string.');
    }

    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    $entry = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->find(trim($name));

    if ($entry) {
      $this->entityManager->remove($entry);
      $this->entityManager->flush();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTasksByModuleName($module_name) {
    $result = array();
    if (!is_string($module_name) || trim($module_name) == FALSE) {
      throw new \InvalidArgumentException('The "module_name" parameter must be a non-empty string.');
    }

    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    /** @var WipModuleTaskStoreEntry[] $entries */
    $entries = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->findBy(array('moduleName' => trim($module_name)));

    if ($entries) {
      foreach ($entries as $entry) {
        $result[] = $entry->toWipModuleTask();
      }
    }
    return $result;
  }

  /**
   * Gets the configured WipModuleTaskStoreInterface instance.
   *
   * @param DependencyManagerInterface $dependency_manager
   *   Optional. The DependencyManager instance.
   *
   * @return WipModuleTaskStoreInterface
   *   The instance.
   *
   * @throws \Acquia\Wip\Exception\DependencyMissingException
   *   If a DependencyManager instance is provided but the
   *   WipModuleTaskStoreInterface has not been set as a dependency.
   */
  public static function getWipModuleTaskStore(DependencyManagerInterface $dependency_manager = NULL) {
    $result = NULL;
    if (NULL !== $dependency_manager) {
      $result = $dependency_manager->getDependency(self::RESOURCE_NAME);
    } else {
      try {
        $result = WipFactory::getObject(self::RESOURCE_NAME);
      } catch (\Exception $e) {
        // Fall back to a new instance of WipModuleTaskStore.
        $result = new self();
      }
    }
    return $result;
  }

}
