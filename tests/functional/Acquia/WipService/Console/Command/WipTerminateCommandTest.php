<?php

namespace Acquia\WipService\Console\Command;

use Acquia\WipService\Console\AbstractWipCtlTest;
use Acquia\WipService\Console\Commands\WipTerminateCommand;
use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\Runtime\WipPool;
use Acquia\Wip\TaskExitStatus;
use Acquia\Wip\WipFactory;
use Ramsey\Uuid\Uuid;

/**
 * Tests that WipTerminateCommandTest behaves as expected.
 */
class WipTerminateCommandTest extends AbstractWipCtlTest {

  /**
   * A basic wip object.
   *
   * @var BasicWip
   */
  private $basicWip;

  /**
   * The wip pool.
   *
   * @var WipPool
   */
  private $pool;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->basicWip = new BasicWip();
    $this->basicWip->setUuid((string) Uuid::uuid4());
    $this->pool = new WipPool();

    WipFactory::setConfigPath('config/config.factory.test.cfg');
  }

  /**
   * Test to ensure terminating works as excepted.
   */
  public function testTerminate() {
    $this->basicWip->setGroup('Buildstesps');
    $task = $this->pool->addTask($this->basicWip);
    $task_id = $task->getId();
    $parameters = ['task_id' => $task_id];
    $stdout = $this->executeCommand(new WipTerminateCommand(), 'terminate', $parameters)->getDisplay();
    $this->assertContains(sprintf('Task %d has been marked for termination.', $task_id), $stdout);

    // Reload the task before updating. Testing a TERMINATED task.
    $task = $this->pool->getTask($task_id);
    $task->setExitStatus(TaskExitStatus::TERMINATED);
    $this->pool->saveTask($task);
    $stdout = $this->executeCommand(new WipTerminateCommand(), 'terminate', $parameters)->getDisplay();
    $this->assertContains(sprintf('Task %d has already finished and cannot be terminated.', $task_id), $stdout);

    // Reload the task before updating. Testing a COMPLETED task.
    $task = $this->pool->getTask($task_id);
    $task->setExitStatus(TaskExitStatus::COMPLETED);
    $this->pool->saveTask($task);
    $stdout = $this->executeCommand(new WipTerminateCommand(), 'terminate', $parameters)->getDisplay();
    $this->assertContains(sprintf('Task %d has already finished and cannot be terminated.', $task_id), $stdout);
    $stdout = $this->executeCommand(new WipTerminateCommand(), 'terminate', ['task_id' => 9999])->getDisplay();
    $this->assertContains('The task does not exist', $stdout);
  }

}
