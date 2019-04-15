<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\Exception\InvalidOperationException;
use Acquia\Wip\Exception\NoTaskException;
use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\Runtime\WipPool;
use Acquia\Wip\Storage\BasicWipPoolStore;
use Acquia\Wip\Storage\BasicWipStore;
use Acquia\Wip\Task;
use Acquia\Wip\TaskExitStatus;
use Acquia\Wip\TaskInterface;
use Acquia\Wip\TaskPriority;
use Acquia\Wip\TaskStatus;
use Acquia\Wip\WipFactory;

/**
 * Tests WipPool behavior.
 */
class WipPoolTest extends \PHPUnit_Framework_TestCase {

  /**
   * The WipPool instance.
   *
   * @var WipPool
   */
  private $wipPool;

  /**
   * The WipPoolStore.
   *
   * @var BasicWipPoolStore
   */
  private $storage;

  /**
   * The WipStore.
   *
   * @var BasicWipStore
   */
  private $objectStorage;

  /**
   * Implicitly tests the constructor - no need to test again elsewhere.
   */
  public function setup() {
    $this->wipPool = WipFactory::getObject('acquia.wip.pool');
    $this->storage = WipFactory::getObject('acquia.wip.storage.wippool');
    $this->storage->initialize();
    $this->wipPool->dependencyManager->swapDependency('acquia.wip.storage.wippool', $this->storage);
    $this->objectStorage = WipFactory::getObject('acquia.wip.storage.wip');
    $this->objectStorage->initialize();
    $this->wipPool->dependencyManager->swapDependency('acquia.wip.storage.wip', $this->objectStorage);
  }

  /**
   * Provides all TaskExitStatus indicating completion.
   *
   * @return array
   *   The array of values provided.
   */
  public function finishedTaskStatusProvider() {
    return array(
      array(TaskExitStatus::COMPLETED),
      array(TaskExitStatus::ERROR_USER),
      array(TaskExitStatus::ERROR_SYSTEM),
      array(TaskExitStatus::TERMINATED),
      array(TaskExitStatus::WARNING),
    );
  }

  /**
   * Tests the count method.
   */
  public function testCount() {
    /** @var Task[] $tasks */
    $tasks = array();
    for ($i = 0; $i < 7; $i++) {
      $wip = new BasicWip();
      $wip->setUuid((string) \Ramsey\Uuid\Uuid::uuid4());
      $wip_iterator = WipFactory::getObject('acquia.wip.iterator');
      $wip_iterator->initialize($wip);
      $task = new Task();
      $task->setUuid($wip->getUuid());
      $task->setWipIterator($wip_iterator);
      $this->storage->save($task);
      $tasks[] = $task;
    }
    $tasks[0]->setStatus(TaskStatus::RESTARTED);
    $tasks[1]->setStatus(TaskStatus::RESTARTED);
    $tasks[1]->setParentId(99);
    $tasks[2]->setGroupName('mygroup');
    $tasks[3]->setPause(TRUE);
    $tasks[4]->setPriority(TaskPriority::CRITICAL);
    $uuid = (string) \Ramsey\Uuid\Uuid::uuid4();
    $tasks[5]->setUuid($uuid);
    $tasks[6]->setIsTerminating(TRUE);
    foreach ($tasks as $task) {
      $this->storage->save($task);
    }

    $this->assertEquals(7, $this->storage->count());
    $this->assertEquals(2, $this->storage->count(TaskStatus::RESTARTED));
    $this->assertEquals(1, $this->storage->count(NULL, 99));
    $this->assertEquals(1, $this->storage->count(NULL, NULL, 'mygroup'));
    $this->assertEquals(1, $this->storage->count(NULL, NULL, NULL, TRUE));
    $this->assertEquals(1, $this->storage->count(NULL, NULL, NULL, NULL, TaskPriority::CRITICAL));
    $this->assertEquals(1, $this->storage->count(NULL, NULL, NULL, NULL, NULL, $uuid));
    $this->assertEquals(0, $this->storage->count(NULL, NULL, NULL, NULL, NULL, (string) \Ramsey\Uuid\Uuid::uuid4()));
    $this->assertEquals(0, $this->storage->count(NULL, 99, NULL, NULL, NULL, (string) \Ramsey\Uuid\Uuid::uuid4()));
    $this->assertEquals(1, $this->storage->count(NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, TRUE));
    $this->storage->initialize();
  }

  /**
   * Tests the getNextTasks method.
   */
  public function testGetNextTasks() {
    // Ensure that we're getting an exception when there is no waiting tasks.
    $exception = FALSE;
    try {
      $this->wipPool->getNextTasks();
    } catch (NoTaskException $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception);

    $wip = new BasicWip();
    $wip_iterator = WipFactory::getObject('acquia.wip.iterator');
    $wip_iterator->initialize($wip);
    $task = new Task();
    $task->setWipIterator($wip_iterator);
    $this->storage->save($task);
    $this->objectStorage->save($task->getId(), $wip_iterator);
    // When WipPool grabs the next task, then the task's loadWipIterator will be
    // fired and it will get a WipStore implementation on its own. Since during
    // testing the storage implementations are not singleton, this would not let
    // find the Task's related Wip iterator and would throw an exception.
    // Therefore we switch the task's WipStore's implementation to be the same
    // as the test's.
    $task->dependencyManager->swapDependency('acquia.wip.storage.wip', $this->objectStorage);

    $next_tasks = $this->wipPool->getNextTasks();
    $next_task = reset($next_tasks);

    $this->assertInstanceOf('Acquia\Wip\Task', $next_task);
  }

  /**
   * Tests that adding a task results in proper storage calls.
   */
  public function testAddTask() {
    $uuid = (string) \Ramsey\Uuid\Uuid::uuid4();
    $group_name = 'group_name';

    // Mock to detect calling the onAdd hook.
    $wip = $this->getMock(
      '\Acquia\Wip\Implementation\BasicWip',
      array('onAdd', 'getUuid', 'getGroup')
    );
    $wip->expects($this->once())
      ->method('onAdd');
    $wip->expects($this->once())
      ->method('getUuid')
      ->willReturn($uuid);
    $wip->expects($this->atLeastOnce())
      ->method('getGroup')
      ->willReturn($group_name);

    $iterator = WipFactory::getObject('acquia.wip.iterator');
    $iterator->initialize($wip);
    $final_task = new Task();
    $final_task->setUuid($uuid);
    $final_task->setStatus(TaskStatus::NOT_STARTED);
    $final_task->setWipIterator($iterator);

    $mock_storage = $this->getMock('\Acquia\Wip\Storage\BasicWipPoolStore');
    $mock_storage->expects($this->exactly(2))
      ->method('save')
      ->withConsecutive(
        array($this->anything()),
        array($this->taskObjectEqualTo($final_task))
      );

    $mock_object_storage = $this->getMock('\Acquia\Wip\Storage\BasicWipStore', array('save'));
    $mock_object_storage->expects($this->once())
      ->method('save')
      ->with($this->anything(), $this->equalTo($iterator));

    $this->wipPool->dependencyManager->swapDependency('acquia.wip.storage.wippool', $mock_storage);
    $this->wipPool->dependencyManager->swapDependency('acquia.wip.storage.wip', $mock_object_storage);

    $final_task->setCreatedTimestamp(time());
    $task = $this->wipPool->addTask($wip);
    $this->assertEquals(TaskStatus::NOT_STARTED, $task->getStatus());
    $this->assertEquals($group_name, $task->getGroupName());
  }

  /**
   * Tests that adding with a specified group name overrides the Wip group name.
   */
  public function testAddWithGroup() {
    // Mock to detect calling the onAdd hook.
    $wip = $this->getMock(
      '\Acquia\Wip\Implementation\BasicWip',
      array('onAdd', 'getUuid', 'getGroup')
    );
    $wip->expects($this->atLeastOnce())
      ->method('getUuid')
      ->willReturn((string) \Ramsey\Uuid\Uuid::uuid4());
    $wip->expects($this->any())
      ->method('getGroup')
      ->willReturn('default_group_name');

    $iterator = WipFactory::getObject('acquia.wip.iterator');
    $iterator->initialize($wip);
    $test_group = 'test_group';
    $final_task = new Task();
    $final_task->setStatus(TaskStatus::NOT_STARTED);
    $final_task->setUuid($wip->getUuid());
    $final_task->setWipIterator($iterator);
    $final_task->setGroupName($test_group);

    $mock_storage = $this->getMock('\Acquia\Wip\Storage\BasicWipPoolStore');

    $mock_storage->expects($this->exactly(2))
      ->method('save')
      ->withConsecutive(
        array($this->anything()),
        array($this->taskObjectEqualTo($final_task))
      );

    $mock_object_storage = $this->getMock('\Acquia\Wip\Storage\BasicWipStore', array('save'));
    $mock_object_storage->expects($this->once())
      ->method('save')
      ->with($this->anything(), $this->equalTo($iterator));

    $this->wipPool->dependencyManager->swapDependency('acquia.wip.storage.wippool', $mock_storage);
    $this->wipPool->dependencyManager->swapDependency('acquia.wip.storage.wip', $mock_object_storage);

    $task = $this->wipPool->addTask($wip, new TaskPriority(), $test_group);
    $this->assertEquals(TaskStatus::NOT_STARTED, $task->getStatus());
    $this->assertEquals($test_group, $task->getGroupName());
  }

  /**
   * Tests adding with a specified client_job_id.
   */
  public function testAddWithClientJobId() {
    // Mock to detect calling the onAdd hook.
    $wip = $this->getMock(
      '\Acquia\Wip\Implementation\BasicWip',
      array('onAdd', 'getUuid', 'getGroup')
    );
    $wip->expects($this->atLeastOnce())
      ->method('getUuid')
      ->willReturn((string) \Ramsey\Uuid\Uuid::uuid4());
    $wip->expects($this->any())
      ->method('getGroup')
      ->willReturn('default_group_name');


    $iterator = WipFactory::getObject('acquia.wip.iterator');
    $iterator->initialize($wip);
    $test_group = 'test_group';
    $test_client_job_id = 'my-job-id';
    $final_task = new Task();
    $final_task->setStatus(TaskStatus::NOT_STARTED);
    $final_task->setUuid($wip->getUuid());
    $final_task->setWipIterator($iterator);
    $final_task->setGroupName($test_group);
    $final_task->setClientJobId($test_client_job_id);

    $mock_storage = $this->getMock('\Acquia\Wip\Storage\BasicWipPoolStore');

    $mock_storage->expects($this->exactly(2))
      ->method('save')
      ->withConsecutive(
        array($this->anything()),
        array($this->taskObjectEqualTo($final_task))
      );

    $mock_object_storage = $this->getMock('\Acquia\Wip\Storage\BasicWipStore', array('save'));
    $mock_object_storage->expects($this->once())
      ->method('save')
      ->with($this->anything(), $this->equalTo($iterator));

    $this->wipPool->dependencyManager->swapDependency('acquia.wip.storage.wippool', $mock_storage);
    $this->wipPool->dependencyManager->swapDependency('acquia.wip.storage.wip', $mock_object_storage);

    $task = $this->wipPool->addTask($wip, new TaskPriority(), $test_group, NULL, $test_client_job_id);
    $this->assertEquals(TaskStatus::NOT_STARTED, $task->getStatus());
    $this->assertEquals($test_client_job_id, $task->getClientJobId());
  }

  /**
   * Constructs a constraint for checking that a task is well-formed.
   *
   * Tasks contain timestamp fields that can cause tests to fail, if, by chance,
   * they subsequently get set in a different second to when they were initially
   * set, the object comparison will fail. This method compares an object's
   * individual attribute values to those of the passed-in task instance, while
   * crucially skipping timestamp fields.
   *
   * @param TaskInterface $task
   *   The task object to check.
   *
   * @return \PHPUnit_Framework_Constraint_And
   *   The constraint.
   */
  private function taskObjectEqualTo(TaskInterface $task) {
    return $this->logicalAnd(
      $this->attributeEqualTo('id', $task->getId()),
      $this->attributeEqualTo('iterator', $task->getWipIterator()),
      $this->attributeEqualTo('workId', $task->getWorkId()),
      $this->attributeEqualTo('parentId', $task->getParentId()),
      $this->attributeEqualTo('name', $task->getName()),
      $this->attributeEqualTo('groupName', $task->getGroupName()),
      $this->attributeEqualTo('runStatus', $task->getStatus()),
      $this->attributeEqualTo('exitStatus', $task->getExitStatus()),
      $this->attributeEqualTo('priority', $task->getPriority()),
      $this->attributeEqualTo('leaseTime', $task->getLeaseTime()),
      $this->attributeEqualTo('isPaused', $task->isPaused()),
      $this->attributeEqualTo('exitMessage', $task->getExitMessage()),
      $this->attributeEqualTo('resourceId', $task->getResourceId()),
      $this->attributeEqualTo('uuid', $task->getUuid()),
      $this->attributeEqualTo('className', $task->getWipClassName()),
      $this->attributeEqualTo('createdTimestamp', $task->getCreatedTimestamp(), 1),
      $this->attributeEqualTo('claimedTimestamp', $task->getClaimedTimestamp(), 1),
      $this->attributeEqualTo('completedTimestamp', $task->getCompletedTimestamp(), 1),
      $this->attributeEqualTo('startTimestamp', $task->getStartTimestamp(), 1),
      $this->attributeEqualTo('wakeTimestamp', $task->getWakeTimestamp(), 1),
      $this->attributeEqualTo('isTerminating', $task->isTerminating())
    );
  }

  /**
   * Tests restart.
   */
  public function testRestart() {
    // Mock to detect calling the onRestart hook.  There should be exactly 2
    // times this gets successfully called in this test.
    $wip = $this->getMock(
      '\Acquia\Wip\Implementation\BasicWip',
      array('onRestart', 'log', 'getUuid')
    );
    $wip->expects($this->exactly(2))
      ->method('onRestart');
    $wip->expects($this->any())
      ->method('log');
    $wip->expects($this->once())
      ->method('getUuid')
      ->willReturn((string) \Ramsey\Uuid\Uuid::uuid4());

    $task = $this->wipPool->addTask($wip);

    // Check that a task cannot be restarted if not in completed status.
    $exception = FALSE;
    try {
      $this->wipPool->restartTask($task);
    } catch (InvalidOperationException $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception);

    $task->setStatus(TaskStatus::COMPLETE);
    $this->storage->save($task);
    $this->wipPool->restartTask($task);

    $this->assertEquals(TaskStatus::RESTARTED, $task->getStatus());
    $this->assertEquals(0, $task->getWakeTimestamp());

    // Test that the task got persisted to storage.
    $stored_task = $this->storage->get($task->getId());

    $this->assertEquals(TaskStatus::RESTARTED, $stored_task->getStatus());

    $stored_iterator = $this->objectStorage->get($stored_task->getId());

    // Simulate some progress.
    $stored_iterator->moveToNextState();
    $this->objectStorage->save($task->getId(), $stored_iterator);

    $this->assertEquals('start', $stored_iterator->getCurrentState());

    $task->setStatus(TaskStatus::COMPLETE);
    $this->storage->save($task);
    $this->wipPool->restartTask($task);

    $stored_iterator = $this->objectStorage->get($stored_task->getId());
    $this->assertInternalType('null', $stored_iterator->getCurrentState());
  }

  /**
   * Tests that the exit message for an unfinished task is not stored.
   */
  public function testSaveUnfinishedTask() {
    $unfinished_task = new Task();
    $unfinished_task->setId(123);
    $unfinished_task->setExitStatus(TaskExitStatus::NOT_FINISHED);
    $unfinished_task->setExitMessage('This message should not be saved.');
    $this->wipPool->saveTask($unfinished_task);
    $this->assertEmpty($this->wipPool->getTask(123)->getExitMessage());
  }

  /**
   * Tests that the exit message is saved for all completion status values.
   *
   * @dataProvider finishedTaskStatusProvider
   */
  public function testSaveFinishedTask($completed_levels) {
    $message = 'This message should be saved in completed tasks.';
    
    $finished_task = new Task();
    $finished_task->setId(123);
    $finished_task->setExitStatus($completed_levels);
    $finished_task->setExitMessage($message);
    $this->wipPool->saveTask($finished_task);
    $this->assertNotEmpty($this->wipPool->getTask(123)->getExitMessage());
    $this->assertContains($message, $this->wipPool->getTask(123)->getExitMessage());
  }

}
