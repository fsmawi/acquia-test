<?php

namespace Acquia\WipIntegrations\DoctrineORM\Entities;

// @codingStandardsIgnoreStart
use Acquia\Wip\Task;
use Acquia\Wip\TaskInterface;

/**
 * Defines an entity for storing Wip tasks.
 *
 * @Entity
 * @Table(name="wip_pool", options={"engine"="InnoDB"}, indexes={
 *   @Index(name="run_status_idx", columns={"run_status", "created"}),
 *   @Index(name="parent_idx", columns={"parent"}),
 *   @Index(name="work_id_idx", columns={"work_id"}),
 *   @Index(name="uuid_idx", columns={"uuid"}),
 *   @Index(name="created_group_name_idx", columns={"created", "group_name"}),
 *   @Index(name="is_terminating_idx", columns={"is_terminating"}),
 *   @Index(name="is_prioritized_idx", columns={"is_prioritized"}),
 *   @Index(name="client_job_id_idx", columns={"client_job_id"}),
 * })
 */
class WipPoolStoreEntry {

  // @codingStandardsIgnoreEnd
  /**
   * The sequential ID.
   *
   * @var int
   *
   * @Id @GeneratedValue @Column(type="integer", options={"unsigned"=true})
   */
  private $wid;

  /**
   * The work ID of the Wip task.
   *
   * Used to group tasks by namespace rules.
   *
   * @var string
   *
   * @Column(type="string", length=255, name="work_id")
   */
  private $workId;

  /**
   * The parent Wip object ID, if any.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true})
   */
  private $parent;

  /**
   * The name of the Wip task.
   *
   * @var string
   *
   * @Column(type="string", length=255)
   */
  private $name;

  /**
   * The group name of the Wip task.
   *
   * @var string
   *
   * @Column(type="string", length=255, name="group_name")
   */
  private $groupName;

  /**
   * The priority of the Wip task.
   *
   * Lower values mean higher priority.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true})
   */
  private $priority;

  /**
   * The run status of the Wip task.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true}, name="run_status")
   */
  private $runStatus;

  /**
   * The exit status of the Wip task.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true}, name="exit_status")
   */
  private $exitStatus;

  /**
   * Whether the Wip task has been marked as terminating.
   *
   * @var bool
   *
   * @Column(type="integer", options={"unsigned"=true}, name="is_terminating")
   */
  private $isTerminating;

  /**
   * Whether the Wip task has been marked as priority these jobs will be processed before terminating one.
   *
   * @var bool
   *
   * @Column(type="integer", options={"unsigned"=true}, name="is_prioritized")
   */
  private $isPrioritized;

  /**
   * The wake time of the Wip task.
   *
   * Defines the time in the future that the task should be awakened and allows
   * scheduling of tasks, long delays, etc.
   *
   * @var bool
   *
   * @Column(type="integer", options={"unsigned"=true}, name="wake_time")
   */
  private $wakeTime;

  /**
   * When the Wip task was created.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true})
   */
  private $created;

  /**
   * When the Wip task was started.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true}, name="start_time")
   */
  private $startTime;

  /**
   * When the Wip task was completed.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true})
   */
  private $completed;

  /**
   * When the Wip task was claimed for processing.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true}, name="claim_time")
   */
  private $claimTime;

  /**
   * The amount of time in seconds a Wip task may be claimed for.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true})
   */
  private $lease;

  /**
   * The amount of time in seconds a Wip task may be processed for.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true}, name="max_run_time")
   */
  private $maxRunTime;

  /**
   * Whether the task is paused.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true})
   */
  private $paused;

  /**
   * A message summarizing the result of the Wip task.
   *
   * @var string
   *
   * @Column(type="text", name="exit_message")
   */
  private $exitMessage;

  /**
   * The ID of the site or group on which the Wip task is operating.
   *
   * @var string
   *
   * @Column(type="string", length=255, name="resource_id")
   */
  private $resourceId;

  /**
   * The UUID of the user who started the task.
   *
   * @var string
   *
   * @Column(type="string", length=255)
   */
  private $uuid;

  /**
   * The class of the Wip task.
   *
   * @var string
   *
   * @Column(type="string", length=255)
   */
  private $class;

  /**
   * The ID of the corresponding client job.
   *
   * @var string
   *
   * @Column(type="string", length=255, name="client_job_id")
   */
  private $clientJobId;

  /**
   * Gets the task ID.
   *
   * @return int
   *   The task ID.
   */
  public function getWid() {
    return $this->wid;
  }

  /**
   * Gets the corresponding client job ID.
   *
   * @return string
   *   The client job ID.
   */
  public function getClientJobId() {
    return $this->clientJobId;
  }

  /**
   * Sets the corresponding client job ID.
   *
   * @param string $client_job_id
   *   The client job ID.
   */
  public function setClientJobId($client_job_id) {
    $this->clientJobId = $client_job_id;
  }

  /**
   * Sets the work ID.
   *
   * @param string $work_id
   *   The work ID.
   */
  public function setWorkId($work_id) {
    // Don't allow any tasks without a valid work ID.
    if (empty($work_id)) {
      throw new \InvalidArgumentException(sprintf(
        'Tasks must have a valid, non-empty work ID. The specified value of "%s" is not allowed.',
        $work_id
      ));
    }
    $this->workId = $work_id;
  }

  /**
   * Gets the work ID.
   *
   * @return string
   *   The work ID.
   */
  public function getWorkId() {
    return $this->workId;
  }

  /**
   * Gets the parent task ID.
   *
   * @return int
   *   The parent task ID.
   */
  public function getParent() {
    return $this->parent;
  }

  /**
   * Sets the parent task ID.
   *
   * @param int $parent
   *   The parent task ID.
   */
  public function setParent($parent) {
    $this->parent = $parent;
  }

  /**
   * Gets the name of the task.
   *
   * @return string
   *   The name of the task.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Sets the name of the task.
   *
   * @param string $name
   *   The name of the task.
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * Gets the group name of the task.
   *
   * @return string
   *   The group name of the task.
   */
  public function getGroupName() {
    return $this->groupName;
  }

  /**
   * Sets the group name of the task.
   *
   * @param string $group_name
   *   The group name of the task.
   */
  public function setGroupName($group_name) {
    $this->groupName = $group_name;
  }

  /**
   * Gets the priority of the task.
   *
   * @return int
   *   The priority of the task.
   */
  public function getPriority() {
    return $this->priority;
  }

  /**
   * Sets the priority of the task.
   *
   * @param int $priority
   *   The priority of the task.
   */
  public function setPriority($priority) {
    $this->priority = $priority;
  }

  /**
   * Gets the run status of the task.
   *
   * @return int
   *   The run status of the task.
   */
  public function getRunStatus() {
    return $this->runStatus;
  }

  /**
   * Sets the run status of the task.
   *
   * @param int $run_status
   *   The run status of the task.
   */
  public function setRunStatus($run_status) {
    $this->runStatus = $run_status;
  }

  /**
   * Gets the exit status of the task.
   *
   * @return int
   *   The exit status of the task.
   */
  public function getExitStatus() {
    return $this->exitStatus;
  }

  /**
   * Sets the exit status of the task.
   *
   * @param int $exit_status
   *   The exit status of the task.
   */
  public function setExitStatus($exit_status) {
    $this->exitStatus = $exit_status;
  }

  /**
   * Gets whether the task is terminating.
   *
   * @return bool
   *   Whether the task is terminating.
   */
  public function getIsTerminating() {
    return boolval($this->isTerminating);
  }

  /**
   * Sets whether the task is terminating.
   *
   * @param bool $is_terminating
   *   Whether the task is terminating.
   */
  public function setIsTerminating($is_terminating) {
    $this->isTerminating = intval($is_terminating);
  }

  /**
   * Gets the wake time of the task.
   *
   * @return int
   *   The wake time of the task.
   */
  public function getWakeTime() {
    return $this->wakeTime;
  }

  /**
   * Sets the wake time of the task.
   *
   * @param int $wake_time
   *   The wake time of the task.
   */
  public function setWakeTime($wake_time) {
    $this->wakeTime = $wake_time;
  }

  /**
   * Gets the created time of the task.
   *
   * @return int
   *   The created time of the task.
   */
  public function getCreated() {
    return $this->created;
  }

  /**
   * Sets the created time of the task.
   *
   * @param int $created
   *   The created time of the task.
   */
  public function setCreated($created) {
    $this->created = $created;
  }

  /**
   * Gets the start time of the task.
   *
   * @return int
   *   The start time of the task.
   */
  public function getStartTime() {
    return $this->startTime;
  }

  /**
   * Sets the start time of the task.
   *
   * @param int $start_time
   *   The start time of the task.
   */
  public function setStartTime($start_time) {
    $this->startTime = $start_time;
  }

  /**
   * Gets the completed time of the task.
   *
   * @return int
   *   The completed time of the task.
   */
  public function getCompleted() {
    return $this->completed;
  }

  /**
   * Sets the completed time of the task.
   *
   * @param int $completed
   *   The completed time of the task.
   */
  public function setCompleted($completed) {
    $this->completed = $completed;
  }

  /**
   * Gets the claimed time of the task.
   *
   * @return int
   *   The claimed time of the task.
   */
  public function getClaimTime() {
    return $this->claimTime;
  }

  /**
   * Sets the claimed time of the task.
   *
   * @param int $claim_time
   *   The claimed time of the task.
   */
  public function setClaimTime($claim_time) {
    $this->claimTime = $claim_time;
  }

  /**
   * Gets the lease time of the task.
   *
   * @return int
   *   The lease time of the task.
   */
  public function getLease() {
    return $this->lease;
  }

  /**
   * Sets the lease time of the task.
   *
   * @param int $lease
   *   The lease time of the task.
   */
  public function setLease($lease) {
    $this->lease = $lease;
  }

  /**
   * Gets the max run time of the task.
   *
   * @return int
   *   The max run time of the task.
   */
  public function getMaxRunTime() {
    return $this->maxRunTime;
  }

  /**
   * Sets the max run time of the task.
   *
   * @param int $max_run_time
   *   The max run time of the task.
   */
  public function setMaxRunTime($max_run_time) {
    $this->maxRunTime = $max_run_time;
  }

  /**
   * Gets the paused status of the task.
   *
   * @return int
   *   The paused status of the task.
   */
  public function getPaused() {
    return $this->paused;
  }

  /**
   * Sets the paused status of the task.
   *
   * @param int $paused
   *   The paused status of the task.
   */
  public function setPaused($paused) {
    $this->paused = $paused;
  }

  /**
   * Gets the exit message of the task.
   *
   * @return string
   *   The exit message of the task.
   */
  public function getExitMessage() {
    return $this->exitMessage;
  }

  /**
   * Sets the exit message of the task.
   *
   * @param string $exit_message
   *   The exit message of the task.
   */
  public function setExitMessage($exit_message) {
    $this->exitMessage = $exit_message;
  }

  /**
   * Gets the resource ID of the task.
   *
   * @return string
   *   The resource ID of the task.
   */
  public function getResourceId() {
    return $this->resourceId;
  }

  /**
   * Sets the resource ID of the task.
   *
   * @param string $resource_id
   *   The resource ID of the task.
   */
  public function setResourceId($resource_id) {
    $this->resourceId = $resource_id;
  }

  /**
   * Gets the UUID of the user who created the task.
   *
   * @return string
   *   The UUID of the user who created the task.
   */
  public function getUuid() {
    return $this->uuid;
  }

  /**
   * Sets the UUID of the user who created the task.
   *
   * @param string $uuid
   *   The UUID of the user who created the task.
   */
  public function setUuid($uuid) {
    $this->uuid = $uuid;
  }

  /**
   * Gets the class of the task.
   *
   * @return string
   *   The class of the task.
   */
  public function getClass() {
    return $this->class;
  }

  /**
   * Sets the class of the task.
   *
   * @param string $class
   *   The class of the task.
   */
  public function setClass($class) {
    $this->class = $class;
  }

  /**
   * Gets whether the task is prioritized.
   *
   * @return bool
   *   Whether the task is prioritized.
   */
  public function getIsPrioritized() {
    return boolval($this->isPrioritized);
  }

  /**
   * Sets whether the task is prioritized.
   *
   * @param bool $prioritize
   *   Whether the task is prioritized.
   */
  public function setIsPrioritized($prioritize) {
    $this->isPrioritized = intval($prioritize);
  }

  /**
   * Converts the specified entries into a Task array.
   *
   * @param WipPoolStoreEntry[] $entries
   *   The entries.
   *
   * @return TaskInterface[]
   *   The tasks.
   */
  public static function toTaskArray($entries) {
    $result = array();
    foreach ($entries as $entry) {
      $result[] = $entry->toTask();
    }
    return $result;
  }

  /**
   * Converts the specified entries into tasks.
   *
   * @param WipPoolStoreEntry[] $entries
   *   The entries.
   *
   * @return TaskInterface[]
   *   The tasks.
   */
  public static function entriesToTasks($entries) {
    $result = array();
    foreach ($entries as $entry) {
      $result[] = $entry->toTask();
    }
    return $result;
  }

  /**
   * Converts this WipPoolStoreEntry into a Task.
   *
   * @return TaskInterface
   *   The task.
   */
  public function toTask() {
    $result = new Task();
    try {
      $result->setWipClassName($this->getClass());
    } catch (\Exception $e) {
    }
    $result->setUuid($this->getUuid());
    $result->setId($this->getWid());
    $result->setWorkId($this->getWorkId());
    $result->setParentId($this->getParent());
    $result->setName($this->getName());
    $result->setGroupName($this->getGroupName());
    $result->setPriority($this->getPriority());
    $result->setIsPrioritized($this->getIsPrioritized());
    $result->setStatus($this->getRunStatus());
    $result->setIsTerminating($this->getIsTerminating());
    $result->setExitStatus($this->getExitStatus());
    $result->setWakeTimestamp($this->getWakeTime());
    $result->setCreatedTimestamp($this->getCreated());
    $result->setStartTimestamp($this->getStartTime());
    $result->setCompletedTimestamp($this->getCompleted());
    $result->setClaimedTimestamp($this->getClaimTime());
    $result->setLeaseTime($this->getLease());
    $result->setTimeout($this->getMaxRunTime());
    $paused = $this->getPaused();
    $result->setPause(!empty($paused));
    $result->setExitMessage($this->getExitMessage());
    $result->setResourceId($this->getResourceId());
    $result->setClientJobId($this->getClientJobId());
    return $result;
  }

  /**
   * Converts the specified task array to an array of WipPoolStoreEntries.
   *
   * @param TaskInterface[] $tasks
   *   The tasks.
   *
   * @return WipPoolStoreEntry[]
   *   The entries.
   */
  public static function fromTaskArray($tasks) {
    $result = array();
    foreach ($tasks as $task) {
      $entry = new WipPoolStoreEntry();
      $entry->fromTask($task);
      $result[] = $entry;
    }
    return $result;
  }

  /**
   * Initializes this instance from the specified Task.
   *
   * @param TaskInterface $task
   *   The task.
   */
  public function fromTask(TaskInterface $task) {
    $this->setUuid($task->getUuid());
    $this->wid = $task->getId();
    $this->setWorkId($task->getWorkId());
    $this->setParent($task->getParentId());
    $this->setName($task->getName());
    $this->setGroupName($task->getGroupName());
    $this->setPriority($task->getPriority());
    $this->setRunStatus($task->getStatus());
    $this->setExitStatus($task->getExitStatus());
    $this->setIsTerminating($task->isTerminating());
    $this->setIsPrioritized($task->isPrioritized());
    $this->setWakeTime($task->getWakeTimestamp());
    $this->setCreated($task->getCreatedTimestamp());
    $this->setStartTime($task->getStartTimestamp());
    $this->setCompleted($task->getCompletedTimestamp());
    $this->setClaimTime($task->getClaimedTimestamp());
    $this->setLease($task->getLeaseTime());
    $this->setMaxRunTime($task->getTimeout());
    $this->setPaused($task->isPaused());
    $this->setExitMessage($task->getExitMessage());
    $this->setResourceId($task->getResourceId());
    $this->setClass($task->getWipClassName());
    $this->setClientJobId($task->getClientJobId());
  }

}
