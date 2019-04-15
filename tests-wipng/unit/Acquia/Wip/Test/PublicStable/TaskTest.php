<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\Exception\NoObjectException;
use Acquia\Wip\Exception\NoTaskException;
use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\Storage\WipPoolStoreInterface;
use Acquia\Wip\Storage\WipStoreInterface;
use Acquia\Wip\Task;
use Acquia\Wip\TaskExitStatus;
use Acquia\Wip\TaskPriority;
use Acquia\Wip\TaskStatus;
use Acquia\Wip\WipFactory;

/**
 * Tests the Task class.
 */
class TaskTest extends \PHPUnit_Framework_TestCase {

  /**
   * The Task instance.
   *
   * @var Task
   */
  private $task;

  /**
   * The WipPoolStore instance.
   *
   * @var WipPoolStoreInterface
   */
  private $poolStorage;

  /**
   * The WipStore instance.
   *
   * @var WipStoreInterface
   */
  private $objectStorage;

  /**
   * Sets up for each test.
   */
  public function setup() {
    $this->task = new Task();
    $this->poolStorage = WipFactory::getObject('acquia.wip.storage.wippool');
    $this->objectStorage = WipFactory::getObject('acquia.wip.storage.wip');
    $this->task->dependencyManager->swapDependency('acquia.wip.storage.wippool', $this->poolStorage);
    $this->task->dependencyManager->swapDependency('acquia.wip.storage.wip', $this->objectStorage);
  }

  /**
   * Data provider for exit status.
   *
   * @return array
   *   The set of valid exit status values.
   */
  public function completedExitStatusProvider() {
    return array(
      array(TaskExitStatus::WARNING),
      array(TaskExitStatus::COMPLETED),
      array(TaskExitStatus::ERROR_USER),
      array(TaskExitStatus::ERROR_SYSTEM),
      array(TaskExitStatus::TERMINATED),
    );
  }

  /**
   * Tests loading a task when the Wip object could not be deserialized.
   */
  public function testLoadWipException() {
    // Check that we throw when the Task does not yet an ID.
    $exception = FALSE;
    try {
      $this->task->loadWipIterator();
    } catch (NoTaskException $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception);

    $id = rand(1000000, 2000000);

    $this->task->setId($id);
    $this->poolStorage->save($this->task);

    // Check that we throw when an object is not found, but the task is found.
    $exception = FALSE;
    try {
      $this->task->loadWipIterator();
    } catch (NoObjectException $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception);
    $this->assertEquals($id, $e->getTaskId());

    // Finally set up the object correctly and test we can now load the task.
    $iterator = WipFactory::getObject('acquia.wip.iterator');
    $wip = new BasicWip();
    $iterator->initialize($wip);

    $this->objectStorage->save($this->task->getId(), $iterator);
    $this->task->loadWipIterator();
    $this->assertInstanceOf('Acquia\Wip\StateTableIteratorInterface', $this->task->getWipIterator());
    $this->assertEquals($id, $this->task->getId());
  }

  /**
   * Tests loading a Wip object.
   */
  public function testLoadWip() {
    $id = rand(1000000, 2000000);

    $this->task->setId($id);
    $this->poolStorage->save($this->task);

    // Check that we throw when an object is not found, but the task is found.
    $exception = FALSE;
    try {
      $this->task->loadWipIterator();
    } catch (NoObjectException $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception);

    // Finally set up the object correctly and test we can now load the task.
    $iterator = WipFactory::getObject('acquia.wip.iterator');
    $wip = new BasicWip();
    $iterator->initialize($wip);

    $this->objectStorage->save($this->task->getId(), $iterator);
    $this->task->loadWipIterator();
    $this->assertInstanceOf('Acquia\Wip\StateTableIteratorInterface', $this->task->getWipIterator());
    $this->assertEquals($id, $this->task->getId());
  }

  /**
   * Tests that the iterator can only be set into the task once.
   *
   * @expectedException \Acquia\Wip\Exception\TaskOverwriteException
   */
  public function testReassignWipIterator() {
    $this->task->setId(rand());
    $wip = new BasicWip();
    $wip_iterator = WipFactory::getObject('acquia.wip.iterator');
    $wip_iterator->initialize($wip);
    $this->task->setWipIterator($wip_iterator);
  }

  /**
   * Tests that setting the iterator fails if the iterator has no Wip object.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetEmptyWipIterator() {
    $wip_iterator = WipFactory::getObject('acquia.wip.iterator');
    $this->task->setWipIterator($wip_iterator);
  }

  /**
   * Tests setting the iterator works properly.
   */
  public function testSetWipIterator() {
    $wip = new BasicWip();
    $wip_iterator = WipFactory::getObject('acquia.wip.iterator');
    $wip_iterator->initialize($wip);
    $this->task->setWipIterator($wip_iterator);
    $this->assertEquals($wip->getGroup(), $this->task->getGroupName());
    $this->assertEquals($wip->getTitle(), $this->task->getName());
    $this->assertEquals(get_class($wip), $this->task->getWipClassName());

    $wip_iterator_check = $this->task->getWipIterator();
    $this->assertNotEmpty($wip_iterator_check);
  }

  /**
   * Tests setting the ID.
   */
  public function testIdMutator() {
    $id = rand();
    $this->task->setId($id);
    $this->assertEquals($id, $this->task->getId());
  }

  /**
   * Tests setting the ID with improper type fails.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidIdMutator() {
    $this->task->setId('invalid');
  }

  /**
   * Tests the default parent ID.
   */
  public function testDefaultParentId() {
    $this->assertEquals(0, $this->task->getParentId());
  }

  /**
   * Tests the setParent method.
   */
  public function testParentIdMutator() {
    $id = rand();
    $this->task->setParentId($id);
    $this->assertEquals($id, $this->task->getParentId());
  }

  /**
   * Test setting the parent ID with improper type fails.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidParentIdMutator() {
    $this->task->setParentId('invalid');
  }

  /**
   * Tests the setWorkId method.
   */
  public function testSetWorkId() {
    $this->assertEquals($this->task->getWorkId(), '');
    $work_id = 'custom-work-id';
    $this->task->setWorkId($work_id);
    $this->assertEquals($this->task->getWorkId(), $work_id);
  }

  /**
   * Verifies that setting a work ID with the wrong type fails.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidWorkId() {
    $this->task->setWorkId('');
  }

  /**
   * Tests that the default timeout is the expected value.
   */
  public function testDefaultTimeout() {
    $this->assertEquals(30, $this->task->getTimeout());
  }

  /**
   * Tests the setTimeout method.
   */
  public function testSetTimeout() {
    for ($i = 0; $i < 10; ++$i) {
      $timeout = rand(1, 30);
      $this->task->setTimeout($timeout);
      $this->assertEquals($timeout, $this->task->getTimeout());
    }
  }

  /**
   * Tests behavior when setting a bad timeout value.
   */
  public function testBadTimeout() {
    $exception = FALSE;
    try {
      $this->task->setTimeout(-1);
    } catch (\InvalidArgumentException $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception);


    $exception = FALSE;
    try {
      $this->task->setTimeout('nothing');
    } catch (\InvalidArgumentException $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception);
  }

  /**
   * Tests the initial start timestamp value.
   */
  public function testInitialStartTime() {
    $initial_value = $this->task->getStartTimestamp();
    $this->assertEquals(0, $initial_value);
  }

  /**
   * Tests setting the start timeout.
   */
  public function testSetStartTime() {
    $now = time();
    $this->task->setStartTimestamp($now);
    $this->assertEquals($now, $this->task->getStartTimestamp());
  }

  /**
   * Tests setting an invalid start timeout fails.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidStartTime() {
    $this->task->setStartTimestamp('invalid');
  }

  /**
   * Tests the default status of a task.
   */
  public function testTaskInitialStatus() {
    $this->assertEquals(TaskStatus::NOT_READY, $this->task->getStatus());
  }

  /**
   * Tests the getStatus method.
   */
  public function testGetTaskStatus() {
    $status = TaskStatus::WAITING;
    $this->task->setStatus($status);
    $this->assertEquals($status, $this->task->getStatus());
  }

  /**
   * Tests illegal status behavior.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testIllegalStatusValue() {
    $status = 15;
    $this->task->setStatus($status);
  }

  /**
   * Tests the default exit status.
   */
  public function testDefaultExitStatus() {
    $this->assertEquals(TaskExitStatus::NOT_FINISHED, $this->task->getExitStatus());
  }

  /**
   * Tests the getExitStatus method.
   */
  public function testGetExitStatus() {
    $status = TaskExitStatus::ERROR_USER;
    $this->task->setExitStatus($status);
    $this->assertEquals($status, $this->task->getExitStatus());
  }

  /**
   * Tests behavior when setting exit status to an illegal value.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testIllegalExitStatusValue() {
    $this->task->setExitStatus('invalid');
  }

  /**
   * Tests the default priority.
   */
  public function testDefaultPriority() {
    $this->assertTrue(TaskPriority::isValid($this->task->getPriority()));
  }

  /**
   * Tests priority.
   */
  public function testPriority() {
    $priority_value = TaskPriority::CRITICAL;
    $this->task->setPriority($priority_value);
    $this->assertEquals($priority_value, $this->task->getPriority());
  }

  /**
   * Tests setting an invalid priority fails.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testPriorityException() {
    $this->task->setPriority(NULL);
  }

  /**
   * Tests the default group value.
   */
  public function testDefaultGroup() {
    $this->assertEquals('', $this->task->getGroupName());
  }

  /**
   * Tests setting the group.
   */
  public function testGroup() {
    $group = 'testgroup';
    $this->task->setGroupName($group);
    $this->assertEquals($group, $this->task->getGroupName());
  }

  /**
   * Tests behavior when setting an invalid group.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidGroup() {
    $this->task->setGroupName('    ');
  }

  /**
   * Tests setting the name.
   */
  public function testSetTaskName() {
    $this->task->setName('test name');
    $this->assertEquals('test name', $this->task->getName());
  }

  /**
   * Tests setting the name with an invalid value.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidName() {
    $this->task->setName('    ');
  }

  /**
   * Tests the default wake time.
   */
  public function testDefaultWakeTime() {
    $this->assertEquals(0, $this->task->getWakeTimestamp());
  }

  /**
   * Tests setting the wake time.
   */
  public function testSetWakeTime() {
    $timestamp = rand();
    $this->task->setWakeTimestamp($timestamp);
    $this->assertEquals($timestamp, $this->task->getWakeTimestamp());
  }

  /**
   * Tests the behavior when setting an invalid wake time.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidWakeTime() {
    $this->task->setWakeTimestamp('invalid');
  }

  /**
   * Tests the setCreatedTimestamp method.
   */
  public function testSetCreatedTime() {
    $timestamp = rand();
    $this->task->setCreatedTimestamp($timestamp);
    $this->assertEquals($timestamp, $this->task->getCreatedTimestamp());
  }

  /**
   * Tests the default created time.
   */
  public function testDefaultCreatedTime() {
    $this->assertTrue(is_int($this->task->getCreatedTimestamp()));
    $this->assertGreaterThan(0, $this->task->getCreatedTimestamp());
  }

  /**
   * Tests setting an invalid created time.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidCreatedTime() {
    $this->task->setCreatedTimestamp('invalid');
  }

  /**
   * Tests setting the completed time.
   */
  public function testSetCompletedTime() {
    $timestamp = rand();
    $this->task->setCompletedTimestamp($timestamp);
    $this->assertEquals($timestamp, $this->task->getCompletedTimestamp());
  }

  /**
   * Tests the default completed time.
   */
  public function testDefaultCompletedTime() {
    $this->assertEquals(0, $this->task->getCompletedTimestamp());
  }

  /**
   * Tests behavior when setting an invalid completed time.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidCompletedTime() {
    $this->task->setCompletedTimestamp('invalid');
  }

  /**
   * Tests setting the claimed time.
   */
  public function testSetClaimTime() {
    $timestamp = rand();
    $this->task->setClaimedTimestamp($timestamp);
    $this->assertEquals($timestamp, $this->task->getClaimedTimestamp());
  }

  /**
   * Tests the default claimed time.
   */
  public function testDefaultClaimedTime() {
    $this->assertEquals(0, $this->task->getClaimedTimestamp());
  }

  /**
   * Tests the behavior when setting an invalid claimed time.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidClaimedTime() {
    $this->task->setClaimedTimestamp('invalid');
  }

  /**
   * Tests setting the lease time.
   */
  public function testSetLeaseTime() {
    $timestamp = rand();
    $this->task->setLeaseTime($timestamp);
    $this->assertEquals($timestamp, $this->task->getLeaseTime());
  }

  /**
   * Tests the default lease time.
   */
  public function testDefaultLeaseTime() {
    $this->assertEquals(180, $this->task->getLeaseTime());
  }

  /**
   * Tests the behavior when setting an invalid lease time.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidLeaseTime() {
    $this->task->setLeaseTime('invalid');
  }

  /**
   * Tests the default paused value.
   */
  public function testDefaultPauseFlag() {
    $this->assertFalse($this->task->isPaused());
  }

  /**
   * Tests setting the paused flag.
   */
  public function testPauseFlag() {
    $this->task->setPause(TRUE);
    $this->assertTrue($this->task->isPaused());
    $this->task->setPause(FALSE);
    $this->assertFalse($this->task->isPaused());
  }

  /**
   * Tests setting the delegated flag.
   */
  public function testDelegatedFlag() {
    // Default not delegated.
    $this->assertFalse($this->task->isDelegated());
    $this->task->setDelegated(TRUE);
    $this->assertTrue($this->task->isDelegated());
    $this->task->setDelegated(FALSE);
    $this->assertFalse($this->task->isDelegated());
  }

  /**
   * Tests the behavior when setting an invalid delegated value.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testBadDelegatedArg() {
    $this->task->setDelegated('');
  }

  /**
   * Tests that a task ID can be cleared.
   */
  public function testClearId() {
    $this->task->setId(rand(1, PHP_INT_MAX));
    $this->assertNotEmpty($this->task->getId());
    $this->task->clearId();
    $this->assertEmpty($this->task->getId());
  }

  /**
   * Tests behavior when setting an invalid pause value.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidPause() {
    $this->task->setPause('invalid');
  }

  /**
   * Tests setting the exit message.
   */
  public function testSetExitMessage() {
    $this->task->setExitMessage('test stuff');
    $this->assertEquals('test stuff', $this->task->getExitMessage());
  }

  /**
   * Test the default exit message.
   */
  public function testDefaultExitMessage() {
    $this->assertEquals('', $this->task->getExitMessage());
  }

  /**
   * Tests the behavior when setting an invalid exit message.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidExitMessage() {
    $this->task->setExitMessage(NULL);
  }

  /**
   * Tests setting the resource ID.
   */
  public function testSetResourceId() {
    $this->task->setResourceId('test resource id');
    $this->assertEquals('test resource id', $this->task->getResourceId());
  }

  /**
   * Tests the default resource ID.
   */
  public function testDefaultResourceId() {
    $this->assertEquals('', $this->task->getResourceId());
  }

  /**
   * Tests the behavior when setting an invalid resource ID.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidResourceId() {
    $this->task->setResourceId(NULL);
  }

  /**
   * Tests setting the user ID.
   */
  public function testSetUuid() {
    $uuid = (string) \Ramsey\Uuid\Uuid::uuid4();
    $this->task->setUuid($uuid);
    $this->assertEquals($uuid, $this->task->getUuid());
  }

  /**
   * Tests the behavior when setting an invalid user ID.
   *
   * @expectedException \InvalidArgumentException
   *
   * @dataProvider invalidUuidProvider
   */
  public function testInvalidUuid($uuid) {
    $this->task->setUuid($uuid);
  }

  /**
   * A user data provider.
   *
   * @return array
   *   An array of invalid user ID values.
   */
  public function invalidUuidProvider() {
    return array(
      array(1),
      array(-10),
      array(1.1),
      array(TRUE),
      array(array(1)),
    );
  }

  /**
   * Tests setting the class name.
   */
  public function testSetClassName() {
    $this->task->setWipClassName('test class name');
    $this->assertEquals('test class name', $this->task->getWipClassName());
  }

  /**
   * Tests the behavior when setting an invalid class name.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetInvalidClassName() {
    $this->task->setWipClassName(NULL);
    $this->assertEquals('test class name', $this->task->getWipClassName());
  }

  /**
   * Tests that the class name cannot be changed once it is set.
   *
   * @expectedException \Acquia\Wip\Exception\TaskOverwriteException
   */
  public function testSetInmutableClassName() {
    $wip = new BasicWip();
    $wip_iterator = WipFactory::getObject('acquia.wip.iterator');
    $wip_iterator->initialize($wip);
    $this->task->setWipIterator($wip_iterator);
    $this->task->setWipClassname('test class name');
  }

  /**
   * Tests whether the task is considered complete.
   *
   * @dataProvider completedExitStatusProvider
   */
  public function testIsCompleted($status) {
    $this->task->setExitStatus($status);
    $this->assertTrue($this->task->isCompleted());
  }

  /**
   * Tests whether the task is considered complete.
   */
  public function testIsNotCompleted() {
    $this->task->setExitStatus(TaskExitStatus::NOT_FINISHED);
    $this->assertFalse($this->task->isCompleted());
  }

  /**
   * Tests getting and setting the Pipeline job ID that corresponds to a task.
   */
  public function testSetAndGetPipelineJobId() {
    $this->assertEquals('', $this->task->getClientJobId());
    $this->task->setClientJobId('1234');
    $this->assertEquals('1234', $this->task->getClientJobId());
  }

}
