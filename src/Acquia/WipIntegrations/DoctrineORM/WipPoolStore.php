<?php

namespace Acquia\WipIntegrations\DoctrineORM;

use Acquia\WipIntegrations\DoctrineORM\Entities\WipPoolStoreEntry;
use Acquia\WipService\App;
use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\DependencyManager;
use Acquia\Wip\Runtime\WipPoolController;
use Acquia\Wip\Storage\ConfigurationStoreInterface;
use Acquia\Wip\Storage\WipPoolStoreInterface;
use Acquia\Wip\Task;
use Acquia\Wip\TaskExitStatus;
use Acquia\Wip\TaskInterface;
use Acquia\Wip\TaskPriority;
use Acquia\Wip\TaskStatus;
use Acquia\Wip\WipPause;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;

/**
 * Provides CRUD features for Task objects using Doctrine ORM.
 *
 * @copydetails WipPoolStoreInterface
 */
class WipPoolStore implements WipPoolStoreInterface, DependencyManagedInterface {

  /**
   * The entity name.
   */
  const ENTITY_NAME = 'Acquia\WipIntegrations\DoctrineORM\Entities\WipPoolStoreEntry';

  /**
   * The default max group concurrency.
   */
  const MAX_GROUP_CONCURRENCY_DEFAULT = 3;

  /**
   * The entity manager.
   *
   * @var EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The max group concurrency.
   *
   * @var int
   */
  private $defaultGroupConcurrency;

  /**
   * An array of the max concurrency per group.
   *
   * @var array
   */
  private $maxConcurrencyPerGroup = [];

  /**
   * Whether the concurrency cleanup still needs to happen.
   *
   * @var bool
   */
  private $cleanup = TRUE;

  /**
   * The dependency manager instance.
   *
   * @var DependencyManager
   */
  private $dependencyManager;

  /**
   * Creates a new instance of WipPoolStore.
   */
  public function __construct() {
    $this->dependencyManager = new DependencyManager();
    $this->dependencyManager->addDependencies($this->getDependencies());
    $this->entityManager = App::getEntityManager();
  }

  /**
   * Gets the default group concurrency.
   *
   * @return int
   *   The default group concurrency.
   */
  private function getDefaultGroupConcurrency() {
    if ($this->defaultGroupConcurrency === NULL) {
      /** @var ConfigurationStoreInterface $config */
      $config = $this->dependencyManager->getDependency('acquia.wip.storage.configuration');
      $this->defaultGroupConcurrency = $config->get(
        'wip_max_group_concurrency',
        self::MAX_GROUP_CONCURRENCY_DEFAULT
      );
    }
    return $this->defaultGroupConcurrency;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.storage.configuration' => 'Acquia\Wip\Storage\ConfigurationStoreInterface',
      WipPoolController::RESOURCE_NAME   => 'Acquia\Wip\Runtime\WipPoolControllerInterface',
      'acquia.wip.storage.thread'        => 'Acquia\Wip\Storage\ThreadStoreInterface',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function save(TaskInterface $task) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    if (empty($task->getUuid())) {
      throw new \DomainException('The task must have a UUID to be added.');
    }
    if ($task->getId()) {
      // @index primary key
      $wip_pool_entry = $this->entityManager
        ->getRepository(self::ENTITY_NAME)
        ->find($task->getId());
    } else {
      $wip_pool_entry = new WipPoolStoreEntry();
      if (!$task->getWipIterator()) {
        throw new \InvalidArgumentException('The task must have an iterator to be added.');
      }
    }
    $wip_pool_entry->fromTask($task);
    $this->entityManager->persist($wip_pool_entry);
    $this->entityManager->flush();

    if (!$task->getId()) {
      $task->setId($wip_pool_entry->getWid());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function count(
    $task_status = NULL,
    $parent = NULL,
    $group_name = NULL,
    $paused = NULL,
    $priority = NULL,
    $uuid = NULL,
    $exit_status = NULL,
    $start_time = NULL,
    $end_time = NULL,
    $is_terminating = NULL,
    $client_job_id = NULL
  ) {
    if ($task_status !== NULL && (!is_int($task_status) || !TaskStatus::isValid($task_status))) {
      throw new \InvalidArgumentException('The task status argument must be a valid task status value.');
    }
    if ($parent !== NULL && (!is_int($parent) || $parent < 0)) {
      throw new \InvalidArgumentException('The parent argument must be a non-negative integer.');
    }
    if ($group_name !== NULL && !is_string($group_name)) {
      throw new \InvalidArgumentException('The group_name argument must be a string.');
    }
    if ($paused !== NULL && !is_bool($paused)) {
      throw new \InvalidArgumentException('The paused argument must be a boolean.');
    }
    if ($priority !== NULL && (!is_int($priority) || !TaskPriority::isValid($priority))) {
      throw new \InvalidArgumentException('The priority argument must be a valid task priority value.');
    }
    if ($uuid !== NULL && !is_string($uuid)) {
      throw new \InvalidArgumentException('The uuid argument must be a string.');
    }
    if ($exit_status !== NULL && (!is_int($exit_status) || !TaskExitStatus::isValid($exit_status))) {
      throw new \InvalidArgumentException('The exit status argument must be a valid exit status value.');
    }
    if ($start_time !== NULL && (!is_int($start_time) || $start_time < 0)) {
      throw new \InvalidArgumentException('The start time argument must be an integer greater than or equal to 0.');
    }
    if ($end_time !== NULL && (!is_int($end_time) || $end_time < 0)) {
      throw new \InvalidArgumentException('The end time argument must be an integer greater than or equal to 0.');
    }
    if ($is_terminating !== NULL && !is_bool($is_terminating)) {
      throw new \InvalidArgumentException('The is_terminating argument must be a boolean.');
    }
    if ($client_job_id !== NULL && !is_string($client_job_id)) {
      throw new \InvalidArgumentException('The client_job_id argument must be a string.');
    }

    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    $qb = $this->entityManager->createQueryBuilder();
    $qb->select('count(p.wid)');
    $qb->from(self::ENTITY_NAME, 'p');
    if ($task_status !== NULL) {
      $qb->andWhere($qb->expr()->eq('p.runStatus', ':task_status'));
      $qb->setParameter('task_status', $task_status);
    }
    if ($parent !== NULL) {
      $qb->andWhere($qb->expr()->eq('p.parent', ':parent'));
      $qb->setParameter('parent', $parent);
    }
    if ($group_name !== NULL) {
      $qb->andWhere($qb->expr()->eq('p.groupName', ':group_name'));
      $qb->setParameter('group_name', $group_name);
    }
    if ($paused !== NULL) {
      $qb->andWhere($qb->expr()->eq('p.paused', ':paused'));
      $qb->setParameter('paused', $paused);
    }
    if ($priority !== NULL) {
      $qb->andWhere($qb->expr()->eq('p.priority', ':priority'));
      $qb->setParameter('priority', $priority);
    }
    if ($uuid !== NULL) {
      $qb->andWhere($qb->expr()->eq('p.uuid', ':uuid'));
      $qb->setParameter('uuid', $uuid);
    }
    if ($exit_status !== NULL) {
      $qb->andWhere($qb->expr()->eq('p.exitStatus', ':exit_status'));
      $qb->setParameter('exit_status', $exit_status);
    }
    if ($start_time !== NULL) {
      $qb->andWhere($qb->expr()->gte('p.startTime', ':start_time'));
      $qb->setParameter('start_time', $start_time);
    }
    if ($end_time !== NULL) {
      $qb->andWhere($qb->expr()->gte('p.completed', ':end_time'));
      $qb->setParameter('end_time', $end_time);
    }
    if ($is_terminating !== NULL) {
      $qb->andWhere($qb->expr()->eq('p.isTerminating', ':is_terminating'));
      $qb->setParameter('is_terminating', $is_terminating);
    }
    if ($client_job_id !== NULL) {
      $qb->andWhere($qb->expr()->eq('p.clientJobId', ':client_job_id'));
      $qb->setParameter('client_job_id', $client_job_id);
    }

    return (int) $qb->getQuery()->getSingleScalarResult();
  }

  /**
   * {@inheritdoc}
   */
  public function load(
    $offset = 0,
    $count = 20,
    $sort_order = 'ASC',
    $status = NULL,
    $parent = NULL,
    $group_name = NULL,
    $paused = NULL,
    $priority = NULL,
    $uuid = NULL,
    $created_before = NULL,
    $is_terminating = NULL,
    $client_job_id = NULL
  ) {
    if (!is_int($offset) || $offset < 0) {
      throw new \InvalidArgumentException('The offset argument must be a non-negative integer.');
    }
    if (!is_int($count) || $count <= 0) {
      throw new \InvalidArgumentException('The count argument must be a positive integer.');
    }
    if (!is_string($sort_order) || !in_array($sort_order, array('ASC', 'DESC'))) {
      throw new \InvalidArgumentException(sprintf(
        'The sort_order argument must be either "ASC" or "DESC", %s given.',
        var_export($sort_order, TRUE)
      ));
    }
    if ($status !== NULL && (!is_int($status) || !TaskStatus::isValid($status))) {
      throw new \InvalidArgumentException('The status argument must be a valid task status value.');
    }
    if ($parent !== NULL && (!is_int($parent) || $parent < 0)) {
      throw new \InvalidArgumentException('The parent argument must be a non-negative integer.');
    }
    if ($group_name !== NULL && !is_string($group_name)) {
      throw new \InvalidArgumentException('The group_name argument must be a string.');
    }
    if ($paused !== NULL && !is_bool($paused)) {
      throw new \InvalidArgumentException('The paused argument must be a boolean.');
    }
    if ($priority !== NULL && (!is_int($priority) || !TaskPriority::isValid($priority))) {
      throw new \InvalidArgumentException('The priority argument must be a valid task priority value.');
    }
    if ($uuid !== NULL && !is_string($uuid)) {
      throw new \InvalidArgumentException('The uuid argument must be a string.');
    }
    if ($created_before !== NULL && (!is_int($created_before) || $created_before < 0)) {
      throw new \InvalidArgumentException('The created before time must be a non-negative integer.');
    }
    if ($is_terminating !== NULL && !is_bool($is_terminating)) {
      throw new \InvalidArgumentException('The is_terminating argument must be a boolean.');
    }
    if ($client_job_id !== NULL && !is_string($client_job_id)) {
      throw new \InvalidArgumentException('The client_job_id argument must be a string.');
    }

    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    $qb = $this->entityManager->createQueryBuilder();
    $qb->select('p');
    $qb->from(self::ENTITY_NAME, 'p');

    if ($status !== NULL) {
      $qb->andWhere($qb->expr()->eq('p.runStatus', ':status'));
      $qb->setParameter('status', $status);
    }
    if ($parent !== NULL) {
      $qb->andWhere($qb->expr()->eq('p.parent', ':parent'));
      $qb->setParameter('parent', $parent);
    }
    if ($group_name !== NULL) {
      $qb->andWhere($qb->expr()->eq('p.groupName', ':group_name'));
      $qb->setParameter('group_name', $group_name);
    }
    if ($paused !== NULL) {
      $qb->andWhere($qb->expr()->eq('p.paused', ':paused'));
      $qb->setParameter('paused', $paused);
    }
    if ($priority !== NULL) {
      $qb->andWhere($qb->expr()->eq('p.priority', ':priority'));
      $qb->setParameter('priority', $priority);
    }
    if ($uuid !== NULL) {
      $qb->andWhere($qb->expr()->eq('p.uuid', ':uuid'));
      $qb->setParameter('uuid', $uuid);
    }
    if ($created_before !== NULL) {
      $qb->andWhere($qb->expr()->lt('p.created', ':created'));
      $qb->setParameter('created', $created_before);
    }
    if ($is_terminating !== NULL) {
      $qb->andWhere($qb->expr()->lt('p.isTerminating', ':is_terminating'));
      $qb->setParameter('is_terminating', $is_terminating);
    }
    if ($client_job_id !== NULL) {
      $qb->andWhere($qb->expr()->eq('p.clientJobId', ':client_job_id'));
      $qb->setParameter('client_job_id', $client_job_id);
    }

    $qb->orderBy('p.wid', $sort_order);
    $qb->setMaxResults($count);
    $qb->setFirstResult($offset);

    $entries = $qb->getQuery()->getResult();
    return WipPoolStoreEntry::toTaskArray($entries);
  }

  /**
   * {@inheritdoc}
   */
  public function loadCompletedIdRange($start, $stop = NULL) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    $format = 'SELECT t.wid FROM %s t WHERE t.runStatus = :complete AND t.wid >= :start';
    if ($stop !== NULL) {
      $format .= ' AND t.wid <= :stop';
    }
    $query = $this->entityManager->createQuery(
      sprintf($format, self::ENTITY_NAME)
    );
    $query->setParameter('complete', TaskStatus::COMPLETE);
    $query->setParameter('start', $start);
    if ($stop !== NULL) {
      $query->setParameter('stop', $stop);
    }
    $query->execute();
    $results = $query->getResult();
    $task_ids = [];
    foreach ($results as $result) {
      $task_ids[] = $result['wid'];
    }
    return $task_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function get($id, $uuid = NULL) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    $criteria = array(
      'wid' => $id,
    );
    if ($uuid !== NULL) {
      $criteria['uuid'] = $uuid;
    }
    // @index primary key
    /** @var WipPoolStoreEntry $wip_pool_entry */
    $wip_pool_entry = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->findOneBy($criteria);
    return $wip_pool_entry ? $wip_pool_entry->toTask() : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getNextTasks($count = 1) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();
    $wip_pool_controller = WipPoolController::getWipPoolController($this->dependencyManager);
    if ($wip_pool_controller->isHardPausedGlobal()) {
      return [];
    }

    $started_tasks = $this->findConcurrentWaitingTasks();
    $this->removeTasksWithActiveThread($started_tasks);
    if (count($started_tasks) >= $count) {
      return array_values($started_tasks);
    }

    // The basic param values that don't change.
    $params = array(
      ':no_pause' => WipPause::NONE,
      ':not_claimed' => Task::NOT_CLAIMED,
    );

    // Find groups that already have enough tasks running to be able to exclude
    // those groups from the main queries.
    $saturated_groups = $this->findSaturatedGroups($this->getDefaultGroupConcurrency());

    // Run the main query once, preferring started work. If that produces no
    // results, try again also allowing work that has not been started.
    $work_type = array(
      TRUE, // Work that has started.
    );
    // Only consider work that hasn't started if a global soft-pause is not in place.
    if (!$wip_pool_controller->isSoftPausedGlobal()) {
      $work_type[] = FALSE; // Work not yet started.
    }
    $new_tasks = array();
    foreach ($work_type as $only_started_work) {
      $dql = sprintf(
        'SELECT p FROM %s p WHERE p.claimTime = :not_claimed AND p.paused = :no_pause',
        self::ENTITY_NAME
      );
      $param_counter = 0;
      $query = $this->entityManager->createQuery();
      $query->setParameters($params);

      $paused_groups = $wip_pool_controller->getHardPausedGroups();
      if ($only_started_work) {
        // Omit soft paused groups when considering work that has not yet started.
        $paused_groups = array_diff($paused_groups, $wip_pool_controller->getSoftPausedGroups());
      }

      // Exclude any tasks belonging to "saturated" groups and paused groups from the results.
      $groups_to_pause = array_unique(array_merge($saturated_groups, $paused_groups));
      if (count($groups_to_pause) > 0) {
        $placeholders = array();
        foreach ($groups_to_pause as $group) {
          ++$param_counter;
          $placeholders[] = "?$param_counter";
          $query->setParameter($param_counter, $group);
        }
        $placeholders_str = implode(', ', $placeholders);
        $dql .= " AND p.groupName NOT IN ($placeholders_str) ";
      }

      if ($only_started_work) {
        $dql .= ' AND p.wakeTime BETWEEN 1 AND :now';
        $dql .= ' AND p.runStatus = :waiting';
        // Sort by prioritized items and then by terminating. This allows us to process
        // critical items before terminating items and then all other priorities.
        $dql .= ' ORDER BY p.isPrioritized DESC, p.isTerminating DESC, p.priority ASC, p.wakeTime ASC, p.wid ASC';
        $query->setParameter('now', time());
        $query->setParameter('waiting', TaskStatus::WAITING);
      } else {
        // Find the work ids that are currently in process. In-progress tasks
        // must have unique work IDs. We do not allow any concurrency at the
        // moment for duplicate work IDs.
        $work_ids_in_progress = $this->findWorkIdsInProcess();
        if (!empty($work_ids_in_progress)) {
          $placeholders = array();
          foreach ($work_ids_in_progress as $work_id) {
            ++$param_counter;
            $placeholders[] = "?$param_counter";
            $query->setParameter($param_counter, $work_id);
          }
          $placeholders_str = implode(', ', $placeholders);
          $dql .= " AND p.workId NOT IN ($placeholders_str)";
        }

        // Find work that has not yet been started.
        $dql .= ' AND p.runStatus IN (:not_started, :restarted)';
        // Sort by prioritized items and then by terminating. This allows us to process
        // critical items before terminating items and then all other priorities.
        $dql .= ' ORDER BY p.isPrioritized DESC, p.isTerminating DESC, p.priority ASC, p.wid ASC';
        $query->setParameter('not_started', TaskStatus::NOT_STARTED);
        $query->setParameter('restarted', TaskStatus::RESTARTED);
      }
      $query->setDQL($dql);
      $query->setMaxResults($count);
      $query->execute();

      /** @var WipPoolStoreEntry[] $wip_pool_entries */
      $wip_pool_entries = $query->getResult();

      $new_tasks = array_merge(
        $new_tasks,
        WipPoolStoreEntry::entriesToTasks($wip_pool_entries)
      );
      $new_tasks = $this->getUniqueTasks($new_tasks);
      if (count($started_tasks) + count($new_tasks) >= $count) {
        break;
      }
    }
    // Only limit new tasks to group concurrency.
    if (count($new_tasks) > 0) {
      $this->removeTasksWithActiveThread($new_tasks);
      $this->applyGroupLimits($new_tasks);
    }

    /** @var TaskInterface[] $results */
    $results = $this->getUniqueTasks(array_merge($started_tasks, $new_tasks));
    return array_values($results);
  }

  /**
   * Returns a set of tasks with unique ids and work ids from the specified array, preserving order.
   *
   * @param TaskInterface[] $tasks
   *   The tasks.
   *
   * @return TaskInterface[]
   *   The unique tasks, order preserved.
   */
  private function getUniqueTasks($tasks) {
    $result = array();
    $ids = array();
    $work_ids = array();
    foreach ($tasks as $task) {
      if (!in_array($task->getId(), $ids) && !in_array($task->getWorkId(), $work_ids)) {
        $result[] = $task;
        $ids[] = $task->getId();
        $work_ids[] = $task->getWorkId();
      }
    }
    return $result;
  }

  /**
   * Retrieves the values from the wip_group_max_concurrency table and caches them.
   */
  private function initializeMaxConcurrencyPerGroup() {
    if (empty($this->maxConcurrencyPerGroup)) {
      $connection = $this->entityManager->getConnection();

      $result = $connection->fetchAll(
        'SELECT * FROM wip_group_max_concurrency'
      );

      $flatten = [];
      foreach ($result as $data) {
        $flatten[$data['group_name']] = $data['max_count'];
      }
      $this->maxConcurrencyPerGroup = $flatten;
    }
  }

  /**
   * Removes tasks from the list with the same ID as a running thread.
   *
   * @param TaskInterface[] $tasks
   *   The tasks list without those tasks with a running thread.
   */
  private function removeTasksWithActiveThread(&$tasks) {
    $thread_store = $this->dependencyManager->getDependency('acquia.wip.storage.thread');
    $running_wids = $thread_store->getRunningWids();
    foreach ($tasks as $index => $task) {
      if (in_array($task->getId(), $running_wids)) {
        unset($tasks[$index]);
      }
    }
  }

  /**
   * Clears cached max concurrency per group values.
   *
   * Note: for unit testing only.
   */
  public function clearCachedConcurrencyGroups() {
    $this->maxConcurrencyPerGroup = [];
  }

  /**
   * Retrieves the concurrency groups with tasks in progress.
   *
   * @return array
   *   An array of group names with their associated current running concurrency.
   */
  private function getRunningTaskGroupCount() {
    $result = [];
    $connection = $this->entityManager->getConnection();

    // Get the number of tasks currently executing per group.
    $active_groups = $connection->fetchAll(
      'SELECT wgc.group_name, count(*) as count
      FROM wip_group_concurrency wgc GROUP BY wgc.group_name'
    );

    if (!empty($active_groups)) {
      $flatten = [];
      foreach ($active_groups as $data) {
        $flatten[$data['group_name']] = $data['count'];
      }
      $result = $flatten;
    }
    return $result;
  }

  /**
   * Returns a subset of tasks that will not over-run group concurrency.
   *
   * Note that the task order will be preserved, but tasks may be removed if
   * group concurrency has been met.
   *
   * @param TaskInterface[] $tasks
   *   The tasks.
   */
  private function applyGroupLimits(&$tasks) {
    $running_task_group_count = $this->getRunningTaskGroupCount();
    foreach ($tasks as $index => $task) {
      $group_name = $task->getGroupName();
      $remaining_capacity = $this->getMaxConcurrency($group_name);
      if (array_key_exists($group_name, $running_task_group_count)) {
        // There are currently tasks running.
        $remaining_capacity -= $running_task_group_count[$group_name];
      } else {
        $running_task_group_count[$group_name] = 0;
      }
      if ($remaining_capacity > 0) {
        // Keep this task, but account for the concurrency.
        $running_task_group_count[$group_name]++;
      } else {
        // Remove this task so as to not violate group concurrency.
        unset($tasks[$index]);
      }
    }
  }

  /**
   * Determines the max concurrency for the given group.
   *
   * @param string $group_name
   *   The name of a concurrency group.
   *
   * @return int
   *   The max concurrency for the given group.
   */
  private function getMaxConcurrency($group_name) {
    if (empty($this->maxConcurrencyPerGroup)) {
      $this->initializeMaxConcurrencyPerGroup();
    }
    if (!array_key_exists($group_name, $this->maxConcurrencyPerGroup)) {
      $this->maxConcurrencyPerGroup[$group_name] = $this->getDefaultGroupConcurrency();
    }
    return $this->maxConcurrencyPerGroup[$group_name];
  }

  /**
   * {@inheritdoc}
   */
  public function remove(TaskInterface $task) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    // @index primary key
    $wip_pool_entry = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->find($task->getId());
    if ($wip_pool_entry) {
      $this->entityManager->remove($wip_pool_entry);
      $this->entityManager->flush();
    }
  }

  /**
   * Prunes old finished tasks.
   *
   * @param int $timestamp
   *   Finished tasks that were created on or older than this timestamp will
   *   be deleted.
   * @param int $limit
   *   The number of tasks to be deleted.
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

    // Fetch a number of task IDs that can be deleted.
    // @index run_status_idx
    $query = $this->entityManager
      ->createQuery(sprintf(
        'SELECT t.wid FROM %s t WHERE t.created <= :created AND t.runStatus = :run_status',
        self::ENTITY_NAME
      ));
    $query->setParameter('created', $timestamp);
    $query->setParameter('run_status', TaskStatus::COMPLETE);
    $query->setMaxResults($limit);
    $query->execute();
    $task_ids = $query->getResult();

    // Actual deletion.
    if ($task_ids) {
      // Prune the associated Wip objects first.
      // @index primary key
      $query = $this->entityManager->createQuery(sprintf(
        'DELETE FROM %s w WHERE w.wid IN (:task_ids)',
        'Acquia\WipIntegrations\DoctrineORM\Entities\WipStoreEntry'
      ));
      $query->setParameter('task_ids', $task_ids);
      $query->execute();

      // Prune the Signals.
      $query = $this->entityManager->createQuery(sprintf(
        'DELETE FROM %s s WHERE s.objectId IN (:task_ids)',
        SignalStore::ENTITY_NAME
      ));
      $query->setParameter('task_ids', $task_ids);
      $query->execute();

      // Prune the SignalCallbacks.
      $query = $this->entityManager->createQuery(sprintf(
        'DELETE FROM %s s WHERE s.wipId IN (:task_ids)',
        SignalCallbackStore::ENTITY_NAME
      ));
      $query->setParameter('task_ids', $task_ids);
      $query->execute();

      // Prune the Threads.
      $query = $this->entityManager->createQuery(sprintf(
        'DELETE FROM %s t WHERE t.wid IN (:task_ids)',
        ThreadStore::ENTITY_NAME
      ));
      $query->setParameter('task_ids', $task_ids);
      $query->execute();

      // Prune the Logs.
      $query = $this->entityManager->createQuery(sprintf(
        'DELETE FROM %s l WHERE l.objectId IN (:task_ids)',
        WipLogStore::ENTITY_NAME
      ));
      $query->setParameter('task_ids', $task_ids);
      $query->execute();

      // Prune the Concurrency.
      $query = $this->entityManager->createQuery(sprintf(
        'DELETE FROM %s c WHERE c.wid IN (:task_ids)',
        'Acquia\WipIntegrations\DoctrineORM\Entities\WipGroupConcurrencyEntry'
      ));
      $query->setParameter('task_ids', $task_ids);
      $query->execute();

      // Lastly, prune the Tasks themselves.
      // @index primary key
      $query = $this->entityManager->createQuery(sprintf(
        'DELETE FROM %s t WHERE t.wid IN (:task_ids)',
        self::ENTITY_NAME
      ));
      $query->setParameter('task_ids', $task_ids);
      $query->execute();
    }

    // Check if there are more items to be deleted.
    // @index run_status_idx
    $query = $this->entityManager->createQuery(sprintf(
      'SELECT t.wid FROM %s t WHERE t.created <= :created AND t.runStatus = :run_status',
      self::ENTITY_NAME
    ));
    $query->setParameter('created', $timestamp);
    $query->setParameter('run_status', TaskStatus::COMPLETE);
    $query->setMaxResults(1);
    $query->execute();
    $task_ids = $query->getResult();

    return !empty($task_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function pruneObjects(array $object_ids) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    // Prune the Tasks themselves.
    // @index primary key
    $query = $this->entityManager->createQuery(sprintf(
      'DELETE FROM %s t WHERE t.wid IN (:wids)',
      self::ENTITY_NAME
    ));
    $query->setParameter('wids', $object_ids);
    $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletedIds($group_name, $created_before, $limit = 1000) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    // Fetch a number of task IDs that can be deleted.
    // @index run_status_idx
    $query = $this->entityManager
      ->createQuery(sprintf(
        'SELECT t.wid FROM %s t WHERE t.created <= :created AND t.groupName = :group_name AND t.runStatus = :run_status',
        self::ENTITY_NAME
      ));
    $query->setParameter('created', $created_before);
    $query->setParameter('run_status', TaskStatus::COMPLETE);
    $query->setParameter('group_name', $group_name);
    $query->setMaxResults($limit);
    $query->execute();
    return $query->getResult();
  }

  /**
   * {@inheritdoc}
   */
  public function getChildrenTaskIds($parent_task_id) {
    if (!is_int($parent_task_id) || $parent_task_id <= 0) {
      throw new \InvalidArgumentException('The parent task id argument must be a positive integer.');
    }

    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    // @index parent_idx
    $query = $this->entityManager->createQuery(sprintf(
      'SELECT t.wid FROM %s t WHERE t.parent = :parent_task_id',
      self::ENTITY_NAME
    ));
    $query->setParameter('parent_task_id', $parent_task_id);
    $query->execute();
    $children_task_wids = $query->getResult();

    $children_task_ids = array();
    foreach ($children_task_wids as $struct) {
      $children_task_ids[] = $struct['wid'];
    }

    return $children_task_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function startProgress(TaskInterface $task) {
    $this->claimConcurrency($task->getId(), $task->getGroupName());
  }

  /**
   * {@inheritdoc}
   */
  public function stopProgress(TaskInterface $task) {
    // As with previous generation WIP: only release concurrency if the task is
    // not WAITING.
    if ($task->getStatus() != TaskStatus::WAITING) {
      $this->releaseConcurrency($task->getId());
    }
  }

  /**
   * Locates the tasks which are WAITING, and due for further processing.
   *
   * More precisely, we are looking for any tasks which we will consider the
   * highest priority. Such a task is WAITING, and hence is present in the
   * concurrency table also, it is due for further processing, and is not
   * paused on any level.
   *
   * @return TaskInterface[]
   *   An array containing the tasks that have been waiting and are now ready
   *   to continue.
   */
  private function findConcurrentWaitingTasks() {
    $query = $this->entityManager->createQuery(sprintf(
      'SELECT wp FROM Acquia\WipIntegrations\DoctrineORM\Entities\WipGroupConcurrencyEntry wgc
      JOIN %s wp WITH wp.wid = wgc.wid
      WHERE wp.runStatus = :waiting AND wp.wakeTime <= :now AND wp.paused = :no_pause
      ORDER BY wp.priority ASC, wp.wakeTime ASC, wp.wid ASC',
      self::ENTITY_NAME
    ));
    $query->setParameters(array(
      ':waiting'  => TaskStatus::WAITING,
      ':now'      => time(),
      ':no_pause' => WipPause::NONE,
    ));
    $results = $query->getResult();

    $tasks = WipPoolStoreEntry::entriesToTasks($results);
    return $this->getUniqueTasks($tasks);
  }

  /**
   * Locates groups that have reached their maximum concurrency.
   *
   * @param int $default_max_concurrency
   *   A default maximum concurrency to use for any group that has not specified
   *   a value elsewhere.
   *
   * @return string[]
   *   An array of group names.
   */
  public function findSaturatedGroups($default_max_concurrency) {
    $connection = $this->entityManager->getConnection();
    $groups = $connection->fetchAll(
      'SELECT wgc.group_name, wgmc.max_count
      FROM wip_group_concurrency wgc
      LEFT JOIN wip_group_max_concurrency wgmc
      ON wgmc.group_name = wgc.group_name
      GROUP BY wgc.group_name
      HAVING (wgmc.max_count IS NOT NULL AND count(*) >= wgmc.max_count)
      OR (wgmc.max_count IS NULL AND count(*) >= :default)
      UNION SELECT group_name, max_count
      FROM wip_group_max_concurrency
      WHERE max_count = 0',
      array(
        ':default' => $default_max_concurrency,
      )
    );

    if ($this->cleanup && !empty($groups)) {
      // There are saturated groups. Make certain the group concurrency entries
      // are still relevant. While this cleanup will not affect the current
      // result, any subsequent queries will benefit from the cleanup.
      //
      // Doing the cleanup here ensures that the cleanup gets called if group
      // concurrency is preventing Wip tasks from being started.
      $this->cleanupConcurrency();
      $this->cleanup = FALSE;
    }

    return array_map(array($this, 'mapGroupName'), $groups);
  }

  /**
   * Returns the "group_name" value of an array.
   *
   * @param array $array
   *   The input array.
   *
   * @return string
   *   The group name.
   */
  private function mapGroupName($array) {
    return $array['group_name'];
  }

  /**
   * {@inheritdoc}
   */
  public function findProcessingTasks() {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    $result = [];
    /** @var WipPoolStoreEntry[] $entries */
    $entries = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->findBy(array('runStatus' => TaskStatus::PROCESSING));

    if (!empty($entries)) {
      foreach ($entries as $entry) {
        $result[] = $entry->toTask();
      }
    }

    return $result;
  }

  /**
   * Locates any work ids that are currently in process.
   *
   * @return array
   *   An array of Task work IDs.
   */
  private function findWorkIdsInProcess() {
    $in_process = array();
    $query = $this->entityManager->createQuery(sprintf(
      'SELECT p.workId FROM %s p WHERE p.runStatus IN (:waiting, :processing)',
      self::ENTITY_NAME
    ));
    $query->setParameter('waiting', TaskStatus::WAITING);
    $query->setParameter('processing', TaskStatus::PROCESSING);
    $query->execute();
    $result = $query->getResult();
    if (!empty($result)) {
      foreach ($result as $task) {
        $in_process[] = $task['workId'];
      }
    }
    return $in_process;
  }

  /**
   * Adds a flag to Indicate that a task of a given group is in progress.
   *
   * @param int $id
   *   The task ID.
   * @param string $group
   *   The group of the task.
   */
  private function claimConcurrency($id, $group) {
    $connection = $this->entityManager->getConnection();
    $connection->executeUpdate(
      'INSERT IGNORE INTO wip_group_concurrency (wid, group_name) VALUES (:wid, :group_name)',
      array(
        ':wid' => $id,
        ':group_name' => $group,
      )
    );
  }

  /**
   * Removes the flag that a task of a given group is in progress.
   *
   * @param int $id
   *   The task ID.
   */
  private function releaseConcurrency($id) {
    $connection = $this->entityManager->getConnection();
    $connection->executeUpdate(
      'DELETE FROM wip_group_concurrency WHERE wid = :id',
      array(
        ':id' => $id,
      )
    );
  }

  /**
   * Deletes all group concurrency items for which the task has completed and deletes all inconsistent threads.
   *
   * Note: threads in the running state without the associated task being in the processing state will block progress
   * on that task.
   */
  public function cleanupConcurrency() {
    $qb = $this->entityManager->createQueryBuilder();
    $qb->select('wgc.wid')
      ->from('Acquia\WipIntegrations\DoctrineORM\Entities\WipGroupConcurrencyEntry', 'wgc')
      ->leftJoin(
        self::ENTITY_NAME,
        'wp',
        Join::WITH,
        'wgc.wid = wp.wid'
      )
      ->where('wp.exitStatus != :not_finished')
      ->setParameter(':not_finished', TaskExitStatus::NOT_FINISHED);

    $entries_to_delete = $qb->getQuery()->getResult();

    if (count($entries_to_delete) > 0) {
      $query = $this->entityManager->createQuery(
        'DELETE FROM Acquia\WipIntegrations\DoctrineORM\Entities\WipGroupConcurrencyEntry w WHERE w.wid IN (?1)'
      );
      $query->setParameter(1, $entries_to_delete);
      $query->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function pauseTask($task_id) {
    $dql = sprintf(
      'UPDATE %s w SET w.paused = 1 WHERE w.wid = :id',
      self::ENTITY_NAME
    );

    $query = $this->entityManager->createQuery();
    $query->setParameter('id', $task_id);
    $query->setDQL($dql);
    $query->setMaxResults(1);
    $query->execute();
    return $this->isTaskPaused($task_id);
  }

  /**
   * {@inheritdoc}
   */
  public function resumeTask($task_id) {
    $dql = sprintf(
      'UPDATE %s w SET w.paused = 0 WHERE w.wid = :id',
      self::ENTITY_NAME
    );

    $query = $this->entityManager->createQuery();
    $params = array(
      ':id' => $task_id,
    );
    $query->setParameters($params);
    $query->setDQL($dql);
    $query->setMaxResults(1);
    $query->execute();
    return !$this->isTaskPaused($task_id);
  }

  /**
   * Indicates whether the specified task is paused.
   *
   * @param int $task_id
   *   The task ID.
   *
   * @return bool
   *   TRUE if the specified task is paused; FALSE otherwise.
   *
   * @throws \DomainException
   *   If the specified task cannot be found.
   */
  private function isTaskPaused($task_id) {
    $result = FALSE;
    $verify = sprintf('SELECT paused FROM wip_pool WHERE wid = ?', self::ENTITY_NAME);
    $pause_info = $this->entityManager->getConnection()->executeQuery($verify, array($task_id))->fetch();
    if (count($pause_info) === 0) {
      throw new \DomainException(sprintf('Task %d not found.', $task_id));
    }
    if (count($pause_info) === 1 && !empty($pause_info['paused'])) {
      $result = TRUE;
    }
    return $result;
  }

  /**
   * Converts an array of task instances to a list of task IDs.
   *
   * @param TaskInterface[] $tasks
   *   An array of task instances.
   *
   * @return int[]
   *   A list of task IDs.
   */
  public static function getTaskIds(array $tasks) {
    $result = array();
    foreach ($tasks as $task) {
      $result[] = $task->getId();
    }
    return $result;
  }

}
