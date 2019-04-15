<?php

namespace Acquia\Wip;

/**
 * Provides an exit message and final log message.
 *
 * This will be used upon Wip object exit to log an appropriate message and set
 * the exit message.  These messages will only be used if the Wip object exits,
 * making it possible to construct error messages with useful context and have
 * those messages only be used if there is a failure.
 *
 * Generally the exit message should be a summary that indicates the type of
 * problem encountered, while the log message should be more detailed so the
 * user can understand exactly what happened.
 */
interface ExitMessageInterface {

  /**
   * Gets the exit message that will be written into the task meta-data.
   *
   * @return string
   *   The exit message.
   */
  public function getExitMessage();

  /**
   * Gets the log message that will accompany the exit message.
   *
   * @return string
   *   The log message
   */
  public function getLogMessage();

  /**
   * Gets the log level that will be used with the associated log message.
   *
   * @return WipLogLevel
   *   The log level.
   */
  public function getLogLevel();

}
