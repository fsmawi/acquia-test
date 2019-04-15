<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\Implementation\IteratorResult;
use Acquia\Wip\IteratorStatus;

/**
 * Missing summary.
 */
class IteratorResultInterfaceTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   */
  public function testConstructor() {
    new IteratorResult();
  }

  /**
   * Missing summary.
   */
  public function testInterface() {
    $iterator = new IteratorResult();
    $this->assertInstanceOf('Acquia\Wip\IteratorResultInterface', $iterator);
  }

  /**
   * Missing summary.
   */
  public function testWakeTime() {
    // Wait 45 seconds.
    $wait_time = 45;
    $iterator = new IteratorResult($wait_time);
    $this->assertEquals($wait_time, $iterator->getWaitTime());
  }

  /**
   * Missing summary.
   */
  public function testIsComplete() {
    $iterator = new IteratorResult(0, FALSE);
    $this->assertFalse($iterator->isComplete());

    $iterator = new IteratorResult(0, TRUE);
    $this->assertTrue($iterator->isComplete());
  }

  /**
   * Missing summary.
   */
  public function testGetStatus() {
    $iterator = new IteratorResult();
    $status = $iterator->getStatus();
    $this->assertEquals(IteratorStatus::OK, $status->getValue());
  }

  /**
   * Missing summary.
   */
  public function testGetMessage() {
    $message = 'Failed to move to state x';
    $iterator = new IteratorResult(0, TRUE, new IteratorStatus(IteratorStatus::ERROR_SYSTEM), $message);
    $this->assertEquals($message, $iterator->getMessage());
  }

}
