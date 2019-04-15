<?php

namespace Acquia\Wip\Modules\NativeModule;

use Acquia\Wip\Implementation\BasicWip;

/**
 * A simple object to test task timeout behavior.
 */
class WipTimeoutTest extends BasicWip {

  /**
   * The number of seconds to wait in the timeout state.
   *
   * @var int
   */
  private $timeoutInterval = 120;

  /**
   * The state table that will be executed by this Wip object.
   *
   * @var string
   */
  protected $stateTable = <<<EOT
start {
  * timeout
}

timeout {
  * finish
}

failure {
  * finish
}

terminate {
  * failure
}
EOT;

  /**
   * Sleeps for the configured duration.
   *
   * The point of this is to validate the behavior when a task takes too long.
   */
  public function timeout() {
    sleep($this->timeoutInterval);
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions($options) {
    if (isset($options->timeoutInterval)) {
      $this->timeoutInterval = intval($options->timeoutInterval);
    }
  }

}
