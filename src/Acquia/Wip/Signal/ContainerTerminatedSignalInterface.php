<?php

namespace Acquia\Wip\Signal;

/**
 * The interface for signals pertaining to container termination.
 */
interface ContainerTerminatedSignalInterface extends ContainerSignalInterface {

  /**
   * Gets the process ID associated with this signal.
   *
   * @return string
   *   The process ID.
   */
  public function getProcessId();

  /**
   * Sets the message that will be associated with task status.
   *
   * @param string $exit_log
   *   The message.
   */
  public function setExitLog($exit_log);

  /**
   * Gets the message that will be associated with task status.
   *
   * @return string
   *   The message.
   */
  public function getExitLog();

  /**
   * Sets the log message associated with this signal.
   *
   * @param string $log
   *   The log message.
   */
  public function setLog($log);

  /**
   * Gets the log message associated with this signal.
   *
   * @return string
   *   The log message.
   */
  public function getLog();

}
