<?php

namespace Acquia\Wip\Signal;

use Acquia\Wip\Container\ContainerResult;

/**
 * Indicates a WIP object that was delegated to a container has completed.
 */
class ContainerCompleteSignal extends ProcessSignal implements CompleteSignalInterface, ContainerSignalInterface {

  /**
   * The message that will be associated with the task status.
   *
   * @var string
   */
  private $exitLog = '';

  /**
   * The final log message written into the log.
   *
   * @var string
   */
  private $log = '';

  /**
   * Gets the process ID associated with this signal.
   *
   * @return string
   *   The process ID.
   */
  public function getProcessId() {
    return ContainerResult::createUniqueId($this->getPid(), $this->getStartTime());
  }

  /**
   * Sets the message that will be associated with task status.
   *
   * @param string $exit_log
   *   The message.
   */
  public function setExitLog($exit_log) {
    if (!is_string($exit_log)) {
      throw new \InvalidArgumentException('The "exit_log" parameter must be a string.');
    }
    $this->exitLog = $exit_log;
  }

  /**
   * Gets the message that will be associated with task status.
   *
   * @return string
   *   The message.
   */
  public function getExitLog() {
    return $this->exitLog;
  }

  /**
   * Sets the log message associated with this signal.
   *
   * @param string $log
   *   The log message.
   */
  public function setLog($log) {
    if (!is_string($log)) {
      throw new \InvalidArgumentException('The "log" parameter must be a string.');
    }
    $this->log = $log;
  }

  /**
   * Gets the log message associated with this signal.
   *
   * @return string
   *   The log message.
   */
  public function getLog() {
    return $this->log;
  }

  /**
   * {@inheritdoc}
   */
  public function initializeFromSignalData($signal_data) {
    parent::initializeFromSignalData($signal_data);
    if (isset($signal_data->exitLog)) {
      $this->setExitLog(strval($signal_data->exitLog));
    }
    if (isset($signal_data->log)) {
      $this->setLog($signal_data->log);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function convertFieldsToObject() {
    $data = get_object_vars(parent::convertFieldsToObject());
    $exit_log = $this->getExitLog();
    if (!empty($exit_log)) {
      $data['exitLog'] = $exit_log;
    }
    $log = $this->getLog();
    if (!empty($log)) {
      $data['log'] = $log;
    }
    return (object) $data;
  }

}
