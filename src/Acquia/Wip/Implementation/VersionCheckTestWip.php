<?php

namespace Acquia\Wip\Implementation;

/**
 * A test Wip object used for the wipversion unit tests.
 *
 * This Wip object should not be modified or used for other purposes.
 */
class VersionCheckTestWip extends BasicWip {

  /**
   * The version associated with this Wip class.
   */
  const CLASS_VERSION = 1;

  /**
   * The state table associated with this Wip instance.
   *
   * @var string
   */
  protected $stateTable = <<<EOT
start {
  * state1
}

state1 {
  * finish
}

failure {
  * finish
  ! finish
}
EOT;

}
