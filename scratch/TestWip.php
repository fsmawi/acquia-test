<?php

namespace Acquia;

/**
 * Defines a dummy Wip object for basic testing purposes.
 */
class TestWip extends \Acquia\Wip\Implementation\BasicWip {
  protected $stateTable = <<<EOT

# This should be replaced.

start {
  * step1
}

step1 {
  * step2
}

step2 {
  * finish
}

EOT;

  /**
   * Performs step 1 in the state table.
   */
  public function step1() {
    $msg = "*** ohmigod ohmigod ohmigod I just ran ***\n";
    file_put_contents('/tmp/wiplog', $msg, FILE_APPEND);
  }

  /**
   * Performs step 2 in the state table.
   */
  public function step2() {
    $msg = "*** so did I ***\n";
    file_put_contents('/tmp/wiplog', $msg, FILE_APPEND);
  }

}
