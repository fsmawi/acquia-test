<?php

namespace Acquia\Wip;

/**
 * The interface for all log entry classes.
 */
interface WipLogEntryInterface extends \JsonSerializable {

  /**
   * Returns the storage ID of this log entry.
   *
   * @return int
   *   The ID.
   */
  public function getId();

  /**
   * Returns the timestamp when the log message was stored.
   *
   * @return int
   *   The Unix timestamp.
   */
  public function getTimestamp();

  /**
   * Returns the log level associated with this log entry.
   *
   * @return int
   *   The log level.
   */
  public function getLogLevel();

  /**
   * Returns the log message associated with this log entry.
   *
   * @return string
   *   The log message.
   */
  public function getMessage();

  /**
   * Gets the ID of the object this log entry is associated with.
   *
   * @return int
   *   The object ID associated with this log entry.
   */
  public function getObjectId();

  /**
   * Gets the container ID this log entry is associated with.
   *
   * @return int
   *   The container ID associated with this log entry.
   */
  public function getContainerId();

  /**
   * Returns whether the log is user readable.
   *
   * @return bool
   *   The boolean indicating whether the log is user-readable.
   */
  public function getUserReadable();

  /**
   * Serializes the object to JSON.
   *
   * @return array
   *   The serializable array representing the object.
   */
  public function jsonSerialize();

  /**
   * A message to inform users that the output has been suppressed.
   */
  const OUTPUT_SUPPRESSED_MESSAGE = '[Output Suppressed]';
}
