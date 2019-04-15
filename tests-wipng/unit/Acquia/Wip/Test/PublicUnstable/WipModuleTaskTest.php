<?php

namespace Acquia\Wip;

/**
 * Tests the WipModuleTask class.
 */
class WipModuleTaskTest extends \PHPUnit_Framework_TestCase {

  /**
   * Provides input and output arrays for testSetAndGet.
   *
   * @return array
   *   Data sets for testSetAndGet.
   */
  public function data() {
    return [
      [
        [
          'module_name' => 'Module',
          'task_name' => 'Module\Name',
          'class_name' => 'Acquia\Module\Name',
          'group_name' => 'GroupName',
          'log_level' => 'Debug',
          'priority' => 'High',
          'version' => '1.0',
        ],
        [
          'module_name' => 'Module',
          'task_name' => 'Module\Name',
          'class_name' => 'Acquia\Module\Name',
          'group_name' => 'GroupName',
          'log_level' => WipLogLevel::DEBUG,
          'priority' => TaskPriority::HIGH,
          'version' => '1.0',
        ],
      ],
    ];
  }

  /**
   * Tests setters and getters through constructor.
   *
   * @param string[] $input
   *   Array of constructor parameters.
   * @param string[] $output
   *   Array of object values.
   *
   * @dataProvider data
   */
  public function testSetAndGet($input, $output) {
    $module_name = $input['module_name'];
    $task_name = $input['task_name'];
    $class_name = $input['class_name'];
    $group_name = $input['group_name'];
    $log_level = $input['log_level'];
    $priority = $input['priority'];

    $task = new WipModuleTask($module_name, $task_name, $class_name, $group_name, $log_level, $priority);

    $this->assertEquals($output['module_name'], $task->getModuleName());
    $this->assertEquals($output['task_name'], $task->getName());
    $this->assertEquals($output['class_name'], $task->getClassName());
    $this->assertEquals($output['group_name'], $task->getGroupName());
    $this->assertEquals($output['log_level'], $task->getLogLevel());
    $this->assertEquals($output['priority'], $task->getPriority());
  }

  /**
   * Tests that invalid WipLogLevel strings are not allowed.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidLogLevel() {
    new WipModuleTask('Module', 'Module\Task', 'Acquia\Module\Task', 'GroupName', 'Invalid');
  }

  /**
   * Tests that invalid TaskPriority strings are not allowed.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidTaskPriority() {
    new WipModuleTask('Module', 'Module\Task', 'Acquia\Module\Task', 'GroupName', 'Debug', 'Invalid');
  }

}
