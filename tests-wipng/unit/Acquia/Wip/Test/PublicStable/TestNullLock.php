<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\NullLock;

/**
 * Missing summary.
 */
class TestNullLock extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @var NullLock
   */
  private $lock = NULL;

  /**
   * Missing summary.
   */
  public function setup() {
    $this->lock = new NullLock();
  }

  /**
   * Just test the dummy implementation all in one go.
   */
  public function testBasics() {
    $result = $this->lock->acquire('test');
    $this->assertInternalType('boolean', $result);
    $this->assertEquals(TRUE, $result);
    $result = $this->lock->isFree('test');
    $this->assertInternalType('boolean', $result);
    $this->assertEquals(TRUE, $result);
    $result = $this->lock->release('test');
    $this->assertInternalType('boolean', $result);
    $this->assertEquals(TRUE, $result);
    $result = $this->lock->isMine('test');
    $this->assertInternalType('boolean', $result);
    $this->assertEquals(TRUE, $result);
  }

}
