<?php

namespace Acquia\WipIntegrations\DoctrineORM;

use Acquia\WipIntegrations\DoctrineORM\Entities\ThreadStoreEntry;
use Acquia\WipService\App;
use Acquia\Wip\Exception\NoTaskException;
use Acquia\Wip\Exception\NoThreadException;
use Acquia\Wip\Runtime\Server;
use Acquia\Wip\Runtime\Thread;
use Acquia\Wip\Ssh\SshProcessInterface;
use Acquia\Wip\Storage\ThreadStoreInterface;
use Acquia\Wip\TaskInterface;
use Acquia\Wip\TaskStatus;
use Acquia\Wip\ThreadStatus;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

/**
 * Provides a CRUD features for Thread objects using Doctrine ORM.
 *
 * @copydetails ThreadStoreInterface
 */
class ThreadStore implements ThreadStoreInterface {

  /**
   * The entity name.
   */
  const ENTITY_NAME = 'Acquia\WipIntegrations\DoctrineORM\Entities\ThreadStoreEntry';

  /**
   * The entity manager.
   *
   * @var EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Creates a new instance of ThreadStore.
   */
  public function __construct() {
    $this->entityManager = App::getEntityManager();
  }

  /**
   * Creates a Thread object out of a ThreadStoreEntry object.
   *
   * @param ThreadStoreEntry $thread_entry
   *   The thread store entity.
   *
   * @return Thread
   *   The thread instance.
   */
  private function convert(ThreadStoreEntry $thread_entry) {
    $thread = new Thread();
    $thread->setId($thread_entry->getId());
    $thread->setServerId($thread_entry->getServerId());
    $thread->setWipId($thread_entry->getWid());
    $thread->setPid($thread_entry->getPid());
    $thread->setCreated($thread_entry->getCreated());
    $thread->setCompleted($thread_entry->getCompleted());
    $thread->setStatus($thread_entry->getStatus());
    $thread->setSshOutput($thread_entry->getSshOutput());
    $process = unserialize($thread_entry->getProcess());
    if ($process instanceof SshProcessInterface) {
      $thread->setProcess($process);
    }
    return $thread;
  }

  /**
   * {@inheritdoc}
   */
  public function save(Thread $thread) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    if ($thread->getId()) {
      // @index primary key
      $thread_entry = $this->entityManager
        ->getRepository(self::ENTITY_NAME)
        ->find($thread->getId());
    }
    if (empty($thread_entry)) {
      $thread_entry = new ThreadStoreEntry();
    }

    if (!$thread->getServerId()) {
      throw new \InvalidArgumentException('The thread is missing a valid server.');
    }
    $thread_entry->setServerId($thread->getServerId());
    if (!$thread->getWipId()) {
      throw new \InvalidArgumentException('The thread is missing a valid Wip.');
    }
    $thread_entry->setWid($thread->getWipId());
    $thread_entry->setPid($thread->getPid());
    $thread_entry->setCreated($thread->getCreated());
    $thread_entry->setCompleted($thread->getCompleted());
    $thread_entry->setStatus($thread->getStatus());
    $thread_entry->setSshOutput($thread->getSshOutput());
    $thread_entry->setProcess(serialize($thread->getProcess()));

    $this->entityManager->persist($thread_entry);
    $this->entityManager->flush();

    if (!$thread->getId()) {
      $thread->setId($thread_entry->getId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($id) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    $thread = NULL;
    // @index primary key
    $thread_entry = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->find($id);
    if ($thread_entry) {
      $thread = $this->convert($thread_entry);
    }
    return $thread;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveThreads(Server $server = NULL) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    // Get all of the threads in the table that are not associated with finished
    // Wip objects.
    $rsm = new ResultSetMappingBuilder($this->entityManager);
    $rsm->addRootEntityFromClassMetadata(self::ENTITY_NAME, 't');
    $query_string = 'SELECT * FROM thread_store t JOIN wip_pool w ON t.wid = w.wid WHERE t.status != ? and w.run_status != ?';
    if (!empty($server)) {
      $query_string .= ' AND t.server_id = ?';
    }
    $query = $this->entityManager
      ->createNativeQuery($query_string, $rsm);
    $query->setParameter(1, ThreadStatus::FINISHED);
    $query->setParameter(2, TaskStatus::COMPLETE);
    if (!empty($server)) {
      $query->setParameter(3, $server->getId());
    }
    $thread_entries = $query->getResult();

    $threads = array();
    foreach ($thread_entries as $thread_entry) {
      $thread = $this->convert($thread_entry);
      $threads[] = $thread;
    }
    return $threads;
  }

  /**
   * {@inheritdoc}
   */
  public function getRunningThreads($server_ids = array()) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    $rsm = new ResultSetMappingBuilder($this->entityManager);
    $rsm->addRootEntityFromClassMetadata(self::ENTITY_NAME, 't');
    $query_string = 'SELECT * FROM thread_store t WHERE t.status != ?';
    if (!empty($server_ids)) {
      $query_string .= ' AND t.server_id IN (?)';
    }
    $query = $this->entityManager
      ->createNativeQuery($query_string, $rsm);
    $query->setParameter(1, ThreadStatus::FINISHED);
    if (!empty($server_ids)) {
      $query->setParameter(2, $server_ids);
    }
    $thread_entries = $query->getResult();

    $threads = array();
    foreach ($thread_entries as $thread_entry) {
      $thread = $this->convert($thread_entry);
      $threads[] = $thread;
    }
    return $threads;
  }

  /**
   * {@inheritdoc}
   */
  public function getRunningWids() {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    $rsm = new ResultSetMappingBuilder($this->entityManager);
    $rsm->addRootEntityFromClassMetadata(self::ENTITY_NAME, 't');
    $query_string = 'SELECT * FROM thread_store t WHERE t.status != ?';
    $query = $this->entityManager
      ->createNativeQuery($query_string, $rsm);
    $query->setParameter(1, ThreadStatus::FINISHED);
    $thread_entries = $query->getResult();
    $wids = array();
    foreach ($thread_entries as $thread_entry) {
      $wids[] = $this->convert($thread_entry)->getWipId();
    }

    return $wids;
  }

  /**
   * {@inheritdoc}
   */
  public function remove(Thread $thread) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    // @index primary key
    $thread_entry = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->find($thread->getId());
    if ($thread_entry) {
      $this->entityManager->remove($thread_entry);
      $this->entityManager->flush();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function pruneObjects(array $object_ids) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();
    $query = $this->entityManager
      ->createQuery(sprintf(
        'DELETE FROM %s t WHERE t.wid IN (:object_ids)',
        self::ENTITY_NAME
      ));
    $query->setParameter('object_ids', $object_ids);
    $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getThreadByTask(TaskInterface $task) {
    if (is_null($task->getId())) {
      $message = 'Unable to locate a thread for an empty Task ID. The provided task is either empty, or was not yet saved in the database.';
      throw new NoTaskException($message);
    }

    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    // @index tasks_idx
    $thread_entry = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->findOneBy(array(
        'wid' => $task->getId(),
        'status' => array(
          ThreadStatus::RUNNING,
          ThreadStatus::RESERVED,
        ),
      ));
    if ($thread_entry) {
      return $this->convert($thread_entry);
    }

    throw new NoThreadException(sprintf('No thread found for task %d', $task->getId()));
  }

  /**
   * Prunes old finished threads.
   *
   * @param int $timestamp
   *   Finished threads that were created on or older than this timestamp will
   *   be deleted.
   * @param int $limit
   *   The number of threads to be deleted.
   *
   * @return bool
   *   TRUE if there are more items ready to be pruned.
   */
  public function prune($timestamp, $limit = 1000) {
    if (!is_int($timestamp) || $timestamp <= 0) {
      throw new \InvalidArgumentException('The timestamp argument must be a positive integer.');
    }
    if (!is_int($limit) || $limit <= 0) {
      throw new \InvalidArgumentException('The limit argument must be a positive integer.');
    }

    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    // Fetch a number of thread IDs that can be deleted.
    // @index created_idx
    $query = $this->entityManager
      ->createQuery(sprintf(
        'SELECT t.id FROM %s t WHERE t.created <= :created AND t.status = :status',
        self::ENTITY_NAME
      ));
    $query->setParameter('created', $timestamp);
    $query->setParameter('status', ThreadStatus::FINISHED);
    $query->setMaxResults($limit);
    $query->execute();
    $thread_ids = $query->getResult();

    // Actual deletion.
    if ($thread_ids) {
      // @index primary key
      $query = $this->entityManager
        ->createQuery(sprintf(
          'DELETE FROM %s t WHERE t.id IN (?1)',
          self::ENTITY_NAME
        ));
      $query->setParameter(1, $thread_ids);
      $query->execute();
    }

    // Check if there are more items to be deleted.
    // @index created_idx
    $query = $this->entityManager
      ->createQuery(sprintf(
        'SELECT t.id FROM %s t WHERE t.created <= :created AND t.status = :status',
        self::ENTITY_NAME
      ));
    $query->setParameter('created', $timestamp);
    $query->setParameter('status', ThreadStatus::FINISHED);
    $query->setMaxResults(1);
    $query->execute();
    $thread_ids = $query->getResult();

    return !empty($thread_ids);
  }

}
