<?php

namespace Acquia\Wip\Test;

use Acquia\Wip\Storage\BasicWipModuleTaskStore;
use Acquia\Wip\Storage\WipModuleTaskStoreInterface;
use Acquia\Wip\WipModuleTask;

/**
 * Tests BasicWipModuleTaskStore class.
 */
class BasicWipModuleTaskStoreTest extends \PHPUnit_Framework_TestCase {

  /**
   * An instance of WipModuleTaskStore.
   *
   * @var WipModuleTaskStoreInterface
   */
  private $wipModuleTaskStore;

  /**
   * Assigns an instance of WipModuleTaskStore.
   */
  public function setUp() {
    parent::setUp();
    $this->wipModuleTaskStore = new BasicWipModuleTaskStore();
  }

  /**
   * Tests that save, get, and delete functions work as expected.
   */
  public function testSaveGetDelete() {
    $task = new WipModuleTask('Test', 'Test\testTask', 'Acquia\Test\TestTask', 'TestGroup', 'DEBUG', 'HIGH');

    $this->wipModuleTaskStore->save($task);
    $retrieved = $this->wipModuleTaskStore->get($task->getName());
    $this->assertEquals($task, $retrieved);

    $this->wipModuleTaskStore->delete($task->getName());
    $retrieved = $this->wipModuleTaskStore->get($task->getName());
    $this->assertNull($retrieved);
  }

  /**
   * Tests that getTasksByModuleName works as expected.
   */
  public function testGetTasksByModuleName() {
    $task_one = new WipModuleTask('Test', 'Test\buildTask', 'Acquia\Test\BuildTask', 'TestGroup', 'DEBUG', 'HIGH');
    $task_two = new WipModuleTask('Test', 'Test\testTask', 'Acquia\Test\TestTask', 'TestGroup', 'DEBUG', 'HIGH');

    $this->wipModuleTaskStore->save($task_one);
    $this->wipModuleTaskStore->save($task_two);

    $retrieved = $this->wipModuleTaskStore->getTasksByModuleName('Test');
    $this->assertEquals($retrieved, array($task_one, $task_two));

    $retrieved = $this->wipModuleTaskStore->getTasksByModuleName('Build');
    $this->assertEmpty($retrieved);
  }

}
