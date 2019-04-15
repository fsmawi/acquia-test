<?php

namespace Acquia\Wip\Test\PublicStable\Signal;

use Acquia\Wip\Signal\SignalType;

/**
 * Missing summary.
 */
class SignalTypeTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testIsLegal() {
    $this->assertTrue(SignalType::isLegal(SignalType::DATA));
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testIsNotLegal() {
    $this->assertFalse(SignalType::isLegal(1000));
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testGetLabel() {
    $this->assertEquals('complete', SignalType::getLabel(SignalType::COMPLETE));
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testGetLabelForBadType() {
    $this->assertEquals('unknown', SignalType::getLabel(1000));
  }

}
