<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\WipApplicationStatus;

/**
 * Missing summary.
 */
class WipApplicationStatusTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   */
  public function testDefaultValue() {
    $status = new WipApplicationStatus();
    $this->assertEquals(WipApplicationStatus::DISABLED, $status->getValue());
  }

  /**
   * Missing summary.
   */
  public function testNonDefaultValue() {
    $status = new WipApplicationStatus(WipApplicationStatus::ENABLED);
    $this->assertEquals(WipApplicationStatus::ENABLED, $status->getValue());
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testIllegalValueNonNumeric() {
    new WipApplicationStatus(NULL);
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testIllegalValueNumeric() {
    new WipApplicationStatus(-1);
  }

}
