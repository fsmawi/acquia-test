<?php

namespace Acquia\Wip;

/**
 * An interface for keeping ThreadPool statistics.
 */
interface ThreadPoolDetailInterface {

  /**
   * Records when a function calls the sleep method.
   *
   * @param float $length
   *   The length of sleep.
   */
  public function recordSleep($length);
  
  /**
   * Gets the total length of time spent in sleep.
   *
   * @return float
   *   The total sleep time.
   */
  public function getTotalSleepTime();

}
