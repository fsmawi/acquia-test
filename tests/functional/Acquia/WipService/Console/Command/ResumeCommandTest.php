<?php

namespace Acquia\WipService\Console\Command;

use Acquia\WipIntegrations\DoctrineORM\WipPoolStore;
use Acquia\WipService\Console\AbstractWipCtlTest;
use Acquia\WipService\Console\Commands\ResumeCommand;
use Acquia\Wip\Runtime\WipPoolController;
use Acquia\Wip\Runtime\WipPoolControllerInterface;
use Acquia\Wip\Storage\WipPoolStoreInterface;
use Acquia\Wip\TaskInterface;
use Acquia\Wip\TaskStatus;
use Acquia\Wip\Test\PrivateStable\Storage\WipPoolStoreTest;
use Acquia\Wip\WipFactory;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests that ResumeCommand behaves as expected.
 */
class ResumeCommandTest extends AbstractWipCtlTest {

  /**
   * The Wip pool controller instance.
   *
   * @var WipPoolControllerInterface
   */
  private $controller;

  /**
   * The Wip pool storage instance.
   *
   * @var WipPoolStoreInterface
   */
  private $wipPoolStore;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->registerTestingConfig();
    $this->wipPoolStore = WipFactory::getObject('acquia.wip.storage.wippool');
    $this->controller = WipFactory::getObject('acquia.wip.pool.controller');
  }

  /**
   * Generates tasks in progress.
   *
   * @return TaskInterface[]
   *   An array of generated task entities.
   */
  private function generateTasksInProgress() {
    for ($i = 0; $i < 5; $i++) {
      $task = WipPoolStoreTest::generateTask();
      $task->setStatus(TaskStatus::PROCESSING);
      $this->wipPoolStore->save($task);
    }
    return $this->wipPoolStore->load();
  }

  /**
   * Tests that the --group and --task options cannot be used together.
   *
   * @expectedException \InvalidArgumentException
   *
   * @expectedExceptionMessageRegexp /The "--groups" and "--tasks" options cannot be used together/
   */
  public function testResumeGroupTaskOptionCollision() {
    $arguments = array(
      '--groups' => TRUE,
      '--tasks' => TRUE,
    );
    $this->executeCommand(new ResumeCommand(), 'resume', $arguments);
  }

  /**
   * Tests that global resume works as expected.
   */
  public function testGlobalResume() {
    $tasks_in_progress = $this->generateTasksInProgress();
    $task_ids = WipPoolStore::getTaskIds($tasks_in_progress);

    foreach (array(TRUE, FALSE) as $success) {
      $mock = $this->getMockBuilder('Acquia\Wip\Runtime\WipPoolController')
        ->setMethods(array('resumeGlobal'))
        ->getMock();
      $mock->expects($this->once())
        ->method('resumeGlobal')
        ->will($this->returnValue($success));
      $command = new ResumeCommand();
      $command->swapDependency(WipPoolController::RESOURCE_NAME, $mock);
      $tester = $this->executeCommand($command, 'resume');

      $expected = $success ? 'Global pause is disabled.' : 'Global resume failed.';
      $this->assertContains($expected, $tester->getDisplay());
      $this->assertSame((int) !$success, $tester->getStatusCode());
      $this->assertContains(
        sprintf('Tasks are currently in progress: %s.', implode(', ', $task_ids)),
        $tester->getDisplay()
      );
    }
  }

  /**
   * Tests that the --groups and --tasks options both require a value.
   *
   * @param string $option
   *   The name of the option.
   * @param string|null $value
   *   The option value.
   * @param string $expected
   *   The expected error message.
   *
   * @dataProvider valueRequiredOptionProvider
   */
  public function testResumeRequiredOptionValue($option, $value, $expected) {
    $this->setExpectedExceptionRegExp(
      '\InvalidArgumentException',
      sprintf('/The "%s" option %s\./', $option, $expected)
    );

    $arguments = array($option => $value);
    $this->executeCommand(new ResumeCommand(), 'resume', $arguments);
  }

  /**
   * Provides options with empty values.
   *
   * @return array
   *    A multidimensional array of parameters.
   */
  public function valueRequiredOptionProvider() {
    return array(
      array('--groups', NULL, 'requires a value'),
      array('--groups', '', 'must not be empty'),
      array('--tasks', NULL, 'requires a value'),
      array('--tasks', '', 'must not be empty'),
    );
  }

  /**
   * Tests that groups can be successfully resumed.
   *
   * @param string $groups
   *   A comma-separated list of group names to resume.
   * @param array $starting
   *   A comma-separated list of group names to start as paused.
   * @param array $ending
   *   A comma-separated list of group names to expect to still be paused.
   *
   * @dataProvider resumeGroupsProvider
   */
  public function testResumeGroupsGlobal($groups, array $starting, array $ending) {
    $tasks_in_progress = $this->generateTasksInProgress();
    $task_ids = WipPoolStore::getTaskIds($tasks_in_progress);
    $this->controller->hardPauseGroups($starting);

    $arguments = array('--groups' => $groups);
    $tester = $this->executeCommand(new ResumeCommand(), 'resume', $arguments);

    $this->assertSame(0, $tester->getStatusCode());
    if (!empty($ending)) {
      $this->assertContains(
        sprintf('Groups currently paused: %s.', implode(', ', $ending)),
        $tester->getDisplay()
      );
    }
    $this->assertContains(
      sprintf('Tasks are currently in progress: %s.', implode(', ', $task_ids)),
      $tester->getDisplay()
    );
    $this->assertContains(
      sprintf('Successfully resumed groups: %s', implode(', ', $groups)),
      $tester->getDisplay()
    );
  }

  /**
   * Provides parameters for testing group resume.
   *
   * @return array
   *   A multidimensional array of parameters.
   */
  public function resumeGroupsProvider() {
    return array(
      array('Group1', array('Group1', 'Group2'), array('Group2')),
      array('Group2', array('Group1', 'Group2'), array('Group1')),
      array('Group1,Group2', array('Group1', 'Group2'), array()),
      array('Group1,Group2,Group3', array('Group1'), array()),
    );
  }

  /**
   * Tests that resuming groups sequentially displays the expected output.
   */
  public function testResumeGroupsSequential() {
    $groups = array('Group1', 'Group2', 'Group3', 'Group4', 'Group5');
    $this->controller->hardPauseGroups($groups);
    $expected_groups = $groups;
    foreach ($groups as $group) {
      array_shift($expected_groups);
      $arguments = array('--groups' => $group);
      $tester = $this->executeCommand(new ResumeCommand(), 'resume', $arguments);

      $this->assertSame(0, $tester->getStatusCode());
      if (!empty($expected_groups)) {
        $this->assertContains(
          sprintf('Groups currently paused: %s.', implode(', ', $expected_groups)),
          $tester->getDisplay()
        );
      }
      $this->assertContains(
        sprintf('Successfully resumed groups: %s', implode(', ', $group)),
        $tester->getDisplay()
      );
    }
  }

  /**
   * Tests that the correct output is displayed when group resume fails.
   *
   * @param string[] $groups
   *   A list of group names to resume.
   *
   * @dataProvider resumeGroupsFailureProvider
   */
  public function testResumeGroupsFailure($groups) {
    $tasks_in_progress = $this->generateTasksInProgress();
    $task_ids = WipPoolStore::getTaskIds($tasks_in_progress);
    $this->controller->hardPauseGroups($groups);

    $mock = $this->getMockBuilder('Acquia\Wip\Runtime\WipPoolController')
      ->setMethods(array('resumeGroups'))
      ->getMock();
    $mock->expects($this->any())
      ->method('resumeGroups')
      ->will($this->returnValue(FALSE));
    $command = new ResumeCommand();
    $command->swapDependency(WipPoolController::RESOURCE_NAME, $mock);

    $arguments = array('--groups' => implode(',', $groups));
    $tester = $this->executeCommand($command, 'resume', $arguments);

    $this->assertSame(1, $tester->getStatusCode());
    $this->assertContains(
      sprintf('Groups currently paused: %s.', implode(', ', $groups)),
      $tester->getDisplay()
    );
    $message = sprintf('Failed to resume groups: %s.', implode(', ', $groups));
    $this->assertContains($message, $tester->getDisplay());
    $this->assertContains(
      sprintf('Tasks are currently in progress: %s.', implode(', ', $task_ids)),
      $tester->getDisplay()
    );
  }

  /**
   * Provides parameters for testing group resume failure.
   *
   * @return array
   *   A multidimensional array of parameters.
   */
  public function resumeGroupsFailureProvider() {
    return array(
      array(array('Group1')),
      array(array('Group1', 'Group2')),
      array(array('Group1', 'Group2', 'Group3')),
    );
  }

  /**
   * Tests that tasks are successfully resumed when they exist.
   *
   * @param string $tasks
   *   A comma-separated list of task IDs.
   *
   * @dataProvider resumeTasksProvider
   */
  public function testResumeTasks($tasks) {
    $tasks_in_progress = $this->generateTasksInProgress();
    $task_ids = WipPoolStore::getTaskIds($tasks_in_progress);

    $arguments = array('--tasks' => $tasks);
    $tester = $this->executeCommand(new ResumeCommand(), 'resume', $arguments);

    $this->assertSame(0, $tester->getStatusCode());
    $this->assertContains(
      sprintf('Resumed tasks: %s.', implode(', ', explode(',', $tasks))),
      $tester->getDisplay()
    );
    $this->assertContains(
      sprintf('Tasks are currently in progress: %s.', implode(', ', $task_ids)),
      $tester->getDisplay()
    );
  }

  /**
   * Provides comma-separated lists of task IDs.
   *
   * @return array
   *   A multidimensional array of parameters.
   */
  public function resumeTasksProvider() {
    return array(
      array('1'),
      array('2'),
      array('1,2'),
      array('1,2,3'),
      array('2,3,4'),
      array('1,2,3,4,5'),
    );
  }

  /**
   * Tests expected output is shown when tasks are resumed sequentially.
   */
  public function testResumeTasksSequential() {
    $tasks_in_progress = $this->generateTasksInProgress();
    $task_ids = WipPoolStore::getTaskIds($tasks_in_progress);
    foreach ($task_ids as $id) {
      $this->controller->pauseTask($id);
    }

    $tasks = $task_ids;
    foreach ($task_ids as $task_id) {
      array_shift($tasks);
      $arguments = array('--tasks' => (string) $task_id);
      $tester = $this->executeCommand(new ResumeCommand(), 'resume', $arguments);

      $this->assertSame(0, $tester->getStatusCode());
      $this->assertContains(
        sprintf('Resumed tasks: %s.', $task_id),
        $tester->getDisplay()
      );
      if (!empty($tasks)) {
        $this->assertContains(
          sprintf('Tasks currently paused: %s.', implode(', ', $tasks)),
          $tester->getDisplay()
        );
      }
      $this->assertContains(
        sprintf('Tasks are currently in progress: %s.', implode(', ', $task_ids)),
        $tester->getDisplay()
      );
    }
  }

  /**
   * Tests that the expected output is present when tasks are not found.
   *
   * @param string $tasks
   *   A comma-separated list of task IDs.
   *
   * @dataProvider taskIdsProvider
   */
  public function testResumeTasksNotFound($tasks) {
    $mock = $this->getMockBuilder('Acquia\Wip\Runtime\WipPoolController')
      ->setMethods(array('resumeTask'))
      ->getMock();
    $mock->expects($this->atLeastOnce())
      ->method('resumeTask')
      ->will($this->throwException(new \DomainException()));
    $command = new ResumeCommand();
    $command->swapDependency(WipPoolController::RESOURCE_NAME, $mock);

    $arguments = array('--tasks' => $tasks);
    $tester = $this->executeCommand($command, 'resume', $arguments);

    $this->assertSame(1, $tester->getStatusCode());
    $this->assertContains(
      sprintf('Not found: %s.', implode(', ', explode(',', $tasks))),
      $tester->getDisplay()
    );
  }

  /**
   * Tests that the expected exception is thrown when tasks fail to resume.
   *
   * @param string $tasks
   *   A comma-separated list of task IDs.
   *
   * @dataProvider taskIdsProvider
   */
  public function testResumeTasksFailed($tasks) {
    $mock = $this->getMockBuilder('Acquia\Wip\Runtime\WipPoolController')
      ->setMethods(array('resumeTask'))
      ->getMock();
    $mock->expects($this->atLeastOnce())
      ->method('resumeTask')
      ->will($this->returnValue(FALSE));
    $command = new ResumeCommand();
    $command->swapDependency(WipPoolController::RESOURCE_NAME, $mock);

    $arguments = array('--tasks' => $tasks);
    $tester = $this->executeCommand($command, 'resume', $arguments);

    $this->assertSame(1, $tester->getStatusCode());
    $this->assertContains(
      sprintf('Failed to resume: %s.', implode(', ', explode(',', $tasks))),
      $tester->getDisplay()
    );
  }

  /**
   * Provides comma-separated lists of task IDs.
   *
   * @return array
   *   A multidimensional array of parameters.
   */
  public function taskIdsProvider() {
    return array(
      array('234'),
      array('5,10'),
      array('2,12,999,696'),
    );
  }

  /**
   * Tests that invalid task IDs are not accepted.
   *
   * @param string $tasks
   *   A comma-separated list of task IDs containing invalid elements.
   * @param string $invalid
   *   A comma-separates list of the invalid task IDs.
   *
   * @dataProvider invalidTaskIdsProvider
   */
  public function testResumeTasksInvalidTaskIds($tasks, $invalid) {
    $this->generateTasksInProgress();

    $arguments = array('--tasks' => $tasks);
    $tester = $this->executeCommand(new ResumeCommand(), 'resume', $arguments);

    $this->assertSame(1, $tester->getStatusCode());
    $this->assertContains(
      sprintf('Not found: %s.', $invalid),
      $tester->getDisplay()
    );
  }

  /**
   * Provides tasks parameters with invalid items.
   *
   * @return array
   *   A multidimensional array of parameters.
   */
  public function invalidTaskIdsProvider() {
    return array(
      array('foo', 'foo'),
      array('1,5,bar,baz', 'bar, baz'),
      array('2,-42', '-42'),
      array('3,271.3834', '271.3834'),
    );
  }

}
