<?php

namespace Acquia\Wip\Implementation;

/**
 * Missing summary.
 */
class BasicTestWip extends BasicWip {

  /**
   * The version associated with this Wip class.
   */
  const CLASS_VERSION = 1;

  /**
   * Sets the Work ID.
   *
   * This is a simple override for basic wip so that we can test the behavior
   * of the wip pool with regard to concurrency. Normally, you would never set
   * the work id after instantiation.
   *
   * @param string $work_id
   *   The work id.
   */
  public function setWorkId($work_id) {
    $this->workId = $work_id;
  }

}
