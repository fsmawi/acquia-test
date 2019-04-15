<?php

namespace Acquia\Wip\Signal;

/**
 * Used for passing data to a Wip object.
 */
class DataSignal extends Signal implements DataSignalInterface {

  /**
   * The data payload.
   *
   * @var object
   */
  private $payload = NULL;

  /**
   * {@inheritdoc}
   */
  public function initializeFromSignalData($signal_data) {
    parent::initializeFromSignalData($signal_data);
    if (!empty($signal_data->payload)) {
      $this->setPayload($signal_data->payload);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function convertFieldsToObject() {
    $data = parent::convertFieldsToObject();
    $data->payload = $this->getPayload();
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function setPayload($payload) {
    if (!is_object($payload)) {
      throw new \InvalidArgumentException('The payload parameter must be an object.');
    }
    $this->payload = $payload;
  }

  /**
   * {@inheritdoc}
   */
  public function getPayload() {
    $result = $this->payload;
    if (!is_object($result)) {
      $result = new \stdClass();
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return SignalType::DATA;
  }

}
