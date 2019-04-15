<?php

namespace Acquia\WipService\Console\Command;

use Acquia\WipService\Console\AbstractWipToolTest;
use Acquia\WipService\Console\Commands\ContainerStatusCommand;
use Acquia\Wip\Task;
use Acquia\Wip\TaskPriority;
use Acquia\Wip\TaskStatus;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Missing summary.
 */
class ContainerStatusCommandTest extends AbstractWipToolTest {

  /**
   * Tests that an error is thrown when used outside of a container.
   */
  public function testDisallowedOutsideContainer() {
    $containerized = getenv('WIP_CONTAINERIZED');
    putenv('WIP_CONTAINERIZED');
    $tester = $this->executeCommand(new ContainerStatusCommand(), 'container-status');
    $this->assertContains('This command is only for use inside a container.', $tester->getDisplay());
    $this->assertSame(1, $tester->getStatusCode());
    if ($containerized) {
      putenv('WIP_CONTAINERIZED=1');
    }
  }

  /**
   * Tests that an error and empty message is thrown when there are no tasks.
   */
  public function testNoResults() {
    $containerized = getenv('WIP_CONTAINERIZED');
    putenv('WIP_CONTAINERIZED=1');
    $tester = $this->executeCommand(new ContainerStatusCommand(), 'container-status');
    if (!$containerized) {
      putenv('WIP_CONTAINERIZED');
    }

    $this->assertContains('Task not found.', $tester->getDisplay());
    $this->assertSame(1, $tester->getStatusCode());
  }

  /**
   * Tests that the task is well-formed and returned in JSON format.
   */
  public function testResultData() {
    $class_name = 'Acquia\\Wip\\Objects\\ClassName';
    $exit_message = '';
    $exit_status = 0;
    $group_name = 'GroupName';
    $id = 1;
    $name = 'name';
    $priority = TaskPriority::HIGH;
    $status = TaskStatus::PROCESSING;
    $time = time();

    $task = new Task();
    $task->setWipClassName($class_name); // Needs to happen early.
    $task->setClaimedTimestamp($time);
    $task->setCompletedTimestamp($time);
    $task->setCreatedTimestamp($time);
    $task->setExitMessage($exit_message);
    $task->setExitStatus($exit_status);
    $task->setGroupName($group_name);
    $task->setId($id);
    $task->setName($name);
    $task->setPriority($priority);
    $task->setStartTimestamp($time);
    $task->setStatus($status);
    $task->setTimeout($time);
    $task->setWakeTimestamp($time);

    $mock = $this->getMockBuilder('Acquia\WipIntegrations\DoctrineORM\WipPoolStore')
      ->setMethods(array('get'))
      ->getMock();
    $mock->expects($this->once())->method('get')->will($this->returnValue($task));
    $command = new ContainerStatusCommand();
    $command->swapDependency('acquia.wip.storage.wippool', $mock);

    $containerized = getenv('WIP_CONTAINERIZED');
    putenv('WIP_CONTAINERIZED=1');
    $tester = $this->executeCommand($command, 'container-status', array());
    if (!$containerized) {
      putenv('WIP_CONTAINERIZED');
    }

    $data = json_decode($tester->getDisplay());
    $this->assertSame($class_name, $data->class_name);
    $this->assertSame($exit_message, $data->exit_message);
    $this->assertSame($exit_status, $data->exit_status);
    $this->assertSame($group_name, $data->group_name);
    $this->assertSame($id, $data->id);
    $this->assertSame($name, $data->name);
    $this->assertSame($priority, $data->priority);
    $this->assertSame($status, $data->run_status);
    $this->assertSame($time, $data->claimed_timestamp);
    $this->assertSame($time, $data->completed_timestamp);
    $this->assertSame($time, $data->created_timestamp);
    $this->assertSame($time, $data->start_timestamp);
    $this->assertSame($time, $data->timeout);
    $this->assertSame($time, $data->wake_timestamp);

    $this->assertSame(0, $tester->getStatusCode());
  }

}
