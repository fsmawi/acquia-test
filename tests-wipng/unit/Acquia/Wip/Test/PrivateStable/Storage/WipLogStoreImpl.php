<?php

namespace Acquia\Wip\Test\PrivateStable\Storage;

use Acquia\Wip\Implementation\WipLogEntry;
use Acquia\Wip\Storage\WipLogStoreInterface;
use Acquia\Wip\WipLogEntryInterface;
use Acquia\Wip\WipLogLevel;

/**
 * Simple implementation of WipLogStoreInterface for testing purposes.
 */
class WipLogStoreImpl implements WipLogStoreInterface {

  private $log = array();

  /**
   * {@inheritdoc}
   */
  public function save(WipLogEntryInterface $log_entry) {
    $this->log[] = $log_entry;
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
    $maximum_log_message = WipLogLevel::FATAL,
    $user_readable = NULL
  ) {
    if (NULL === $count) {
      // Unlimited results.
      $count = PHP_INT_MAX;
    }
    $result = array();
    $initial_position = count($this->log) - 1;
    for ($i = $initial_position - $offset; $i >= 0 && $count > 0; $i--) {
      $entry = $this->log[$i];
      if ($entry instanceof WipLogEntry) {
        if ($object_id !== NULL && $object_id !== $entry->getObjectId()) {
          continue;
        }
        if ($user_readable !== NULL && $user_readable !== $entry->getUserReadable()) {
          continue;
        }
        if ($entry->getLogLevel() > $minimum_log_level || $entry->getLogLevel() < $maximum_log_message) {
          continue;
        }
        $result[] = $entry;
        $count--;
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(
    $object_id = NULL,
    $minimum_log_level = WipLogLevel::TRACE,
    $maximum_log_level = WipLogLevel::FATAL,
    $prune_time = PHP_INT_MAX,
    $user_readable = NULL,
    $count = NULL
  ) {
    $result = $this->load($object_id, 0, NULL, $minimum_log_level, $maximum_log_level, $user_readable);
    $this->log = array_udiff(
      $this->log,
      $result,
      'Acquia\Wip\Test\PrivateStable\Storage\WipLogStoreImpl::compareLogEntry'
    );
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
    $result = $this->load($object_id, 0, NULL, $minimum_log_level, $maximum_log_level, $user_readable);
    $this->log = array_udiff(
      $this->log,
      $result,
      'Acquia\Wip\Test\PrivateStable\Storage\WipLogStoreImpl::compareLogEntry'
    );
  }

  /**
   * {@inheritdoc}
   */
  public function pruneObjectsNoResults(array $object_ids, $prune_time = PHP_INT_MAX) {
    // Do nothing.
  }

  /**
   * Compares two log entries.
   *
   * @param WipLogEntry $a
   *   The first entry.
   * @param WipLogEntry $b
   *   The second entry.
   *
   * @return WipLogEntry int
   *   0 if a == b; -1 if a < b; 1 if a > b
   */
  public static function compareLogEntry(WipLogEntry $a, WipLogEntry $b) {
    $difference = 0;
    if ($a->getObjectId() !== $b->getObjectId()) {
      if ($a->getObjectId() === NULL) {
        $difference = -1;
      } elseif ($b->getLogLevel() === NULL) {
        $difference = 1;
      } else {
        $difference = $a - $b;
      }
    }
    if (!$difference) {
      $difference = $a->getLogLevel() - $b->getLogLevel();
    }
    if (!$difference) {
      $difference = $a->getMessage() - $b->getMessage();
    }
    return $difference;
  }

  /**
   * Deletes log messages by their ID and returns the deleted log.
   *
   * Completely mocked out in this implementation for testing.
   *
   * @param int $log_id
   *   The database entry ID of the log entry.
   *
   * @return WipLogEntryInterface
   *   The deleted log message.
   */
  public function deleteById($log_id) {
    $entry_to_return = NULL;
    foreach ($this->log as $key => $entry) {
      if ($entry->getId() === $log_id) {
        $entry_to_return = $entry;
        unset($this->log[$key]);
        break;
      }
    }

    return $entry_to_return;
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
    $result = array();
    $initial_position = count($this->log) - 1;
    for ($i = $initial_position; $i >= 0; $i--) {
      $entry = $this->log[$i];
      if ($entry instanceof WipLogEntry) {
        if ($object_id !== NULL && $object_id !== $entry->getObjectId()) {
          continue;
        }
        if ($user_readable !== NULL && $user_readable !== $entry->getUserReadable()) {
          continue;
        }
        if ($entry->getLogLevel() > $minimum_log_level || $entry->getLogLevel() < $maximum_log_level) {
          continue;
        }
        if (preg_match(sprintf('/%s/', $regex), $entry->getMessage(), $matches) === 1) {
          $result[] = $entry;
        }
      }
    }
    return $result;
  }

}
