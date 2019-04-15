<?php

namespace Acquia\Wip\Implementation;

use Acquia\Wip\Storage\WipLogStoreInterface;
use Acquia\Wip\WipLogEntryInterface;
use Acquia\Wip\WipLogLevel;
use PDO;

/**
 * Simple log storage that is useful for debugging but not production.
 */
class SqliteWipLogStore implements WipLogStoreInterface {

  /**
   * The log filename.
   *
   * @var string
   */
  private $logFile;

  /**
   * The database connection.
   *
   * @var PDO
   */
  private $fileDb;

  /**
   * Creates a new instance of FileWipLogStore that saves to the specified file.
   *
   * @param string $log_file
   *   Optional. If not provided, a file at the top of the workspace will be
   *   used.
   */
  public function __construct($log_file = NULL) {
    if ($log_file === NULL) {
      $log_file = 'wip.sql3';
    }
    $this->logFile = $log_file;
    $this->open();
    $this->verify();
  }

  /**
   * Returns the log file path.
   *
   * @return string
   *   The log file path.
   */
  public function getLogFilePath() {
    return $this->logFile;
  }

  /**
   * {@inheritdoc}
   */
  private function verify() {
    // Create table messages.
    $wip_log_table_create = <<<EOT
CREATE TABLE IF NOT EXISTS wip_log
  (
    id INTEGER PRIMARY KEY,
    object_id INTEGER,
    level INTEGER,
    timestamp INTEGER,
    message TEXT,
    container_id TEXT,
    user_readable INTEGER
  )
EOT;
    $this->fileDb->exec($wip_log_table_create);
  }

  /**
   * Opens the database.
   */
  private function open() {
    if (NULL === $this->fileDb) {
      // Create databases and open connection.
      $db_connect = sprintf("sqlite:%s", $this->logFile);
      $this->fileDb = new PDO($db_connect);
      // Set errormode to exceptions.
      $this->fileDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(WipLogEntryInterface $log_entry) {
    $object_id = $log_entry->getObjectId();
    if ($object_id === NULL) {
      $object_id = 0;
    }
    // Prepare INSERT statement to SQLite3 file db.
    $insert = <<<EOT
INSERT INTO wip_log (
  object_id,
  level,
  timestamp,
  message,
  container_id,
  user_readable
)
VALUES (
  :object_id,
  :level,
  :timestamp,
  :message,
  :container_id,
  :user_readable
)
EOT;
    $stmt = $this->fileDb->prepare($insert);
    // Bind parameters to statement variables.
    $log_level = $log_entry->getLogLevel();
    $timestamp = $log_entry->getTimestamp();
    $message = $log_entry->getMessage();
    $container_id = $log_entry->getContainerId();
    $user_readable = intval($log_entry->getUserReadable());
    $stmt->bindParam(':object_id', $object_id);
    $stmt->bindParam(':level', $log_level);
    $stmt->bindParam(':timestamp', $timestamp);
    $stmt->bindParam(':message', $message);
    $stmt->bindParam(':container_id', $container_id);
    $stmt->bindParam(':user_readable', $user_readable);
    $stmt->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function load(
    $object_id = NULL,
    $offset = 0,
    $count = 20,
    $sort_order = 'ASC',
    $minimum_log_level = WipLogLevel::TRACE,
    $maximum_log_level = WipLogLevel::FATAL,
    $user_readable = NULL
  ) {
    if ($object_id !== NULL && !is_int($object_id)) {
      throw new \InvalidArgumentException('If provided, the object_id parameter must be an integer.');
    }
    if ($user_readable !== NULL && !is_bool($user_readable)) {
      throw new \InvalidArgumentException('If provided, the user_readable parameter must be a boolean.');
    }
    $result = array();
    $conditions = [];
    if ($object_id !== NULL) {
      $conditions[] = sprintf('object_id = %d', $object_id);
    }
    if ($user_readable !== NULL) {
      $conditions[] = sprintf('user_readable = %d', $user_readable);
    }
    $conditions[] = sprintf('level <= %d AND level >= %d', $minimum_log_level, $maximum_log_level);
    $order_by = sprintf('ORDER BY timestamp %s, id %s', $sort_order, $sort_order);
    $limit = sprintf('LIMIT %d, %d', $offset, $count);
    $query = sprintf('SELECT * FROM wip_log WHERE %s %s %s', implode(' AND ', $conditions), $order_by, $limit);
    $entries = $this->fileDb->query($query);
    foreach ($entries as $entry) {
      $result[] = $this->convertToWipLogEntry($entry);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($object_id = NULL, $prune_time = PHP_INT_MAX, $user_readable = NULL, $count = NULL) {
    if ($object_id !== NULL && !is_int($object_id)) {
      throw new \InvalidArgumentException('If provided, the object_id parameter must be an integer.');
    }
    if ($user_readable !== NULL && !is_bool($user_readable)) {
      throw new \InvalidArgumentException('If provided, the user_readable parameter must be a boolean.');
    }
    $result = [];
    $conditions = [];
    if ($object_id !== NULL) {
      $conditions[] = sprintf('object_id = %d', $object_id);
    }
    if ($user_readable !== NULL) {
      $conditions[] = sprintf('user_readable = %d', $user_readable);
    }
    $conditions[] = sprintf('timestamp <= %d', $prune_time);
    $limit_condition = '';
    if ($count !== NULL) {
      $limit_condition = sprintf('LIMIT 0, %d', $count);
    }
    // Find the entries, to return after delete.
    $find_query = sprintf('SELECT * FROM wip_log WHERE %s %s', implode(' AND ', $conditions), $limit_condition);
    $entries = $this->fileDb->query($find_query);
    foreach ($entries as $entry) {
      $result[] = $this->convertToWipLogEntry($entry);
    }
    // Delete the entries.
    $delete_query = sprintf('DELETE FROM wip_log WHERE %s %s', implode(' AND ', $conditions), $limit_condition);
    $this->fileDb->query($delete_query);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function prune(
    $object_id = NULL,
    $minimum_log_level = WipLogLevel::TRACE,
    $maximum_log_level = WipLogLevel::FATAL,
    $user_readable = NULL
  ) {
    if ($object_id !== NULL && !is_int($object_id)) {
      throw new \InvalidArgumentException('If provided, the object_id parameter must be an integer.');
    }
    if ($user_readable !== NULL && !is_bool($user_readable)) {
      throw new \InvalidArgumentException('If provided, the user_readable parameter must be a boolean.');
    }
    $result = [];
    $conditions = [];
    if ($object_id !== NULL) {
      $conditions[] = sprintf('object_id = %d', $object_id);
    }
    if ($user_readable !== NULL) {
      $conditions[] = sprintf('user_readable = %d', $user_readable);
    }
    $conditions[] = sprintf('(level > %d OR level < %d)', $minimum_log_level, $maximum_log_level);
    // Find the entries, to return after prune.
    $find_query = sprintf('SELECT * FROM wip_log WHERE %s', implode(' AND ', $conditions));
    $entries = $this->fileDb->query($find_query);
    foreach ($entries as $entry) {
      $result[] = $this->convertToWipLogEntry($entry);
    }
    // Prune the entries.
    $prune_query = sprintf('DELETE FROM wip_log WHERE %s', implode(' AND ', $conditions));
    $this->fileDb->query($prune_query);
    return $result;
  }

  /**
   * Converts the specified log entry from the db to a WipLogEntry instance.
   *
   * @param array $db_entry
   *   The entry from the database.
   *
   * @return WipLogEntry
   *   The WipLogEntry instance.
   */
  private function convertToWipLogEntry($db_entry) {
    return new WipLogEntry(
      intval($db_entry['level']),
      $db_entry['message'],
      intval($db_entry['object_id']),
      intval($db_entry['timestamp']),
      intval($db_entry['id']),
      $db_entry['container_id'],
      boolval($db_entry['user_readable'])
    );
  }

  /**
   * {@inheritdoc}
   */
  public function deleteById($log_id) {
    if (!is_int($log_id)) {
      throw new \InvalidArgumentException('The log_id parameter must be an integer.');
    }
    $result = NULL;
    // Find the log entry and save it to return.
    $find_query = <<<EOT
SELECT * FROM wip_log
WHERE id = $log_id
EOT;
    $entries = $this->fileDb->query($find_query);
    // There should be zero or one entry since IDs are unique.
    if (!empty($entries)) {
      $fetched = $entries->fetch();
      if (!empty($fetched)) {
        $result = $this->convertToWipLogEntry($fetched);
      }
    }
    // Delete the log entry.
    $delete_query = <<<EOT
DELETE FROM wip_log
WHERE id = $log_id
EOT;
    $this->fileDb->query($delete_query);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function pruneObjectsNoResults(array $object_ids, $prune_time = PHP_INT_MAX) {
    $delete_query = <<<EOT
DELETE FROM wip_log
WHERE timestamp = $prune_time
AND object_ids IN ($object_ids)
EOT;
    $this->fileDb->query($delete_query);
  }

  /**
   * {@inheritdoc}
   */
  public function cleanUp() {
    // This implementation should never be used inside a container as it does not
    // do any real log clean up and always returns TRUE.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function loadRegex(
    $object_id,
    $regex,
    $sort_order = 'ASC',
    $minimum_log_level = WipLogLevel::TRACE,
    $maximum_log_level = WipLogLevel::FATAL,
    $user_readable = NULL
  ) {
    if ($object_id !== NULL && !is_int($object_id)) {
      throw new \InvalidArgumentException('If provided, the object_id parameter must be an integer.');
    }
    if ($user_readable !== NULL && !is_bool($user_readable)) {
      throw new \InvalidArgumentException('If provided, the user_readable parameter must be a boolean.');
    }
    $result = array();
    $conditions = [];
    if ($object_id !== NULL) {
      $conditions[] = sprintf('object_id = %d', $object_id);
    }
    if ($user_readable !== NULL) {
      $conditions[] = sprintf('user_readable = %d', $user_readable);
    }
    $conditions[] = sprintf('level <= %d AND level >= %d', $minimum_log_level, $maximum_log_level);
    $order_by = sprintf('ORDER BY timestamp %s, id %s', $sort_order, $sort_order);
    $query = sprintf('SELECT * FROM wip_log WHERE %s %s', implode(' AND ', $conditions), $order_by);
    $entries = $this->fileDb->query($query);
    foreach ($entries as $entry) {
      if (1 === preg_match(sprintf('/%s/', $regex), $entry['message'], $matches)) {
        $result[] = $this->convertToWipLogEntry($entry);
      }
    }
    return $result;
  }

}
