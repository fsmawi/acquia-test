<?php

namespace Acquia\Wip\Runtime;

/**
 * Contains the ThreadPoolInterface.
 */
interface ThreadPoolInterface {

  /**
   * Dispatches some WIP tasks to any available threads for limited time.
   */
  public function process();

  /**
   * Checks whether the status of the system allows processing to proceed.
   *
   * This may include checks such as whether a database connection is intact, or
   * if a previously acquired lock is still held.
   *
   * @return bool
   *   TRUE if the system is in a stable state, otherwise FALSE.
   */
  public function systemStatusOk();

}
