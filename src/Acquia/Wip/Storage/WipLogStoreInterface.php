<?php

namespace Acquia\Wip\Storage;

use Acquia\Wip\WipLogEntryInterface;
use Acquia\Wip\WipLogLevel;

/**
 * The WipLogStoreInterface is used to store log messages.
 */
interface WipLogStoreInterface {

  /**
   * Saves the specified WipLogEntryInterface instance to the log.
   *
   * @param WipLogEntryInterface $log_entry
   *   The log message.
   *
   * @return bool
   *   TRUE if the entry was saved successfully; FALSE otherwise.
   */
  public function save(WipLogEntryInterface $log_entry);

  /**
   * Fetches log messages.
   *
   * Note that using NULL as the object ID indicates the object ID should not be
   * considered when performing the load operation. Using NULL will load across
   * all objects and non-object system messages. If instead the aim is to load
   * only system messages, use the object ID of 0 instead.
   *
   * @param int $object_id
   *   Optional. The ID of the object to collect log messages for. If not
   *   provided the resulting log messages will not be constrained to a single
   *   object.
   * @param int $offset
   *   Optional. The offset into the result set.
   * @param int $count
   *   Optional. The maximum number of results to return. If not provided, up
   *   to 20 messages will be returned.
   * @param string $sort_order
   *   Optional. The order of the returned results. Defaults to ascending order.
   * @param int $minimum_log_level
   *   Optional. If not provided, the resulting log messages will not be
   *   constrained by a minimum log level.
   * @param int $maximum_log_level
   *   Optional. If not provided, the resulting log messages will not be
   *   constrained by a maximum log level.
   * @param null|bool $user_readable
   *   Optional. If true, only user readable logs will be loaded. If null, the
   *   user_readable flag will be ignored.
   *
   * @return WipLogEntryInterface[]
   *   The log messages.
   */
  public function load(
    $object_id = NULL,
    $offset = 0,
    $count = 20,
    $sort_order = 'ASC',
    $minimum_log_level = WipLogLevel::TRACE,
    $maximum_log_level = WipLogLevel::FATAL,
    $user_readable = NULL
  );

  /**
   * Loads log messages that match the specified regular expression.
   *
   * @param int $object_id
   *   The ID of the object to collect log messages for. If not provided the
   *   resulting log messages will not be constrained to a single object.
   * @param string $regex
   *   The regular expression that log messages must match.
   * @param string $sort_order
   *   Optional. The order of the returned results. Defaults to ascending order.
   * @param int $minimum_log_level
   *   Optional. If not provided, the resulting log messages will not be
   *   constrained by a minimum log level.
   * @param int $maximum_log_level
   *   Optional. If not provided, the resulting log messages will not be
   *   constrained by a maximum log level.
   * @param null|bool $user_readable
   *   Optional. If true, only user readable logs will be loaded. If null, the
   *   user_readable flag will be ignored.
   *
   * @return WipLogEntryInterface[]
   *   The log messages.
   */
  public function loadRegex(
    $object_id,
    $regex,
    $sort_order = 'ASC',
    $minimum_log_level = WipLogLevel::TRACE,
    $maximum_log_level = WipLogLevel::FATAL,
    $user_readable = NULL
  );

  /**
   * Deletes log messages in the specified range of log levels.
   *
   * Note that using NULL as the object ID indicates the object ID should not be
   * considered when performing the delete operation. Using NULL will delete
   * across all objects and non-object system messages. If instead the aim is to
   * delete only system messages, use the object ID of 0 instead.
   *
   * @param int $object_id
   *   Optional. The ID of the object to delete log messages for. If not
   *   provided the resulting log messages will not be constrained to a single
   *   object. If 0 is specified, system log messages alone will be affected by
   *   the delete operation.
   * @param int $prune_time
   *   Optional. If provided, only log entries older than this timestamp will be
   *   deleted.
   * @param null|bool $user_readable
   *   Optional. If true, only user readable logs will be deleted. If null, the
   *   user_readable flag will be ignored.
   * @param int $count
   *   Optional. number of items to delete.
   *
   * @return WipLogEntryInterface[]
   *   The log messages.
   */
  public function delete($object_id = NULL, $prune_time = PHP_INT_MAX, $user_readable = NULL, $count = NULL);

  /**
   * Prunes log messages to the specified range of log levels..
   *
   * Note that using NULL as the object ID indicates the object ID should not be
   * considered when performing the prune operation. Using NULL will prune
   * across all objects and non-object system messages. If instead the aim is to
   * prune only system messages, use the object ID of 0 instead.
   *
   * @param int $object_id
   *   The ID of the object to delete log messages for. If not
   *   provided the resulting log messages will not be constrained to a single
   *   object.
   * @param int $minimum_log_level
   *   Optional. If not provided, the resulting log messages will not be
   *   constrained by a minimum log level.
   * @param int $maximum_log_level
   *   Optional. If not provided, the resulting log messages will not be
   *   constrained by a maximum log level.
   * @param null|bool $user_readable
   *   Optional. If true, only user readable logs will be pruned. If null, the
   *   user_readable flag will be ignored.
   *
   * @return WipLogEntryInterface[]
   *   The log messages.
   */
  public function prune(
    $object_id = NULL,
    $minimum_log_level = WipLogLevel::TRACE,
    $maximum_log_level = WipLogLevel::FATAL,
    $user_readable = NULL
  );

  /**
   * Prunes log messages for specific objects.
   *
   * @param int[] $object_ids
   *   List of object ids.
   * @param int $prune_time
   *   Optional. If provided, only log entries older than this timestamp will be
   *   deleted.
   */
  public function pruneObjectsNoResults(array $object_ids, $prune_time = PHP_INT_MAX);

  /**
   * Deletes log messages by their ID and returns the deleted log.
   *
   * @param int $log_id
   *   The database entry ID of the log entry.
   *
   * @return WipLogEntryInterface
   *   The deleted log message.
   */
  public function deleteById($log_id);

  /**
   * Performs any cleanup needed before the wip process exits.
   *
   * @return bool
   *   TRUE if completed successfully.
   */
  public function cleanUp();

}
