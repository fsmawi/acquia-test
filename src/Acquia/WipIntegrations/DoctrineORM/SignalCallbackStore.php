<?php

namespace Acquia\WipIntegrations\DoctrineORM;

use Acquia\WipIntegrations\DoctrineORM\Entities\SignalCallbackStoreEntry;
use Acquia\WipService\App;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Defines a CRUD API for signal callbacks.
 */
class SignalCallbackStore {

  /**
   * The entity name.
   */
  const ENTITY_NAME = 'Acquia\WipIntegrations\DoctrineORM\Entities\SignalCallbackStoreEntry';

  /**
   * The entity manager.
   *
   * @var EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Creates a new instance of SignalCallbackStore.
   */
  public function __construct() {
    $this->entityManager = App::getEntityManager();
  }

  /**
   * Inserts a new signal callback record.
   *
   * @param string $uuid
   *   The unique ID of the signal.
   * @param int $wip_id
   *   The associated Wip task ID.
   * @param int $type
   *   The type of signal callback.
   *
   * @return SignalCallbackStoreEntry
   *   The signal callback entity.
   */
  public function insert($uuid, $wip_id, $type) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    $entry = new SignalCallbackStoreEntry();
    $entry->setUuid($uuid);
    $entry->setWipId($wip_id);
    $entry->setType($type);

    $this->entityManager->persist($entry);
    $this->entityManager->flush();

    return $entry;
  }

  /**
   * Loads a signal callback.
   *
   * @param string $uuid
   *   The unique ID of the signal.
   *
   * @return SignalCallbackStoreEntry
   *   The signal callback entity.
   */
  public function load($uuid) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    // @index primary key
    return $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->find($uuid);
  }

  /**
   * Loads a signal callback by Wip ID.
   *
   * @param int $wip_id
   *   The unique ID of the Wip.
   *
   * @return SignalCallbackStoreEntry[]
   *   The signal callback entity.
   */
  public function loadByWipId($wip_id) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    return $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->findBy(array('wipId' => $wip_id));
  }

  /**
   * Loads all signal callbacks.
   *
   * @return array
   *   An array of signal callback entities.
   */
  public function loadAll() {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    $result = array();
    // @index primary key
    $entries = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->findBy(array());
    foreach ($entries as $entry) {
      $result[] = $entry;
    }
    return $result;
  }

  /**
   * Deletes a signal callback entity.
   *
   * @param SignalCallbackStoreEntry $entry
   *   The signal callback entity.
   */
  public function delete(SignalCallbackStoreEntry $entry) {
    $this->entityManager->remove($entry);
    $this->entityManager->flush();
  }

  /**
   * Deletes signal callbacks.
   *
   * @param int[] $uuids
   *   List of uuids to delete.
   */
  public function pruneObjects(array $uuids) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();
    $query = $this->entityManager
      ->createQuery(sprintf(
        'DELETE FROM %s t WHERE t.uuid IN (:uuids)',
        self::ENTITY_NAME
      ));
    $query->setParameter('uuids', $uuids);
    $query->execute();
  }

}
