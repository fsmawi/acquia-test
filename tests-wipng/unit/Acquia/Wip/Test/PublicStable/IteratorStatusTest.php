<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\IteratorStatus;

/**
 * Missing summary.
 */
class IteratorStatusTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   */
  public function testDefaultValue() {
    $status = new IteratorStatus();
    $this->assertEquals(IteratorStatus::OK, $status->getValue());
  }

  /**
   * Missing summary.
   */
  public function testNonDefaultValue() {
    $status = new IteratorStatus(IteratorStatus::ERROR_SYSTEM);
    $this->assertEquals(IteratorStatus::ERROR_SYSTEM, $status->getValue());
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testIllegalValue() {
    $status = new IteratorStatus(15);
  }

  /**
   * Missing summary.
   */
  public function testIsError() {
    $error_status = array(
      IteratorStatus::ERROR_USER,
      IteratorStatus::ERROR_SYSTEM,
    );

    foreach ($error_status as $status) {
      $this->assertTrue(IteratorStatus::isError($status));
    }

    $non_error_status = array(
      IteratorStatus::WARNING,
      IteratorStatus::OK,
      IteratorStatus::TERMINATED,
    );

    foreach ($non_error_status as $status) {
      $this->assertFalse(IteratorStatus::isError($status));
    }
  }

  /**
   * Missing summary.
   */
  public function testIsTerminated() {
    $this->assertTrue(IteratorStatus::isTerminated(IteratorStatus::TERMINATED));

    $non_error_status = array(
      IteratorStatus::ERROR_USER,
      IteratorStatus::ERROR_SYSTEM,
      IteratorStatus::WARNING,
      IteratorStatus::OK,
    );

    foreach ($non_error_status as $status) {
      $this->assertFalse(IteratorStatus::isTerminated($status));
    }
  }

}
