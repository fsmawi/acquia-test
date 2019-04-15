<?php

namespace Acquia\Wip;

use Acquia\Wip\Storage\WipLogStoreInterface;

/**
 * The WipLogInterface describes the API for performing logging within Wip.
 */
interface WipLogInterface {

  /**
   * Logs the specified message.
   *
   * @param int $level
   *   The WipLogLevel value describing the level of this log message.
   * @param string $message
   *   The message to log.
   * @param int $object_id
   *   Optional. The object ID this message is associated with. If not provided
   *   the message will be logged as a system log message.
   * @param bool $user_readable
   *   Optional. Whether this log message is user readable.
   *
   * @return bool
   *   TRUE if the log message was logged successfully; FALSE otherwise.
   */
  public function log($level, $message, $object_id = NULL, $user_readable = FALSE);

  /**
   * Log one of several log messages depending on the level.
   *
   * All multi-log messages will be logged as non-user-readable.  You do not have
   * to specify a log message for every level.  If you specify more than one,
   * all log messages configured for logging will be concatenated together in
   * order of log level.
   *
   * Example:
   * ```` php
   * multiLog($obj_id, $max_log_level,
   *   WipLogLevel::ERROR, 'An error occurred',
   *   WipLogLevel::TRACE, ' - on line 43',
   *   ...
   * );
   * ````
   * This example will log the message 'An error occurred - on line 43' at the
   * 'ERROR' log level, assuming that $max_log_level is >= WipLogLevel::ERROR.
   *
   * @param int $object_id
   *   The object ID this message is associated with. If not provided the
   *   message will be logged as a system log message.
   * @param int $level
   *   The WipLogLevel value describing the level of the associated log message.
   * @param string $message
   *   This message is paired to the preceding log level. If the max_log_level
   *   is set such that the level associated with this message will be written
   *   to the log store, this message will be logged.
   *
   * @throws \Exception
   *   If the log message could not be written.
   */
  public function multiLog($object_id, $level, $message);

  /**
   * Returns the storage layer which is used to log all messages.
   *
   * @return WipLogStoreInterface
   *   The storage layer.
   */
  public function getStore();

}
