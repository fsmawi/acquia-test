<?php

namespace Acquia\WipIntegrations\DoctrineORM\Entities;

use Acquia\Wip\Implementation\WipLogEntry;
use Acquia\Wip\WipLogEntryInterface;

// @codingStandardsIgnoreStart
/**
 * Defines an entity for storing log messages.
 *
 * @Entity @Table(name="wip_log", options={"engine"="InnoDB"}, indexes={
 *   @Index(name="level_idx", columns={"level"}),
 *   @Index(name="object_id_idx", columns={"object_id"}),
 *   @Index(name="timestamp_idx", columns={"timestamp"}),
 *   @Index(name="object_id_timestamp_idx", columns={"object_id", "timestamp"})
 * })
 *
 * This class handles hydration of log records to WipLogEntry objects.
 */
class WipLogStoreEntry {

  // @codingStandardsIgnoreEnd
  /**
   * The sequential ID of the log entry.
   *
   * @var int
   *
   * @Id @GeneratedValue @Column(type="integer", options={"unsigned"=true})
   */
  private $id;

  /**
   * The Unix timestamp representing the time the message was logged.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true})
   */
  private $timestamp = NULL;

  /**
   * The log level associated with this message.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true})
   */
  private $level = NULL;

  /**
   * The message text.
   *
   * @var string
   *
   * @Column(type="text")
   */
  private $message = NULL;

  /**
   * The ID of the object this log entry is associated with.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true}, name="object_id")
   */
  private $objectId = NULL;

  /**
   * The ID of the container this log entry is associated with.
   *
   * @var string
   *
   * @Column(type="text", name="container_id")
   */
  private $containerId = NULL;

  /**
   * Indicates whether the log entry is user-readable.
   *
   * @var int
   *
   * @Column(type="integer", name="user_readable")
   */
  private $userReadable = FALSE;

  /**
   * Gets the sequential ID of the log entry.
   *
   * @return int
   *   The sequential ID.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Creates a new instance with the values from the specified WipLogEntry.
   *
   * @param WipLogEntryInterface $log_entry
   *   The source of the values.
   *
   * @return WipLogStoreEntry
   *   The WipLogStoreEntry instance populated the same as the specified
   *   log_entry.
   */
  public static function fromWipLogEntry(WipLogEntryInterface $log_entry) {
    $entry = new WipLogStoreEntry();
    $entry->id = $log_entry->getId();
    $entry->timestamp = $log_entry->getTimestamp();
    $entry->objectId = $log_entry->getObjectId();
    if (NULL === $entry->objectId) {
      // This is for all log messages not associated to a Wip object.
      $entry->objectId = 0;
    }
    $entry->level = $log_entry->getLogLevel();
    $entry->message = $log_entry->getMessage();
    $entry->containerId = $log_entry->getContainerId();
    $entry->userReadable = $log_entry->getUserReadable();

    return $entry;
  }

  /**
   * Creates a new WipLogEntry from the values in this instance.
   *
   * @return WipLogEntry
   *   The populated WipLogEntry instance.
   */
  public function toWipLogEntry() {
    return new WipLogEntry(
      $this->level,
      $this->message,
      $this->objectId,
      $this->timestamp,
      $this->id,
      $this->containerId,
      $this->userReadable
    );
  }

}
