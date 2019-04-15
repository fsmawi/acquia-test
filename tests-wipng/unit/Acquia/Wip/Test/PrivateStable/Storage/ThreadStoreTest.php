<?php

namespace Acquia\Wip\Test\PrivateStable\Storage;

use Acquia\Wip\Exception\NoTaskException;
use Acquia\Wip\Exception\NoThreadException;
use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\Runtime\Server;
use Acquia\Wip\Runtime\Thread;
use Acquia\Wip\Task;
use Acquia\Wip\ThreadStatus;
use Acquia\Wip\WipFactory;

/**
 * Missing summary.
 */
class ThreadStoreTest extends \PHPUnit_Framework_TestCase {

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
  private $taskStore;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    $this->threadStore = WipFactory::getObject('acquia.wip.storage.thread');
    $this->serverStore = WipFactory::getObject('acquia.wip.storage.server');
    $this->taskStore = WipFactory::getObject('acquia.wip.storage.wippool');
    $this->threadStore->initialize();
    $this->serverStore->initialize();
    $this->taskStore->initialize();
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
    $thread->setWipId(rand());
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

    // Add a thread.
    $thread = new Thread();
    $thread->setServerId($server->getId());
    $thread->setWipId(rand());
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

    // Add a thread.
    $thread = new Thread();
    $thread->setServerId($server->getId());
    $thread->setWipId(rand());
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

    // Add a thread to be deleted.
    $thread_delete = new Thread();
    $thread_delete->setServerId($server->getId());
    $thread_delete->setWipId(1);
    $thread_delete->setStatus(ThreadStatus::RUNNING);
    $this->threadStore->save($thread_delete);

    // Add a thread that will stay around.
    $thread_stays = new Thread();
    $thread_stays->setServerId($server->getId());
    $thread_stays->setWipId(1);
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
    $wip_iterator = WipFactory::getObject('acquia.wip.iterator');
    $wip_iterator->initialize($wip);
    $task = new Task();
    $task->setWipIterator($wip_iterator);
    $this->taskStore->save($task);

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
    $wip_iterator = WipFactory::getObject('acquia.wip.iterator');
    $wip_iterator->initialize($wip);
    $task = new Task();
    $task->setWipIterator($wip_iterator);
    $this->taskStore->save($task);

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
    } catch (NoThreadException $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception);

    $exception = FALSE;
    try {
      $task = new Task();
      $found_thread = $this->threadStore->getThreadByTask($task);
    } catch (NoTaskException $e) {
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
    $this->threadStore->save($thread);

    $found_thread = $this->threadStore->get($thread->getId());
    $this->assertInstanceOf('\Acquia\Wip\Runtime\Thread', $found_thread);
    $this->assertEquals($thread->getId(), $found_thread->getId());

    $not_found = $this->threadStore->get(5000000);
    $this->assertNotInstanceOf('\Acquia\Wip\Runtime\Thread', $not_found);
    $this->assertInternalType('null', $not_found);
  }

}
