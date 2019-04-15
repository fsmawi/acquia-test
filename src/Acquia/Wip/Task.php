<?php

namespace Acquia\Wip;

use Acquia\Wip\Exception\NoObjectException;
use Acquia\Wip\Exception\NoTaskException;
use Acquia\Wip\Exception\TaskOverwriteException;

/**
 * An object containing metadata about a task.
 */
class Task implements TaskInterface, DependencyManagedInterface {

  /**
   * Value of "claim timestamp" representing "not claimed".
   */
  const NOT_CLAIMED = 0;

  /**
   * The Task id.
   *
   * @var int
   */
  private $id = NULL;

  /**
   * The iterator.
   *
   * @var StateTableIteratorInterface
   */
  private $iterator;

  /**
   * The unique id of this task.
   *
   * @var string
   */
  private $workId = '';

  /**
   * The parent Task's id.
   *
   * @var int
   */
  private $parentId = 0;

  /**
   * The name of this task.
   *
   * @var string
   */
  private $name = '';

  /**
   * The group name of this task.
   *
   * @var string
   */
  private $groupName = '';

  /**
   * The run status of this task.
   *
   * @var int
   */
  private $runStatus = TaskStatus::NOT_READY;

  /**
   * The exit status of this task.
   *
   * @var int
   */
  private $exitStatus = TaskExitStatus::NOT_FINISHED;

  /**
   * The priority of this task.
   *
   * @var int
   */
  private $priority = TaskPriority::MEDIUM;

  /**
   * Whether the task should be terminating.
   *
   * @var bool
   */
  private $isTerminating = FALSE;

  /**
   * Whether the task should be prioritized.
   *
   * Tasks will run in the following order: prioritized terminating tasks,
   * prioritized tasks, all other terminating tasks and other non-terminating
   * tasks.
   *
   * @var bool
   */
  private $isPrioritized = FALSE;

  /**
   * The timestamp corresponding to when this task was started.
   *
   * @var int
   */
  private $startTimestamp = 0;

  /**
   * The timestamp corresponding to when this task should be checked again.
   *
   * @var int
   */
  private $wakeTimestamp = 0;

  /**
   * The timestamp corresponding to when this task was created.
   *
   * @var int
   */
  private $createdTimestamp;

  /**
   * The timestamp corresponding to when this task was completed.
   *
   * @var int
   */
  private $completedTimestamp = 0;

  /**
   * The timestamp corresponding to when this task was claimed.
   *
   * @var int
   */
  private $claimedTimestamp = 0;

  /**
   * Maximum number of seconds in a processing run.
   *
   * This task should only be processed up to this number of seconds in a single
   * processing run.
   *
   * @var int
   */
  private $maxRuntime = 30;

  /**
   * The amount of time this task may be claimed for.
   *
   * @var int
   */
  private $leaseTime = 180;

  /**
   * Flag to store if the Task is paused.
   *
   * @var bool
   */
  private $isPaused = FALSE;

  /**
   * Exit message.
   *
   * @var string
   */
  private $exitMessage = '';

  /**
   * Resource ID.
   *
   * For future use. This variable would identify what site / sitegroup this
   * Task is working on.
   *
   * @var string
   */
  private $resourceId = '';

  /**
   * The UUID of the user who created this task.
   *
   * @var string
   */
  private $uuid;

  /**
   * The name of the Wip object class.
   *
   * @var string
   */
  private $className;

  /**
   * Flag whether this task is delegated to a container.
   *
   * @var bool
   */
  private $delegated = FALSE;

  /**
   * The client job ID corresponding to this task.
   *
   * @var string
   */
  private $clientJobId = '';

  /**
   * The DependencyManager instance responsible for verifying dependencies.
   *
   * @var DependencyManager
   */
  public $dependencyManager;

  /**
   * Creates a new instance of Task.
   *
   * @throws Exception\DependencyTypeException
   *   If appropriate dependencies are not provided.
   */
  public function __construct() {
    $this->dependencyManager = new DependencyManager();
    $this->dependencyManager->addDependencies($this->getDependencies());
    $this->setCreatedTimestamp(time());
  }

  /**
   * Returns the set of resources this class depends on.
   *
   * @return string[]
   *   The dependencies.
   */
  public function getDependencies() {
    return array(
      'acquia.wip.storage.wippool' => 'Acquia\Wip\Storage\WipPoolStoreInterface',
      'acquia.wip.storage.wip' => 'Acquia\Wip\Storage\WipStoreInterface',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadWipIterator() {
    if (!$this->getId()) {
      throw new NoTaskException('The Task does not yet have an ID to load a WIP object iterator.');
    }

    /** @var \Acquia\Wip\Storage\BasicWipStore $object_storage */
    $object_storage = $this->dependencyManager->getDependency('acquia.wip.storage.wip');

    $iterator = $object_storage->get($this->getId());
    if (!$iterator instanceof StateTableIteratorInterface) {
      $exception = new NoObjectException(sprintf('Unable to load WIP object iterator for ID %d', $this->getId()));
      $exception->setTaskId($this->getId());
      throw $exception;
    }
    $iterator->setId($this->getId());
    $this->iterator = $iterator;
    $wip = $iterator->getWip();
    $this->className = get_class($wip);
  }

  /**
   * {@inheritdoc}
   */
  public function getWipIterator() {
    return $this->iterator;
  }

  /**
   * {@inheritdoc}
   */
  public function setWipIterator(StateTableIteratorInterface $iterator) {
    if ($this->getId() !== $iterator->getId()) {
      throw new TaskOverwriteException('The iterator ID and task ID do not match.');
    }
    $wip = $iterator->getWip();
    if (!$wip) {
      throw new \InvalidArgumentException('The iterator must hold a Wip object.');
    }
    if (!$this->getWorkId()) {
      $this->setWorkId($wip->getWorkId());
    }
    if (!$this->getGroupName()) {
      $this->setGroupName($wip->getGroup());
    }
    if (!$this->getName()) {
      $this->setName($wip->getTitle());
    }
    $this->className = get_class($wip);
    $this->iterator = $iterator;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeout() {
    return $this->maxRuntime;
  }

  /**
   * {@inheritdoc}
   */
  public function setTimeout($timeout) {
    if (!is_int($timeout) || $timeout <= 0) {
      throw new \InvalidArgumentException(
        sprintf('The timeout argument must be a positive integer but received: "%s".', $timeout)
      );
    }

    $this->maxRuntime = $timeout;
  }

  /**
   * {@inheritdoc}
   */
  public function getStartTimestamp() {
    return $this->startTimestamp;
  }

  /**
   * {@inheritdoc}
   */
  public function setStartTimestamp($timestamp) {
    if (!is_int($timestamp) || $timestamp < 0) {
      throw new \InvalidArgumentException(
        sprintf('The start timestamp argument must be a non-negative integer but received: "%s".', $timestamp)
      );
    }
    $this->startTimestamp = $timestamp;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->runStatus;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    if (!TaskStatus::isValid($status)) {
      throw new \InvalidArgumentException(sprintf('Tried to set illegal status value of %s.', $status));
    }
    $this->runStatus = $status;
  }

  /**
   * {@inheritdoc}
   */
  public function getExitStatus() {
    return $this->exitStatus;
  }

  /**
   * {@inheritdoc}
   */
  public function setExitStatus($status) {
    if (!TaskExitStatus::isValid($status)) {
      throw new \InvalidArgumentException(sprintf('Tried to set illegal exit status value of %s.', $status));
    }
    $this->exitStatus = $status;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    if (!is_int($id) || $id <= 0) {
      throw new \InvalidArgumentException('Invalid task ID provided.');
    }
    $this->id = $id;
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkId() {
    return $this->workId;
  }

  /**
   * {@inheritdoc}
   */
  public function setWorkId($work_id) {
    if (empty($work_id)) {
      throw new \InvalidArgumentException('Invalid work ID provided.');
    }
    return $this->workId = $work_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentId() {
    return $this->parentId;
  }

  /**
   * {@inheritdoc}
   */
  public function setParentId($id) {
    if (!is_int($id) || $id < 0) {
      throw new \InvalidArgumentException('Invalid parent task ID provided.');
    }
    $this->parentId = $id;
  }

  /**
   * {@inheritdoc}
   */
  public function getPriority() {
    return $this->priority;
  }

  /**
   * {@inheritdoc}
   */
  public function setPriority($priority) {
    if (!TaskPriority::isValid($priority)) {
      throw new \InvalidArgumentException(sprintf('Tried to set illegal priority value of %s.', $priority));
    }
    $this->priority = $priority;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupName() {
    return $this->groupName;
  }

  /**
   * {@inheritdoc}
   */
  public function setGroupName($group_name) {
    if (!is_string($group_name) || !trim($group_name)) {
      throw new \InvalidArgumentException('Tried to set an empty group name.');
    }
    $this->groupName = trim($group_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    if (!is_string($name) || !trim($name)) {
      throw new \InvalidArgumentException('Tried to set an empty task name.');
    }
    $this->name = trim($name);
  }

  /**
   * {@inheritdoc}
   */
  public function getWakeTimestamp() {
    return $this->wakeTimestamp;
  }

  /**
   * {@inheritdoc}
   */
  public function setWakeTimestamp($timestamp) {
    if (!is_int($timestamp) || $timestamp < 0) {
      throw new \InvalidArgumentException(
        sprintf('The wake timestamp argument must be a non-negative integer but received: "%s".', $timestamp)
      );
    }
    $this->wakeTimestamp = $timestamp;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTimestamp() {
    return $this->createdTimestamp;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTimestamp($timestamp) {
    if (!is_int($timestamp) || $timestamp < 0) {
      throw new \InvalidArgumentException(
        sprintf('The created timestamp argument must be a non-negative integer but received: "%s".', $timestamp)
      );
    }
    $this->createdTimestamp = $timestamp;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletedTimestamp() {
    return $this->completedTimestamp;
  }

  /**
   * {@inheritdoc}
   */
  public function setCompletedTimestamp($timestamp) {
    if (!is_int($timestamp) || $timestamp < 0) {
      throw new \InvalidArgumentException(
        sprintf('The completed timestamp argument must be a non-negative integer but received: "%s".', $timestamp)
      );
    }
    $this->completedTimestamp = $timestamp;
  }

  /**
   * {@inheritdoc}
   */
  public function getClaimedTimestamp() {
    return $this->claimedTimestamp;
  }

  /**
   * {@inheritdoc}
   */
  public function setClaimedTimestamp($timestamp) {
    if (!is_int($timestamp) || $timestamp < 0) {
      throw new \InvalidArgumentException(
        sprintf('The claimed timestamp argument must be a non-negative integer but received: "%s".', $timestamp)
      );
    }
    $this->claimedTimestamp = $timestamp;
  }

  /**
   * {@inheritdoc}
   */
  public function getLeaseTime() {
    return $this->leaseTime;
  }

  /**
   * {@inheritdoc}
   */
  public function setLeaseTime($time) {
    if (!is_int($time) || $time <= 0) {
      throw new \InvalidArgumentException(
        sprintf('The lease time argument must be a positive integer but received: "%s".', $time)
      );
    }
    $this->leaseTime = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function isPaused() {
    return $this->isPaused;
  }

  /**
   * {@inheritdoc}
   */
  public function setPause($pause) {
    if (!is_bool($pause)) {
      throw new \InvalidArgumentException('The pause argument must be a boolean.');
    }
    $this->isPaused = $pause;
  }

  /**
   * {@inheritdoc}
   */
  public function getExitMessage() {
    return $this->exitMessage;
  }

  /**
   * {@inheritdoc}
   */
  public function setExitMessage($exit_message) {
    if (!is_string($exit_message)) {
      throw new \InvalidArgumentException('The exit message argument must be a string.');
    }
    $this->exitMessage = $exit_message;
  }

  /**
   * {@inheritdoc}
   */
  public function getResourceId() {
    return $this->resourceId;
  }

  /**
   * {@inheritdoc}
   */
  public function setResourceId($resource_id) {
    if (!is_string($resource_id)) {
      throw new \InvalidArgumentException('The resource id argument must be a string.');
    }
    $this->resourceId = $resource_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getUuid() {
    return $this->uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function setUuid($uuid) {
    if (empty($uuid) || !is_string($uuid)) {
      throw new \InvalidArgumentException('The uuid argument must be a string.');
    }
    $this->uuid = $uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function getWipClassName() {
    return $this->className;
  }

  /**
   * {@inheritdoc}
   */
  public function setWipClassName($class_name) {
    if (!is_string($class_name) || !trim($class_name)) {
      throw new \InvalidArgumentException('The class name argument must be a string.');
    }
    if ($this->getId() || $this->iterator) {
      throw new TaskOverwriteException(
        'The iterator already has an ID / Wip iterator assigned to it, the class name can not be changed.'
      );
    }
    $this->className = $class_name;
  }

  /**
   * {@inheritdoc}
   */
  public function isDelegated() {
    return $this->delegated;
  }

  /**
   * {@inheritdoc}
   */
  public function setDelegated($delegated) {
    if (!is_bool($delegated)) {
      throw new \InvalidArgumentException('Delegated flag must be a boolean.');
    }

    $this->delegated = $delegated;
  }

  /**
   * {@inheritdoc}
   */
  public function clearId() {
    $this->id = NULL;
  }

  /**
   * Returns whether the task has completed or not.
   *
   * The term "Completed" refers to any exit status that is not
   * TaskExitStatus::NOT_FINISHED.
   *
   * @return bool
   *   Whether the task has completed.
   */
  public function isCompleted() {
    if ($this->getExitStatus() !== TaskExitStatus::NOT_FINISHED) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setIsTerminating($terminating) {
    $this->isTerminating = $terminating;
  }

  /**
   * {@inheritdoc}
   */
  public function isTerminating() {
    return $this->isTerminating;
  }

  /**
   * {@inheritdoc}
   */
  public function setIsPrioritized($prioritized) {
    $this->isPrioritized = $prioritized;
  }

  /**
   * {@inheritdoc}
   */
  public function isPrioritized() {
    return $this->isPrioritized;
  }

  /**
   * {@inheritdoc}
   */
  public function getClientJobId() {
    return $this->clientJobId;
  }

  /**
   * {@inheritdoc}
   */
  public function setClientJobId($client_job_id) {
    $this->clientJobId = $client_job_id;
  }

}
