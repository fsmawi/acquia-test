<?php

namespace Acquia\Wip\Signal;

use Acquia\Wip\Ssh\SshResult;

/**
 * Missing summary.
 */
class SshCompleteSignal extends ProcessSignal implements CompleteSignalInterface, SshSignalInterface {

  private $state = NULL;
  private $server;

  /**
   * {@inheritdoc}
   */
  public function initializeFromSignalData($signal_data) {
    parent::initializeFromSignalData($signal_data);

    if (!empty($signal_data->state)) {
      $this->state = strval($signal_data->state);
    }
    if (!empty($signal_data->server)) {
      $this->server = strval($signal_data->server);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function convertFieldsToObject() {
    $data = get_object_vars(parent::convertFieldsToObject());
    $state = $this->getState();
    if (!empty($state)) {
      $data['state'] = $state;
    }
    return (object) $data;
  }

  /**
   * {@inheritdoc}
   */
  public function setPid($pid) {
    if (empty($pid) || !is_numeric($pid) || $pid < 0) {
      throw new \InvalidArgumentException('The pid parameter must be a positive integer.');
    }
    parent::setPid($pid);
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return $this->state;
  }

  /**
   * Gets the process ID associated with this signal.
   *
   * @return string
   *   The process ID.
   */
  public function getProcessId() {
    return SshResult::createUniqueId($this->server, $this->getPid(), $this->getStartTime());
  }

}
