<?php

namespace Acquia\Wip;

/**
 * This class writes log messages to the php-errors.log file.
 */
class PhpErrorLog {

  /**
   * Logs the specified message.
   *
   * @param WipLogEntryInterface $entry
   *   The log entry.
   *
   * @return bool
   *   TRUE if the log message is logged successfully; FALSE otherwise.
   */
  public function log(WipLogEntryInterface $entry) {
    $result = FALSE;
    $log_path = $this->getLogPath();
    if (!empty($log_path)) {
      global $argv;
      $path = implode(' ', $argv);
      if (empty($path)) {
        // This was a REST API call.
        $path = "$_SERVER[REQUEST_URI]";
      }
      $entry = sprintf(
        "%d - %s; level=%d; %s; task:%d; user=%s\n",
        $entry->getTimestamp(),
        $path,
        WipLogLevel::toString($entry->getLogLevel()),
        $entry->getMessage(),
        $entry->getObjectId(),
        $entry->getUserReadable() ? 'true' : 'false'
      );
      if (!is_writable($log_path)) {
        print_r(sprintf('%s::%s %s is not a writable log file', get_class($this), __METHOD__, $log_path));
        $result = FALSE;
      } else {
        file_put_contents($log_path, $entry, FILE_APPEND);
        $result = TRUE;
      }
    }
    return $result;
  }

  /**
   * Gets the path to the log file.
   *
   * @return string
   *   The path.
   */
  private function getLogPath() {
    $log_file = dirname(getenv("ACQUIA_HOSTING_DRUPAL_LOG")) . '/php-errors.log';
    return $log_file;
  }

}
