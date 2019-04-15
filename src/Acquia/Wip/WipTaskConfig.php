<?php

namespace Acquia\Wip;

use Acquia\Wip\Objects\ParameterDocument;
use Acquia\Wip\Signal\CallbackInterface;

/**
 * The WipTaskConfig object is used for configuration of a Wip Task.
 *
 * This mechanism provides for a separation of Wip Task instantiation time and
 * Wip Task initialization, which makes it easy to establish the task
 * configuration before the Wip Task is created.
 */
class WipTaskConfig {

  /**
   * The class ID that will be used to instantiate the Wip object.
   *
   * @var string
   */
  private $classId = NULL;

  /**
   * The Wip ID of the non-container Wip object.
   *
   * @var int
   */
  private $wipId = NULL;

  /**
   * The UUID of the user to associate with the Wip object.
   *
   * @var string
   */
  private $uuid = NULL;

  /**
   * The group name of the Wip object.
   *
   * @var string
   */
  private $groupName = NULL;

  /**
   * The ParameterDocument that provides environment data.
   *
   * @var ParameterDocument
   */
  private $document = NULL;

  /**
   * The options which hold non-environment data.
   *
   * @var object
   */
  private $options = NULL;

  /**
   * The callback.
   *
   * @var CallbackInterface
   */
  private $callback = NULL;

  /**
   * The minimum log level.
   *
   * @var int
   */
  private $logLevel = WipLogLevel::ALERT;

  /**
   * The time the task was created.
   *
   * @var int
   */
  private $createdTimestamp = NULL;

  /**
   * The timestamp indicating when the WipTask was initialized.
   *
   * @var int
   */
  private $initializeTime;

  /**
   * Sets the class ID for the Wip task.
   *
   * This class ID will be used to instantiate the Wip task.
   *
   * @param string $class_id
   *   The class ID.
   */
  public function setClassId($class_id) {
    if (!is_string($class_id) || empty($class_id)) {
      throw new \InvalidArgumentException('The class_id parameter must be a non-empty string.');
    }
    $this->classId = $class_id;
  }

  /**
   * Gets the class ID.
   *
   * @return string
   *   The class ID.
   */
  public function getClassId() {
    return $this->classId;
  }

  /**
   * Sets the Wip ID.
   *
   * This refers to the original Wip ID.  In a scenario in which a Wip object
   * delegates to the container, this would be the non-container Wip ID, not the
   * one in the container.
   *
   * @param int $id
   *   The Wip ID.
   */
  public function setWipId($id) {
    if (!is_int($id) || $id <= 0) {
      throw new \InvalidArgumentException('The id parameter must be a positive integer.');
    }
    $this->wipId = $id;
  }

  /**
   * Gets the Wip ID.
   *
   * @return int
   *   The Wip ID.
   */
  public function getWipId() {
    return $this->wipId;
  }

  /**
   * Sets the UUID.
   *
   * @param string $uuid
   *   The UUID.
   */
  public function setUuid($uuid) {
    if (!is_string($uuid)) {
      throw new \InvalidArgumentException('The uuid parameter must be a string.');
    }
    $this->uuid = $uuid;
  }

  /**
   * Gets the UUID.
   *
   * @return string
   *   The UUID.
   */
  public function getUuid() {
    return $this->uuid;
  }

  /**
   * The name of the group this Wip task belongs to.
   *
   * @param string $group_name
   *   The group name.
   */
  public function setGroupName($group_name) {
    if (!is_string($group_name) || empty($group_name)) {
      throw new \InvalidArgumentException('The group_name parameter must be a non-empty string.');
    }
    $this->groupName = $group_name;
  }

  /**
   * Gets the group name associated with the task.
   *
   * @return string
   *   The group name.
   */
  public function getGroupName() {
    return $this->groupName;
  }

  /**
   * Sets the ParameterDocument which provides environment information.
   *
   * @param ParameterDocument $document
   *   The parameter document.
   */
  public function setParameterDocument(ParameterDocument $document) {
    $this->document = $document;
  }

  /**
   * Gets the ParameterDocument which provides environment information.
   *
   * @return ParameterDocument
   *   The parameter document.
   */
  public function getParameterDocument() {
    return $this->document;
  }

  /**
   * Sets options which provides all non-environment information.
   *
   * @param object $options
   *   The options.
   */
  public function setOptions($options) {
    $this->options = $options;
  }

  /**
   * Gets the Wip task options.
   *
   * @return object
   *   The options.
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * Sets the callback that will be invoked upon completion.
   *
   * @param CallbackInterface $callback
   *   The callback.
   */
  public function setCallback(CallbackInterface $callback) {
    $this->callback = $callback;
  }

  /**
   * Gets the callback.
   *
   * @return CallbackInterface
   *   The callback.
   */
  public function getCallback() {
    return $this->callback;
  }

  /**
   * Sets the log level.
   *
   * Any messages with a level higher than the specified level will be pruned
   * upon successful exit.
   *
   * @param int $log_level
   *   The log level.
   */
  public function setLogLevel($log_level) {
    if (!WipLogLevel::isValid($log_level)) {
      throw new \InvalidArgumentException(sprintf('Log level %s is not valid.', $log_level));
    }
    $this->logLevel = $log_level;
  }

  /**
   * Gets the log level.
   *
   * @return int
   *   The log level.
   */
  public function getLogLevel() {
    return $this->logLevel;
  }

  /**
   * Sets the time the Wip task was created by the user.
   *
   * @param int $created
   *   The timestamp representing the time the Wip object was created.
   */
  public function setCreatedTimestamp($created) {
    $this->createdTimestamp = $created;
  }

  /**
   * Gets the creation time of the Wip task created by the user.
   *
   * @return int
   *   The Unix timestamp indicating the creation time.
   *
   * @throws \DomainException
   *   If the creation time has not been set.
   */
  public function getCreatedTimestamp() {
    if (NULL === $this->createdTimestamp) {
      throw new \DomainException('The created timestamp has not been set.');
    }
    return $this->createdTimestamp;
  }

  /**
   * Sets the time the Wip task was initialized.
   *
   * This is helpful in determining time differences between environments (such
   * as the difference outside a container and within the container).
   *
   * @param int $time
   *   The timestamp identifying when the Wip object was initialized.
   */
  public function setInitializeTime($time) {
    $this->initializeTime = $time;
  }

  /**
   * Gets the time the Wip task was initialized.
   *
   * @return int
   *   The timestamp identifying when the Wip object was initialized.
   */
  public function getInitializeTime() {
    if (NULL === $this->initializeTime) {
      throw new \DomainException('The initialize time has not been set.');
    }
    return $this->initializeTime;
  }

}
