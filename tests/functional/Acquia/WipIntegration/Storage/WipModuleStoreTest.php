<?php

namespace Acquia\WipIntegrations\DoctrineORM\Entities;

use Acquia\WipIntegrations\DoctrineORM\WipModuleStore;
use Acquia\WipIntegrations\DoctrineORM\WipModuleTaskStore;
use Acquia\Wip\Storage\WipModuleStoreInterface;
use Acquia\Wip\Storage\WipModuleTaskStoreInterface;
use Acquia\Wip\WipModule;
use Acquia\Wip\WipModuleInterface;
use Acquia\Wip\WipModuleTask;

/**
 * Tests the WipModuleStore class.
 */
class WipModuleStoreTest extends \PHPUnit_Framework_TestCase {

  /**
   * An instance of WipModuleStore.
   *
   * @var WipModuleStoreInterface
   */
  private $wipModuleStore;

  /**
   * An instance of WipModuleStore.
   *
   * @var WipModuleTaskStoreInterface
   */
  private $wipModuleTaskStore;

  /**
   * Assigns an instance of WipModuleStore.
   */
  public function setUp() {
    parent::setUp();
    $this->wipModuleStore = new WipModuleStore();
    $this->wipModuleTaskStore = new WipModuleTaskStore();
  }

  /**
   * Cleans up the database tables.
   */
  public function tearDown() {
    $modules = $this->wipModuleStore->getByEnabled(TRUE);
    foreach ($modules as $module) {
      $this->deleteModule($module);
    }
    $modules = $this->wipModuleStore->getByEnabled(FALSE);
    foreach ($modules as $module) {
      $this->deleteModule($module);
    }
  }

  /**
   * Deletes the specified module.
   *
   * @param WipModuleInterface $module
   *   The module to delete.
   */
  private function deleteModule(WipModuleInterface $module) {
    $tasks = $module->getTasks();
    foreach ($tasks as $task) {
      $this->wipModuleTaskStore->delete($task->getName());
    }
    $this->wipModuleStore->delete($module->getName());
  }

  /**
   * Tests that save, get, and delete functions work as expected.
   */
  public function testSaveGetDelete() {
    $module = new WipModule();
    $module->setName('Canary');
    $module->setVcsUri('uri');
    $module->setVcsPath('path');

    $this->wipModuleStore->save($module);
    $retrieved = $this->wipModuleStore->get($module->getName());
    $this->assertEquals($module->getName(), $retrieved->getName());
    $this->assertEquals($module->getVcsUri(), $retrieved->getVcsUri());
    $this->assertEquals($module->getVcsPath(), $retrieved->getVcsPath());

    $this->wipModuleStore->delete($module->getName());
    $retrieved = $this->wipModuleStore->get($module->getName());
    $this->assertEmpty($retrieved);
  }

  /**
   * Tests that getByTaskName works as expected.
   */
  public function testGetByTaskName() {
    $module = new WipModule();
    $module->setName('TestModule');
    $module->setVcsUri('uri');
    $module->setVcsPath('path');

    $task = new WipModuleTask('TestModule', 'testModule', '/Acquia/Test', 'testGroup', 'DEBUG', 'HIGH');

    $this->wipModuleStore->save($module);
    $this->wipModuleTaskStore->save($task);

    $retrieved = $this->wipModuleStore->getByTaskName($task->getName());

    $this->assertEquals($module->getName(), $retrieved->getName());
    $this->assertEquals($module->getVcsUri(), $retrieved->getVcsUri());
    $this->assertEquals($module->getVcsPath(), $retrieved->getVcsPath());
    $this->wipModuleStore->delete('TestModule');
  }

  /**
   * Tests that getByEnabled works as expected.
   */
  public function testGetByEnabled() {
    $module_one = new WipModule();
    $module_one->setName('TestModule1');
    $module_one->setVcsUri('uri');
    $module_one->setVcsPath('path');
    $module_one->enable();

    $module_two = new WipModule();
    $module_two->setName('TestModule2');
    $module_two->setVcsUri('uri');
    $module_two->setVcsPath('path');
    $module_two->disable();

    $this->wipModuleStore->save($module_one);
    $this->wipModuleStore->save($module_two);

    /** @var WipModuleInterface[] $retrieved */
    $retrieved = $this->wipModuleStore->getByEnabled(TRUE);
    $this->assertEquals(1, count($retrieved));
    $this->assertEquals($module_one->getName(), $retrieved[0]->getName());
    $this->assertEquals($module_one->getVcsUri(), $retrieved[0]->getVcsUri());
    $this->assertEquals($module_one->getVcsPath(), $retrieved[0]->getVcsPath());

    $retrieved = $this->wipModuleStore->getByEnabled(FALSE);
    $this->assertEquals(1, count($retrieved));
    $this->assertEquals($module_two->getName(), $retrieved[0]->getName());
    $this->assertEquals($module_two->getVcsUri(), $retrieved[0]->getVcsUri());
    $this->assertEquals($module_two->getVcsPath(), $retrieved[0]->getVcsPath());
    $this->wipModuleStore->delete('TestModule1');
    $this->wipModuleStore->delete('TestModule2');
  }

  /**
   * Tests that getByReady works as expected.
   */
  public function testGetByReady() {
    $module_one = new WipModule();
    $module_one->setName('TestModule1');
    $module_one->setVcsUri('uri');
    $module_one->setVcsPath('path');
    $module_one->setReady(TRUE);

    $module_two = new WipModule();
    $module_two->setName('TestModule2');
    $module_two->setVcsUri('uri');
    $module_two->setVcsPath('path');
    $module_two->setReady(FALSE);

    $this->wipModuleStore->save($module_one);
    $this->wipModuleStore->save($module_two);

    /** @var WipModuleInterface[] $retrieved */
    $retrieved = $this->wipModuleStore->getByReady(TRUE);
    $this->assertEquals(1, count($retrieved));
    $this->assertEquals($module_one->getName(), $retrieved[0]->getName());
    $this->assertEquals($module_one->getVcsUri(), $retrieved[0]->getVcsUri());
    $this->assertEquals($module_one->getVcsPath(), $retrieved[0]->getVcsPath());

    $retrieved = $this->wipModuleStore->getByReady(FALSE);
    $this->assertEquals(1, count($retrieved));
    $this->assertEquals($module_two->getName(), $retrieved[0]->getName());
    $this->assertEquals($module_two->getVcsUri(), $retrieved[0]->getVcsUri());
    $this->assertEquals($module_two->getVcsPath(), $retrieved[0]->getVcsPath());
    $this->wipModuleStore->delete('TestModule1');
    $this->wipModuleStore->delete('TestModule2');
  }

}
