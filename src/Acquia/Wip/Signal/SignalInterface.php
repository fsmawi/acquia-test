<?php

namespace Acquia\Wip\Signal;

/**
 * The SignalInterface describes signal metadata and contents.
 */
interface SignalInterface {

  /**
   * Configures this instance from the specified signal.
   *
   * This is used to construct specific classes from a generic signal.
   *
   * @param SignalInterface $signal
   *   The signal.
   */
  public function initializeFromSignal(SignalInterface $signal);

  /**
   * Configures this instance from the specified signal data.
   *
   * @param object $signal_data
   *   The signal data.
   */
  public function initializeFromSignalData($signal_data);

  /**
   * Converts this signal into a simple object.
   *
   * @return object
   *   The object, containing fields for each value in the signal.
   */
  public function convertToObject();

  /**
   * Converts the signal fields into a simple object.
   *
   * @return object
   *   The object, containing fields for each value in the signal.
   */
  public function convertFieldsToObject();

  /**
   * Gets the signal ID.
   *
   * @return int
   *   The signal ID.
   */
  public function getId();

  /**
   * Sets the signal ID.
   *
   * @param int $id
   *   The signal ID.
   *
   * @throws \InvalidArgumentException
   *   If the ID is not a positive integer.
   */
  public function setId($id);

  /**
   * Gets the object ID associated with this signal.
   *
   * @return int
   *   The object ID.
   */
  public function getObjectId();

  /**
   * Sets the object ID associated with this signal.
   *
   * @param int $object_id
   *   The object ID.
   *
   * @throws \InvalidArgumentException
   *   If the ID is not a positive integer.
   */
  public function setObjectId($object_id);

  /**
   * Gets the Unix timestamp indicating when this signal was sent.
   *
   * Note that the sent time is the time that the signal was added to the signal
   * store.
   *
   * @return int
   *   The time this signal was sent.
   */
  public function getSentTime();

  /**
   * Sets the Unix timestamp indicating when this signal was sent.
   *
   * @param int $sent_time
   *   The timestamp indicating when this signal was sent.
   *
   * @throws \InvalidArgumentException
   *   If the sent time is not a positive integer.
   */
  public function setSentTime($sent_time);

  /**
   * Gets the Unix timestamp indicating when this signal was consumed.
   *
   * @return int
   *   The time this signal was consumed.
   */
  public function getConsumedTime();

  /**
   * Sets the Unix timestamp indicating when this signal was consumed.
   *
   * @param int $consumed_time
   *   The timestamp indicating when this signal was consumed.
   *
   * @throws \InvalidArgumentException
   *   If the consumed time is not a positive integer.
   */
  public function setConsumedTime($consumed_time);

  /**
   * Gets the signal type.
   *
   * @return int
   *   The signal type.
   */
  public function getType();

  /**
   * Sets the signal type.
   *
   * @param int $signal_type
   *   The signal type.
   *
   * @throws \InvalidArgumentException
   *   If the signal type is not a legal value.
   */
  public function setType($signal_type);

  /**
   * Gets the signal data.
   *
   * @return object
   *   The data.
   */
  public function getData();

  /**
   * Sets the signal data.
   *
   * @param object $data
   *   The data.
   *
   * @throws \InvalidArgumentException
   *   If the signal data is not an object.
   */
  public function setData($data);

  /**
   * Gets the signal extra data that is being passed to the signal handler.
   *
   * @return object
   *   The extra data.
   */
  public function getExtraData();

  /**
   * Sets the signal extra data that is being passed to the signal handler.
   *
   * @param object $data
   *   The extra data.
   *
   * @throws \InvalidArgumentException
   *   If the data is not an object.
   */
  public function setExtraData($data);

}
