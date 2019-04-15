<?php

namespace Acquia\WipIntegrations\DoctrineORM;

use Acquia\WipIntegrations\DoctrineORM\Entities\WipStoreEntry;
use Acquia\WipService\App;
use Acquia\Wip\Exception\WipStoreSaveException;
use Acquia\Wip\Implementation\BasicIncludeFile;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\StateTableIteratorInterface;
use Acquia\Wip\Storage\WipStoreInterface;
use Acquia\Wip\WipLogLevel;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Provides a CRUD features for Wip objects using Doctrine ORM.
 */
class WipStore implements WipStoreInterface {

  /**
   * The entity name.
   */
  const ENTITY_NAME = 'Acquia\WipIntegrations\DoctrineORM\Entities\WipStoreEntry';

  /**
   * The entity manager.
   *
   * @var EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Creates a new instance of WipStore.
   */
  public function __construct() {
    $this->entityManager = App::getEntityManager();
  }

  /**
   * {@inheritdoc}
   */
  public function save($wip_id, StateTableIteratorInterface $wip_iterator) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    if ($wip_id >= 0) {
      // @index primary key
      $wip_entry = $this->entityManager
        ->getRepository(self::ENTITY_NAME)
        ->find($wip_id);
      if (!$wip_entry) {
        $wip_entry = new WipStoreEntry();
        $wip_entry->setWid($wip_id);
      }
    } else {
      throw new \InvalidArgumentException('Invalid Wip id specified.');
    }
    $wip = $wip_iterator->getWip();
    if (empty($wip)) {
      throw new WipStoreSaveException('The iterator is missing a Wip object.');
    }
    $entry_timestamp = $wip_entry->getTimestamp();
    $wip_timestamp = $wip->getTimestamp();
    if ($wip_timestamp != 0 && $wip_timestamp != $entry_timestamp) {
      // Since this Wip was read from the database, another thread has used and updated the Wip in the table.
      $message = sprintf(
        "Trying to save a Wip (%s) that does not have the same 'last updated' timestamp as the one in WipStore (%s)",
        $wip_timestamp,
        $entry_timestamp
      );
      WipLog::getWipLog()->log(WipLogLevel::FATAL, $message);
      throw new \RuntimeException($message);
    }

    $now = time();
    $wip->setTimestamp($now);
    $wip_entry->setObj(serialize($wip_iterator));
    $wip_entry->setRequires(serialize($wip->getIncludes()));
    $wip_entry->setTimestamp($now);

    $this->entityManager->persist($wip_entry);
    $this->entityManager->flush();
  }

  /**
   * {@inheritdoc}
   */
  public function get($id) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    // @index primary key
    /** @var WipStoreEntry $wip_entry */
    $wip_entry = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->find($id);

    // Ensure that the required files are included before we unserialize the Wip
    // iterator and its associated Wip object.
    if ($wip_entry) {
      $include_files = unserialize($wip_entry->getRequires());
      /** @var BasicIncludeFile $include_file */
      foreach ($include_files as $include_file) {
        require_once $include_file->getFullPath();
      }
    }
    return $wip_entry ? unserialize($wip_entry->getObj()) : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimestampByWipId($id) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    // @index primary key
    /** @var WipStoreEntry $wip_entry */
    $wip_entry = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->find($id);

    $timestamp = NULL;
    if ($wip_entry) {
      $timestamp = $wip_entry->getTimestamp();
    }

    return $timestamp;
  }

  /**
   * {@inheritdoc}
   */
  public function remove($id) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    // @index primary key
    $wip_entry = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->find($id);
    if ($wip_entry) {
      $this->entityManager->remove($wip_entry);
      $this->entityManager->flush();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function pruneObjects(array $object_ids) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();
    // Delete the entries.
    $delete_query = sprintf(
      'DELETE FROM %s w WHERE w.wid IN (:wids)',
      self::ENTITY_NAME
    );

    $delete = $this->entityManager->createQuery($delete_query);
    $delete->setParameter('wids', $object_ids);
    $delete->execute();
  }

}
