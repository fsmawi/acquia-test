<?php

namespace Acquia\Wip\Signal;

/**
 * Methods common to completed asynchronous processes.
 */
interface CompleteSignalInterface extends SignalInterface {

  /**
   * Gets the unique process ID associated with this signal.
   *
   * @return string
   *   The process ID.
   */
  public function getProcessId();

}
