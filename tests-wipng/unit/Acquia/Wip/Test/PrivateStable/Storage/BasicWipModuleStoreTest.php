<?php

namespace Acquia\Wip\Test;

use Acquia\Wip\Storage\BasicWipModuleStore;
use Acquia\Wip\Storage\BasicWipModuleTaskStore;
use Acquia\Wip\Storage\WipModuleStoreInterface;
use Acquia\Wip\Storage\WipModuleTaskStoreInterface;
use Acquia\Wip\WipModule;
use Acquia\Wip\WipModuleTask;

/**
 * Tests the BasicWipModuleStore class.
 */
class BasicWipModuleStoreTest extends \PHPUnit_Framework_TestCase {

  /**
   * An instance of BasicWipModuleStore.
   *
   * @var WipModuleStoreInterface
   */
  private $basicWipModuleStore;

  /**
   * An instance of BasicWipModuleStore.
   *
   * @var WipModuleTaskStoreInterface
   */
  private $basicWipModuleTaskStore;

  /**
   * Assigns an instance of BasicWipModuleStore.
   */
  public function setUp() {
    parent::setUp();
    $this->basicWipModuleStore = new BasicWipModuleStore();
    $this->basicWipModuleTaskStore = new BasicWipModuleTaskStore();
  }

  /**
   * Tests that save, get, and delete functions work as expected.
   */
  public function testSaveGetDelete() {
    $module = new WipModule();
    $module->setName('Canary');

    $this->basicWipModuleStore->delete($module->getName());
    $this->basicWipModuleStore->save($module);
    $retrieved = $this->basicWipModuleStore->get($module->getName());
    $this->assertEquals($module, $retrieved);
    $this->basicWipModuleStore->delete($module->getName());
    $retrieved = $this->basicWipModuleStore->get($module->getName());

    $this->assertNull($retrieved);
  }

  /**
   * Tests that getByTaskName works as expected.
   */
  public function testGetByTaskName() {
    $module = new WipModule();
    $module->setName('TestModule');
    $task = new WipModuleTask('TestModule', 'testModule', '/Acquia/Test', 'testGroup', 'DEBUG', 'HIGH');
    $module->setTasks(array($task));

    $this->basicWipModuleStore->delete($module->getName());
    $this->basicWipModuleStore->save($module);
    $this->basicWipModuleTaskStore->delete($task->getName());
    $this->basicWipModuleTaskStore->save($task);

    $retrieved = $this->basicWipModuleStore->getByTaskName($task->getName());

    $this->assertEquals($retrieved, $module);
  }

  /**
   * Tests that getByEnabled works as expected.
   */
  public function testGetByEnabled() {
    $module_one = new WipModule();
    $module_one->setName('TestModule1');
    $module_one->enable();

    $module_two = new WipModule();
    $module_two->setName('TestModule2');
    $module_two->disable();

    $this->basicWipModuleStore->delete($module_one->getName());
    $this->basicWipModuleStore->save($module_one);
    $this->basicWipModuleStore->delete($module_two->getName());
    $this->basicWipModuleStore->save($module_two);

    $retrieved = $this->basicWipModuleStore->getByEnabled(TRUE);
    $this->assertEquals($retrieved, [$module_one]);

    $retrieved = $this->basicWipModuleStore->getByEnabled(FALSE);
    $this->assertEquals($retrieved, [$module_two]);
  }

  /**
   * Tests that getByReady works as expected.
   */
  public function testGetByReady() {
    $module_one = new WipModule();
    $module_one->setName('TestModule1');
    $module_one->setReady(TRUE);

    $module_two = new WipModule();
    $module_two->setName('TestModule2');
    $module_two->setReady(FALSE);

    $this->basicWipModuleStore->delete($module_one->getName());
    $this->basicWipModuleStore->save($module_one);
    $this->basicWipModuleStore->delete($module_two->getName());
    $this->basicWipModuleStore->save($module_two);

    $retrieved = $this->basicWipModuleStore->getByReady(TRUE);
    $this->assertEquals($retrieved, [$module_one]);

    $retrieved = $this->basicWipModuleStore->getByReady(FALSE);
    $this->assertEquals($retrieved, [$module_two]);
  }

}
