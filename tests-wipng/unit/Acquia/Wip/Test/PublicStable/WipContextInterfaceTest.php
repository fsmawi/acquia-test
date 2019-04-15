<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\IteratorStatus;
use Acquia\Wip\Iterators\BasicIterator\StateTableIterator;
use Acquia\Wip\Iterators\BasicIterator\WipContext;

/**
 * Missing summary.
 */
class WipContextInterfaceTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   */
  public function testWipContextConstructor() {
    $wip_context = new WipContext();
    $this->assertInstanceOf('Acquia\Wip\WipContextInterface', $wip_context);
  }

  /**
   * Missing summary.
   */
  public function testSetGet() {
    $wip_context = new WipContext();
    $value = 'value';
    $wip_context->name = $value;
    $this->assertEquals($value, $wip_context->name);
  }

  /**
   * Missing summary.
   */
  public function testIsSet() {
    $wip_context = new WipContext();
    $value = 'value';
    $this->assertFalse(isset($wip_context->name));

    $wip_context->name = $value;
    $this->assertTrue(isset($wip_context->name));
  }

  /**
   * Missing summary.
   */
  public function testUnset() {
    $wip_context = new WipContext();
    $value = 'value';
    $wip_context->name = $value;
    unset($wip_context->name);
    $this->assertFalse(isset($wip_context->name));
  }

  /**
   * Missing summary.
   */
  public function testDefaultExitCode() {
    $wip_context = new WipContext();
    $value = $wip_context->getExitCode();
    $this->assertEquals(IteratorStatus::OK, $value);
  }

  /**
   * Missing summary.
   */
  public function testDefaultExitCodeWithIterator() {
    $iterator = new StateTableIterator();
    $wip_context = $iterator->getWipContext('start');
    $value = $wip_context->getExitCode();
    $this->assertEquals(IteratorStatus::OK, $value);
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidExitMessage() {
    $wip_context = new WipContext();
    $wip_context->setExitMessage(15);
  }

  /**
   * Missing summary.
   */
  public function testExitMessage() {
    $wip_context = new WipContext();
    $result = $wip_context->getExitMessage();
    $this->assertInternalType('string', $result);
  }

  /**
   * Missing summary.
   */
  public function testExitMessageWithIterator() {
    $message = 'This is the exit message.';
    $iterator = new StateTableIterator();
    $wip_context = $iterator->getWipContext('start');
    $value = $wip_context->setExitMessage($message);
    $value = $wip_context->getExitMessage();
    $this->assertEquals($message, $value);
  }

}
