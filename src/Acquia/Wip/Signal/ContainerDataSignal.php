<?php

namespace Acquia\Wip\Signal;

use Acquia\Wip\Container\ContainerResult;

/**
 * Provides container resource use information.
 */
class ContainerDataSignal extends ProcessSignal implements ContainerDataSignalInterface {

  /**
   * The disk space used by the workload, measured in gigabytes.
   *
   * @var float
   */
  private $workloadDisk = 0.0;

  /**
   * The disk space used by the container, measured in gigabytes.
   *
   * @var float
   */
  private $containerDisk = 0.0;

  /**
   * The container runtime, measured in seconds.
   *
   * @var int
   */
  private $time = 0;

  /**
   * Configures a new instance of ContainerTerminatedSignal.
   */
  public function __construct() {
    $this->setType(SignalType::DATA);
  }

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
   * {@inheritdoc}
   */
  public function setWorkloadDiskUse($disk_use) {
    if (!is_float($disk_use) || $disk_use < 0) {
      throw new \InvalidArgumentException('The "disk_use" parameter must be a positive float value.');
    }
    $this->workloadDisk = $disk_use;
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkloadDiskUse() {
    return $this->workloadDisk;
  }

  /**
   * {@inheritdoc}
   */
  public function setContainerDiskUse($disk_use) {
    if (!is_float($disk_use) || $disk_use < 0) {
      throw new \InvalidArgumentException('The "disk_use" parameter must be a positive float value.');
    }
    $this->containerDisk = $disk_use;
  }

  /**
   * {@inheritdoc}
   */
  public function getContainerDiskUse() {
    return $this->containerDisk;
  }

  /**
   * {@inheritdoc}
   */
  public function setTime($time) {
    if (!is_int($time) || $time < 0) {
      throw new \InvalidArgumentException('The "time" parameter must be a positive integer.');
    }
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function getTime() {
    return $this->time;
  }

  /**
   * {@inheritdoc}
   */
  public function initializeFromSignalData($signal_data) {
    parent::initializeFromSignalData($signal_data);
    if (isset($signal_data->time) && is_numeric($signal_data->time)) {
      $this->setTime(intval($signal_data->time));
    }
    if (isset($signal_data->disk) && is_numeric($signal_data->disk)) {
      $this->setWorkloadDiskUse(floatval($signal_data->disk));
    }
    if (isset($signal_data->initial_disk) && is_numeric($signal_data->initial_disk)) {
      $this->setContainerDiskUse(floatval($signal_data->initial_disk));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function convertFieldsToObject() {
    $data = get_object_vars(parent::convertFieldsToObject());
    $data['time'] = $this->getTime();
    $data['disk'] = $this->getWorkloadDiskUse();
    $data['initial_disk'] = $this->getContainerDiskUse();
    return (object) $data;
  }

}
