<?php

namespace Acquia\Wip\Signal;

/**
 * A simple implementation of the SignalInterface.
 */
class Signal implements SignalInterface {

  /**
   * The signal ID.
   *
   * @var int
   */
  private $id;

  /**
   * The object ID associated with this signal.
   *
   * @var int
   */
  private $objectId;

  /**
   * The timestamp indicating when this signal was sent.
   *
   * @var int
   */
  private $sentTime;

  /**
   * The timestamp indicating when this signal was consumed.
   *
   * @var int
   */
  private $consumedTime;

  /**
   * The signal type.
   *
   * @var int
   */
  private $signalType = SignalType::COMPLETE;

  /**
   * The signal data.
   *
   * @var object
   */
  private $data;

  /**
   * Extra data that can be passed to the signal handler.
   *
   * @var object
   */
  private $extraData;

  /**
   * {@inheritdoc}
   */
  public function initializeFromSignal(SignalInterface $signal) {
    $this_data = $this->getData();
    $signal_data = $signal->getData();
    if (empty($this_data) && !empty($signal_data)) {
      $this->setData($signal_data);
    }
    $signal_object_data = $signal->convertToObject();
    $this->initializeFromSignalData($signal_object_data);
  }

  /**
   * {@inheritdoc}
   */
  public function initializeFromSignalData($signal_data) {
    if (!empty($signal_data->id)) {
      $this->setId(intval($signal_data->id));
    }
    if (!empty($signal_data->objectId)) {
      $this->setObjectId(intval($signal_data->objectId));
    }
    if (!empty($signal_data->type)) {
      $this->setType(intval($signal_data->type));
    }
    if (!empty($signal_data->sentTime)) {
      $this->setSentTime(intval($signal_data->sentTime));
    }
    if (!empty($signal_data->consumedTime)) {
      $this->setConsumedTime(intval($signal_data->consumedTime));
    }
    if (!empty($signal_data->extraData)) {
      $this->setExtraData($signal_data->extraData);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function convertToObject() {
    $data_array = get_object_vars($this->convertFieldsToObject());
    $callback_data = $this->getData();
    if (!empty($callback_data)) {
      // Allow the signal data to override values.
      $callback_data_array = get_object_vars($callback_data);
      $data_array = array_merge($data_array, $callback_data_array);
    }
    return (object) $data_array;
  }

  /**
   * {@inheritdoc}
   */
  public function convertFieldsToObject() {
    $data = array();
    $id = $this->getId();
    if (!empty($id)) {
      $data['id'] = $id;
    }
    $object_id = $this->getObjectId();
    if (!empty($object_id)) {
      $data['objectId'] = $object_id;
    }
    $type = $this->getType();
    if (!empty($type)) {
      $data['type'] = $type;
    }
    $sent_time = $this->getSentTime();
    if (!empty($sent_time)) {
      $data['sentTime'] = $sent_time;
    }
    $consumed_time = $this->getConsumedTime();
    if (!empty($consumed_time)) {
      $data['consumedTime'] = $consumed_time;
    }
    $signal_data = $this->getData();
    if (!empty($signal_data)) {
      // Allow the signal data to override values.
      $signal_data_array = get_object_vars($signal_data);
      $data = array_merge($data, $signal_data_array);
    }
    return (object) $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    if (!is_int($id) || $id < 0) {
      throw new \InvalidArgumentException('The id argument must be a positive integer.');
    }
    $this->id = $id;
  }

  /**
   * {@inheritdoc}
   */
  public function getObjectId() {
    return $this->objectId;
  }

  /**
   * {@inheritdoc}
   */
  public function setObjectId($object_id) {
    if (!is_int($object_id) || $object_id < 0) {
      throw new \InvalidArgumentException('The object_id argument must be a positive integer.');
    }
    $this->objectId = $object_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getSentTime() {
    return $this->sentTime;
  }

  /**
   * {@inheritdoc}
   */
  public function setSentTime($sent_time) {
    if (!is_int($sent_time) || $sent_time < 0) {
      throw new \InvalidArgumentException('The sent_time argument must be a positive integer.');
    }
    $this->sentTime = $sent_time;
  }

  /**
   * {@inheritdoc}
   */
  public function getConsumedTime() {
    return $this->consumedTime;
  }

  /**
   * {@inheritdoc}
   */
  public function setConsumedTime($consumed_time) {
    if (!is_int($consumed_time) || $consumed_time < 0) {
      throw new \InvalidArgumentException('The consumed_time argument must be a positive integer.');
    }
    $this->consumedTime = $consumed_time;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->signalType;
  }

  /**
   * {@inheritdoc}
   */
  public function setType($signal_type) {
    if (!SignalType::isLegal($signal_type)) {
      throw new \InvalidArgumentException('The signal_type argument must be valid SignalType value.');
    }
    $this->signalType = $signal_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    return $this->data;
  }

  /**
   * {@inheritdoc}
   */
  public function setData($data) {
    if (!is_object($data)) {
      throw new \InvalidArgumentException('The data argument must be an object.');
    }
    $this->data = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function setExtraData($data) {
    if (!is_object($data)) {
      throw new \InvalidArgumentException('The data argument must be an object.');
    }
    $this->extraData = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtraData() {
    return $this->extraData;
  }

}
