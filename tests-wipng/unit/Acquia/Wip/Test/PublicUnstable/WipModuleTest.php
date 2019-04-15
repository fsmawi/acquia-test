<?php

namespace Acquia\WipIntegrations\DoctrineORM\Entities;

use Acquia\Wip\WipModule;
use Acquia\Wip\WipModuleTask;
use Acquia\Wip\WipModuleTaskInterface;

/**
 * Tests the WipModule class.
 */
class WipModuleTest extends \PHPUnit_Framework_TestCase {

  /**
   * Provides data to testSetAndGet.
   *
   * @return array
   *   Sets of input and output for WipModule setters and getters.
   */
  public function data() {
    return [
      [
        [
          'version' => '1.0',
          'name' => 'Module',
          'directory' => '\full\path',
          'enabled' => FALSE,
          'ready' => TRUE,
          'includes' => ['autoload.php'],
          'vcs_uri' => 'uri',
          'vcs_path' => 'path',
        ],
        [
          'version' => '1.0',
          'name' => 'Module',
          'directory' => '\full\path',
          'enabled' => FALSE,
          'ready' => TRUE,
          'includes' => ['autoload.php'],
          'vcs_uri' => 'uri',
          'vcs_path' => 'path',
        ],
      ],
      [
        [
          'version' => '2.0',
          'name' => 'name',
          'enabled' => NULL,
          'ready' => FALSE,
          'includes' => [],
          'vcs_uri' => NULL,
          'vcs_path' => NULL,
        ],
        [
          'version' => '2.0',
          'name' => 'name',
          'directory' => 'name',
          'enabled' => FALSE,
          'ready' => FALSE,
          'includes' => [],
          'vcs_uri' => NULL,
          'vcs_path' => NULL,
        ],
      ],
      [
        [
          'name' => NULL,
          'enabled' => NULL,
          'vcs_uri' => NULL,
          'vcs_path' => NULL,
        ],
        [
          'version' => 'NotSet',
          'name' => 'NotSet',
          'directory' => 'NotSet',
          'enabled' => FALSE,
          'ready' => FALSE,
          'includes' => [],
          'vcs_uri' => NULL,
          'vcs_path' => NULL,
        ],
      ],
    ];
  }

  /**
   * Tests the constructor, setters, and getters.
   *
   * @param array $input
   *   Values to set.
   * @param array $output
   *   Values to get.
   *
   * @dataProvider data
   *   Data for testing setters and getters.
   */
  public function testSetAndGet($input, $output) {
    $module = new WipModule($input['name'], $input['vcs_uri'], $input['vcs_path']);
    if (array_key_exists('version', $input)) {
      $module->setVersion($input['version']);
    }
    if (array_key_exists('directory', $input)) {
      $module->setDirectory($input['directory']);
    }
    if (array_key_exists('includes', $input)) {
      $module->setIncludes($input['includes']);
    }
    if (array_key_exists('ready', $input)) {
      $module->setReady($input['ready']);
    }
    if ($input['enabled']) {
      $module->enable();
    } elseif (!$input['enabled']) {
      $module->disable();
    }

    $this->assertEquals($module->getName(), $output['name']);
    $this->assertEquals($module->getVcsUri(), $output['vcs_uri']);
    $this->assertEquals($module->getVcsPath(), $output['vcs_path']);
    $this->assertEquals($module->getVersion(), $output['version']);
    $this->assertEquals($module->getDirectory(), $output['directory']);
    $this->assertEquals($module->getIncludes(), $output['includes']);
    $this->assertEquals($module->isReady(), $output['ready']);
    $this->assertEquals($module->isEnabled(), $output['enabled']);
  }

  /**
   * Provides data for testTasks.
   *
   * @return array
   *   Set of task field values.
   */
  public function taskData() {
    return [
      [
        [
          [
            'name' => 'Module\Build',
            'group_name' => 'Build',
            'class_name' => 'Acquia\Module\Build',
            'log_level' => 'Alert',
          ],
          [
            'name' => 'Module\Canary',
            'group_name' => 'Canary',
            'class_name' => 'Acquia\Module\Canary',
            'log_level' => 'Info',
          ],
          [
            'name' => 'Module\Test',
            'group_name' => 'Test',
            'class_name' => 'Acquia\Module\Test',
            'log_level' => 'Debug',
          ],
        ],
      ],
    ];
  }

  /**
   * Tests functions around tasks associated with a WipModule.
   *
   * @param array $data
   *   An array of arrays of task field values.
   *
   * @dataProvider taskData
   *   Data for testing setters and getters.
   */
  public function testTasks($data) {
    /** @var WipModuleTaskInterface[] $tasks */
    $tasks = [];
    foreach ($data as $task_data) {
      $task = new WipModuleTask(
        'Module',
        $task_data['name'],
        $task_data['class_name'],
        $task_data['group_name'],
        $task_data['log_level']
      );
      $tasks[] = $task;
    }
    $module = new WipModule('Module');
    $module->setTasks($tasks);
    $this->assertEquals($module->getTasks(), $tasks);

    foreach ($tasks as $task) {
      $name = $task->getName();
      $result = $module->getTask($name);
      $this->assertEquals($result, $task);
    }
  }

  /**
   * Provides values that are not non-empty strings.
   *
   * @return array
   *   Test values.
   */
  public function invalidStrings() {
    return [
      ['', 1, NULL, TRUE, [], new \stdClass()],
      ['   ', 1, NULL, TRUE, [], new \stdClass()],
    ];
  }

  /**
   * Tests that invalid version values throw an exception.
   *
   * @param mixed $invalid
   *   Test values that are not non-empty strings.
   *
   * @dataProvider invalidStrings
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidVersion($invalid) {
    $module = new WipModule();
    $module->setVersion($invalid);
  }

  /**
   * Tests that invalid name values will throw an exception.
   *
   * @param mixed $invalid
   *   Test values that are not non-empty strings.
   *
   * @dataProvider invalidStrings
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidName($invalid) {
    $module = new WipModule();
    $module->setName($invalid);
  }

  /**
   * Tests that invalid directory values will throw an exception.
   *
   * @param mixed $invalid
   *   Test values that are not non-empty strings.
   *
   * @dataProvider invalidStrings
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidDirectory($invalid) {
    $module = new WipModule();
    $module->setDirectory($invalid);
  }

  /**
   * Provides values that are not booleans.
   *
   * @return array
   *   Test values.
   */
  public function invalidBooleans() {
    return [
      ['', 'not a boolean', [], 0, 1, 2, new \stdClass()],
    ];
  }

  /**
   * Tests that invalid ready values will throw an exception.
   *
   * @param mixed $invalid
   *   Test values that are not booleans.
   *
   * @dataProvider invalidBooleans
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidReady($invalid) {
    $module = new WipModule();
    $module->setReady($invalid);
  }

  /**
   * Provides values that are not string arrays.
   *
   * @return array
   *   Test values.
   */
  public function invalidIncludes() {
    return [
      ['', 1, new \stdClass()],
    ];
  }

  /**
   * Tests that invalid ready values will throw an exception.
   *
   * @param mixed $invalid
   *   Test values that are not string arrays.
   *
   * @dataProvider invalidIncludes
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidIncludes($invalid) {
    $module = new WipModule();
    $module->setIncludes($invalid);
  }

  /**
   * Provides values that are not WipModuleTask arrays.
   *
   * @return array
   *   Test values.
   */
  public function invalidTasks() {
    return [
      [1, 2, 3],
      [TRUE, TRUE, TRUE],
      [new \stdClass()],
    ];
  }

  /**
   * Tests that setTasks only accepts arrays of WipModuleTasks.
   *
   * @param mixed $invalid
   *   Test values that are not WipModuleTask arrays.
   *
   * @dataProvider invalidTasks
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidTasks($invalid) {
    $module = new WipModule();
    $module->setTasks($invalid);
  }

  /**
   * Tests that task instantiation accepts non-empty values..
   *
   * @param string $module_name
   *   The module name.
   * @param string $task_name
   *   The task name.
   * @param string $class_name
   *   The class name.
   * @param string $group_name
   *   The group name.
   * @param string $log_level
   *   The log level.
   * @param string $priority
   *   The priority.
   *
   * @dataProvider invalidTaskData
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidTaskInstantiation(
    $module_name,
    $task_name,
    $class_name,
    $group_name,
    $log_level,
    $priority
  ) {
    new WipModuleTask(
      $module_name,
      $task_name,
      $class_name,
      $group_name,
      $log_level,
      $priority
    );
  }

  /**
   * Provides values that are not valid for instantiating a WipModuleTask.
   *
   * @return array
   *   Test values.
   */
  public function invalidTaskData() {
    return [
      [1, 2, 3, 4, 5, 6],
      [TRUE, TRUE, TRUE, TRUE, TRUE, TRUE],
      ['', '', '', '', '', ''],
      [
        '   ',
        'name',
        'class_name',
        'group_name',
        'Info',
        'Medium',
      ],
      [
        'module_name',
        '   ',
        'class_name',
        'group_name',
        'Info',
        'Medium',
      ],
      [
        'module_name',
        'name',
        '   ',
        'group_name',
        'Info',
        'Medium',
      ],
      [
        'module_name',
        'name',
        'class_name',
        '   ',
        'Info',
        'Medium',
      ],
      [
        'module_name',
        'name',
        'class_name',
        'group_name',
        '    ',
        'Medium',
      ],

      [
        'module_name',
        'name',
        'class_name',
        'group_name',
        'Info',
        '   ',
      ],
    ];
  }

}
