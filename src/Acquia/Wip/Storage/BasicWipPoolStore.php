<?php

namespace Acquia\Wip\Storage;

use Acquia\Wip\DependencyManager;
use Acquia\Wip\Exception\DependencyMissingException;
use Acquia\Wip\Task;
use Acquia\Wip\TaskInterface;
use Acquia\Wip\TaskPriority;
use Acquia\Wip\TaskStatus;
use Acquia\Wip\WipFactory;

/**
 * Provides a base class to test Thread storage.
 *
 * @copydetails WipPoolStoreInterface
 */
class BasicWipPoolStore implements WipPoolStoreInterface {

  /**
   * The WipFactory resource name.
   */
  const RESOURCE_NAME = 'acquia.wip.storage.wippool';

  /**
   * A basic implementation of storage as an in-memory array of Task objects.
   *
   * @var Task[]
   */
  private $tasks = array();

  /**
   * Implements an "autoincrement" ID.
   *
   * @var int
   */
  private $id = 1;

  /**
   * Resets the basic implementation's storage.
   */
  public function initialize() {
    $this->tasks = array();
    $this->id = 1;
  }

  /**
   * {@inheritdoc}
   */
  public function save(TaskInterface $task) {
    if (is_null($task->getId())) {
      if (!$task->getWipIterator()) {
        throw new \InvalidArgumentException('The Task must have an iterator to be added.');
      }
      $task->setId($this->id++);
    }
    // Clone the task so that we can test actual storage.  If we don't clone
    // here, then we'll keep retrieving the exact same object.
    $this->tasks[$task->getId()] = clone $task;
  }

  /**
   * {@inheritdoc}
   */
  public function get($id, $uuid = NULL) {
    $result = NULL;
    if (isset($this->tasks[$id])) {
      if ($uuid === NULL || $this->tasks[$id]->getUuid() === $uuid) {
        $result = clone($this->tasks[$id]);
      }
    } else {
      $result = FALSE;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function count(
    $status = NULL,
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
    $task_list = array_keys($this->tasks);
    if ($status !== NULL && (is_int($status) && TaskStatus::isValid($status))) {
      $task_list = array_filter($task_list, function ($id) use ($status) {
        return $this->tasks[$id]->getStatus() === $status;
      });
    }
    if ($parent !== NULL && (is_int($parent) || $parent >= 0)) {
      $task_list = array_filter($task_list, function ($id) use ($parent) {
        return $this->tasks[$id]->getParentId() === $parent;
      });
    }
    if ($group_name !== NULL && is_string($group_name)) {
      $task_list = array_filter($task_list, function ($id) use ($group_name) {
        return $this->tasks[$id]->getGroupName() === $group_name;
      });
    }
    if ($paused !== NULL && is_bool($paused)) {
      $task_list = array_filter($task_list, function ($id) use ($paused) {
        return $this->tasks[$id]->isPaused() === $paused;
      });
    }
    if ($priority !== NULL && (is_int($priority) && TaskPriority::isValid($priority))) {
      $task_list = array_filter($task_list, function ($id) use ($priority) {
        return $this->tasks[$id]->getPriority() === $priority;
      });
    }
    if ($uuid !== NULL && is_string($uuid)) {
      $task_list = array_filter($task_list, function ($id) use ($uuid) {
        return $this->tasks[$id]->getUuid() === $uuid;
      });
    }
    if ($exit_status !== NULL && is_int($exit_status)) {
      $task_list = array_filter($task_list, function ($id) use ($exit_status) {
        return $this->tasks[$id]->getExitStatus() === $exit_status;
      });
    }
    if ($start_time !== NULL && is_int($start_time)) {
      $task_list = array_filter($task_list, function ($id) use ($start_time) {
        return $this->tasks[$id]->getStartTimestamp() > $start_time;
      });
    }
    if ($end_time !== NULL && is_int($end_time)) {
      $task_list = array_filter($task_list, function ($id) use ($end_time) {
        return $this->tasks[$id]->getCompletedTimestamp() > $end_time;
      });
    }
    if ($is_terminating !== NULL && is_bool($is_terminating)) {
      $task_list = array_filter($task_list, function ($id) use ($is_terminating) {
        return $this->tasks[$id]->isTerminating() === $is_terminating;
      });
    }
    if ($client_job_id !== NULL && is_string($client_job_id)) {
      $task_list = array_filter($task_list, function ($id) use ($client_job_id) {
        return $this->tasks[$id]->getClientJobId() === $client_job_id;
      });
    }

    return count($task_list);
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
    $result = array();
    foreach ($this->tasks as $task) {
      if ($status !== NULL && $status !== $task->getStatus()) {
        continue;
      }
      if ($parent !== NULL && $parent !== $task->getParentId()) {
        continue;
      }
      if ($group_name !== NULL && $group_name !== $task->getGroupName()) {
        continue;
      }
      if ($paused !== NULL && $paused !== $task->isPaused()) {
        continue;
      }
      if ($priority !== NULL && $priority !== $task->getPriority()) {
        continue;
      }
      if ($uuid !== NULL && $uuid !== $task->getUuid()) {
        continue;
      }
      if ($created_before !== NULL && $task->getCreatedTimestamp() > $created_before) {
        continue;
      }
      if ($is_terminating !== NULL && ($is_terminating !== $task->isTerminating())) {
        continue;
      }
      if ($client_job_id !== NULL && $client_job_id !== $task->getClientJobId()) {
        continue;
      }
      $result[] = $task;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function loadCompletedIdRange($start, $stop = NULL) {
    $result = array();
    foreach ($this->tasks as $task) {
      $id = $task->getId();
      if ($this->inRange($id, $start, $stop)) {
        $result[] = $id;
      }
    }

    return $result;
  }

  /**
   * Determines if the given ID is in the specified range.
   *
   * @param int $id
   *   The ID to evaluate.
   * @param int $start
   *   The start of the range.
   * @param int $stop
   *   Optional. The end of the range.
   *
   * @return bool
   *   Whether the given ID is in the range.
   */
  private function inRange($id, $start, $stop = NULL) {
    if ($stop === NULL) {
      $result = $id >= $start && $id <= $stop;
    } else {
      $result = $id >= $start;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletedIds($group_name, $created_before, $limit = 100) {
    $result = array();
    foreach ($this->tasks as $task) {
      if ($group_name !== NULL && $group_name !== $task->getGroupName()) {
        continue;
      }
      if ($created_before !== NULL && $task->getCreatedTimestamp() > $created_before) {
        continue;
      }
      if (TaskStatus::COMPLETE !== $task->getStatus()) {
        continue;
      }
      $result[] = $task;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getNextTasks($count = 1) {
    $tasks = array();
    $found = 0;
    foreach ($this->tasks as $task) {
      if (!in_array($task->getStatus(), array(TaskStatus::COMPLETE, TaskStatus::PROCESSING))) {
        $tasks[] = $task;
        $found++;
        if ($found >= $count) {
          break;
        }
      }
    }
    return $tasks;
  }

  /**
   * {@inheritdoc}
   */
  public function remove(TaskInterface $task) {
    unset($this->tasks[$task->getId()]);
  }

  /**
   * {@inheritdoc}
   */
  public function pruneObjects(array $object_ids) {
    foreach ($object_ids as $wid) {
      unset($this->tasks[$wid]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getChildrenTaskIds($parent_task_id) {
    if (!is_int($parent_task_id) || $parent_task_id <= 0) {
      throw new \InvalidArgumentException('The parent task id argument must be a positive integer.');
    }

    $children_task_ids = array();
    foreach ($this->tasks as $task) {
      if ($task->getParentId() == $parent_task_id) {
        $children_task_ids[] = $task->getId();
      }
    }

    return $children_task_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function startProgress(TaskInterface $task) {
  }

  /**
   * {@inheritdoc}
   */
  public function stopProgress(TaskInterface $task) {
  }

  /**
   * {@inheritdoc}
   */
  public function cleanupConcurrency() {
  }

  /**
   * Gets the WipPoolStore instance.
   *
   * @param DependencyManager $dependency_manager
   *   Optional. If provided the specified dependency manager will be used to resolve the WipPoolStore; otherwise
   *   the WipFactory will be used.
   *
   * @return WipPoolStoreInterface
   *   The WipPool storage.
   *
   * @throws DependencyMissingException
   *   If a dependency manager was provided for which the WipPoolStore dependency could not be met.
   */
  public static function getWipPoolStore(DependencyManager $dependency_manager = NULL) {
    $result = NULL;
    if (NULL !== $dependency_manager) {
      $result = $dependency_manager->getDependency(self::RESOURCE_NAME);
    } else {
      try {
        $result = WipFactory::getObject(self::RESOURCE_NAME);
      } catch (\Exception $e) {
        // Fall back to a new instance of WipPoolStore.
        $result = new self();
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function pauseTask($task_id, $uuid = NULL) {
    if (isset($this->tasks[$task_id])) {
      $task = $this->tasks[$task_id];
      if ($uuid !== NULL && $uuid !== $task->getUuid()) {
        // Don't do anything because the UUID doesn't match.
      } else {
        $task->setPause(TRUE);
      }
      $result = $task->isPaused();
    } else {
      throw new \DomainException(sprintf('Task %d not found.', $task_id));
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function resumeTask($task_id, $uuid = NULL) {
    if (isset($this->tasks[$task_id])) {
      $task = $this->tasks[$task_id];
      if ($uuid !== NULL && $uuid !== $task->getUuid()) {
        // Don't do anything because the UUID doesn't match.
      } else {
        $task->setPause(FALSE);
      }
      $result = !$task->isPaused();
    } else {
      throw new \DomainException(sprintf('Task %d not found.', $task_id));
    }
    return $result;
  }

  /**
   * Queries for processing tasks.
   *
   * @return TaskInterface[]
   *   An array of processing tasks.
   */
  public function findProcessingTasks() {
    $result = array();
    foreach ($this->tasks as $task_id => $task) {
      if ($task->getStatus() === TaskStatus::PROCESSING) {
        $result[] = $task;
      }
    }
    return $result;
  }

}
