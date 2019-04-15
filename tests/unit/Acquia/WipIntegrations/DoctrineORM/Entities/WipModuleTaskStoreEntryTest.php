<?php

namespace Acquia\WipIntegrations\DoctrineORM\Entities;

use Acquia\Wip\TaskPriority;
use Acquia\Wip\WipLogLevel;
use Acquia\Wip\WipModuleTask;
use Acquia\Wip\WipModuleTaskInterface;

/**
 * Tests the WipModuleTaskStoreEntry methods.
 */
class WipModuleTaskStoreEntryTest extends \PHPUnit_Framework_TestCase {

  /**
   * Tests that toWipModuleTask converts from an entry exactly.
   */
  public function testToWipModuleTask() {
    $entry = new WipModuleTaskStoreEntry();
    $entry->setName('Module\Task');
    $entry->setModuleName('Module');
    $entry->setClassName('Acquia\Module\Task');
    $entry->setGroupName('TaskGroup');
    $entry->setLogLevel('Debug');
    $entry->setPriority('High');

    /** @var WipModuleTaskInterface $module */
    $task = $entry->toWipModuleTask();

    $this->assertEquals($task->getName(), $entry->getName());
    $this->assertEquals($task->getModuleName(), $entry->getModuleName());
    $this->assertEquals($task->getClassName(), $entry->getClassName());
    $this->assertEquals($task->getGroupName(), $entry->getGroupName());
    $this->assertEquals($task->getLogLevel(), WipLogLevel::toInt($entry->getLogLevel()));
    $this->assertEquals($task->getPriority(), TaskPriority::toInt($entry->getPriority()));
  }

  /**
   * Tests that fromWipModuleTask converts to an entry exactly.
   */
  public function testFromWipModuleTask() {
    $task = new WipModuleTask('Module', 'Module\Task', 'Acquia\Module\Task', 'TaskGroup', 'Debug', 'High');

    $entry = WipModuleTaskStoreEntry::fromWipModuleTask($task);

    $this->assertEquals($entry->getName(), $task->getName());
    $this->assertEquals($entry->getModuleName(), $task->getModuleName());
    $this->assertEquals($entry->getClassName(), $task->getClassName());
    $this->assertEquals($entry->getGroupName(), $task->getGroupName());
    $this->assertEquals($entry->getLogLevel(), WipLogLevel::toString($task->getLogLevel()));
    $this->assertEquals($entry->getPriority(), TaskPriority::toString($task->getPriority()));
  }

}
