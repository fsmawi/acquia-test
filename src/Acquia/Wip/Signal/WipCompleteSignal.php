<?php

namespace Acquia\Wip\Signal;

use Acquia\Wip\IteratorStatus;
use Acquia\Wip\WipTaskResult;

/**
 * Contains data associated with a signal indicating a wip object has completed.
 */
class WipCompleteSignal extends ProcessSignal implements CompleteSignalInterface, WipSignalInterface {

  private $state = NULL;

  /**
   * The ID of the completed Wip task.
   *
   * @var int
   */
  private $completeWipId;

  /**
   * {@inheritdoc}
   */
  public function initializeFromSignalData($signal_data) {
    parent::initializeFromSignalData($signal_data);

    if (!empty($signal_data->state)) {
      $this->state = strval($signal_data->state);
    }
    if (!empty($signal_data->completedWipId)) {
      $this->setCompletedWipId(intval($signal_data->completedWipId));
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
    $completed_wip_id = $this->getCompletedWipId();
    if (!empty($completed_wip_id)) {
      $data['completedWipId'] = $completed_wip_id;
    }
    return (object) $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return $this->state;
  }

  /**
   * Gets the ID of the Wip object that completed.
   *
   * @return int
   *   The ID of the Wip object that completed.
   */
  public function getCompletedWipId() {
    return $this->completeWipId;
  }

  /**
   * Gets the process ID associated with this signal.
   *
   * @return string
   *   The process ID.
   */
  public function getProcessId() {
    return WipTaskResult::createUniqueId($this->completeWipId);
  }

  /**
   * Sets the ID of the Wip object that has completed.
   *
   * @param int $completed_wip_id
   *   The Wip ID of the object that completed.
   *
   * @throws \InvalidArgumentException
   *   If the completed_wip_id argument is not a positive integer.
   */
  public function setCompletedWipId($completed_wip_id) {
    if (!is_int($completed_wip_id) || $completed_wip_id <= 0) {
      throw new \InvalidArgumentException('The completed_wip_id argument must be a positive integer.');
    }
    $this->completeWipId = $completed_wip_id;
  }

  /**
   * Sets the exit code for the completed Wip object.
   *
   * @param int $exit_code
   *   The Wip task exit code.
   *
   * @throws \InvalidArgumentException
   *   If the specified exit code is not a valid iterator status.
   */
  public function setExitCode($exit_code) {
    if (!IteratorStatus::isValid($exit_code)) {
      $message = sprintf('The exit_code argument "%s" is not a valid iterator status.', $exit_code);
      throw new \InvalidArgumentException($message);
    }
    parent::setExitCode($exit_code);
  }

}
