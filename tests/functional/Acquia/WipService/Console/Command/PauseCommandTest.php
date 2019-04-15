<?php

namespace Acquia\WipService\Console\Command;

use Acquia\WipIntegrations\DoctrineORM\WipPoolStore;
use Acquia\WipService\Console\AbstractWipCtlTest;
use Acquia\WipService\Console\Commands\PauseCommand;
use Acquia\Wip\Runtime\WipPoolController;
use Acquia\Wip\State\GlobalPause;
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
 * Tests that PauseCommand behaves as expected.
 */
class PauseCommandTest extends AbstractWipCtlTest {

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
  public function testPauseGroupTaskOptionCollision() {
    $arguments = array(
      '--groups' => TRUE,
      '--tasks' => TRUE,
    );
    $this->executeCommand(new PauseCommand(), 'pause', $arguments);
  }

  /**
   * Tests that global pause works as expected.
   *
   * @param string $pause_type
   *   The type of pause, either "hard_pause" or "soft_pause".
   * @param string $human_readable
   *   The human-readable type of pause expected in the console output.
   * @param array $arguments
   *   The command line arguments and options.
   * @param string $is_method
   *   The method on WipPoolController that checks whether hard or soft pause is
   *   enabled.
   * @param string $set_method
   *   The method on WipPoolController that sets the paused state and returns
   *   the list of tasks currently in progress.
   *
   * @dataProvider globalPauseProvider
   */
  public function testGlobalPause($pause_type, $human_readable, array $arguments, $is_method, $set_method) {
    $tasks_in_progress = $this->generateTasksInProgress();
    $task_ids = WipPoolStore::getTaskIds($tasks_in_progress);

    foreach (array(TRUE, FALSE) as $success) {
      $mock = $this->getMockBuilder('Acquia\Wip\Runtime\WipPoolController')
        ->setMethods(array($is_method, $set_method))
        ->getMock();
      $mock->expects($this->any())
        ->method($set_method)
        ->will($this->returnValue($tasks_in_progress));
      $mock->expects($this->any())
        ->method($is_method)
        ->will($this->returnValue($success));
      $command = new PauseCommand();
      $command->swapDependency(WipPoolController::RESOURCE_NAME, $mock);
      $tester = $this->executeCommand($command, 'pause', $arguments);

      $message = $success ?
        sprintf('Global %s is enabled', $human_readable) :
        sprintf('Failed to apply global %s', $human_readable);
      $this->assertContains($message, $tester->getDisplay());
      $this->assertContains(
        sprintf('Tasks are currently in progress: %s.', implode(', ', $task_ids)),
        $tester->getDisplay()
      );
    }
  }

  /**
   * Provides parameters for testing global pause.
   *
   * @return array
   *   A multidimensional array of parameters.
   */
  public function globalPauseProvider() {
    return array(
      array(
        GlobalPause::HARD_PAUSE,
        'pause',
        array(),
        'isHardPausedGlobal',
        'hardPauseGlobal',
      ),
      array(
        GlobalPause::SOFT_PAUSE,
        'soft pause',
        array('--soft' => TRUE),
        'isSoftPausedGlobal',
        'softPauseGlobal',
      ),
    );
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
  public function testPauseRequiredOptionValue($option, $value, $expected) {
    $this->setExpectedExceptionRegExp(
      '\InvalidArgumentException',
      sprintf('/The "%s" option %s\./', $option, $expected)
    );

    $arguments = array($option => $value);
    $this->executeCommand(new PauseCommand(), 'pause', $arguments);
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
   * Tests that groups can be successfully paused.
   *
   * @param string $groups
   *   A comma-separated list of group names.
   * @param string $human_readable
   *   The human-readable pause type expected in the output.
   * @param array $arguments
   *   An array of additional arguments to pass to the command.
   *
   * @dataProvider pauseGroupsProvider
   */
  public function testPauseGroups($groups, $human_readable, array $arguments) {
    $tasks_in_progress = $this->generateTasksInProgress();
    $task_ids = WipPoolStore::getTaskIds($tasks_in_progress);

    $arguments = array('--groups' => $groups) + $arguments;
    $tester = $this->executeCommand(new PauseCommand(), 'pause', $arguments);

    $this->assertSame(0, $tester->getStatusCode());
    $this->assertContains(
      sprintf('Groups currently paused: %s.', implode(', ', explode(',', $groups))),
      $tester->getDisplay()
    );
    $this->assertContains(
      sprintf('Tasks are currently in progress: %s.', implode(', ', $task_ids)),
      $tester->getDisplay()
    );
    $this->assertContains(
      sprintf('Successfully applied %s to groups: %s', $human_readable, implode(', ', $groups)),
      $tester->getDisplay()
    );
  }

  /**
   * Provides parameters for testing group pause.
   *
   * @return array
   *   A multidimensional array of parameters.
   */
  public function pauseGroupsProvider() {
    return array(
      array('Group1', 'pause', array()),
      array('Group1', 'soft pause', array('--soft' => TRUE)),
      array('Group1,Group2', 'pause', array()),
      array('Group1,Group2', 'soft pause', array('--soft' => TRUE)),
      array('Group1,Group2,Group3', 'pause', array()),
      array('Group1,Group2,Group3', 'soft pause', array('--soft' => TRUE)),
    );
  }

  /**
   * Tests that pausing groups sequentially displays the expected output.
   */
  public function testPauseGroupsSequential() {
    $groups = array('Group1', 'Group2', 'Group3');
    $expected_groups = array();
    foreach ($groups as $group) {
      $expected_groups[] = $group;
      $arguments = array('--groups' => $group);
      $tester = $this->executeCommand(new PauseCommand(), 'pause', $arguments);

      $this->assertSame(0, $tester->getStatusCode());
      $this->assertContains(
        sprintf('Groups currently paused: %s.', implode(', ', $expected_groups)),
        $tester->getDisplay()
      );
      $this->assertContains(
        sprintf('Successfully applied pause to groups: %s', implode(', ', $group)),
        $tester->getDisplay()
      );
    }
  }

  /**
   * Tests that the correct output is displayed when group pause fails.
   *
   * @param string[] $groups
   *   A list of group names to pause.
   * @param string[] $paused_groups
   *   A list of groups that will be paused during the operation.
   * @param string[] $expected
   *   A list of groups that we expect to be paused during the operation.
   * @param string $get_method
   *   The mocked getter method we expect to return the paused groups.
   * @param string $human_readable
   *   The human-readable pause type expected in the output.
   * @param array $arguments
   *   An array of additional arguments to pass to the command.
   *
   * @dataProvider pauseGroupsFailureProvider
   */
  public function testPauseGroupsFailure($groups, $paused_groups, $expected, $get_method, $human_readable, $arguments) {
    $mock = $this->getMockBuilder('Acquia\Wip\Runtime\WipPoolController')
      ->setMethods(array($get_method))
      ->getMock();
    $mock->expects($this->any())
      ->method($get_method)
      ->will($this->returnValue($paused_groups));
    $command = new PauseCommand();
    $command->swapDependency(WipPoolController::RESOURCE_NAME, $mock);

    $arguments = array('--groups' => implode(',', $groups)) + $arguments;
    $tester = $this->executeCommand($command, 'pause', $arguments);

    $message = sprintf('Failed to %s groups: %s.', $human_readable, implode(', ', $expected));
    $this->assertContains($message, $tester->getDisplay());
  }

  /**
   * Provides various parameters for testing group pause failure.
   *
   * @return array
   *   A multidimensional array of parameters.
   */
  public function pauseGroupsFailureProvider() {
    return array(
      array(
        array('Group1'),
        array(),
        array('Group1'),
        'getHardPausedGroups',
        'pause',
        array(),
      ),
      array(
        array('Group1'),
        array(),
        array('Group1'),
        'getSoftPausedGroups',
        'soft pause',
        array('--soft' => TRUE),
      ),
      array(
        array('Group1', 'Group2'),
        array('Group1'),
        array('Group2'),
        'getHardPausedGroups',
        'pause',
        array(),
      ),
      array(
        array('Group1', 'Group2'),
        array('Group1'),
        array('Group2'),
        'getSoftPausedGroups',
        'soft pause',
        array('--soft' => TRUE),
      ),
    );
  }

  /**
   * Tests that tasks are successfully paused when they exist.
   *
   * @param string $tasks
   *   A comma-separated list of task IDs.
   *
   * @dataProvider pauseTasksProvider
   */
  public function testPauseTasks($tasks) {
    $tasks_in_progress = $this->generateTasksInProgress();
    $task_ids = WipPoolStore::getTaskIds($tasks_in_progress);

    $arguments = array('--tasks' => $tasks);
    $tester = $this->executeCommand(new PauseCommand(), 'pause', $arguments);

    $this->assertSame(0, $tester->getStatusCode());
    $this->assertContains(
      sprintf('Paused successfully: %s.', implode(', ', explode(',', $tasks))),
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
  public function pauseTasksProvider() {
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
   * Tests that the expected output is shown when tasks are added sequentially.
   */
  public function testPauseTasksSequential() {
    $tasks_in_progress = $this->generateTasksInProgress();
    $task_ids = WipPoolStore::getTaskIds($tasks_in_progress);

    $tasks = array();
    foreach ($task_ids as $task_id) {
      $tasks[] = $task_id;
      $arguments = array('--tasks' => (string) $task_id);
      $tester = $this->executeCommand(new PauseCommand(), 'pause', $arguments);

      $this->assertSame(0, $tester->getStatusCode());
      $this->assertContains(
        sprintf('Paused successfully: %s.', $task_id),
        $tester->getDisplay()
      );
      $this->assertContains(
        sprintf('Tasks currently paused: %s.', implode(', ', $tasks)),
        $tester->getDisplay()
      );
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
  public function testPauseTasksNotFound($tasks) {
    $mock = $this->getMockBuilder('Acquia\Wip\Runtime\WipPoolController')
      ->setMethods(array('pauseTask'))
      ->getMock();
    $mock->expects($this->any())
      ->method('pauseTask')
      ->will($this->throwException(new \DomainException()));
    $command = new PauseCommand();
    $command->swapDependency(WipPoolController::RESOURCE_NAME, $mock);

    $arguments = array('--tasks' => $tasks);
    $tester = $this->executeCommand($command, 'pause', $arguments);

    $this->assertSame(1, $tester->getStatusCode());
    $this->assertContains(
      sprintf('Not found: %s.', implode(', ', explode(',', $tasks))),
      $tester->getDisplay()
    );
  }

  /**
   * Tests that the expected exception is thrown when tasks fail to be paused.
   *
   * @param string $tasks
   *   A comma-separated list of task IDs.
   *
   * @dataProvider taskIdsProvider
   */
  public function testPauseTasksFailed($tasks) {
    $mock = $this->getMockBuilder('Acquia\Wip\Runtime\WipPoolController')
      ->setMethods(array('pauseTask'))
      ->getMock();
    $mock->expects($this->any())
      ->method('pauseTask')
      ->will($this->returnValue(FALSE));
    $command = new PauseCommand();
    $command->swapDependency(WipPoolController::RESOURCE_NAME, $mock);

    $arguments = array('--tasks' => $tasks);
    $tester = $this->executeCommand($command, 'pause', $arguments);

    $this->assertSame(1, $tester->getStatusCode());
    $this->assertContains(
      sprintf('Failed to pause: %s.', implode(', ', explode(',', $tasks))),
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
  public function testPauseTasksInvalidTaskIds($tasks, $invalid) {
    $this->generateTasksInProgress();

    $arguments = array('--tasks' => $tasks);
    $tester = $this->executeCommand(new PauseCommand(), 'pause', $arguments);

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
