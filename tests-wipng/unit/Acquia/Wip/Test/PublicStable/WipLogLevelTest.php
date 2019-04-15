<?php

 namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\WipLogLevel;

/**
 * Missing summary.
 */
class WipLogLevelTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testFatal() {
    $value = WipLogLevel::FATAL;
    $this->assertEquals('fatal', strtolower(WipLogLevel::toString($value)));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testError() {
    $value = WipLogLevel::ERROR;
    $this->assertEquals('error', strtolower(WipLogLevel::toString($value)));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testAlert() {
    $value = WipLogLevel::ALERT;
    $this->assertEquals('alert', strtolower(WipLogLevel::toString($value)));
    $this->assertTrue(WipLogLevel::isValid($value));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testWarning() {
    $value = WipLogLevel::WARN;
    $this->assertEquals('warning', strtolower(WipLogLevel::toString($value)));
    $this->assertTrue(WipLogLevel::isValid($value));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testInfo() {
    $value = WipLogLevel::INFO;
    $this->assertEquals('info', strtolower(WipLogLevel::toString($value)));
    $this->assertTrue(WipLogLevel::isValid($value));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testDebug() {
    $value = WipLogLevel::DEBUG;
    $this->assertEquals('debug', strtolower(WipLogLevel::toString($value)));
    $this->assertTrue(WipLogLevel::isValid($value));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testTrace() {
    $value = WipLogLevel::TRACE;
    $this->assertEquals('trace', strtolower(WipLogLevel::toString($value)));
    $this->assertTrue(WipLogLevel::isValid($value));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   *
   * @expectedException \InvalidArgumentException
   */
  public function testIncorrectValueType() {
    $value = '1';
    WipLogLevel::isValid($value);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   *
   * @expectedException \InvalidArgumentException
   */
  public function testToStringWithBadValue() {
    $value = 15;
    WipLogLevel::toString($value);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   *
   * @expectedException \InvalidArgumentException
   */
  public function testToStringWithBadType() {
    $value = "1";
    WipLogLevel::toString($value);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testGetAll() {
    $levels = WipLogLevel::getAll();
    $this->assertEquals(7, count($levels));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testIsValidLabel() {
    $this->assertTrue(WipLogLevel::isValidLabel('fatal'));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testIsValidLabelUppercase() {
    $this->assertTrue(WipLogLevel::isValidLabel('FATAL'));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   *
   * @expectedException \InvalidArgumentException
   */
  public function testIsValidLabelBadType() {
    $this->assertTrue(WipLogLevel::isValidLabel(15));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testToInt() {
    $level = 'fatal';
    $int_level = WipLogLevel::toInt($level);
    $this->assertEquals(1, $int_level);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testToIntUppercase() {
    $level = 'FATAL';
    $int_level = WipLogLevel::toInt($level);
    $this->assertEquals(1, $int_level);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   *
   * @expectedException \InvalidArgumentException
   */
  public function testToIntBadValue() {
    $level = 'NonexistentLevel';
    WipLogLevel::toInt($level);
  }

}
