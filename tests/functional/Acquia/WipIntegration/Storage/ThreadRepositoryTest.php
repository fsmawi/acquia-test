<?php

namespace Acquia\WipIntegrations\DoctrineORM\Entities;

use Acquia\WipService\Test\AbstractFunctionalTest;
use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\Iterators\BasicIterator\StateTableIterator;
use Acquia\Wip\Runtime\Server;
use Acquia\Wip\Runtime\Thread;
use Acquia\Wip\Task;
use Acquia\Wip\TaskStatus;
use Acquia\Wip\ThreadStatus;
use Acquia\Wip\WipFactory;

/**
 * Missing summary.
 */
class ThreadRepositoryFunctionalTest extends AbstractFunctionalTest {

  /**
   * The ThreadStore instance.
   *
   * @var \Acquia\Wip\Storage\ThreadStoreInterface
   */
  private $threadStore;

  /**
   * The ServerStore instance.
   *
   * @var \Acquia\Wip\Storage\ServerStoreInterface
   */
  private $serverStore;

  /**
   * The WipPoolStore instance.
   *
   * @var \Acquia\Wip\Storage\WipPoolStoreInterface
   */
  private $wipPoolStore;

  /**
   * The WipStore instance.
   *
   * @var \Acquia\Wip\Storage\WipStoreInterface
   */
  private $wipStore;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    WipFactory::setConfigPath('config/config.factory.test.cfg');

    $this->serverStore = WipFactory::getObject('acquia.wip.storage.server', $this->app);
    $this->threadStore = WipFactory::getObject('acquia.wip.storage.thread', $this->app);
    $this->wipPoolStore = WipFactory::getObject('acquia.wip.storage.wippool', $this->app);
    $this->wipStore = WipFactory::getObject('acquia.wip.storage.wip', $this->app);

    $server = new Server('test.server.example.com');
    $this->serverStore->save($server);
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testAddThreadInvalidServer() {
    $thread = new Thread();
    $this->threadStore->save($thread);
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testAddThreadInvalidWip() {
    $server = $this->serverStore->getServerByHostname('test.server.example.com');
    // Add a thread.
    $thread = new Thread($server);
    $thread->setServerId($server->getId());
    $this->threadStore->save($thread);
  }

  /**
   * Missing summary.
   */
  public function testAddThread() {
    $server = $this->serverStore->getServerByHostname('test.server.example.com');

    // Add a Wip object.
    $iterator = new StateTableIterator();
    $wip = new BasicWip();
    $wip->setUuid((string) \Ramsey\Uuid\Uuid::uuid4());
    $iterator->initialize($wip);
    $iterator->compileStateTable();
    $task = new Task();
    $task->setUuid($wip->getUuid());
    $task->setWipIterator($iterator);
    $task->setStatus(TaskStatus::PROCESSING);
    $this->wipPoolStore->save($task);
    $wip_id = $task->getId();

    // Add a thread.
    $thread = new Thread();
    $thread->setServerId($server->getId());
    $thread->setWipId($wip_id);
    $thread->setStatus(ThreadStatus::RUNNING);
    $this->threadStore->save($thread);

    // Retrieve the running threads on a server.
    $threads = $this->threadStore->getActiveThreads($server);
    $this->assertEquals(1, count($threads));

    // Ensure that the threads are properly separated by server.
    $server2 = new Server('test2.server.example.com');
    $this->serverStore->save($server2);
    $server2 = $this->serverStore->getServerByHostname('test2.server.example.com');
    $threads = $this->threadStore->getActiveThreads($server2);
    $this->assertEquals(0, count($threads));
  }

  /**
   * Missing summary.
   */
  public function testFinishThread() {
    $server = $this->serverStore->getServerByHostname('test.server.example.com');

    // Add a Wip object.
    $iterator = new StateTableIterator();
    $wip = new BasicWip();
    $wip->setUuid((string) \Ramsey\Uuid\Uuid::uuid4());
    $iterator->initialize($wip);
    $iterator->compileStateTable();
    $task = new Task();
    $task->setUuid($wip->getUuid());
    $task->setWipIterator($iterator);
    $task->setStatus(TaskStatus::PROCESSING);
    $this->wipPoolStore->save($task);
    $wip_id = $task->getId();

    // Add a thread.
    $thread = new Thread();
    $thread->setServerId($server->getId());
    $thread->setWipId($wip_id);
    $thread->setStatus(ThreadStatus::RUNNING);
    $this->threadStore->save($thread);

    // Ensure that the thread is stored.
    $threads = $this->threadStore->getActiveThreads($server);
    $this->assertEquals(1, count($threads));

    // Mark the thread finished.
    $thread->setStatus(ThreadStatus::FINISHED);
    $this->threadStore->save($thread);

    // Ensure that the running threads on the server is empty.
    $threads = $this->threadStore->getActiveThreads($server);
    $this->assertEquals(0, count($threads));
  }

  /**
   * Missing summary.
   */
  public function testRemoveThread() {
    $server = $this->serverStore->getServerByHostname('test.server.example.com');

    // Add a Wip object.
    $iterator = new StateTableIterator();
    $wip = new BasicWip();
    $wip->setUuid((string) \Ramsey\Uuid\Uuid::uuid4());
    $iterator->initialize($wip);
    $iterator->compileStateTable();
    $task = new Task();
    $task->setUuid($wip->getUuid());
    $task->setWipIterator($iterator);
    $task->setStatus(TaskStatus::PROCESSING);
    $this->wipPoolStore->save($task);
    $wip_id = $task->getId();

    // Add a thread to be deleted.
    $thread_delete = new Thread();
    $thread_delete->setServerId($server->getId());
    $thread_delete->setWipId($wip_id);
    $thread_delete->setStatus(ThreadStatus::RUNNING);
    $this->threadStore->save($thread_delete);

    // Add a thread that will stay around.
    $thread_stays = new Thread();
    $thread_stays->setServerId($server->getId());
    $thread_stays->setWipId($wip_id);
    $thread_stays->setStatus(ThreadStatus::RUNNING);
    $this->threadStore->save($thread_stays);

    // Retrieve the running threads on a server.
    $threads = $this->threadStore->getActiveThreads($server);
    $this->assertEquals(2, count($threads));

    // Remove the thread.
    $this->threadStore->remove($thread_delete);

    // Ensure that the right thread was deleted.
    $threads = $this->threadStore->getActiveThreads($server);
    $this->assertEquals(1, count($threads));
    $thread = array_pop($threads);
    $this->assertEquals($thread_stays->getId(), $thread->getId());
  }

  /**
   * Missing summary.
   */
  public function testGetThreadByTask() {
    $wip = new BasicWip();
    $wip->setUuid((string) \Ramsey\Uuid\Uuid::uuid4());
    $wip_iterator = WipFactory::getObject('acquia.wip.iterator');
    $wip_iterator->initialize($wip);
    $task = new Task();
    $task->setUuid($wip->getUuid());
    $task->setWipIterator($wip_iterator);
    $this->wipPoolStore->save($task);

    $server = $this->serverStore->getServerByHostname('test.server.example.com');

    $thread = new Thread();
    $thread->setServerId($server->getId());
    $thread->setStatus(ThreadStatus::RUNNING);
    $thread->setWipId($task->getId());
    $this->threadStore->save($thread);

    // Add some noise to the thread pool.
    for ($i = 0; $i <= rand(1, 20); ++$i) {
      $thread = new Thread();
      $thread->setServerId($server->getId());
      if (rand(0, 1)) {
        $thread->setStatus(ThreadStatus::RUNNING);
      }
      $thread->setWipId(rand(1000001, 2000000));
      $this->threadStore->save($thread);
    }

    $found_thread = $this->threadStore->getThreadByTask($task);

    $this->assertEquals($task->getId(), $found_thread->getWipId());
  }

  /**
   * Missing summary.
   */
  public function testBadThreadByTask() {
    $wip = new BasicWip();
    $wip->setUuid((string) \Ramsey\Uuid\Uuid::uuid4());
    $wip_iterator = WipFactory::getObject('acquia.wip.iterator');
    $wip_iterator->initialize($wip);
    $task = new Task();
    $task->setUuid($wip->getUuid());
    $task->setWipIterator($wip_iterator);
    $this->wipPoolStore->save($task);

    $server = $this->serverStore->getServerByHostname('test.server.example.com');

    // Don't store a thread for the test task this time (to test exceptions).
    // Add some noise to the thread pool.
    for ($i = 0; $i <= rand(1, 20); ++$i) {
      $thread = new Thread();
      $thread->setServerId($server->getId());
      if (rand(0, 1)) {
        $thread->setStatus(ThreadStatus::RUNNING);
      }
      $thread->setWipId(rand(1000001, 2000000));
      $this->threadStore->save($thread);
    }

    $exception = FALSE;
    try {
      $found_thread = $this->threadStore->getThreadByTask($task);
    } catch (\Acquia\Wip\Exception\NoThreadException $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception);

    $exception = FALSE;
    try {
      $task = new Task();
      $found_thread = $this->threadStore->getThreadByTask($task);
    } catch (\Acquia\Wip\Exception\NoTaskException $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception);
  }

  /**
   * Missing summary.
   */
  public function testGet() {
    $server = $this->serverStore->getServerByHostname('test.server.example.com');

    $thread = new Thread();
    $thread->setServerId($server->getId());
    $thread->setWipId(rand());
    $thread->setPid(rand());
    $thread->setCreated(rand());
    $thread->setCompleted(rand());
    $thread->setStatus(ThreadStatus::FINISHED);
    $thread->setSshOutput(md5(rand()));
    $this->threadStore->save($thread);

    $found_thread = $this->threadStore->get($thread->getId());
    $this->assertInstanceOf('\Acquia\Wip\Runtime\Thread', $found_thread);
    $this->assertEquals($thread->getId(), $found_thread->getId());
    $this->assertEquals($thread->getServerId(), $found_thread->getServerId());
    $this->assertEquals($thread->getWipId(), $found_thread->getWipId());
    $this->assertEquals($thread->getPid(), $found_thread->getPid());
    $this->assertEquals($thread->getCreated(), $found_thread->getCreated());
    $this->assertEquals($thread->getCompleted(), $found_thread->getCompleted());
    $this->assertEquals($thread->getSshOutput(), $found_thread->getSshOutput());

    $not_found = $this->threadStore->get(5000000);
    $this->assertNotInstanceOf('\Acquia\Wip\Runtime\Thread', $not_found);
    $this->assertInternalType('null', $not_found);
  }

  /**
   * Missing summary.
   */
  public function testPrune() {
    $delete_time_limit = time();

    $server = $this->serverStore->getServerByHostname('test.server.example.com');

    // Add an old and finished thread to be deleted.
    $old_finished_thread1 = new Thread();
    $old_finished_thread1->setServerId($server->getId());
    $old_finished_thread1->setWipId(rand());
    $old_finished_thread1->setStatus(ThreadStatus::FINISHED);
    $old_finished_thread1->setCreated($delete_time_limit);
    $this->threadStore->save($old_finished_thread1);
    $thread_check = $this->threadStore->get($old_finished_thread1->getId());
    $this->assertNotEmpty($thread_check);
    $this->assertEquals($old_finished_thread1->getId(), $thread_check->getId());

    // Add an old and finished thread to be deleted.
    $old_finished_thread2 = new Thread();
    $old_finished_thread2->setServerId($server->getId());
    $old_finished_thread2->setWipId(rand());
    $old_finished_thread2->setStatus(ThreadStatus::FINISHED);
    $old_finished_thread2->setCreated($delete_time_limit);
    $this->threadStore->save($old_finished_thread2);
    $thread_check = $this->threadStore->get($old_finished_thread2->getId());
    $this->assertNotEmpty($thread_check);
    $this->assertEquals($old_finished_thread2->getId(), $thread_check->getId());

    // Add an old and unfinished thread that should not be deleted.
    $old_unfinished_thread = new Thread();
    $old_unfinished_thread->setServerId($server->getId());
    $old_unfinished_thread->setWipId(rand());
    $old_unfinished_thread->setStatus(ThreadStatus::RUNNING);
    $old_unfinished_thread->setCreated($delete_time_limit);
    $this->threadStore->save($old_unfinished_thread);
    $thread_check = $this->threadStore->get($old_unfinished_thread->getId());
    $this->assertNotEmpty($thread_check);
    $this->assertEquals($old_unfinished_thread->getId(), $thread_check->getId());

    // Add a recent finished thread to should not be deleted.
    $recent_finished_thread = new Thread();
    $recent_finished_thread->setServerId($server->getId());
    $recent_finished_thread->setWipId(rand());
    $recent_finished_thread->setStatus(ThreadStatus::FINISHED);
    $recent_finished_thread->setCreated($delete_time_limit + 1);
    $this->threadStore->save($recent_finished_thread);
    $thread_check = $this->threadStore->get($recent_finished_thread->getId());
    $this->assertNotEmpty($thread_check);
    $this->assertEquals($recent_finished_thread->getId(), $thread_check->getId());

    $result = $this->threadStore->prune($delete_time_limit);

    // Check that the proper threads were deleted and preserved.
    $thread_check = $this->threadStore->get($old_finished_thread1->getId());
    $this->assertEmpty($thread_check);
    $thread_check = $this->threadStore->get($old_finished_thread2->getId());
    $this->assertEmpty($thread_check);
    $thread_check = $this->threadStore->get($old_unfinished_thread->getId());
    $this->assertNotEmpty($thread_check);
    $this->assertEquals($old_unfinished_thread->getId(), $thread_check->getId());
    $thread_check = $this->threadStore->get($recent_finished_thread->getId());
    $this->assertNotEmpty($thread_check);
    $this->assertEquals($recent_finished_thread->getId(), $thread_check->getId());
    // The prune's result should be FALSE, as in no more items to be deleted.
    $this->assertFalse($result);
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testPruneInvalidTimestamp() {
    $this->threadStore->prune(NULL);
  }

  /**
   * Missing summary.
   */
  public function testPruneLimited() {
    $delete_time_limit = time();

    $server = $this->serverStore->getServerByHostname('test.server.example.com');

    // Add an old and finished thread to be deleted.
    $old_finished_thread1 = new Thread();
    $old_finished_thread1->setServerId($server->getId());
    $old_finished_thread1->setWipId(rand());
    $old_finished_thread1->setStatus(ThreadStatus::FINISHED);
    $old_finished_thread1->setCreated($delete_time_limit);
    $this->threadStore->save($old_finished_thread1);
    $thread_check = $this->threadStore->get($old_finished_thread1->getId());
    $this->assertNotEmpty($thread_check);
    $this->assertEquals($old_finished_thread1->getId(), $thread_check->getId());

    // Add an old and finished thread to be deleted.
    $old_finished_thread2 = new Thread();
    $old_finished_thread2->setServerId($server->getId());
    $old_finished_thread2->setWipId(rand());
    $old_finished_thread2->setStatus(ThreadStatus::FINISHED);
    $old_finished_thread2->setCreated($delete_time_limit);
    $this->threadStore->save($old_finished_thread2);
    $thread_check = $this->threadStore->get($old_finished_thread2->getId());
    $this->assertNotEmpty($thread_check);
    $this->assertEquals($old_finished_thread2->getId(), $thread_check->getId());

    $result = $this->threadStore->prune($delete_time_limit, 1);

    // One of the threads must be deleted and the other must be still around.
    $thread_check1 = $this->threadStore->get($old_finished_thread1->getId());
    $thread_check2 = $this->threadStore->get($old_finished_thread2->getId());
    $this->assertTrue(empty($thread_check1) xor empty($thread_check2));
    // The prune's result should be TRUE, as in there are more items to be
    // deleted.
    $this->assertTrue($result);
  }

  /**
   * Missing summary.
   */
  public function testPruneWhenEmpty() {
    $this->threadStore->prune(time());
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testPruneInvalidLimit() {
    $this->threadStore->prune(time(), NULL);
  }

}
