<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\ThreadStatus;

/**
 * Missing summary.
 */
class ThreadStatusTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   */
  public function testDefaultValue() {
    $thread_status = new ThreadStatus();
    $this->assertEquals(ThreadStatus::RESERVED, $thread_status->getValue());
  }

  /**
   * Missing summary.
   */
  public function testNonDefaultValue() {
    $thread_status = new ThreadStatus(ThreadStatus::RUNNING);
    $this->assertEquals(ThreadStatus::RUNNING, $thread_status->getValue());
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testIllegalValue() {
    new ThreadStatus(-1);
  }

}
