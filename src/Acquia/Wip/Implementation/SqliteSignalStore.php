<?php

namespace Acquia\Wip\Implementation;

use Acquia\Wip\Signal\SignalInterface;
use Acquia\Wip\Storage\SignalStoreInterface;
use PDO;

/**
 * Simple implementation of the signal store for unit testing.
 */
class SqliteSignalStore implements SignalStoreInterface {

  /**
   * The database connection.
   *
   * @var PDO
   */
  private $database;

  /**
   * Creates a new instance of SqliteSignalStore.
   *
   * @param string $log_file
   *   Optional. If not provided, a file at the top of the workspace will be
   *   used.
   */
  public function __construct($log_file = NULL) {
    $this->logFile = $log_file !== NULL ? $log_file : 'wip.sql3';
    $this->open();
    $this->verify();
  }

  /**
   * Creates the DB table if needed.
   */
  private function verify() {
    $table_create = <<<EOT
CREATE TABLE IF NOT EXISTS wip_signal (
  id INTEGER PRIMARY KEY,
  object_id INTEGER,
  type INTEGER,
  sent INTEGER,
  consumed INTEGER,
  data TEXT
)
EOT;
    $this->database->exec($table_create);
  }

  /**
   * Opens the database connection.
   */
  private function open() {
    if ($this->database === NULL) {
      $database_name = sprintf('sqlite:%s', $this->logFile);
      $this->database = new PDO($database_name);
      // Set errormode to exceptions.
      $this->database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function send(SignalInterface $signal) {
    $object_id = $signal->getObjectId();
    if ($object_id === NULL) {
      $object_id = 0;
    }
    // The signal must have a sent time.
    if ($signal->getSentTime() === NULL) {
      $signal->setSentTime(time());
    }
    $insert = <<<EOT
INSERT INTO wip_signal (object_id, type, sent, consumed, data)
  VALUES (:object_id, :type, :sent, 0, :data)
EOT;
    $statement = $this->database->prepare($insert);
    $type = $signal->getType();
    $sent_time = $signal->getSentTime();
    $serialized_signal = serialize($signal);
    $statement->bindParam(':object_id', $object_id);
    $statement->bindParam(':type', $type);
    $statement->bindParam(':sent', $sent_time);
    $statement->bindParam(':data', $serialized_signal);
    $statement->execute();
    $signal->setId(intval($this->database->lastInsertId()));
  }

  /**
   * Flags the signal as consumed.
   *
   * @param SignalInterface $signal
   *   The signal to mark as consumed.
   */
  public function consume(SignalInterface $signal) {
    $id = $signal->getId();
    $signal->setConsumedTime(time());
    if ($id !== NULL && is_int($id)) {
      $query = <<<EOT
UPDATE wip_signal
  SET consumed = :consumed WHERE id = :id
EOT;
      $statement = $this->database->prepare($query);
      $consumed_time = $signal->getConsumedTime();
      $statement->bindParam(':id', $id);
      $statement->bindParam(':consumed', $consumed_time);
      $statement->execute();
    }
  }

  /**
   * Deletes the specified signal.
   *
   * @param SignalInterface $signal
   *   The signal to delete.
   */
  public function delete(SignalInterface $signal) {
    $id = $signal->getId();
    if ($id !== NULL && is_int($id)) {
      $query = <<<EOT
DELETE FROM wip_signal
  WHERE id = :id
EOT;
      $statement = $this->database->prepare($query);
      $statement->bindParam(':id', $id);
      $statement->execute();
      $this->database->query($query);
    }
  }

  /**
   * Loads the signal with the specified signal ID.
   *
   * @param int $signal_id
   *   The signal ID.
   *
   * @return SignalInterface
   *   The signal.
   */
  public function load($signal_id) {
    $result = NULL;
    if (!is_int($signal_id) || $signal_id <= 0) {
      throw new \InvalidArgumentException('The signal_id parameter must be a positive integer.');
    }
    $query = <<<EOT
SELECT * FROM wip_signal WHERE id = :id
EOT;
    $statement = $this->database->prepare($query);
    $statement->bindParam(':id', $signal_id);
    $statement->execute();
    $results = $statement->fetchAll(PDO::FETCH_OBJ);
    if (count($results) !== 0) {
      $result = $this->convertToSignal($results[0]);
    }
    return $result;
  }

  /**
   * Gets all signals associated with the specified object.
   *
   * @param int $object_id
   *   The object ID associated with the desired signals.
   *
   * @return SignalInterface[]
   *   The set of Signal instances associated with the specified object ID.
   */
  public function loadAll($object_id) {
    $results = array();
    if (!is_int($object_id) || $object_id <= 0) {
      throw new \InvalidArgumentException('The object_id parameter must be a positive integer.');
    }
    $query = <<<EOT
SELECT * FROM wip_signal WHERE object_id = :object_id
EOT;
    $statement = $this->database->prepare($query);
    $statement->bindParam(':object_id', $object_id);
    $statement->execute();
    $query_results = $statement->fetchAll(PDO::FETCH_OBJ);
    foreach ($query_results as $result) {
      $results[] = $this->convertToSignal($result);
    }
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getUuids(array $object_ids) {
    $query = <<<EOT
SELECT uuid FROM wip_signal WHERE object_id IN ($object_ids)
EOT;
    return $this->fileDb->query($query);
  }

  /**
   * {@inheritdoc}
   */
  public function pruneObjects(array $object_ids) {
    $query = <<<EOT
DELETE FROM wip_signal WHERE object_id IN ($object_ids)
EOT;
    $this->fileDb->query($query);
  }

  /**
   * Gets all signals that have not yet been consumed.
   *
   * @param int $object_id
   *   The object ID associated with the desired signals.
   *
   * @return SignalInterface[]
   *   The set of Signal instances associated with the specified object that
   *   have not been consumed.
   */
  public function loadAllActive($object_id) {
    $results = array();
    if ($object_id !== NULL) {
      $query = <<<EOT
SELECT * FROM wip_signal WHERE object_id = :object_id AND consumed = 0
EOT;
      $statement = $this->database->prepare($query);
      $statement->bindParam(':object_id', $object_id);
      $statement->execute();
      $query_results = $statement->fetchAll(PDO::FETCH_OBJ);
      foreach ($query_results as $result) {
        $results[] = $this->convertToSignal($result);
      }
    }
    return $results;
  }

  /**
   * Converts from a database query result into a signal.
   *
   * @param object $object
   *   The object from the database query.
   *
   * @return SignalInterface
   *   The signal.
   */
  private function convertToSignal($object) {
    $signal = unserialize($object->data);
    $signal->setId(intval($object->id));
    $signal->setObjectId(intval($object->object_id));
    $signal->setType(intval($object->type));
    $signal->setSentTime(intval($object->sent));
    $signal->setConsumedTime(intval($object->consumed));
    return $signal;
  }

}
