<?php

namespace Acquia\WipIntegrations\DoctrineORM;

use Acquia\WipIntegrations\DoctrineORM\Entities\SignalStoreEntry;
use Acquia\WipService\App;
use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\DependencyManager;
use Acquia\Wip\DependencyManagerInterface;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\Lock\WipPoolRowLock;
use Acquia\Wip\Signal\SignalInterface;
use Acquia\Wip\Storage\SignalStoreInterface;
use Acquia\Wip\Storage\WipPoolStoreInterface;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipLogLevel;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Defines CRUD operations and handling behavior for signals.
 */
class SignalStore implements SignalStoreInterface, DependencyManagedInterface {

  /**
   * The WipFactory resource name.
   */
  const RESOURCE_NAME = 'acquia.wip.storage.signal';

  /**
   * The entity name.
   */
  const ENTITY_NAME = 'Acquia\WipIntegrations\DoctrineORM\Entities\SignalStoreEntry';

  /**
   * The entity manager.
   *
   * @var EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The dependency manager.
   *
   * @var DependencyManagerInterface
   */
  protected $dependencyManager;

  /**
   * Creates a new instance of SignalStore.
   */
  public function __construct() {
    $this->entityManager = App::getEntityManager();
    $this->dependencyManager = new DependencyManager();
    $this->dependencyManager->addDependencies($this->getDependencies());
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.storage.wippool' => 'Acquia\Wip\Storage\WipPoolStoreInterface',
      'acquia.wip.wiplog' => 'Acquia\Wip\WipLogInterface',
      'acquia.wip.lock.rowlock.wippool' => 'Acquia\Wip\Lock\RowLockInterface',
    );
  }

  /**
   * Converts a SignalStoreEntry into a SignalInterface instance.
   *
   * @param SignalStoreEntry $entry
   *   A Signal DB object.
   *
   * @return SignalInterface
   *   A signal instance created from the DB object.
   */
  private function convert(SignalStoreEntry $entry) {
    $signal = unserialize($entry->getData());

    $signal->setId($entry->getId());
    $signal->setObjectId($entry->getObjectId());
    $signal->setType($entry->getType());
    $signal->setSentTime($entry->getSent());
    $signal->setConsumedTime($entry->getConsumed());

    return $signal;
  }

  /**
   * {@inheritdoc}
   */
  public function send(SignalInterface $signal) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    if ($signal->getId()) {
      $signal_entry = $this->entityManager
        ->getRepository(self::ENTITY_NAME)
        ->find($signal->getId());
    } else {
      $signal_entry = new SignalStoreEntry();
    }

    if ($signal->getSentTime() === NULL) {
      $signal->setSentTime(time());
    }
    if ($signal->getObjectId() === NULL) {
      $signal->setObjectId(0);
    }
    if ($signal->getConsumedTime() === NULL) {
      $signal->setConsumedTime(0);
    }
    if ($signal->getData() === NULL) {
      $signal->setData(new \stdClass());
    }

    $signal_entry->setObjectId($signal->getObjectId());
    $signal_entry->setType($signal->getType());
    $signal_entry->setSent($signal->getSentTime());
    $signal_entry->setConsumed($signal->getConsumedTime());
    $signal_entry->setData(serialize($signal));

    $this->entityManager->persist($signal_entry);
    $this->entityManager->flush();

    if (!$signal->getId()) {
      $signal->setId($signal_entry->getId());
    }
    $arguments = array($signal);
    try {
      WipPoolRowLock::getWipPoolRowLock($signal->getObjectId(), NULL, $this->dependencyManager)
        ->setTimeout(30)
        ->runAtomic($this, 'storeData', $arguments);
    } catch (\Exception $e) {
      WipLog::getWipLog($this->dependencyManager)
        ->log(
          WipLogLevel::ERROR,
          sprintf(
            "Signal %d failed to update the wake time on task %d. %s",
            $signal->getId(),
            $signal->getObjectId(),
            $e->getMessage()
          )
        );
    }
  }

  /**
   * Stores data for the given signal.
   *
   * This is called via WipPoolRowLock.
   *
   * @param SignalInterface $signal
   *   The signal to send.
   */
  public function storeData(SignalInterface $signal) {
    // Set the wake time on the WIP that this signal was for so that it can
    // proceed with processing immediately.
    if (WipPoolRowLock::getWipPoolRowLock($signal->getObjectId(), NULL, $this->dependencyManager)->hasLock()) {
      /** @var WipPoolStoreInterface $wip_storage */
      $wip_storage = $this->dependencyManager->getDependency('acquia.wip.storage.wippool');
      $wip = $wip_storage->get($signal->getObjectId());
      if ($wip) {
        $wip->setWakeTimestamp(time());
        $wip_storage->save($wip);
      }
    } else {
      /** @var WipLogInterface $log */
      $message = sprintf('Failed to lock wip_pool: %s before calling %s', $signal->getObjectId(), __METHOD__);
      $log = $this->dependencyManager->getDependency('acquia.wip.wiplog');
      $log->log(WipLogLevel::FATAL, $message, 0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function consume(SignalInterface $signal) {
    $id = $signal->getId();
    $signal->setConsumedTime(time());
    if ($id) {
      /** @var Connection $connection */
      $connection = $this->entityManager->getConnection();
      $connection->executeUpdate(
        'UPDATE signal_store SET consumed = :consumed WHERE id = :id',
        array(
          ':consumed' => $signal->getConsumedTime(),
          ':id' => $id,
        )
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete(SignalInterface $signal) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();
    // @index primary key
    $signal_entry = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->find($signal->getId());
    if ($signal_entry) {
      $this->entityManager->remove($signal_entry);
      $this->entityManager->flush();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function load($signal_id) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    $signal = NULL;
    // @index primary key
    $signal_entry = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->find($signal_id);
    if ($signal_entry) {
      $signal = $this->convert($signal_entry);
    }
    return $signal;
  }

  /**
   * {@inheritdoc}
   */
  public function loadAll($object_id) {
    return $this->query(array('objectId' => $object_id));
  }

  /**
   * {@inheritdoc}
   */
  public function getUuids(array $object_ids) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();
    $query = $this->entityManager
      ->createQuery(sprintf(
        'SELECT t.id FROM %s t WHERE t.objectId IN (:object_ids)',
        self::ENTITY_NAME
      ));
    $query->setParameter('object_ids', $object_ids);
    $query->execute();
    return $query->getResult();
  }

  /**
   * {@inheritdoc}
   */
  public function pruneObjects(array $object_ids) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();
    $query = $this->entityManager
      ->createQuery(sprintf(
        'DELETE FROM %s t WHERE t.objectId IN (:object_ids)',
        self::ENTITY_NAME
      ));
    $query->setParameter('object_ids', $object_ids);
    $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function loadAllActive($object_id) {
    return $this->query(array('objectId' => $object_id, 'consumed' => 0));
  }

  /**
   * Executes a query on the signal_store database table.
   *
   * @param array $criteria
   *   An associative array of conditions: keys are the column names, values are
   *   the values to filter to.
   *
   * @return array SignalInterface[]
   *   An array of Signal objects.
   */
  private function query($criteria) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    $signals = array();
    // @index primary key
    $signal_entries = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->findBy($criteria, array('id' => 'ASC'));
    foreach ($signal_entries as $signal_entry) {
      $signals[] = $this->convert($signal_entry);
    }
    return $signals;
  }

  /**
   * Gets the SignalStore instance.
   *
   * @param DependencyManagerInterface $dependency_manager
   *   Optional. The DependencyManager instance.
   *
   * @return SignalStoreInterface
   *   The SignalStoreInterface instance.
   *
   * @throws \Acquia\Wip\Exception\DependencyMissingException
   *   If a DependencyManager instance is provided but the SignalStore has not been
   *   set as a dependency.
   */
  public static function getSignalStore(DependencyManagerInterface $dependency_manager = NULL) {
    $result = NULL;
    if (NULL !== $dependency_manager) {
      $result = $dependency_manager->getDependency(self::RESOURCE_NAME);
    } else {
      try {
        $result = WipFactory::getObject(self::RESOURCE_NAME);
      } catch (\Exception $e) {
        // Fall back to a new instance of SignalStore.
        $result = new self();
      }
    }
    return $result;
  }

}
