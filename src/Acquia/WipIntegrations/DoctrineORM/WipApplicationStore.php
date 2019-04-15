<?php

namespace Acquia\WipIntegrations\DoctrineORM;

use Acquia\WipIntegrations\DoctrineORM\Entities\WipApplicationStoreEntry;
use Acquia\WipService\App;
use Acquia\Wip\Exception\WipApplicationStoreSaveException;
use Acquia\Wip\Runtime\WipApplication;
use Acquia\Wip\Storage\WipApplicationStoreInterface;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Provides a CRUD features for WipApplication objects using Doctrine ORM.
 */
class WipApplicationStore implements WipApplicationStoreInterface {

  /**
   * The entity name.
   */
  const ENTITY_NAME = 'Acquia\WipIntegrations\DoctrineORM\Entities\WipApplicationStoreEntry';

  /**
   * The entity manager.
   *
   * @var EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Creates a new instance of WipApplicationStore.
   */
  public function __construct() {
    $this->entityManager = App::getEntityManager();
  }

  /**
   * Creates a WipApplication object out of a WipApplicationStoreEntry object.
   *
   * @param WipApplicationStoreEntry $wip_application_entry
   *   The Wip application entity.
   *
   * @return WipApplication
   *   The Wip application instance.
   */
  private function convert(WipApplicationStoreEntry $wip_application_entry) {
    $wip_application = new WipApplication();
    $wip_application->setId($wip_application_entry->getId());
    $wip_application->setHandler($wip_application_entry->getHandler());
    $wip_application->setStatus($wip_application_entry->getStatus());
    return $wip_application;
  }

  /**
   * {@inheritdoc}
   */
  public function save(WipApplication $wip_application) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    if ($wip_application->getId()) {
      // @index primary key
      $wip_application_entry = $this->entityManager
        ->getRepository(self::ENTITY_NAME)
        ->find($wip_application->getId());
    } else {
      $wip_application_entry = new WipApplicationStoreEntry();
    }
    if (!$wip_application->getHandler()) {
      throw new \InvalidArgumentException('The application must have its handler specified.');
    }
    $wip_application_entry->setHandler($wip_application->getHandler());
    $wip_application_entry->setStatus($wip_application->getStatus());

    try {
      $this->entityManager->persist($wip_application_entry);
      $this->entityManager->flush();
    } catch (DBALException $e) {
      $check = $this->getByHandler($wip_application->getHandler());
      if ($check && (!$wip_application->getId() || $check->getId() != $wip_application_entry->getId())) {
        throw new WipApplicationStoreSaveException(
          'There is already a wip application with the specified handler.'
        );
      } else {
        throw $e;
      }
    }

    if (!$wip_application->getId()) {
      $wip_application->setId($wip_application_entry->getId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($id) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    // @index primary key
    $wip_application_entry = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->find($id);
    return $wip_application_entry ? $this->convert($wip_application_entry) : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getByHandler($handler) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    // @index unique key
    $wip_application_entry = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->findOneBy(array('handler' => $handler));
    return $wip_application_entry ? $this->convert($wip_application_entry) : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function remove(WipApplication $wip_application) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    // @index primary key
    $wip_application_entry = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->find($wip_application->getId());
    if ($wip_application_entry) {
      $this->entityManager->remove($wip_application_entry);
      $this->entityManager->flush();
    }
  }

}
