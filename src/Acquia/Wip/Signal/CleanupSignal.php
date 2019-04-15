<?php

namespace Acquia\Wip\Signal;

/**
 * The CleanupSignal is used for requesting and canceling resource cleanup.
 */
class CleanupSignal extends DataSignal {

  /**
   * Indicates a request for resource cleanup.
   */
  const ACTION_REQUEST = 1;

  /**
   * Indicates the resource cleanup should be canceled.
   */
  const ACTION_CANCEL = 2;

  /**
   * Indicates whether this instance is requesting cleanup or cancellation.
   *
   * @var int
   */
  private $action = NULL;

  /**
   * The type of resource.
   *
   * @var string
   */
  private $resourceType = NULL;

  /**
   * The resource ID.
   *
   * @var string
   */
  private $resourceId = NULL;

  /**
   * The resource name.
   *
   * @var string
   */
  private $resourceName;

  /**
   * The set of valid action IDs.
   *
   * @var int[]
   */
  private $validActions = array(
    self::ACTION_REQUEST,
    self::ACTION_CANCEL,
  );

  /**
   * Initializes new CleanupSignal object.
   */
  public function __construct() {
    $this->initializeClassId();
  }

  /**
   * The action indicates whether the signal is requesting or canceling cleanup.
   *
   * @param int $action
   *   The action.
   *
   * @throws \InvalidArgumentException
   *   If the action is not a valid value.
   */
  public function setAction($action) {
    if (!is_int($action)) {
      throw new \InvalidArgumentException('The action parameter must be an integer value.');
    }
    if (!in_array($action, $this->validActions)) {
      throw new \InvalidArgumentException(sprintf('The action parameter value "%d" is not a valid action.', $action));
    }
    $this->action = $action;
  }

  /**
   * Gets the action associated with this signal.
   *
   * @return int
   *   The action.
   */
  public function getAction() {
    return $this->action;
  }

  /**
   * Identifies the type of resource to be released.
   *
   * @param string $resource_type
   *   The resource type.
   *
   * @throws \InvalidArgumentException
   *   If the resource_type parameter is empty or not a string.
   */
  public function setResourceType($resource_type) {
    if (empty($resource_type) || !is_string($resource_type)) {
      throw new \InvalidArgumentException('The resource_type parameter must be a non-empty string.');
    }
    $this->resourceType = $resource_type;
  }

  /**
   * Gets the resource type.
   *
   * @return string
   *   The resource type.
   */
  public function getResourceType() {
    return $this->resourceType;
  }

  /**
   * Sets the resource ID.
   *
   * @param string $resource_id
   *   The resource ID.
   *
   * @throws \InvalidArgumentException
   *   If the resource_id parameter is empty or not a string.
   */
  public function setResourceId($resource_id) {
    if (empty($resource_id) || !is_string($resource_id)) {
      throw new \InvalidArgumentException('The resource_id parameter must be a non-empty string.');
    }
    $this->resourceId = $resource_id;
  }

  /**
   * Sets the resource ID.
   *
   * @return string
   *   The resource ID.
   */
  public function getResourceId() {
    return $this->resourceId;
  }

  /**
   * Sets the name of the resource to be released.
   *
   * @param string $name
   *   The resource name.
   *
   * @throws \InvalidArgumentException
   *   If the name parameter is empty or not a string.
   */
  public function setResourceName($name) {
    if (empty($name) || !is_string($name)) {
      throw new \InvalidArgumentException('The resource name parameter must be a non-empty string.');
    }
    $this->resourceName = $name;
  }

  /**
   * Gets the name of the resource to be released.
   *
   * @return string
   *   the resource name.
   */
  public function getResourceName() {
    return $this->resourceName;
  }

  /**
   * Gets the DataSignal payload.
   *
   * @return object
   *   The payload.
   */
  public function getPayload() {
    $payload = parent::getPayload();
    $action = $this->getAction();
    if (is_int($action)) {
      $payload->action = $action;
    }
    $type = $this->getResourceType();
    if (!empty($type)) {
      $payload->resourceType = $type;
    }
    $id = $this->getResourceId();
    if (!empty($id)) {
      $payload->resourceId = $id;
    }
    $name = $this->getResourceName();
    if (!empty($name)) {
      $payload->resourceName = $name;
    }
    return $payload;
  }

  /**
   * Sets the DataSignal payload.
   *
   * @param object $payload
   *   The payload.
   */
  public function setPayload($payload) {
    if (isset($payload->action) && is_numeric($payload->action)) {
      $this->setAction(intval($payload->action));
    }
    if (!empty($payload->resourceType)) {
      $this->setResourceType(strval($payload->resourceType));
    }
    if (!empty($payload->resourceId)) {
      $this->setResourceId(strval($payload->resourceId));
    }
    if (!empty($payload->resourceName)) {
      $this->setResourceName(strval($payload->resourceName));
    }
    parent::setPayload($payload);
  }

  /**
   * Sets the class ID in the signal data.
   *
   * This is important because it helps the SignalFactory instantiate the
   * correct signal class.
   */
  private function initializeClassId() {
    $data = $this->getData();
    if (empty($data)) {
      $data = new \stdClass();
    }
    $data->classId = 'acquia.wip.signal.cleanup';
    $this->setData($data);
  }

}
