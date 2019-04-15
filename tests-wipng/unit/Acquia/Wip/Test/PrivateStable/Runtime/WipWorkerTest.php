<?php

namespace Acquia\Wip\Test\PrivateStable\Runtime;

use Acquia\Wip\Exception\NoTaskException;
use Acquia\Wip\ExitMessage;
use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\Implementation\IteratorResult;
use Acquia\Wip\Implementation\SqliteWipLogStore;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\IteratorStatus;
use Acquia\Wip\Runtime\Server;
use Acquia\Wip\Runtime\Thread;
use Acquia\Wip\Runtime\WipPool;
use Acquia\Wip\Runtime\WipWorker;
use Acquia\Wip\Storage\BasicServerStore;
use Acquia\Wip\Storage\BasicThreadStore;
use Acquia\Wip\Storage\BasicWipPoolStore;
use Acquia\Wip\Storage\BasicWipStore;
use Acquia\Wip\Task;
use Acquia\Wip\TaskInterface;
use Acquia\Wip\ThreadStatus;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogLevel;

/**
 * Missing summary.
 */
class WipWorkerTest extends \PHPUnit_Framework_TestCase {

  const TIMEOUT = 5;

  /**
   * Missing summary.
   *
   * @var WipWorker
   */
  private $worker;

  /**
   * Missing summary.
   *
   * @var BasicThreadStore
   */
  private $threadStorage;

  /**
   * Missing summary.
   *
   * @var WipPool
   */
  private $pool;

  /**
   * Missing summary.
   *
   * @var BasicWipPoolStore
   */
  private $poolStorage;

  /**
   * Missing summary.
   *
   * @var BasicWipStore
   */
  private $objectStorage;

  /**
   * Missing summary.
   *
   * @var BasicServerStore
   */
  private $serverStorage;

  /**
   * Missing summary.
   *
   * @var BasicWip
   */
  private $basicWip;

  /**
   * Missing summary.
   */
  public function setup() {
    $this->worker = new WipWorker();
    $this->threadStorage = new BasicThreadStore();
    $this->pool = new WipPool();
    // Ensure that storage implementations use all the same instances.
    $this->poolStorage = new BasicWipPoolStore();
    $this->objectStorage = new BasicWipStore();
    $this->serverStorage = new BasicServerStore();
    $this->pool->dependencyManager->swapDependency('acquia.wip.storage.wippool', $this->poolStorage);
    $this->pool->dependencyManager->swapDependency('acquia.wip.storage.wip', $this->objectStorage);
    // Make load task do a NULL op and leave the current iterator in place.
    $this->worker->getDependencyManager()->swapDependency('acquia.wip.storage.wippool', $this->poolStorage);
    $this->worker->getDependencyManager()->swapDependency('acquia.wip.storage.thread', $this->threadStorage);
    $this->worker->getDependencyManager()->swapDependency('acquia.wip.pool', $this->pool);
    $this->basicWip = new BasicWip();
    $this->basicWip->setUuid((string) \Ramsey\Uuid\Uuid::uuid4());
  }

  /**
   * Provides a list of types of error exit status for testing.
   *
   * @return array
   *   The array of values provided.
   */
  public function errorTypeProvider() {
    return array(
      array(IteratorStatus::ERROR_SYSTEM, 4),
      array(IteratorStatus::ERROR_USER, 3),
    );
  }

  /**
   * Missing summary.
   */
  public function testGetTask() {
    $this->basicWip->setWipLog(new WipLog(new SqliteWipLogStore()));
    $task = $this->pool->addTask($this->basicWip);
    $task->dependencyManager->swapDependency('acquia.wip.storage.wippool', $this->poolStorage);
    $task->dependencyManager->swapDependency('acquia.wip.storage.wip', $this->objectStorage);
    $this->poolStorage->save($task);
    $this->worker->setTaskId($task->getId());
    $this->worker->getTask();
  }

  /**
   * Missing summary.
   */
  public function testIncompleteProcess() {
    $exception = FALSE;
    try {
      $this->worker->process();
    } catch (NoTaskException $e) {
      $exception = TRUE;
    }

    $this->assertTrue($exception);
  }

  /**
   * Missing summary.
   */
  public function testSimpleProcess() {
    $iterator = $this->getMockIterator(TRUE, new IteratorStatus(), 0, "TEST run successful");
    $task = $this->getIteratorTask($iterator);
    $this->poolStorage->save($task);
    $this->worker->setTaskId($task->getId());
    $this->worker->process();
  }

  /**
   * Missing summary.
   */
  public function testWipOnStart() {
    $iterator = $this->getMockIterator(TRUE, new IteratorStatus(), 0, "TEST run successful");
    $task = $this->getIteratorTask($iterator);
    $task->setStartTimestamp(0);
    $this->poolStorage->save($task);
    $this->worker->setTaskId($task->getId());
    $this->worker->process();
  }

  /**
   * Missing summary.
   */
  public function testOnTerminate() {
    $iterator = $this->getMockIterator(TRUE, new IteratorStatus(IteratorStatus::TERMINATED), 0, "TEST run terminated");
    $task = $this->getIteratorTask($iterator);
    $this->poolStorage->save($task);
    $this->worker->setTaskId($task->getId());
    $this->worker->process();
  }

  /**
   * Missing summary.
   */
  public function testPruneLogsOnSuccess() {
    WipFactory::removeMapping('$acquia.wip.wiplog.prune');
    $obj_id = 137;
    $message = 'trace message.';
    $iterator = $this->getMockIterator(TRUE, new IteratorStatus(IteratorStatus::OK), 0, "TEST run successful");
    $iterator->getWip()->setId($obj_id);
    $task = $this->getIteratorTask($iterator, $obj_id);
    $this->assertEquals($obj_id, $task->getId());
    $this->poolStorage->save($task);
    $this->worker->setTaskId($task->getId());
    $wip_log = $this->worker->getWipLog();
    $iterator->getWip()->setWipLog($wip_log);
    $log_store = $wip_log->getStore();
    $log_store->delete();
    $wip_log->log(WipLogLevel::TRACE, $message, $obj_id);
    $this->worker->process();
    $entries = $log_store->load($obj_id);
    $this->assertEquals(0, count($entries));
  }

  /**
   * Missing summary.
   *
   * @dataProvider errorTypeProvider
   */
  public function testPruneLogsOnError($error_type, $log_entry_count) {
    $obj_id = 137;
    $message = 'trace message.';
    $iterator = $this->getMockIterator(TRUE, new IteratorStatus($error_type), 0, "TEST run error");
    $iterator->getWip()->setId($obj_id);
    $task = $this->getIteratorTask($iterator, $obj_id);
    $this->assertEquals($obj_id, $task->getId());
    $this->poolStorage->save($task);
    $this->worker->setTaskId($task->getId());
    $wip_log = $this->worker->getWipLog();
    $iterator->getWip()->setWipLog($wip_log);
    $log_store = $wip_log->getStore();
    $log_store->delete();
    $wip_log->log(WipLogLevel::TRACE, $message, $obj_id);
    $this->worker->process();
    $entries = $log_store->load($obj_id);
    $this->assertEquals($log_entry_count, count($entries));
  }

  /**
   * Missing summary.
   *
   * @dataProvider errorTypeProvider
   */
  public function testOnError($error_type) {
    $iterator = $this->getMockIterator(TRUE, new IteratorStatus($error_type), 0, "TEST run terminated");
    $task = $this->getIteratorTask($iterator);
    $thread = new Thread();
    $thread->setWipId($task->getId());
    $thread->setServerId(rand());
    $this->threadStorage->save($thread);
    $this->poolStorage->save($task);
    $this->worker->setTaskId($task->getId());
    $result = $this->worker->process();
    $this->worker->complete($result);

    // Simulate WipExecCommand::releaseThreadById.
    $thread->setStatus(ThreadStatus::FINISHED);
    $thread->setCompleted(time());
    $this->threadStorage->save($thread);

    // Check that an errored task sets its thread to completed but does not
    // remove it.
    /** @var Thread $reloaded_thread */
    $reloaded_thread = $this->threadStorage->get($thread->getId());
    $this->assertEquals($task->getId(), $reloaded_thread->getWipId());
    $this->assertEquals(ThreadStatus::FINISHED, $reloaded_thread->getStatus());
  }

  /**
   * Asserts that prcoess() quits within an expected timeout.
   */
  public function testTimeout() {
    $iterator = $this->getMockIterator(FALSE, new IteratorStatus(), 1, "TEST running");
    $task = $this->getIteratorTask($iterator);
    $this->poolStorage->save($task);
    $this->worker->setTaskId($task->getId());
    $start = time();
    $this->worker->process();

    // Multiply the expected greater value by 2 to allow for small
    // discrepancies in timing.
    $this->assertLessThan(self::TIMEOUT * 2, (time() - $start));
  }

  /**
   * Missing summary.
   */
  public function testComplete() {
    $iterator = $this->getMockIterator(FALSE, new IteratorStatus(), 1, "TEST running");
    /* @var Task $task */
    $task = $this->getIteratorTask($iterator);
    $task->setId(rand(1, 1000000));
    $server = new Server('mytesthost');
    $this->serverStorage->save($server);
    $thread = new Thread();
    $thread->setServerId($server->getId());
    $thread->setWipId($task->getId());
    $thread->setStatus(ThreadStatus::RUNNING);
    $this->threadStorage->save($thread);
    $this->worker->getDependencyManager()->swapDependency('acquia.wip.storage.thread', $this->threadStorage);
    // Check exception is thrown before there is a task to work on.
    $exception = FALSE;
    try {
      $this->worker->complete(new IteratorResult());
    } catch (NoTaskException $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception);

    // Check before processing that the thread can be loaded.
    /** @var Thread $found_thread */
    $found_thread = $this->threadStorage->get($thread->getId());
    $this->assertEquals($thread->getId(), $found_thread->getId());

    $this->poolStorage->save($task);
    $this->worker->setTaskId($task->getId());
    $result = $this->worker->process();
    $this->assertEquals(Task::NOT_CLAIMED, $task->getClaimedTimestamp());
    $this->worker->complete($result);

    // Simulate WipExecCommand::releaseThreadById.
    $this->threadStorage->remove($thread);

    /** @var Thread $found_thread */
    $found_thread = $this->threadStorage->get($thread->getId());
    // A successfully-processed thread will be removed from storage.
    $this->assertEmpty($found_thread);
  }

  /**
   * Missing summary.
   */
  public function testStartStopProgress() {
    $this->poolStorage = $this->getMock('Acquia\Wip\Storage\BasicWipPoolStore', array('stopProgress'));

    $this->poolStorage->expects($this->once())
      ->method('stopProgress');

    $this->pool->dependencyManager->swapDependency('acquia.wip.storage.wippool', $this->poolStorage);
    $this->worker->getDependencyManager()->swapDependency('acquia.wip.storage.wippool', $this->poolStorage);
    $this->worker->getDependencyManager()->swapDependency('acquia.wip.pool', $this->pool);

    $iterator = $this->getMockIterator(FALSE, new IteratorStatus(), 0, "TEST");
    $task = $this->getIteratorTask($iterator);
    $this->poolStorage->save($task);
    $this->worker->setTaskId($task->getId());
    $thread = new Thread();
    $thread->setWipId($task->getId());
    $thread->setServerId(rand());
    $this->threadStorage->save($thread);
    $this->poolStorage->save($task);
    $this->worker->setTaskId($task->getId());
    $result = $this->worker->process();
    $this->worker->complete($result);
  }

  /**
   * Data provider for testing exit messages.
   *
   * @return array
   *   Data array for testing exit messages.
   */
  public function getExitMessageProvider() {
    return [
      // Result's message should be used.
      [15, 'Iterator', 'Result', 'Result', FALSE],
      // No message in the result; the wip object's message should be used.
      [16, 'Iterator', '', 'Wip', FALSE],
      // No message in the result or wip object; the iterator's message should
      // be used.
      [17, 'Iterator', '', 'Iterator', TRUE],
      // No message at all.
      [18, '', '', '', TRUE],
    ];
  }

  /**
   * Tests getting the exit status of tasks.
   *
   * If a task does not have a status, WipWorker should try to get one from
   * the wip object itself or its iterator. If the message is still empty
   * after trying those two locations, use an empty string.
   *
   * @param int $task_id
   *   The task id.
   * @param string $message
   *   The wip message.
   * @param string $result_message
   *   The iterator message.
   * @param string $expected
   *   The expected output.
   * @param bool $clear_exit_message
   *   Should the exit message text be cleared.
   *
   * @dataProvider getExitMessageProvider
   */
  public function testGetExitMessage(
    $task_id,
    $message,
    $result_message,
    $expected,
    $clear_exit_message
  ) {
    $iterator = $this->getMockIterator(TRUE, new IteratorStatus(), 1, $message, $result_message);
    if ($clear_exit_message) {
      $this->basicWip->setExitMessage(new ExitMessage(''));
    }
    /** @var Task $task */
    $task = $this->getIteratorTask($iterator, $task_id);
    $this->poolStorage->save($task);
    $this->worker->setTaskId($task->getId());
    $this->worker->process();
    $processed_task = $this->pool->getTask($task_id);
    $this->assertEquals($expected, $processed_task->getExitMessage());
  }

  /**
   * Returns an iterator that succeeds after a single step.
   *
   * @param bool $complete
   *   Set to TRUE if the iterator should signal completion after a single
   *   iteration.
   * @param IteratorStatus $status
   *   The iterator status.
   * @param int $wait
   *   The wait time.
   * @param string $message
   *   The message.
   * @param string $result_message
   *   The message to use for the result.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   *   The mock.
   */
  private function getMockIterator(
    $complete,
    IteratorStatus $status,
    $wait = 0,
    $message = 'TEST',
    $result_message = 'RESULT'
  ) {
    if (is_null($complete)) {
      $complete = TRUE;
    }
    $result = new IteratorResult($wait, $complete, $status, $result_message);

    // Mock iterator that always returns success.
    $iterator = $this->getMock(
      'Acquia\Wip\Iterators\BasicIterator\StateTableIterator',
      array(
        'moveToNextState',
        'getWip',
        'getExitMessage',
      )
    );
    $iterator->expects(!$complete ? $this->any() : $this->once())
      ->method('moveToNextState')
      ->will($this->returnValue($result));

    $wip = $this->basicWip;
    $wip->setWipLog(new WipLog(new SqliteWipLogStore()));
    $wip->setIterator($iterator);
    $this->basicWip->setExitMessage(new ExitMessage('Wip'));
    $iterator->expects($this->any())
      ->method('getWip')
      ->will($this->returnValue($wip));
    $iterator->expects($this->any())
      ->method('getExitMessage')
      ->will($this->returnValue($message));

    return $iterator;
  }

  /**
   * Returns a Task object that uses the given Iterator.
   *
   * @param \PHPUnit_Framework_MockObject_MockObject $iterator
   *   The iterator.
   * @param int $obj_id
   *   The Wip object id.
   *
   * @return TaskInterface
   *   A mock Task object.
   */
  private function getIteratorTask(\PHPUnit_Framework_MockObject_MockObject $iterator, $obj_id = 15) {
    $task = $this->getMock(
      'Acquia\Wip\Task',
      array(
        'getTimeout',
        'getWipIterator',
        'setWipIterator',
        'getId',
        'loadWipIterator',
      )
    );

    // This Task returns the mock iterator.
    $task->expects($this->atLeastOnce())
      ->method('getWipIterator')
      ->will($this->returnValue($iterator));

    // This Task returns the mock iterator.
    $task->expects($this->atLeastOnce())
      ->method('setWipIterator')
      ->will($this->returnValue($iterator));

    // This Task returns the mock iterator.
    $task->expects($this->atLeastOnce())
      ->method('loadWipIterator')
      ->will($this->returnValue($iterator));

    // Force getTimeout to return 5 seconds - lower than the default 30.
    $task->expects($this->any())
      ->method('getTimeout')
      ->will($this->returnValue(self::TIMEOUT));

    $task->expects($this->any())
      ->method('getId')
      ->will($this->returnValue($obj_id));

    return $task;
  }

}
