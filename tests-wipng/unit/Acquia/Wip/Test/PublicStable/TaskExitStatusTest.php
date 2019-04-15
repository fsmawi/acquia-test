<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\IteratorStatus;
use Acquia\Wip\TaskExitStatus;

/**
 * Missing summary.
 */
class TaskExitStatusTest extends \PHPUnit_Framework_TestCase {

  /**
   * Provides invalid status values.
   *
   * @return array
   *   The invalid values.
   */
  public function invalidStatusValueProvider() {
    return array(
      array(NULL),
      array(TRUE),
      array(''),
      array('string'),
      array(new \stdClass()),
      array(array('string')),
      array(1000),
    );
  }

  /**
   * An array of all valid status.
   *
   * @var array
   */
  private $validStatus = array(
    TaskExitStatus::NOT_FINISHED,
    TaskExitStatus::WARNING,
    TaskExitStatus::ERROR_USER,
    TaskExitStatus::ERROR_SYSTEM,
    TaskExitStatus::TERMINATED,
    TaskExitStatus::COMPLETED,
  );

  /**
   * Missing summary.
   */
  public function testDefaultValue() {
    $exit_status = new TaskExitStatus();
    $this->assertEquals(TaskExitStatus::NOT_FINISHED, $exit_status->getValue());
  }

  /**
   * Missing summary.
   */
  public function testNonDefaultValue() {
    $exit_status = new TaskExitStatus(TaskExitStatus::ERROR_USER);
    $this->assertEquals(TaskExitStatus::ERROR_USER, $exit_status->getValue());
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testIllegalValue() {
    new TaskExitStatus(-1);
  }

  /**
   * Missing summary.
   */
  public function testConvert() {
    // Expected output => input : done this way around to use the ints as keys.
    $valid_conversions = array(
      TaskExitStatus::WARNING => new IteratorStatus(IteratorStatus::WARNING),
      TaskExitStatus::ERROR_USER => new IteratorStatus(IteratorStatus::ERROR_USER),
      TaskExitStatus::ERROR_SYSTEM => new IteratorStatus(IteratorStatus::ERROR_SYSTEM),
      TaskExitStatus::TERMINATED => new IteratorStatus(IteratorStatus::TERMINATED),
      TaskExitStatus::COMPLETED => new IteratorStatus(IteratorStatus::OK),
    );

    foreach ($valid_conversions as $expected_output => $input) {
      $this->assertEquals($expected_output, TaskExitStatus::fromIteratorStatus($input));
    }
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testBadConvert() {
    $iterator_status = $this->getMock('Acquia\Wip\IteratorStatus');
    $iterator_status->expects($this->once())
      ->method('getValue')
      ->will($this->returnValue(-1));

    TaskExitStatus::fromIteratorStatus($iterator_status);
  }

  /**
   * Missing summary.
   */
  public function testIsError() {
    $error_status = array(
      TaskExitStatus::ERROR_USER,
      TaskExitStatus::ERROR_SYSTEM,
    );

    foreach ($error_status as $status) {
      $this->assertTrue(TaskExitStatus::isError($status));
    }

    $non_error_status = array(
      TaskExitStatus::WARNING,
      TaskExitStatus::COMPLETED,
      TaskExitStatus::NOT_FINISHED,
      TaskExitStatus::TERMINATED,
    );

    foreach ($non_error_status as $status) {
      $this->assertFalse(TaskExitStatus::isError($status));
    }
  }

  /**
   * Missing summary.
   */
  public function testIsTerminated() {
    $this->assertTrue(TaskExitStatus::isTerminated(TaskExitStatus::TERMINATED));

    $non_error_status = array(
      TaskExitStatus::ERROR_USER,
      TaskExitStatus::ERROR_SYSTEM,
      TaskExitStatus::WARNING,
      TaskExitStatus::COMPLETED,
      TaskExitStatus::NOT_FINISHED,
    );

    foreach ($non_error_status as $status) {
      $this->assertFalse(TaskExitStatus::isTerminated($status));
    }
  }

  /**
   * Tests that getValues returns all valid status values.
   */
  public function testGetValues() {
    $values = TaskExitStatus::getValues();
    foreach ($this->validStatus as $status) {
      $this->assertContains($status, $values);
    }
    foreach ($values as $value) {
      $this->assertContains($value, $this->validStatus);
    }
  }

  /**
   * Tests that valid integer values correspond to correct labels.
   */
  public function testGetLabel() {
    $this->assertEquals('Not finished', TaskExitStatus::getLabel(TaskExitStatus::NOT_FINISHED));
    $this->assertEquals('Warning', TaskExitStatus::getLabel(TaskExitStatus::WARNING));
    $this->assertEquals('User error', TaskExitStatus::getLabel(TaskExitStatus::ERROR_USER));
    $this->assertEquals('System error', TaskExitStatus::getLabel(TaskExitStatus::ERROR_SYSTEM));
    $this->assertEquals('Terminated', TaskExitStatus::getLabel(TaskExitStatus::TERMINATED));
    $this->assertEquals('Completed', TaskExitStatus::getLabel(TaskExitStatus::COMPLETED));
  }

  /**
   * Tests that invalid values cause an error to be thrown.
   *
   * @param mixed $value
   *   The invalid value.
   *
   * @dataProvider invalidStatusValueProvider
   *
   * @expectedException \InvalidArgumentException
   */
  public function testGetLabelWithInvalidValues($value) {
    TaskExitStatus::getLabel($value);
  }

}
