<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\TaskPriority;

/**
 * Missing summary.
 */
class TaskPriorityTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   */
  public function testDefaultValue() {
    $task_priority = new TaskPriority();
    $this->assertEquals(TaskPriority::MEDIUM, $task_priority->getValue());
  }

  /**
   * Missing summary.
   */
  public function testNonDefaultValue() {
    $task_priority = new TaskPriority(TaskPriority::LOW);
    $this->assertEquals(TaskPriority::LOW, $task_priority->getValue());
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testIllegalValue() {
    new TaskPriority(NULL);
  }

}
