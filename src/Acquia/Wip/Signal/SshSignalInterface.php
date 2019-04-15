<?php

namespace Acquia\Wip\Signal;

/**
 * This is a tag interface used to identify the source of a signal.
 *
 * This particular interface should be added to any signal implementation that
 * is used for asynchronous Ssh tasks.
 */
interface SshSignalInterface extends ProcessSignalInterface {

  /**
   * Returns the process ID of the associated ssh process.
   *
   * @return int
   *   The process ID.
   */
  public function getPid();

}
