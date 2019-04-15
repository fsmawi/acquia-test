<?php

namespace Acquia\Wip\Signal;

use Acquia\Wip\Timer;

/**
 * The ProcessSignal is associated with asynchronous processes.
 */
class ProcessSignal extends Signal implements ProcessSignalInterface {

  /**
   * The process start time.
   *
   * @var int
   */
  private $startTime = NULL;

  /**
   * The process end time.
   *
   * @var int
   */
  private $endTime = NULL;

  /**
   * The process ID.
   *
   * @var int
   */
  private $pid;

  /**
   * The process exit code.
   *
   * @var int
   */
  private $exitCode;

  /**
   * The exit message.
   *
   * @var string
   */
  private $exitMessage;

  /**
   * Timer data from the process.
   *
   * @var Timer
   */
  private $timer;

  /**
   * {@inheritdoc}
   */
  public function getStartTime() {
    return $this->startTime;
  }

  /**
   * Sets the process start time.
   *
   * @param int $start_time
   *   The Unix timestamp representing the process start time.
   *
   * @throws \InvalidArgumentException
   *   If the $start_time argument is not a positive integer.
   */
  public function setStartTime($start_time) {
    if (!is_int($start_time) || $start_time <= 0) {
      throw new \InvalidArgumentException('The start_time argument must be a positive integer.');
    }
    $this->startTime = $start_time;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndTime() {
    return $this->endTime;
  }

  /**
   * Sets the process end time.
   *
   * @param int $end_time
   *   The Unix timestamp representing the process end time.
   *
   * @throws \InvalidArgumentException
   *   If the $end_time argument is not a positive integer.
   */
  public function setEndTime($end_time) {
    if (!is_int($end_time) || $end_time <= 0) {
      throw new \InvalidArgumentException('The end_time argument must be a positive integer.');
    }
    $this->endTime = $end_time;
  }

  /**
   * {@inheritdoc}
   */
  public function getPid() {
    return $this->pid;
  }

  /**
   * {@inheritdoc}
   */
  public function setPid($pid) {
    $this->pid = $pid;
  }

  /**
   * {@inheritdoc}
   */
  public function setExitCode($exit_code) {
    $this->exitCode = $exit_code;
  }

  /**
   * {@inheritdoc}
   */
  public function getExitCode() {
    return $this->exitCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setExitMessage($exit_message) {
    if (!is_string($exit_message)) {
      throw new \InvalidArgumentException('The exit_message argument must be a string.');
    }
    $this->exitMessage = $exit_message;
  }

  /**
   * {@inheritdoc}
   */
  public function setTimer($timer) {
    if ($timer instanceof Timer) {
      $this->timer = $timer;
    } elseif (is_string($timer)) {
      $this->timer = Timer::fromJson($timer);
    } else {
      throw new \InvalidArgumentException('The timer parameter must be a string or of type TimerInterface.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTimer() {
    return $this->timer;
  }

  /**
   * Gets the exit message of the completed Wip object.
   *
   * @return string
   *   The exit message.
   */
  public function getExitMessage() {
    return $this->exitMessage;
  }

  /**
   * {@inheritdoc}
   */
  public function initializeFromSignalData($signal_data) {
    parent::initializeFromSignalData($signal_data);
    if ($this->getType() !== SignalType::COMPLETE) {
      throw new \InvalidArgumentException('The signal type must be "SignalType::COMPLETE".');
    }
    if (!empty($signal_data->startTime)) {
      $this->setStartTime(intval($signal_data->startTime));
    }
    if (!empty($signal_data->endTime)) {
      $this->setEndTime(intval($signal_data->endTime));
    }
    if (!empty($signal_data->pid)) {
      $this->setPid(strval($signal_data->pid));
    }
    if (isset($signal_data->exitCode)) {
      $this->setExitCode(intval($signal_data->exitCode));
    }
    if (isset($signal_data->exitMessage)) {
      $this->setExitMessage(strval($signal_data->exitMessage));
    }
    if (isset($signal_data->timer)) {
      $this->setTimer($signal_data->timer);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function convertFieldsToObject() {
    $data = get_object_vars(parent::convertFieldsToObject());
    $start_time = $this->getStartTime();
    if (!empty($start_time)) {
      $data['startTime'] = $start_time;
    }
    $end_time = $this->getEndTime();
    if (!empty($end_time)) {
      $data['endTime'] = $end_time;
    }
    $pid = $this->getPid();
    if (!empty($pid)) {
      $data['pid'] = $pid;
    }
    $exit_code = $this->getExitCode();
    if (!empty($exit_code)) {
      $data['exitCode'] = $exit_code;
    }
    $exit_message = $this->getExitMessage();
    if (!empty($exit_message)) {
      $data['exitMessage'] = $exit_message;
    }
    $timer = $this->getTimer();
    if (!empty($timer)) {
      $data['timer'] = $timer->toJson();
    }
    $signal_data = $this->getData();
    if (!empty($signal_data)) {
      // Allow the signal data to override values.
      $signal_data_array = get_object_vars($signal_data);
      $data = array_merge($data, $signal_data_array);
    }
    return (object) $data;
  }

}
