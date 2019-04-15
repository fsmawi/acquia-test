<?php

namespace Acquia\WipIntegrations\DoctrineORM;

use Acquia\WipIntegrations\DoctrineORM\Entities\WipModuleStoreEntry;
use Acquia\WipIntegrations\DoctrineORM\Entities\WipModuletaskStoreEntry;
use Acquia\WipService\App;
use Acquia\Wip\DependencyManagerInterface;
use Acquia\Wip\Storage\WipModuleStoreInterface;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipModuleInterface;
use Doctrine\ORM\EntityManagerInterface;
use Silex\Application;

/**
 * Provides CRUD features for modules using Doctrine ORM.
 *
 * @copydetails ModuleStoreInterface
 */
class WipModuleStore implements WipModuleStoreInterface {

  /**
   * The WipFactory resource name.
   */
  const RESOURCE_NAME = 'acquia.wip.storage.module';

  /**
   * The entity name.
   */
  const ENTITY_NAME = 'Acquia\WipIntegrations\DoctrineORM\Entities\WipModuleStoreEntry';

  /**
   * The entity manager.
   *
   * @var EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Creates a new instance of WipModuleStore.
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

    /** @var WipModuleStoreEntry $entry */
    $entry = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->find(trim($name));

    $result = NULL;
    if ($entry) {
      $result = $entry->toWipModule();
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function save(WipModuleInterface $module) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    /** @var WipModuleStoreEntry $entry */
    $entry = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->find($module->getName());

    /* Make new entry if not updating an existing entry. */
    if (!$entry) {
      $entry = new WipModuleStoreEntry();
    }
    $entry->setName($module->getName());
    $entry->setVersion($module->getVersion());
    $entry->setVcsUri($module->getVcsUri());
    $entry->setVcsPath($module->getVcsPath());
    $entry->setDirectory($module->getDirectory());
    $entry->setIncludes(serialize($module->getIncludes()));
    $entry->setEnabled($module->isEnabled());
    $entry->setReady($module->isReady());

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
      ->find($name);

    if ($entry) {
      $this->entityManager->remove($entry);
      $this->entityManager->flush();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getByTaskName($name) {
    if (!is_string($name) || trim($name) == FALSE) {
      throw new \InvalidArgumentException('The "name" parameter must be a non-empty string.');
    }

    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    /** @var WipModuleTaskStoreEntry $task_entry */
    $task_entry = $this->entityManager
      ->getRepository(WipModuleTaskStore::ENTITY_NAME)
      ->findOneBy(array('name' => trim($name)));

    if (empty($task_entry)) {
      return NULL;
    }

    $module_name = $task_entry->getModuleName();

    /** @var WipModuleStoreEntry $entry */
    $entry = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->find($module_name);

    $result = NULL;
    if ($entry) {
      $result = $entry->toWipModule();
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getByEnabled($enabled) {
    if (!is_bool($enabled)) {
      throw new \InvalidArgumentException('The "enabled" parameter must be a boolean.');
    }

    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    /** @var WipModuleStoreEntry $entry */
    $entries = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->findBy(array('enabled' => $enabled));

    $result = array();
    if ($entries) {
      foreach ($entries as $entry) {
        $result[] = $entry->toWipModule();
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getByReady($ready) {
    if (!is_bool($ready)) {
      throw new \InvalidArgumentException('The "ready" parameter must be a boolean.');
    }

    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    /** @var WipModuleStoreEntry $entry */
    $entries = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->findBy(array('ready' => $ready));

    $result = array();
    if ($entries) {
      foreach ($entries as $entry) {
        $result[] = $entry->toWipModule();
      }
    }
    return $result;
  }

  /**
   * Gets the configured WipModuleStoreInterface instance.
   *
   * @param DependencyManagerInterface $dependency_manager
   *   Optional. The DependencyManager instance.
   *
   * @return WipModuleStoreInterface
   *   The instance.
   *
   * @throws \Acquia\Wip\Exception\DependencyMissingException
   *   If a DependencyManager instance is provided but the
   *   WipModuleStoreInterface has not been set as a dependency.
   */
  public static function getWipModuleStore(DependencyManagerInterface $dependency_manager = NULL) {
    $result = NULL;
    if (NULL !== $dependency_manager) {
      $result = $dependency_manager->getDependency(self::RESOURCE_NAME);
    } else {
      try {
        $result = WipFactory::getObject(self::RESOURCE_NAME);
      } catch (\Exception $e) {
        // Fall back to a new instance of WipModuleStore.
        $result = new self();
      }
    }
    return $result;
  }

}
