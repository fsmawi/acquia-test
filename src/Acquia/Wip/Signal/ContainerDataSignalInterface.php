<?php

namespace Acquia\Wip\Signal;

/**
 * Describes the interface for signals presenting container use data.
 */
interface ContainerDataSignalInterface extends ContainerSignalInterface {

  /**
   * Sets the amount of disk space used by the workload.
   *
   * @param float $disk_use
   *   The amount of disk space used by the workload measured in gigabytes.
   */
  public function setWorkloadDiskUse($disk_use);

  /**
   * Gets the amount of disk space used by the workload.
   *
   * @return float
   *   The amount of disk space used by the workload measured in gigabytes.
   */
  public function getWorkloadDiskUse();

  /**
   * Sets the amount of disk space used by the container.
   *
   * @param float $disk_use
   *   The amount of disk space used by the container measured in gigabytes.
   */
  public function setContainerDiskUse($disk_use);

  /**
   * Gets the amount of disk space used by the container.
   *
   * @return float
   *   The amount of disk space used by the container measured in gigabytes.
   */
  public function getContainerDiskUse();

  /**
   * Sets the container runtime.
   *
   * @param int $time
   *   The container runtime measured in seconds.
   */
  public function setTime($time);

  /**
   * Gets the container runtime.
   *
   * @return int
   *   The container runtime measured in seconds.
   */
  public function getTime();

}
