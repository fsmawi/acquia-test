<?php

namespace Acquia\Wip\Modules\NativeModule;

use Acquia\Wip\Implementation\BasicWip;

/**
 * A simple Wip object to ensure that wip-service is still running.
 *
 * This object is intended to be run by a Jenkins job every n minutes to ensure
 * that jobs are being processed and run in wip-service.
 */
class Canary extends BasicWip {

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
  * finish
}

failure {
  * finish
  ! finish
}

terminate {
  * failure
}
EOT;

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return 'Canary';
  }

}
