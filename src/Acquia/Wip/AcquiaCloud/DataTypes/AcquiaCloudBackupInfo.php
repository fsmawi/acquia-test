<?php

namespace Acquia\Wip\AcquiaCloud\DataTypes;

use Acquia\Cloud\Api\Response\DatabaseBackup;
use Acquia\Wip\AcquiaCloud\AcquiaCloudDataType;

/**
 * Contains the information associated with a database backup.
 */
class AcquiaCloudBackupInfo extends AcquiaCloudDataType implements \JsonSerializable {

  /**
   * The backup ID.
   *
   * @var int
   */
  private $id;

  /**
   * The backup type.
   *
   * @var string
   */
  private $type;

  /**
   * The backup file path.
   *
   * @var string
   */
  private $path;

  /**
   * The Unix timestamp indicating when the backup was started.
   *
   * @var int
   */
  private $started;

  /**
   * The Unix timestamp indicating when the backup was completed.
   *
   * @var int
   */
  private $completed;

  /**
   * Indicates whether this backup has been deleted.
   *
   * @var bool
   */
  private $deleted;

  /**
   * The checksum of the backup.
   *
   * @var string
   */
  private $checksum;

  /**
   * The database role name associated with the backup.
   *
   * @var string
   */
  private $name;

  /**
   * The URI that can be used to download the backup.
   *
   * @var string
   */
  private $link;

  /**
   * Creates a new instance initialized from the specified backup object.
   *
   * @param DatabaseBackup $backup
   *   The database backup.
   */
  public function __construct(DatabaseBackup $backup) {
    $this->setId(intval($backup->id()));
    $this->setType($backup->type());
    $this->setPath($backup->path());
    $this->setStarted($backup->started()->getTimestamp());
    $this->setCompleted($backup->completed()->getTimestamp());
    $this->setDeleted($backup->deleted());
    $this->setChecksum($backup->checksum());
    $this->setName($backup->databaseName());
    $this->setLink($backup->link());
  }

  /**
   * Gets the database backup ID.
   *
   * @return int
   *   The ID.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Sets the database backup ID.
   *
   * @param int $id
   *   The database backup ID.
   */
  private function setId($id) {
    if (!is_int($id) || $id <= 0) {
      throw new \InvalidArgumentException('The id parameter must be a positive integer.');
    }
    $this->id = $id;
  }

  /**
   * Gets the database backup type.
   *
   * @return string
   *   The type.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Sets the database backup type.
   *
   * @param string $type
   *   The type.
   */
  private function setType($type) {
    if (!is_string($type) || empty($type)) {
      throw new \InvalidArgumentException('The type parameter must be a non-empty string.');
    }
    $this->type = $type;
  }

  /**
   * Gets the database backup path.
   *
   * @return string
   *   The path.
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * Sets the database backup path.
   *
   * @param string $path
   *   The path.
   */
  private function setPath($path) {
    if (!is_string($path) || empty($path)) {
      throw new \InvalidArgumentException('The path parameter must be a non-empty string.');
    }
    $this->path = $path;
  }

  /**
   * Gets the Unix timestamp indicating when the backup was started.
   *
   * @return int
   *   The start time.
   */
  public function getStarted() {
    return $this->started;
  }

  /**
   * Sets the Unix timestamp indicating when the backup was started.
   *
   * @param int $started
   *   The start time.
   */
  private function setStarted($started) {
    if (!is_int($started) || $started <= 0) {
      throw new \InvalidArgumentException('The started parameter must be a positive integer.');
    }
    $this->started = $started;
  }

  /**
   * Gets the Unix timestamp indicating when the backup completed.
   *
   * @return int
   *   The completion time.
   */
  public function getCompleted() {
    return $this->completed;
  }

  /**
   * Sets the Unix timestamp indicating when the backup completed.
   *
   * @param int $completed
   *   The completion time.
   */
  private function setCompleted($completed) {
    if (!is_int($completed) || $completed <= 0) {
      throw new \InvalidArgumentException('The completed parameter must be a positive integer.');
    }
    $this->completed = $completed;
  }

  /**
   * Indicates whether the backup has been deleted.
   *
   * @return bool
   *   TRUE if the backup has been deleted; FALSE otherwise.
   */
  public function isDeleted() {
    return $this->deleted;
  }

  /**
   * Set whether the backup has been deleted or not.
   *
   * @param bool $deleted
   *   TRUE indicates the backup has been deleted.
   */
  private function setDeleted($deleted) {
    if (!is_bool($deleted)) {
      throw new \InvalidArgumentException('The deleted parameter must be a boolean value.');
    }
    $this->deleted = $deleted;
  }

  /**
   * Gets the backup checksum.
   *
   * @return string
   *   The checksum.
   */
  public function getChecksum() {
    return $this->checksum;
  }

  /**
   * Sets the backup checksum.
   *
   * @param string $checksum
   *   The checksum.
   */
  private function setChecksum($checksum) {
    if (!is_string($checksum) || empty($checksum)) {
      throw new \InvalidArgumentException('The checksum parameter must be a non-empty string.');
    }
    $this->checksum = $checksum;
  }

  /**
   * Gets the database role name associated with the backup.
   *
   * @return string
   *   The database role name.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Sets the database role name associated with the backup.
   *
   * @param string $name
   *   The database role name.
   */
  private function setName($name) {
    if (!is_string($name) || empty($name)) {
      throw new \InvalidArgumentException('The name parameter must be a non-empty value.');
    }
    $this->name = $name;
  }

  /**
   * Gets the URL with which the backup can be downloaded.
   *
   * @return string
   *   The URL.
   */
  public function getLink() {
    return $this->link;
  }

  /**
   * Sets the URL where the backup can be downloaded.
   *
   * @param string $link
   *   The URL.
   */
  private function setLink($link) {
    if (!is_string($link) || empty($link)) {
      throw new \InvalidArgumentException('The link parameter must be a non-empty string.');
    }
    $this->link = $link;
  }

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize() {
    $result = array(
      'id' => $this->id,
      'type' => $this->type,
      'path' => $this->path,
      'started' => $this->started,
      'completed' => $this->completed,
      'deleted' => $this->deleted,
      'checksum' => $this->checksum,
      'name' => $this->name,
      'link' => $this->link,
    );
    return (object) $result;
  }

}
