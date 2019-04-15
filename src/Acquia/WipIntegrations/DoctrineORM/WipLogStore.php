<?php

namespace Acquia\WipIntegrations\DoctrineORM;

use Acquia\WipIntegrations\DoctrineORM\Entities\WipLogStoreEntry;
use Acquia\WipService\App;
use Acquia\Wip\DependencyManagerInterface;
use Acquia\Wip\Storage\WipLogStoreInterface;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogEntryInterface;
use Acquia\Wip\WipLogLevel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

/**
 * Provides CRUD features for WipLogEntry objects using Doctrine ORM.
 *
 * @copydetails WipPoolStoreInterface
 */
class WipLogStore implements WipLogStoreInterface {

  /**
   * The WipFactory resource name.
   */
  const RESOURCE_NAME = 'acquia.wip.wiplogstore';

  /**
   * The class of the associated entity.
   */
  const ENTITY_NAME = 'Acquia\WipIntegrations\DoctrineORM\Entities\WipLogStoreEntry';

  /**
   * The entity manager.
   *
   * @var EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Creates a new instance of WipLogStore.
   */
  public function __construct() {
    $this->entityManager = App::getEntityManager();
  }

  /**
   * Gets the total number of log entries matching a query.
   *
   * @param int $object_id
   *   Optional. The ID of the object to count log messages for. If not
   *   provided the resulting log messages will not be constrained to a single
   *   object.
   * @param int $minimum_log_level
   *   Optional. If not provided, the count of the log messages will not be
   *   constrained by a minimum log level.
   * @param int $maximum_log_level
   *   Optional. If not provided, the count of the log messages will not be
   *   constrained by a maximum log level.
   * @param null|bool $user_readable
   *   Optional. If true, only user readable logs will be counted. If null,
   *   the user_readable flag will be ignored.
   * @param string $uuid
   *   Optional. The UUID of the user associated with the log messages.
   *
   * @return int
   *   The total number of log entries matching the query.
   */
  public function count(
    $object_id = NULL,
    $minimum_log_level = WipLogLevel::TRACE,
    $maximum_log_level = WipLogLevel::FATAL,
    $user_readable = NULL,
    $uuid = NULL
  ) {
    if ($object_id !== NULL && !is_int($object_id)) {
      throw new \InvalidArgumentException(sprintf(
        'The object ID parameter must be an integer, %s given.',
        gettype($object_id)
      ));
    }
    if ($user_readable !== NULL && !is_bool($user_readable)) {
      throw new \InvalidArgumentException(sprintf(
        'The user readable parameter must be a boolean, %s given.',
        gettype($user_readable)
      ));
    }
    if ($uuid !== NULL && !is_string($uuid)) {
      throw new \InvalidArgumentException(sprintf(
        'The uuid parameter must be a string, %s given.',
        gettype($uuid)
      ));
    }

    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    $qb = $this->entityManager->createQueryBuilder();
    $qb->select('count(l.id)');
    $qb->from(self::ENTITY_NAME, 'l');
    $qb->where($qb->expr()->andX(
      $qb->expr()->lte('l.level', ':min'),
      $qb->expr()->gte('l.level', ':max')
    ));
    $qb->setParameter('min', $minimum_log_level);
    $qb->setParameter('max', $maximum_log_level);

    if ($object_id !== NULL) {
      $qb->andWhere($qb->expr()->eq('l.objectId', ':object_id'));
      $qb->setParameter('object_id', $object_id);
    }
    if ($user_readable !== NULL) {
      $qb->andWhere($qb->expr()->eq('l.userReadable', ':user_readable'));
      $qb->setParameter('user_readable', $user_readable);
    }
    if ($uuid !== NULL) {
      $qb->innerJoin(WipPoolStore::ENTITY_NAME, 'p', Join::WITH, 'l.objectId = p.wid');
      $qb->andWhere($qb->expr()->eq('p.uuid', ':uuid'));
      $qb->setParameter('uuid', $uuid);
    }

    return (int) $qb->getQuery()->getSingleScalarResult();
  }

  /**
   * Fetches log messages.
   *
   * @param int $object_id
   *   Optional. The ID of the object to collect log messages for. If not
   *   provided the resulting log messages will not be constrained to a single
   *   object.
   * @param int $offset
   *   Optional. The offset into the result set.
   * @param int $count
   *   Optional. The maximum number of results to return. If not provided, up
   *   to 20 messages will be returned.
   * @param string $sort_order
   *   Optional. The order of the returned results. Defaults to ascending order.
   * @param int $minimum_log_level
   *   Optional. If not provided, the resulting log messages will not be
   *   constrained by a minimum log level.
   * @param int $maximum_log_level
   *   Optional. If not provided, the resulting log messages will not be
   *   constrained by a maximum log level.
   * @param null|bool $user_readable
   *   Optional. If true, only user readable logs will be loaded. If null, the
   *   user_readable flag will be ignored.
   * @param string $uuid
   *   Optional. The UUID of the user associated with the log messages.
   *
   * @return WipLogEntryInterface[]
   *   The log messages.
   */
  public function load(
    $object_id = NULL,
    $offset = 0,
    $count = 20,
    $sort_order = 'ASC',
    $minimum_log_level = WipLogLevel::TRACE,
    $maximum_log_level = WipLogLevel::FATAL,
    $user_readable = NULL,
    $uuid = NULL
  ) {
    if ($object_id !== NULL && !is_int($object_id)) {
      throw new \InvalidArgumentException(sprintf(
        'The object ID parameter must be an integer, %s given.',
        gettype($object_id)
      ));
    }
    if ($user_readable !== NULL && !is_bool($user_readable)) {
      throw new \InvalidArgumentException(sprintf(
        'The user readable parameter must be a boolean, %s given.',
        gettype($user_readable)
      ));
    }
    if ($uuid !== NULL && !is_string($uuid)) {
      throw new \InvalidArgumentException(sprintf(
        'The uuid parameter must be a string, %s given.',
        gettype($uuid)
      ));
    }

    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    $qb = $this->entityManager->createQueryBuilder();
    $qb->select('l');
    $qb->from(self::ENTITY_NAME, 'l');
    $qb->where($qb->expr()->andX(
      $qb->expr()->lte('l.level', ':min'),
      $qb->expr()->gte('l.level', ':max')
    ));
    $qb->setParameter('min', $minimum_log_level);
    $qb->setParameter('max', $maximum_log_level);

    if ($object_id !== NULL) {
      $qb->andWhere($qb->expr()->eq('l.objectId', ':object_id'));
      $qb->setParameter('object_id', $object_id);
    }
    if ($user_readable !== NULL) {
      $qb->andWhere($qb->expr()->eq('l.userReadable', ':user_readable'));
      $qb->setParameter('user_readable', $user_readable);
    }
    if ($uuid !== NULL) {
      $qb->innerJoin(WipPoolStore::ENTITY_NAME, 'p', Join::WITH, 'l.objectId = p.wid');
      $qb->andWhere($qb->expr()->eq('p.uuid', ':uuid'));
      $qb->setParameter('uuid', $uuid);
    }

    $qb->orderBy('l.timestamp', $sort_order);
    $qb->addOrderBy('l.id', $sort_order);
    $qb->setMaxResults($count);
    $qb->setFirstResult($offset);

    $entries = $qb->getQuery()->getResult();

    foreach ($entries as $key => $entry) {
      if ($entry instanceof WipLogStoreEntry) {
        $entries[$key] = $entry->toWipLogEntry();
      }
    }
    return $entries;
  }

  /**
   * {@inheritdoc}
   */
  public function loadRegex(
    $object_id = NULL,
    $regex = NULL,
    $sort_order = 'ASC',
    $minimum_log_level = WipLogLevel::TRACE,
    $maximum_log_level = WipLogLevel::FATAL,
    $user_readable = NULL
  ) {
    if (!is_int($object_id)) {
      throw new \InvalidArgumentException(sprintf(
        'The "object_id" parameter must be an integer, %s given.',
        gettype($object_id)
      ));
    }
    if (!is_string($regex)) {
      throw new \InvalidArgumentException(sprintf(
        'The "regex" parameter must be a string, %s given.',
        gettype($regex)
      ));
    }
    if ($user_readable !== NULL && !is_bool($user_readable)) {
      throw new \InvalidArgumentException(sprintf(
        'The "user_readable" parameter must be a boolean, %s given.',
        gettype($user_readable)
      ));
    }

    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();
    $query_string = <<<EOT
SELECT * FROM wip_log
  WHERE object_id = %d
    AND level <= %d
    AND level >= %d
    AND message REGEXP '%s'
  ORDER BY id ASC
EOT;
    $query_string = sprintf($query_string, $object_id, $minimum_log_level, $maximum_log_level, $regex);

    $rsm = new ResultSetMappingBuilder($this->entityManager);
    $rsm->addRootEntityFromClassMetadata('Acquia\WipIntegrations\DoctrineORM\Entities\WipLogStoreEntry', 'l');

    $query = $this->entityManager->createNativeQuery($query_string, $rsm);
    $entries = $query->getResult();

    foreach ($entries as $key => $entry) {
      if ($entry instanceof WipLogStoreEntry) {
        $entries[$key] = $entry->toWipLogEntry();
      } else {
        printf("Entry:\n%s\n", print_r($entry, TRUE));
      }
    }
    return $entries;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($object_id = NULL, $prune_time = PHP_INT_MAX, $user_readable = NULL, $count = NULL) {
    if ($object_id !== NULL && !is_int($object_id)) {
      throw new \InvalidArgumentException(sprintf(
        'The object ID parameter must be an integer, %s given.',
        gettype($object_id)
      ));
    }
    if ($user_readable !== NULL && !is_bool($user_readable)) {
      throw new \InvalidArgumentException(sprintf(
        'The user readable parameter must be a boolean, %s given.',
        gettype($user_readable)
      ));
    }

    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();
    $result = [];

    $conditions = [];
    if ($object_id !== NULL) {
      $conditions[] = sprintf('l.objectId = %d', $object_id);
    }
    if ($user_readable !== NULL) {
      $conditions[] = sprintf('l.userReadable = %d', $user_readable);
    }
    $conditions[] = sprintf('l.timestamp < %d', $prune_time);
    $limit_condition = '';
    if ($count !== NULL) {
      $limit_condition = sprintf('LIMIT 0, %d', $count);
    }

    // Find the entries, to return after delete.
    $find_query = sprintf(
      'SELECT l FROM %s l WHERE %s %s',
      self::ENTITY_NAME,
      implode(' AND ', $conditions),
      $limit_condition
    );

    $find = $this->entityManager->createQuery($find_query);
    /** @var WipLogStoreEntry[] $entries */
    $entries = $find->execute();
    foreach ($entries as $entry) {
      $result[] = $entry->toWipLogEntry();
    }

    // Delete the entries.
    $delete_query = sprintf(
      'DELETE FROM %s l WHERE %s %s',
      self::ENTITY_NAME,
      implode(' AND ', $conditions),
      $limit_condition
    );

    $delete = $this->entityManager->createQuery($delete_query);
    $delete->execute();

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function pruneObjectsNoResults(array $object_ids, $prune_time = PHP_INT_MAX) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();
    // Delete the entries.
    $delete_query = sprintf(
      'DELETE FROM %s l WHERE l.timestamp < :timestamp AND l.objectId IN (:object_ids)',
      self::ENTITY_NAME
    );

    $delete = $this->entityManager->createQuery($delete_query);
    $delete->setParameters(['timestamp' => $prune_time, 'object_ids' => $object_ids]);
    $delete->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function prune(
    $object_id = NULL,
    $minimum_log_level = WipLogLevel::TRACE,
    $maximum_log_level = WipLogLevel::FATAL,
    $user_readable = NULL
  ) {
    if ($object_id !== NULL && !is_int($object_id)) {
      throw new \InvalidArgumentException(sprintf(
        'The object ID parameter must be an integer, %s given.',
        gettype($object_id)
      ));
    }
    if ($user_readable !== NULL && !is_bool($user_readable)) {
      throw new \InvalidArgumentException(sprintf(
        'The user readable parameter must be a boolean, %s given.',
        gettype($user_readable)
      ));
    }

    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    $conditions = [];
    if ($object_id !== NULL) {
      $conditions[] = sprintf('l.objectId = %d', $object_id);
    }
    if ($user_readable !== NULL) {
      $conditions[] = sprintf('l.userReadable = %d', $user_readable);
    }
    $conditions[] = sprintf(
      '(l.level > %d OR l.level < %d)',
      $minimum_log_level,
      $maximum_log_level
    );

    // Find the entries, to return after prune.
    $find_query = sprintf(
      'SELECT l FROM %s l WHERE %s',
      self::ENTITY_NAME,
      implode(' AND ', $conditions)
    );

    $find = $this->entityManager->createQuery($find_query);
    $result = [];
    /** @var WipLogStoreEntry[] $entries */
    $entries = $find->execute();
    foreach ($entries as $entry) {
      $result[] = $entry->toWipLogEntry();
    }

    // Prune the entries.
    $prune_query = sprintf(
      'DELETE FROM %s l WHERE %s',
      self::ENTITY_NAME,
      implode(' AND ', $conditions)
    );

    $prune = $this->entityManager->createQuery($prune_query);
    $prune->execute();

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function save(WipLogEntryInterface $log_entry) {
    // Ensure that ORM's static cache is not interfering.
    $entity_manager = App::getEntityManager();
    $entity_manager->clear();

    $entry = WipLogStoreEntry::fromWipLogEntry($log_entry);
    $entity_manager->persist($entry);
    $entity_manager->flush();
    return $entry->getId();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteById($log_id) {
    if ($log_id !== NULL && !is_int($log_id)) {
      throw new \InvalidArgumentException(sprintf(
        'The log ID parameter must be an integer, %s given.',
        gettype($log_id)
      ));
    }

    $result = NULL;

    // Find the log entry and save it to return.
    $find_query = sprintf(
      'SELECT l FROM %s l WHERE l.id = %d',
      self::ENTITY_NAME,
      $log_id
    );

    $q = $this->entityManager->createQuery($find_query);
    /** @var WipLogStoreEntry[] $entries */
    $entries = $q->execute();

    // There should be zero or one entry since IDs are unique.
    if (!empty($entries)) {
      $fetched = $entries[0];
      if (!empty($fetched)) {
        $result = $fetched->toWipLogEntry();
      }
    }

    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    $delete_query = sprintf(
      'DELETE FROM %s l WHERE l.id = %d',
      self::ENTITY_NAME,
      $log_id
    );
    $q = $this->entityManager->createQuery($delete_query);
    $q->execute();

    // There should only be one entry since IDs are unique.
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanUp() {
    // This implementation should never be used inside a container as it does not
    // do any real log clean up and always returns TRUE.
    return TRUE;
  }

  /**
   * Gets the WipLogStore instance.
   *
   * @param DependencyManagerInterface $dependency_manager
   *   Optional. The DependencyManager instance.
   *
   * @return WipLogStoreInterface
   *   The WipLogStoreInterface instance.
   *
   * @throws \Acquia\Wip\Exception\DependencyMissingException
   *   If a DependencyManager instance is provided but the WipLogStore has not
   *   been set as a dependency.
   */
  public static function getWipLogStore(DependencyManagerInterface $dependency_manager = NULL) {
    $result = NULL;
    if (NULL !== $dependency_manager) {
      $result = $dependency_manager->getDependency(self::RESOURCE_NAME);
    } else {
      try {
        $result = WipFactory::getObject(self::RESOURCE_NAME);
      } catch (\Exception $e) {
        // Fall back to a new instance of WipLog.
        $result = new self();
      }
    }
    return $result;
  }

}
