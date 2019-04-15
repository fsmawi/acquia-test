<?php

namespace Acquia\Wip\Signal;

use Acquia\Wip\AcquiaCloud\AcquiaCloudResult;

/**
 * Contains the AcquiaCloudCompleteSignal class.
 */
class AcquiaCloudCompleteSignal extends ProcessSignal implements CompleteSignalInterface, AcquiaCloudSignalInterface {
  private $queue = '';
  private $state = '';
  private $description = '';
  private $created = 0;
  private $started = 0;
  private $completed = 0;
  private $sender = '';
  private $result = '';
  private $cookie = '';
  private $logs = '';

  /**
   * {@inheritdoc}
   */
  public function initializeFromSignal(SignalInterface $signal) {
    parent::initializeFromSignal($signal);
    if ($this->getType() !== SignalType::COMPLETE) {
      throw new \InvalidArgumentException('The signal type must be "SignalType::COMPLETE".');
    }
    $this->parseData();
  }

  /**
   * {@inheritdoc}
   */
  public function setPid($pid) {
    if (!is_numeric($pid)) {
      throw new \InvalidArgumentException('The pid parameter must be an integer.');
    }
    parent::setPid($pid);
  }

  /**
   * Gets the queue.
   */
  public function getQueue() {
    return $this->queue;
  }

  /**
   * Gets the signal's state.
   */
  public function getState() {
    return $this->state;
  }

  /**
   * Gets the signal's description.
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * Gets the time that the signal was created.
   */
  public function getCreated() {
    return $this->created;
  }

  /**
   * Gets the time that the signal started.
   */
  public function getStarted() {
    return $this->getStartTime();
  }

  /**
   * Gets the time that the signal completed.
   */
  public function getCompleted() {
    return $this->getEndTime();
  }

  /**
   * Gets the signal's sender.
   */
  public function getSender() {
    return $this->sender;
  }

  /**
   * Gets the signal's results.
   */
  public function getResult() {
    return $this->result;
  }

  /**
   * Gets the signal's cookie.
   */
  public function getCookie() {
    return $this->cookie;
  }

  /**
   * Gets the signal's logs.
   */
  public function getLogs() {
    return $this->logs;
  }

  /**
   * Missing summary.
   */
  private function parseData() {
    $data = $this->getData();

    $this->setPid(intval($data->id));
    if (!empty($data->queue)) {
      $this->queue = $data->queue;
    }
    if (!empty($data->state)) {
      $this->state = $data->state;
    }
    if (!empty($data->description)) {
      $this->description = $data->description;
    }
    if (!empty($data->created)) {
      $this->created = intval($data->created);
    }
    if (!empty($data->started)) {
      $this->started = $data->started;
      $this->setStartTime($data->started);
    }
    if (!empty($data->completed)) {
      $this->completed = $data->completed;
      $this->setEndTime($data->completed);
    }
    if (!empty($data->sender)) {
      $this->sender = $data->sender;
    }
    if (!empty($data->result)) {
      $this->result = $data->result;
    }
    if (!empty($data->cookie)) {
      $this->cookie = $data->cookie;
    }
    if (!empty($data->logs)) {
      $this->logs = $data->logs;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessId() {
    return AcquiaCloudResult::createUniqueId($this->getPid());
  }

}
