<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\TaskStatus;

/**
 * Missing summary.
 */
class TaskStatusTest extends \PHPUnit_Framework_TestCase {

  /**
   * An array of valid status values.
   *
   * @var array
   */
  private $validStatus = array(
    TaskStatus::NOT_READY,
    TaskStatus::NOT_STARTED,
    TaskStatus::WAITING,
    TaskStatus::PROCESSING,
    TaskStatus::COMPLETE,
    TaskStatus::RESTARTED,
  );

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
   * Missing summary.
   */
  public function testDefaultValue() {
    $task_status = new TaskStatus();
    $this->assertEquals(TaskStatus::NOT_STARTED, $task_status->getValue());
  }

  /**
   * Missing summary.
   */
  public function testNonDefaultValue() {
    $task_status = new TaskStatus(TaskStatus::WAITING);
    $this->assertEquals(TaskStatus::WAITING, $task_status->getValue());
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testIllegalValue() {
    new TaskStatus(15);
  }

  /**
   * Tests that getValues returns all valid status values.
   */
  public function testGetValues() {
    $values = TaskStatus::getValues();
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
    $this->assertEquals('Not ready', TaskStatus::getLabel(TaskStatus::NOT_READY));
    $this->assertEquals('Not started', TaskStatus::getLabel(TaskStatus::NOT_STARTED));
    $this->assertEquals('Waiting', TaskStatus::getLabel(TaskStatus::WAITING));
    $this->assertEquals('Processing', TaskStatus::getLabel(TaskStatus::PROCESSING));
    $this->assertEquals('Completed', TaskStatus::getLabel(TaskStatus::COMPLETE));
    $this->assertEquals('Restarted', TaskStatus::getLabel(TaskStatus::RESTARTED));
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
    TaskStatus::getLabel($value);
  }

}
