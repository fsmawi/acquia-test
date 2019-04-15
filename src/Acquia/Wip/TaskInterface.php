<?php

namespace Acquia\Wip;

/**
 * Interface for an object representing a WIP task and attached metadata.
 *
 * A Task should be thought of by example as representing a row in the WipPool
 * table.  It is able to obtain the WIP object itself, but the Task is not that
 * object.  The main purpose of the Task is to contain metadata from the WipPool
 * and to be able to instantiate a concrete WIP object from that data.
 */
interface TaskInterface {

  /**
   * Gets the actual WIP object associated with the WIP task.
   *
   * @return StateTableIteratorInterface
   *   The iterator.
   */
  public function getWipIterator();

  /**
   * Loads Wip iterator for a Task from storage.
   */
  public function loadWipIterator();

  /**
   * Sets the Wip iterator for a new Task.
   *
   * @param StateTableIteratorInterface $iterator
   *   The iterator.
   *
   * @throws \Acquia\Wip\Exception\TaskOverwriteException
   *   If the task already has an ID or a Wip iterator assigned.
   * @throws \InvalidArgumentException
   *   If the Wip iterator does not hold a Wip.
   */
  public function setWipIterator(StateTableIteratorInterface $iterator);

  /**
   * Returns the maximum number of seconds to spend on this task in one run.
   *
   * @return int
   *   The timeout.
   */
  public function getTimeout();

  /**
   * Sets the maximum number of seconds to spend on this task in one run.
   *
   * @param int $timeout
   *   The maximum number of seconds.
   *
   * @throws \InvalidArgumentException
   *   If the argument is not a positive integer.
   */
  public function setTimeout($timeout);

  /**
   * Returns the Unix timestamp corresponding to the time this task was started.
   *
   * @return int
   *   The Unix timestamp.
   */
  public function getStartTimestamp();

  /**
   * Sets the Unix timestamp corresponding to the time this task was started.
   *
   * @param int $timestamp
   *   The Unix timestamp.
   *
   * @throws \InvalidArgumentException
   *   If the argument is not a non-negative integer.
   */
  public function setStartTimestamp($timestamp);

  /**
   * Gets the current status of this Task instance.
   *
   * The status indicates which run state the task is currently in.  Can
   * be one of:
   * ```` php
   * TaskStatus::NOT_STARTED
   * TaskStatus::WAITING
   * TaskStatus::IN_PROGRESS
   * TaskStatus::COMPLETED
   * TaskStatus::RESTARTED
   * ````
   * The default run status is TaskStatus::NOT_STARTED.
   *
   * @return int
   *   The current task status.
   *
   * @throws \RuntimeException
   *   If the current status is not a legal value.
   */
  public function getStatus();

  /**
   * Sets the current status of this Task instance.
   *
   * @param int $status
   *   The status.
   */
  public function setStatus($status);

  /**
   * Gets the exit status of this Task instance.
   *
   * The status indicates which exit state the task is currently in.  Can
   * be one of:
   * ```` php
   * TaskExitStatus::NOT_FINISHED
   * TaskExitStatus::WARNING
   * TaskExitStatus::ERROR
   * TaskExitStatus::TERMINATED
   * TaskExitStatus::COMPLETED
   * ````
   * The default exit status is TaskExitStatus::NOT_FINISHED.
   *
   * @return int
   *   The task exit status.
   */
  public function getExitStatus();

  /**
   * Sets the exit status of this Task instance.
   *
   * @param int $status
   *   The status.
   *
   * @throws \InvalidArgumentException
   *   If the specified exit status in not a legal value.
   */
  public function setExitStatus($status);

  /**
   * Gets the ID this Task instance.
   *
   * @return int
   *   The task's ID.
   */
  public function getId();

  /**
   * Sets the ID of this Task instance.
   *
   * @param int $id
   *   The Task's ID.
   *
   * @throws \InvalidArgumentException
   *   If the argument is not a positive integer.
   */
  public function setId($id);

  /**
   * Gets the Task's Work ID.
   *
   * @return string
   *   The work ID.
   */
  public function getWorkId();

  /**
   * Sets the Task's Work ID.
   *
   * @param string $work_id
   *   The Task's Work ID.
   */
  public function setWorkId($work_id);

  /**
   * Gets the parent Task's ID of this Task instance.
   *
   * @return int
   *   The parent task's ID.
   */
  public function getParentId();

  /**
   * Sets the parent Task ID of this Task instance.
   *
   * @param int $id
   *   The Task's parent Task ID.
   *
   * @throws \InvalidArgumentException
   *   If the argument is not a non-negative integer.
   */
  public function setParentId($id);

  /**
   * Gets the priority of this Task instance.
   *
   * The priority determines which Tasks are going to be processed sooner. Can
   * be one of:
   * ```` php
   * TaskPriority::CRITICAL
   * TaskPriority::HIGH
   * TaskPriority::MEDIUM
   * TaskPriority::LOW
   * ````
   * The Task's default priority is TaskPriority::MEDIUM.
   *
   * @return int
   *   The priority value.
   */
  public function getPriority();

  /**
   * Sets the task priority.
   *
   * @param int $priority
   *   The priority.
   *
   * @throws \InvalidArgumentException
   *   If the argument is not a valid TaskPriority value.
   */
  public function setPriority($priority);

  /**
   * Gets the group name of this Task.
   *
   * The group name by default is the class name of the Wip that this Task
   * handles.
   *
   * @return string
   *   The group name.
   */
  public function getGroupName();

  /**
   * Sets the group name for this Task.
   *
   * @param string $group_name
   *   The group name.
   *
   * @throws \InvalidArgumentException
   *   If the argument is not a string or is empty.
   */
  public function setGroupName($group_name);

  /**
   * Gets the name of this Task.
   *
   * The name by default will be the same as the Wip's title.
   *
   * @return string
   *   The Task's name.
   */
  public function getName();

  /**
   * Sets the name for this Task.
   *
   * @param string $name
   *   The Task's name.
   *
   * @throws \InvalidArgumentException
   *   If the argument is not a string or is empty.
   */
  public function setName($name);

  /**
   * Gets the timestamp when this Task should be checked again.
   *
   * @return int
   *   The wake timestamp.
   */
  public function getWakeTimestamp();

  /**
   * Sets the timestamp when this Task should be checked again.
   *
   * @param int $timestamp
   *   The wake timestamp.
   *
   * @throws \InvalidArgumentException
   *   If the argument is not a non-negative integer.
   */
  public function setWakeTimestamp($timestamp);

  /**
   * Gets the UNIX timestamp when this Task was created.
   *
   * @return int
   *   The creation timestamp.
   */
  public function getCreatedTimestamp();

  /**
   * Sets the timestamp when this Task was created.
   *
   * @param int $timestamp
   *   The creation timestamp.
   *
   * @throws \InvalidArgumentException
   *   If the argument is not a non-negative integer.
   */
  public function setCreatedTimestamp($timestamp);

  /**
   * Gets the timestamp when this Task was completed.
   *
   * @return int
   *   The completion timestamp.
   */
  public function getCompletedTimestamp();

  /**
   * Sets the timestamp when this Task was completed.
   *
   * @param int $timestamp
   *   The completion timestamp.
   *
   * @throws \InvalidArgumentException
   *   If the argument is not a non-negative integer.
   */
  public function setCompletedTimestamp($timestamp);

  /**
   * Gets the timestamp when this Task was claimed.
   *
   * @return int
   *   The claim timestamp.
   */
  public function getClaimedTimestamp();

  /**
   * Sets the timestamp when this Task was claimed.
   *
   * @param int $timestamp
   *   The claim timestamp.
   *
   * @throws \InvalidArgumentException
   *   If the argument is not a non-negative integer.
   */
  public function setClaimedTimestamp($timestamp);

  /**
   * Gets the amount of time this Task may be claimed for.
   *
   * @return int
   *   The lease time.
   */
  public function getLeaseTime();

  /**
   * Sets the amount of time this Task may be claimed for.
   *
   * @param int $time
   *   The lease times.
   *
   * @throws \InvalidArgumentException
   *   If the argument is not a positive integer.
   */
  public function setLeaseTime($time);

  /**
   * Returns if the Task has been paused.
   *
   * @return bool
   *   TRUE if the Task is paused.
   */
  public function isPaused();

  /**
   * Marks the Task paused.
   *
   * @param bool $pause
   *   TRUE if the Task is paused.
   *
   * @throws \InvalidArgumentException
   *   If the argument is not a boolean.
   */
  public function setPause($pause);

  /**
   * Gets the exit message.
   *
   * @return string
   *   The exit message.
   */
  public function getExitMessage();

  /**
   * Sets the exit message.
   *
   * @param string $exit_message
   *   The exit message.
   *
   * @throws \InvalidArgumentException
   *   If the argument is not a string.
   */
  public function setExitMessage($exit_message);

  /**
   * Gets the id of resource the Task is meant for.
   *
   * @return string
   *   The resource ID.
   */
  public function getResourceId();

  /**
   * Sets the id of resource the Task is meant for.
   *
   * @param string $resource_id
   *   The resource ID.
   *
   * @throws \InvalidArgumentException
   *   If the argument is not a string.
   */
  public function setResourceId($resource_id);

  /**
   * Gets the UUID of the user who started the task.
   *
   * @return string
   *   The user UUID.
   */
  public function getUuid();

  /**
   * Sets the UUID of the user who started the task.
   *
   * @param string $uuid
   *   The user UUID.
   *
   * @throws \InvalidArgumentException
   *   If the argument is not a string.
   */
  public function setUuid($uuid);

  /**
   * Gets the Wip's class associated with this Task.
   *
   * @return string
   *   The Wip's class name.
   */
  public function getWipClassName();

  /**
   * Sets the Wip's class associated with this Task.
   *
   * @param string $class_name
   *   The Wip's class name.
   *
   * @throws \Acquia\Wip\Exception\TaskOverwriteException
   *   If the task already has an ID or a Wip iterator assigned.
   */
  public function setWipClassName($class_name);

  /**
   * Determines whether a WIP task is flagged as delegated (to a container).
   *
   * @return bool
   *   TRUE if the task has been delegated, otherwise FALSE.
   */
  public function isDelegated();

  /**
   * Sets the delegated flag on a WIP task.
   *
   * @param bool $status
   *   Whether or not the Task should be considered delegated.
   */
  public function setDelegated($status);

  /**
   * Removes the instance's ID.
   *
   * This can be useful in 2 cases:
   *   1. To create a clone of a WIP task (because clearing the ID allows it to
   *      be inserted into the database twice).
   *   2. To remove the ID from a task prior to delegating the task to another
   *      instance of WIP (eg a containerized WIP).
   */
  public function clearId();

  /**
   * Gets whether a task has been marked as terminating.
   *
   * @return bool
   *   Has the task been marked as terminating.
   */
  public function isTerminating();

  /**
   * Flags that a task should be terminated.
   *
   * @param bool $terminating
   *   Whether the task should be marked as terminating.
   */
  public function setIsTerminating($terminating);

  /**
   * Gets whether a task has been marked as prioritized.
   *
   * @return bool
   *   Has the task been marked as prioritized.
   */
  public function isPrioritized();

  /**
   * Flags that a task should be prioritized.
   *
   * @param bool $prioritized
   *   Whether the task should be marked as prioritized.
   */
  public function setIsPrioritized($prioritized);

  /**
   * Gets the client job ID corresponding to this task.
   *
   * @return string
   *   The job id assigned by a client of wip-service.
   */
  public function getClientJobId();

  /**
   * Sets the client job ID corresponding to this task.
   *
   * @param string $client_job_id
   *   The client job ID corresponding to this task.
   */
  public function setClientJobId($client_job_id);

}
