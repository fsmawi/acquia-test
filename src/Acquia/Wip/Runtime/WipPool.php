<?php

namespace Acquia\Wip\Runtime;

use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\DependencyManager;
use Acquia\Wip\Exception\InvalidOperationException;
use Acquia\Wip\Exception\NoTaskException;
use Acquia\Wip\Exception\RowLockException;
use Acquia\Wip\Lock\WipPoolRowLock;
use Acquia\Wip\Metrics\MetricsRelayInterface;
use Acquia\Wip\Storage\BasicWipPoolStore;
use Acquia\Wip\Storage\WipStoreInterface;
use Acquia\Wip\Task;
use Acquia\Wip\TaskExitStatus;
use Acquia\Wip\TaskInterface;
use Acquia\Wip\TaskPriority;
use Acquia\Wip\TaskStatus;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipInterface;

/**
 * Manages the jobs awaiting processing.
 */
class WipPool implements WipPoolInterface, DependencyManagedInterface {

  /**
   * An instance of DependencyManager.
   *
   * @var DependencyManager
   */
  public $dependencyManager;

  /**
   * Creates a new instance of WipPool.
   *
   * @throws \Acquia\Wip\Exception\DependencyTypeException
   *   If the WipPool dependencies are not satisfied.
   */
  public function __construct() {
    $this->dependencyManager = new DependencyManager();
    $this->dependencyManager->addDependencies($this->getDependencies());
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.storage.wippool' => 'Acquia\Wip\Storage\WipPoolStoreInterface',
      'acquia.wip.storage.wip' => 'Acquia\Wip\Storage\WipStoreInterface',
      'acquia.wip.metrics.relay' => 'Acquia\Wip\Metrics\MetricsRelayInterface',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getNextTasks($count = 1) {
    // @todo - claim global lock on wip pool storage? Unclear whether we need to
    $tasks = BasicWipPoolStore::getWipPoolStore($this->dependencyManager)->getNextTasks($count);
    if (empty($tasks)) {
      throw new NoTaskException('No more tasks available for processing.');
    }

    // Instrument the number of tasks in each status type.
    $this->measureTaskCounts();

    // @todo - release global lock on wip pool storage
    return $tasks;
  }

  /**
   * Finds the number of tasks in each status and reports them accordingly.
   */
  protected function measureTaskCounts() {
    $storage = BasicWipPoolStore::getWipPoolStore($this->dependencyManager);
    $relay = $this->getMetricsRelay();

    $namespaces = array(
      TaskStatus::NOT_READY => 'wip.pool.count.not_ready',
      TaskStatus::NOT_STARTED => 'wip.pool.count.not_started',
      TaskStatus::WAITING => 'wip.pool.count.waiting',
      TaskStatus::PROCESSING => 'wip.pool.count.processing',
      TaskStatus::COMPLETE => 'wip.pool.count.complete',
      TaskStatus::RESTARTED => 'wip.pool.count.restarted',
    );

    foreach ($namespaces as $status => $namespace) {
      $count = $storage->count($status);

      // New tasks have not updated the db yet, so decrement.
      // @todo - if measured outside of getNextTasks() this logic won't be valid.
      if ($status === TaskStatus::NOT_STARTED && $count > 0) {
        $count--;
      }

      $relay->gauge($namespace, $count);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTask($task_id) {
    // @todo - claim global lock on wip pool storage? Unclear whether we need to
    $task = BasicWipPoolStore::getWipPoolStore($this->dependencyManager)->get($task_id);
    if (!$task instanceof TaskInterface || empty($task)) {
      throw new NoTaskException(sprintf('There is no task associated with ID %s.', $task_id));
    }

    // @todo - release global lock on wip pool storage
    return $task;
  }

  /**
   * {@inheritdoc}
   */
  public function addTask(
    WipInterface $wip,
    TaskPriority $priority = NULL,
    $group_name = '',
    $parent_id = NULL,
    $client_job_id = NULL
  ) {
    /** @var WipStoreInterface $object_storage */
    $object_storage = $this->dependencyManager->getDependency('acquia.wip.storage.wip');

    $iterator = WipFactory::getObject('acquia.wip.iterator');
    $iterator->initialize($wip);

    $task = new Task();
    $task->setUuid($wip->getUuid());

    // For consistency, set the iterator on the object being stored - this might
    // help during testing (for in-memory storage implementations).
    $task->setWipIterator($iterator);
    if (!isset($priority)) {
      $priority = TaskPriority::MEDIUM;
    } else {
      $priority = $priority->getValue();
    }
    $task->setPriority($priority);
    // Prioritized critical tasks.
    if ($priority == TaskPriority::CRITICAL) {
      $task->setIsPrioritized(TRUE);
    } else {
      $task->setIsPrioritized(FALSE);
    }
    if (empty($group_name)) {
      $group_name = $wip->getGroup();
    }
    $task->setGroupName($group_name);
    $task->setWorkId($wip->getWorkId());

    if (!empty($client_job_id)) {
      $task->setClientJobId($client_job_id);
    }

    if (NULL !== $parent_id) {
      if (!is_int($parent_id)) {
        throw new \InvalidArgumentException('The "parent" parameter must be an integer.');
      }
      $task->setParentId($parent_id);
    }

    // Save the task metadata.
    $task = $this->saveTask($task);
    if (is_int($task->getId())) {
      $wip->setId($task->getId());
    }

    // Invoke any onAdd hook of the WIP object. Note this should be done before
    // the object is saved so any changes to internal state during onAdd will
    // persist.
    $wip->onAdd();

    // Save the iterator state.
    $object_storage->save($task->getId(), $iterator);

    // Now the task is ready to be executed.
    $task->setStatus(TaskStatus::NOT_STARTED);
    $task = $this->saveTask($task);

    $relay = $this->getMetricsRelay();
    $relay->increment('wip.add');

    return $task;
  }

  /**
   * {@inheritdoc}
   */
  public function saveTask(TaskInterface $task) {
    // Don't save the exit message until the task has finished.
    if ($task->getExitStatus() == TaskExitStatus::NOT_FINISHED) {
      $task->setExitMessage('');
    }

    BasicWipPoolStore::getWipPoolStore($this->dependencyManager)->save($task);
    return $task;
  }

  /**
   * {@inheritdoc}
   */
  public function restartTask(TaskInterface $task) {
    if (!WipPoolRowLock::getWipPoolRowLock($task->getId(), NULL, $this->dependencyManager)->hasLock()) {
      throw new RowLockException(
        'The wip_pool row update lock must be acquired before calling restartTask.'
      );
    }
    if ($task->getStatus() !== TaskStatus::COMPLETE) {
      throw new InvalidOperationException('Tasks can only be restarted from the status "COMPLETE"');
    }

    /** @var WipStoreInterface $object_storage */
    $object_storage = $this->dependencyManager->getDependency('acquia.wip.storage.wip');

    $task->setStatus(TaskStatus::RESTARTED);
    $task->setWakeTimestamp(0);
    $iterator = $task->getWipIterator();
    $iterator->restart();

    BasicWipPoolStore::getWipPoolStore($this->dependencyManager)->save($task);
    $object_storage->save($task->getId(), $iterator);
  }

  /**
   * {@inheritdoc}
   */
  public function startProgress(TaskInterface $task) {
    // By default, this hook just delegates handling to the storage layer.
    BasicWipPoolStore::getWipPoolStore($this->dependencyManager)->startProgress($task);
  }

  /**
   * {@inheritdoc}
   */
  public function stopProgress(TaskInterface $task) {
    // By default, this hook just delegates handling to the storage layer.
    BasicWipPoolStore::getWipPoolStore($this->dependencyManager)->stopProgress($task);
  }

  /**
   * {@inheritdoc}
   */
  public function cleanupConcurrency() {
    BasicWipPoolStore::getWipPoolStore($this->dependencyManager)->cleanupConcurrency();
  }

  /**
   * Retrieves the metrics relay object.
   *
   * @return MetricsRelayInterface
   *   The metrics relay.
   */
  protected function getMetricsRelay() {
    return $this->dependencyManager->getDependency('acquia.wip.metrics.relay');
  }

  /**
   * {@inheritdoc}
   */
  public function getUnpausedTasksBeingExecutedCount() {
    $result = 0;
    $wip_pool_store = BasicWipPoolStore::getWipPoolStore($this->dependencyManager);
    foreach (array(TaskStatus::PROCESSING, TaskStatus::WAITING) as $status) {
      $result += $wip_pool_store->count(
        $task_status = $status,
        $parent = NULL,
        $group_name = NULL,
        $paused = FALSE
      );
    }
    return $result;
  }

}
