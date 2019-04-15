<?php

namespace Acquia\Wip\Signal;

/**
 * This is a tag interface used to identify the source of a signal.
 *
 * This particular interface should be added to any signal implementation that
 * is used for asynchronous Cloud API calls.
 */
interface AcquiaCloudSignalInterface extends ProcessSignalInterface {

  /**
   * Returns the Cloud task ID of the associated task.
   *
   * @return int
   *   The task ID.
   */
  public function getPid();

}
