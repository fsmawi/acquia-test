<?php

namespace Acquia\Wip\Implementation;

use Acquia\Wip\RuntimeDataEntry;
use Acquia\Wip\Storage\RuntimeDataEntryStoreInterface;
use PDO;

/**
 * Simple log storage that is useful for debugging but not production.
 */
class SqliteDataEntryStore implements RuntimeDataEntryStoreInterface {

  /**
   * The log file.
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
   * Creates the task_runtime table if necessary.
   */
  private function verify() {
    // Create table messages.
    $task_runtime_table_create = <<<EOT
CREATE TABLE IF NOT EXISTS task_runtime
(role_name TEXT,
customer_id TEXT,
data_count INTEGER,
data_average FLOAT,
data_squared INTEGER,
PRIMARY KEY (role_name, customer_id))
EOT;
    $this->fileDb->exec($task_runtime_table_create);
  }

  /**
   * Opens the database connection.
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
  public function save($role_name, $customer_id, $run_time) {
    if (!is_string($role_name) || empty($role_name)) {
      throw new \InvalidArgumentException('The role_name parameter must be a non-empty string.');
    }
    if (!is_string($customer_id) || empty($customer_id)) {
      throw new \InvalidArgumentException('The customer_id parameter must be a non-empty string.');
    }
    if (!is_int($run_time) || $run_time < 0) {
      throw new \InvalidArgumentException('The run_time parameter must be a positive integer.');
    }
    $update = NULL;
    $select = ('SELECT COUNT(*) FROM task_runtime WHERE role_name = :role_name AND customer_id = :customer_id');
    $query = $this->fileDb->prepare($select);
    $query->bindParam(':role_name', $role_name);
    $query->bindParam(':customer_id', $customer_id);
    $query->execute();
    $result = $query->fetch();
    $count = intval($result[0]);
    if ($count > 0) {
      // Update.
      $update = <<<EOT
UPDATE task_runtime
SET data_count = data_count + 1,
  data_average = (data_average * data_count + :runtime) / (data_count + 1),
  data_squared = (data_squared + :runtime * :runtime)
WHERE role_name = :role_name AND customer_id = :customer_id
EOT;
    } else {
      // Insert.
      // Prepare INSERT statement to SQLite3 file db.
      $update = <<<EOT
INSERT INTO task_runtime
  (role_name, customer_id, data_count, data_average, data_squared)
  VALUES (:role_name, :customer_id, 1, :runtime, (:runtime * :runtime))
EOT;
    }
    $stmt = $this->fileDb->prepare($update);
    // Bind parameters to statement variables.
    $stmt->bindParam(':role_name', $role_name);
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->bindParam(':runtime', $run_time, PDO::PARAM_INT);
    $stmt->execute();
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function load($name, $customer_id) {
    if (!is_string($name) || empty($name)) {
      throw new \InvalidArgumentException('The name parameter must be a non-empty string.');
    }
    if (!is_string($customer_id) || empty($customer_id)) {
      throw new \InvalidArgumentException('The customer_id parameter must be a non-empty string.');
    }
    $result = NULL;
    $query = "SELECT * FROM task_runtime WHERE role_name = :role_name AND customer_id = :customer_id";
    $stmt = $this->fileDb->prepare($query);
    $stmt->bindParam(':role_name', $name);
    $stmt->bindParam(':customer_id', $customer_id);
    if ($stmt->execute()) {
      $entries = $stmt->fetchAll();
      foreach ($entries as $entry) {
        $result = $this->convertToRuntimeDataEntry($entry);
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($name, $customer_id = NULL) {
    if (!empty($customer_id) && !is_string($customer_id)) {
      throw new \InvalidArgumentException('If provided, the customer_id parameter must be a string.');
    }
    if (empty($customer_id)) {
      $query = "DELETE FROM task_runtime WHERE role_name = :role_name";
    } else {
      $query = "DELETE FROM task_runtime WHERE role_name = :role_name AND customer_id = :customer_id";
    }
    $stmt = $this->fileDb->prepare($query);
    $stmt->bindParam(':role_name', $name);
    if (!empty($customer_id)) {
      $stmt->bindParam(':customer_id', $customer_id);
    }
    $stmt->execute();
  }

  /**
   * Converts the specified log entry from the db to a RuntimeDataEntry instance.
   *
   * @param array $db_entry
   *   The entry from the database.
   *
   * @return RuntimeDataEntry
   *   The WipLogEntry instance.
   */
  private function convertToRuntimeDataEntry($db_entry) {
    $data_entry = new RuntimeDataEntry();
    $data_entry->initialize(
      $db_entry['role_name'],
      intval($db_entry['data_count']),
      floatval($db_entry['data_average']),
      intval($db_entry['data_squared'])
    );
    return $data_entry;
  }

}
