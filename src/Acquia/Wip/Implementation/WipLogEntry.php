<?php

namespace Acquia\Wip\Implementation;

use Acquia\Wip\WipLogEntryInterface;
use Acquia\Wip\WipLogLevel;

/**
 * The WipLogEntry embodies a single log message with associated attributes.
 */
class WipLogEntry implements WipLogEntryInterface {

  /**
   * The ID of this log entry.
   *
   * @var int
   */

  private $id = NULL;

  /**
   * The Unix timestamp representing the time the message was logged.
   *
   * @var int
   */
  private $timestamp = NULL;

  /**
   * The log level associated with this message.
   *
   * @var int
   */
  private $level = NULL;

  /**
   * The message text.
   *
   * @var string
   */
  private $message = NULL;

  /**
   * The ID of the object this log entry is associated with.
   *
   * @var int
   */
  private $objectId = NULL;

  /**
   * The ID of the container this log entry is associated with.
   *
   * @var int
   */
  private $containerId = NULL;

  /**
   * The boolean indicating whether this log entry is user readable.
   *
   * @var bool
   */
  private $userReadable = FALSE;

  /**
   * Creates a new WipLogEntry instance with the specified level and message.
   *
   * @param int $level
   *   The log level.  Must be a valid level from WipLogLevel.
   * @param string $message
   *   The message to log.
   * @param int $object_id
   *   Optional. The object ID associated with this message.
   * @param int $timestamp
   *   Optional. The timestamp when this entry was logged. If not specified, the
   *   current time will be used.
   * @param int $id
   *   Optional. The log entry id, representing the entry in the storage
   *   mechanism. The ID will be assigned when this LogEntry is stored.
   * @param string $container_id
   *   Optional. The container ID this message is associated with.
   * @param bool $user_readable
   *   Optional. The boolean indicating whether this log entry is user readable.
   */
  public function __construct(
    $level,
    $message,
    $object_id = NULL,
    $timestamp = NULL,
    $id = NULL,
    $container_id = '0',
    $user_readable = FALSE
  ) {
    if (!WipLogLevel::isValid($level)) {
      throw new \InvalidArgumentException('The "level" argument must be a valid WipLogLevel value.');
    }
    if (!is_string($message) || empty($message)) {
      throw new \InvalidArgumentException('The message argument must be a non-empty string.');
    }
    if ($object_id !== NULL && (!is_int($object_id) || $object_id < 0)) {
      throw new \InvalidArgumentException('The object_id argument must be a positive integer.');
    }
    if ($timestamp !== NULL && (!is_int($timestamp) || $timestamp <= 0)) {
      throw new \InvalidArgumentException('The timestamp argument must be a positive integer.');
    }
    if ($id !== NULL && !is_int($id)) {
      throw new \InvalidArgumentException('The id argument must be an integer.');
    }
    if ($container_id == NULL || !is_string($container_id)) {
      throw new \InvalidArgumentException('The container_id argument must be a string.');
    }
    if (!is_bool($user_readable) && !($user_readable === 1 || $user_readable === 0)) {
      throw new \InvalidArgumentException('The user_readable argument must be a boolean.');
    }
    $this->level = $level;
    $this->message = $message;
    $this->objectId = $object_id;
    if ($timestamp === NULL) {
      $timestamp = time();
    }
    $this->timestamp = $timestamp;
    $this->id = $id;
    $this->containerId = $container_id;
    $this->userReadable = $user_readable;
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
  public function getTimestamp() {
    return $this->timestamp;
  }

  /**
   * {@inheritdoc}
   */
  public function getLogLevel() {
    return $this->level;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    return $this->message;
  }

  /**
   * {@inheritdoc}
   */
  public function getObjectId() {
    return $this->objectId;
  }

  /**
   * Sets the object ID associated with this log entry.
   *
   * @param int $object_id
   *   The object ID.
   */
  public function setObjectId($object_id) {
    if ($object_id == NULL || (!is_int($object_id) || $object_id < 0)) {
      throw new \InvalidArgumentException('The object_id argument must be a positive integer.');
    }
    $this->objectId = $object_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getContainerId() {
    return $this->containerId;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserReadable() {
    return $this->userReadable;
  }

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize() {
    $result = array();
    $result['level'] = $this->level;
    $result['message'] = $this->message;
    $result['object_id'] = $this->objectId;
    $result['timestamp'] = $this->timestamp;
    $result['id'] = $this->id;
    $result['container_id'] = $this->containerId;
    $result['user_readable'] = $this->userReadable;
    return $result;
  }

}
