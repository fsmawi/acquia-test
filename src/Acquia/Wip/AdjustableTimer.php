<?php

namespace Acquia\Wip;

/**
 * Extends the Timer class to allow adjusting start time for testing purposes.
 */
class AdjustableTimer extends Timer {

  /**
   * Adjusts the start time.
   *
   * @param int $difference
   *   The start time adjustment.  Use a negative value to push the start time back in time; positive to go forward.
   */
  public function adjustStart($difference) {
    $this->start += $difference;
  }

}
