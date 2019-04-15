<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\ServerStatus;

/**
 * Missing summary.
 */
class ServerStatusTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   */
  public function testDefaultValue() {
    $server_status = new ServerStatus();
    $this->assertEquals(ServerStatus::AVAILABLE, $server_status->getValue());
  }

  /**
   * Missing summary.
   */
  public function testNonDefaultValue() {
    $server_status = new ServerStatus(ServerStatus::NOT_AVAILABLE);
    $this->assertEquals(ServerStatus::NOT_AVAILABLE, $server_status->getValue());
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testIllegalValue() {
    new ServerStatus(15);
  }

}
