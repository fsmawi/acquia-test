<?php

namespace Acquia\Wip\Test\PrivateStable;

use Acquia\Wip\Environment;
use Acquia\Wip\Exception\InvalidOperationException;
use Acquia\Wip\Exception\NoObjectException;
use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\Implementation\WipLogEntry;
use Acquia\Wip\Runtime\Server;
use Acquia\Wip\Runtime\Thread;
use Acquia\Wip\Runtime\ThreadPool;
use Acquia\Wip\Runtime\WipPool;
use Acquia\Wip\Ssh\SshInterface;
use Acquia\Wip\Ssh\SshProcess;
use Acquia\Wip\Ssh\SshServiceInterface;
use Acquia\Wip\StateTableIteratorInterface;
use Acquia\Wip\Storage\BasicServerStore;
use Acquia\Wip\Storage\BasicThreadStore;
use Acquia\Wip\Storage\BasicWipPoolStore;
use Acquia\Wip\Task;
use Acquia\Wip\TaskExitStatus;
use Acquia\Wip\TaskStatus;
use Acquia\Wip\Test\PrivateStable\Runtime\Resource\BasicMutableThreadStore;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipLogLevel;

/**
 * Tests ThreadPool operations.
 */
class ThreadPoolTest extends \PHPUnit_Framework_TestCase {

  /**
   * For testing, the maximum number of threads available per server.
   */
  const MAX_THREADS_PER_SERVER = 10;

  /**
   * The ThreadPool instance.
   *
   * @var ThreadPool
   */
  private $threadPool;

  /**
   * The Ssh instance.
   *
   * @var SshInterface
   */
  private $mockSsh;

  /**
   * The SshService.
   *
   * @var SshServiceInterface
   */
  private $mockSshService;

  /**
   * The ThreadStore instance.
   *
   * @var BasicThreadStore
   */
  private $threadStore;

  /**
   * The ServerStore instance.
   *
   * @var BasicServerStore
   */
  private $serverStore;

  /**
   * The WipLog.
   *
   * @var WipLogInterface
   */
  private $wipLog;

  /**
   * A flag that indicates whether a callback was triggered.
   *
   * @var bool
   */
  private $callbackTriggered = FALSE;

  /**
   * Implicitly tests the constructor - no need to test this separately.
   */
  public function setup() {
    $this->threadPool = new ThreadPool();
    // Set a sensibly short time limit for testing.
    $this->threadPool->setTimeLimit(2);

    $this->setupMockSSH();
    $this->serverStore = WipFactory::getObject('acquia.wip.storage.server');
    $this->serverStore->initialize();
    $this->wipLog = WipFactory::getObject('acquia.wip.wiplog');
    $this->threadStore = new BasicMutableThreadStore($this->mockSsh);
    $this->threadPool->dependencyManager->swapDependency('acquia.wip.storage.server', $this->serverStore);
    $this->threadPool->dependencyManager->swapDependency('acquia.wip.storage.thread', $this->threadStore);
    $pool_store = $this->threadPool->dependencyManager->getDependency('acquia.wip.storage.wippool');
    $pool_store->initialize();
  }

  /**
   * Tests the getWipPool method.
   */
  public function testGetWipPool() {
    $wip_pool = $this->threadPool->getWipPool();
    $this->assertTrue($wip_pool instanceof WipPool);
  }

  /**
   * Sets up some dummy servers and threads for testing.
   *
   * @param array $servers
   *   An associative array of hostname => maximum_threads server definitions to
   *   use as the full list of servers during testing.
   * @param array $running_threads
   *   An array of hostnames, each representing 1 thread on the given hostname.
   *   A hostname may be used more than once to consume multiple threads on that
   *   server.
   */
  private function setupThreads($servers = array(), $running_threads = array()) {
    foreach ($servers as $hostname => $thread_count) {
      $server = new Server($hostname);
      $server->setTotalThreads($thread_count);
      $this->serverStore->save($server);
    }

    foreach ($running_threads as $hostname) {
      $server = $this->serverStore->getServerByHostname($hostname);
      $thread = new Thread();
      $thread->setServerId($server->getId());
      $thread->setWipId(rand());
      $thread->setStatus(\Acquia\Wip\ThreadStatus::RUNNING);
      // Assume that a real Ssh client is no use for testing.
      $thread->dependencyManager->swapDependency('acquia.wip.ssh.client', $this->mockSsh);
      $thread->dependencyManager->swapDependency('acquia.wip.storage.server', $this->serverStore);
      $this->threadStore->save($thread);
    }
  }

  /**
   * Sets up and stores a Mock SSHClientInterface, including a Mocked SSH2.
   */
  private function setupMockSsh() {
    $this->mockSsh = $this->getMock('\Acquia\Wip\Ssh\Ssh');
    $this->mockSshService = $this->getMock('\Acquia\Wip\Ssh\SshService');
    $this->mockSsh->expects($this->any())
      ->method('getSshService')
      ->will($this->returnValue($this->mockSshService));
  }

  /**
   * Tests the process method.
   */
  public function testProcess() {
    $servers = array(
      'myhost1' => 1,
    );
    $this->setupThreads($servers);

    $this->threadPool->process();
  }

  /**
   * Tests that NoWorkerServersException is thrown when there are no servers.
   *
   * @expectedException \Acquia\Wip\Exception\NoWorkerServersException
   *
   * @expectedExceptionMessageRegexp ~There are no worker servers.*~
   */
  public function testNoWorkerServers() {
    $this->threadPool->getAvailableThreads();
  }

  /**
   * Tests the getAvailableThreads method.
   */
  public function testGetAvailableThreads() {
    $servers = array(
      'myhost1' => 1,
    );
    $this->setupThreads($servers);
    $threads = $this->threadPool->getAvailableThreads();
    $this->assertEquals(1, count($threads));
    $this->assertInstanceOf('Acquia\Wip\Runtime\Thread', reset($threads));
  }

  /**
   * Tests the getAvailableThreads order.
   */
  public function testGetAvailableThreadsOrder() {
    $test_host_1 = 'myhost1';
    $test_host_2 = 'myhost2';
    $servers = array(
      $test_host_1 => 3,
      $test_host_2 => 3,
    );
    $threads = array(
      $test_host_1,
      $test_host_1,
    );

    $this->setupThreads($servers, $threads);
    $threads = $this->threadPool->getAvailableThreads();

    // 6 total threads defined in the server_store, 2 are currently being used.
    // Only 4 threads remain.
    $this->assertEquals(4, count($threads));

    $host_names = $this->getThreadHostArray($threads);

    // The first 2 threads should be using 'myhost2', since 'myhost1' is
    // currently executing 2 more threads than 'myhost2'.
    $first_host = array_shift($host_names);
    $second_host = array_shift($host_names);
    $this->assertEquals($test_host_2, $first_host);
    $this->assertEquals($test_host_2, $second_host);

    // The next 2 threads will be on both 'myhost1' and 'myhost2', but the
    // order doesn't matter.
    $this->assertContains($test_host_1, $host_names);
    $this->assertContains($test_host_2, $host_names);
  }

  /**
   * Returns an array of host names associated with the specified threads.
   *
   * @param Thread[] $threads
   *   The threads.
   *
   * @return string[]
   *   The host names associated with the specified threads.
   */
  private function getThreadHostArray($threads) {
    $result = array();
    foreach ($threads as $thread) {
      $result[] = $this->getThreadHost($thread);
    }
    return $result;
  }

  /**
   * Returns the hostname associated with the server ID assigned to a thread.
   *
   * @param Thread $thread
   *   The thread.
   *
   * @return string
   *   The hostname assigned to the thread.
   */
  private function getThreadHost(Thread $thread) {
    $server_store = BasicServerStore::getServerStore();
    $server = $server_store->get($thread->getServerId());
    return $server->getHostname();
  }

  /**
   * Tests behavior when no threads are available.
   */
  public function testGetAvailableThreadsWithNoThreadsAvailable() {
    $task = new Task();
    $testhost = 'myhost1';
    $servers = array(
      $testhost => 1,
    );

    // Just one thread, assigned to the single server.
    $threads = array(
      $testhost,
    );
    $this->setupThreads($servers, $threads);
    $threads = $this->threadPool->getAvailableThreads();
    $this->assertEmpty($threads);
  }

  /**
   * Tests behavior when thread counts for all servers are at their limit.
   */
  public function testThreadLimits() {
    $limit1 = rand(5, self::MAX_THREADS_PER_SERVER);
    $limit2 = rand(5, self::MAX_THREADS_PER_SERVER);
    $max_threads = $limit1 + $limit2;

    $servers = array(
      'myhost1' => $limit1,
      'myhost2' => $limit2,
    );
    $this->setupThreads($servers);

    $mock_wip_pool = $this->mockInfiniteWipPool(20);

    // Assert also that the Ssh exec method gets called the same number of
    // times.
    $this->setUpSshProcess($max_threads);

    $this->threadPool->dependencyManager->swapDependency('acquia.wip.pool', $mock_wip_pool);

    // We have to give this a little more time for Travis tests.
    $this->threadPool->setTimeLimit(10);
    $this->threadPool->process();
  }

  /**
   * Tests the task exit status is set properly when an exception is thrown.
   */
  public function testDispatchException() {
    $mock_thread_pool = $this->getMock(
      'Acquia\Wip\Runtime\ThreadPool',
      array(
        'dispatch',
      )
    );
    $mock_thread_pool->expects($this->any())
      ->method('dispatch')
      ->willThrowException(new \Exception('TEST exception'));

    $servers = array(
      'myhost1' => self::MAX_THREADS_PER_SERVER,
    );
    $this->setupThreads($servers);

    $mock_wip_pool = $this->mockInfiniteWipPool(1);
    $tasks = $mock_wip_pool->getNextTasks();
    $task = reset($tasks);

    $mock_thread_pool->dependencyManager->swapDependency('acquia.wip.pool', $mock_wip_pool);
    $wip_pool_store = $mock_wip_pool->dependencyManager->getDependency('acquia.wip.storage.wippool');

    $mock_thread_pool->process();

    $task = $wip_pool_store->get($task->getId());
    $this->assertEquals(TaskStatus::COMPLETE, $task->getStatus());
    $this->assertEquals(TaskExitStatus::ERROR_SYSTEM, $task->getExitStatus());
  }

  /**
   * Test the setTimeLimit method.
   */
  public function testTimeLimit() {
    $max_iterations = 3;
    for ($i = 0; $i <= $max_iterations; ++$i) {
      $this->threadPool->setTimeLimit($test_value = rand(0, 500));
      $this->assertEquals($test_value, $this->threadPool->getTimeLimit());
    }
    // Set a practical value for testing.
    $this->threadPool->setTimeLimit(2);
  }

  /**
   * Tests that thread dispatch results in the proper task settings.
   */
  public function testDispatch() {
    $server = new Server('myhost1');
    $this->serverStore->save($server);

    $thread = $this->getMock('Acquia\Wip\Runtime\Thread', array('dispatch'), array($server));
    $thread->setServerId($server->getId());
    $wip_id = rand();

    $wip = new BasicWip();
    $wip->setId($wip_id);
    $wip_iterator = WipFactory::getObject('acquia.wip.iterator');
    $wip_iterator->initialize($wip);
    $task = new Task();
    $task->setId($wip_id);
    $task->setWipIterator($wip_iterator);

    $thread->expects($this->once())
      ->method('dispatch')
      ->with($this->equalTo($task));

    $this->threadPool->dispatch($thread, $task);
    $this->assertNotEquals(Task::NOT_CLAIMED, $task->getClaimedTimestamp());
    $this->assertEquals(TaskStatus::PROCESSING, $task->getStatus());
  }

  /**
   * Tests releaseThread.
   */
  public function testRelease() {
    $task = new Task();
    $task->setId(rand());
    $exception = FALSE;
    try {
      $this->threadPool->releaseThread(new Thread());
    } catch (InvalidOperationException $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception);


    $servers = array(
      'myhost1' => 1,
    );
    $threads = array(
      'myhost1',
    );
    $this->setupThreads($servers, $threads);
    $threads = $this->threadStore->getActiveThreads();
    $this->threadPool->releaseThread(reset($threads));
  }

  /**
   * Tests stop.
   */
  public function testStop() {
    $this->assertFalse($this->threadPool->getQuit());
    $this->threadPool->stop();
    $this->assertTrue($this->threadPool->getQuit());
  }

  /**
   * Tests setDirectoryPrefix.
   */
  public function testDirectoryPrefix() {
    $text = rand() . ' - ' . rand();
    $this->threadPool->setDirectoryPrefix($text);
    $this->assertEquals($text, $this->threadPool->getDirectoryPrefix());
  }

  /**
   * Tests the behavior when the thread threshold has been exceeded.
   */
  public function testThresholdWarning() {
    $threshold = 2;
    WipFactory::addConfiguration('$acquia.wip.threadpool.threshold => ' . $threshold);
    $servers = array(
      'myhost1' => 1,
    );
    $this->setupThreads($servers);

    $mock_wip_pool = $this->mockInfiniteWipPool(10);

    $this->threadPool->dependencyManager->swapDependency('acquia.wip.pool', $mock_wip_pool);

    $this->setUpSshProcess();

    $this->threadPool->setTimeLimit(5);
    $this->threadPool->process();

    /** @var WipLogEntry[] $logs */
    $logs = $this->wipLog->getStore()->load(NULL, 0, 30, 'DESC');
    $found = FALSE;
    foreach ($logs as $log) {
      if (1 === preg_match('/Unable to obtain a thread for \d+ consecutive iterations/', $log->getMessage())) {
        $found = TRUE;
        break;
      }
    }
    $this->assertTrue($found);
  }

  /**
   * Sets up the mock SSH object to return a dummy SshProcess object.
   *
   * @param int $count
   *   If set, this specifies the exact number of times that Ssh::execAsync
   *   is expected to be called.  If it is not set, any number of times is
   *   acceptable.
   */
  private function setUpSshProcess($count = NULL) {
    $pid = rand();
    Environment::setRuntimeSitegroup('test');
    Environment::setRuntimeEnvironmentName('prod');
    $env = new Environment();

    /** @var PHPUnit_Framework_MockObject_MockObject $mock_ssh */
    $ssh_process = new SshProcess($env, 'TEST SSH PROCESS', $pid, time());
    $mock_ssh = $this->mockSsh;
    $mock_ssh->expects(isset($count) ? $this->exactly($count) : $this->any())
      ->method('execAsync')
      ->will($this->returnValue($ssh_process));
  }

  /**
   * Tests behavior when the Wip object is broken.
   */
  public function testBrokenWip() {
    // General setup ...
    $servers = array(
      'myhost1' => self::MAX_THREADS_PER_SERVER,
    );
    $this->setupThreads($servers);

    // Each task we attempt to get from the pool should blow up spectacularly.
    $exception = new NoObjectException('TEST NoObjectException - task failed to load.');
    $exception->setTaskId($random_id = rand(1, 1000000));
    $task = new Task();
    $task->setStatus(TaskStatus::NOT_STARTED);
    $task->setId($random_id);
    $mock_wip_pool = $this->getMock('Acquia\Wip\Runtime\WipPool', array('getNextTasks'));
    $mock_wip_pool->expects($this->any())
      ->method('getNextTasks')
      ->willReturn([$task]);
    $mock_thread_pool = $this->getMock('Acquia\Wip\Runtime\ThreadPool', array('executeTasks'));
    $mock_thread_pool->expects($this->any())
      ->method('executeTask')
      ->will($this->throwException($exception));

    $pool_store = new BasicWipPoolStore();
    $pool_store->initialize();
    // The exception handler in ThreadPool will expect this object to be
    // available in storage, even though we're already throwing an exception
    // during getNextTasks without actually loading it at that point. To avoid
    // the exception handler blowing up, we need to ensure that the task exists
    // in storage.
    $pool_store->save($task);
    $mock_wip_pool->dependencyManager->swapDependency('acquia.wip.storage.wippool', $pool_store);
    $mock_thread_pool->dependencyManager->swapDependency('acquia.wip.pool', $mock_wip_pool);
    $mock_thread_pool->dependencyManager->swapDependency('acquia.wip.storage.wippool', $pool_store);
    $mock_thread_pool->setTimeLimit(1);
    $mock_thread_pool->process();

    // Check that the exception is logged (implying also that it was caught). Load
    // the non-user-readable one explicitly and check that against the expected
    // message.
    /** @var WipLogEntry $log_entry */
    $logs = $this->wipLog->getStore()->load($random_id, 0, 30, 'DESC', WipLogLevel::FATAL, WipLogLevel::FATAL, FALSE);
    $found = FALSE;
    $message = sprintf('Failure in loading the iterator for task %d', $random_id);
    foreach ($logs as $log) {
      if ($random_id === $log->getObjectId() && strpos($log->getMessage(), $message) !== FALSE) {
        $found = TRUE;
        break;
      }
    }
    $this->assertTrue($found);

    // Check that the task is now errored-out (and in theory would not get
    // picked up on the next loop).
    $this->assertEquals(TaskExitStatus::ERROR_SYSTEM, $pool_store->get($random_id)->getExitStatus());
    $this->assertEquals(TaskStatus::COMPLETE, $pool_store->get($random_id)->getStatus());
  }

  /**
   * Tests the status callback.
   */
  public function testStatusCheck() {
    // General setup ...
    $servers = array(
      'myhost1' => self::MAX_THREADS_PER_SERVER,
    );
    $this->setupThreads($servers);

    $mock_wip_pool = $this->mockInfiniteWipPool(1);

    $this->threadPool->dependencyManager->swapDependency('acquia.wip.pool', $mock_wip_pool);

    $this->threadPool->setTimeLimit(1);
    $this->setUpSshProcess();

    $exception = FALSE;
    try {
      $this->threadPool->setStatusCheckCallback(array($this, 'failureCallback'));
      $this->threadPool->process();
    } catch (\RuntimeException $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception);

    $this->assertFalse($this->callbackTriggered);
    $this->threadPool->setStatusCheckCallback(array($this, 'successCallback'));
    $this->threadPool->process();

    $this->assertTrue($this->callbackTriggered);
  }

  /**
   * The success callback used for testing.
   */
  public function successCallback() {
    $this->callbackTriggered = TRUE;
    return TRUE;
  }

  /**
   * The failure callback used for testing.
   */
  public function failureCallback() {
    return FALSE;
  }

  /**
   * Creates a new task with a random ID.
   *
   * @param StateTableIteratorInterface $wip_iterator
   *   A Wip iterator.
   *
   * @return Task
   *   The new task.
   */
  private function makeNewTask(StateTableIteratorInterface $wip_iterator) {
    $task = new Task();
    $task->setStatus(TaskStatus::NOT_STARTED);
    $task->setWipIterator($wip_iterator);
    $task->setId(rand());
    return $task;
  }

  /**
   * Gets a WIP pool mock that returns a specific number of objects.
   *
   * @return PHPUnit_Framework_MockObject_MockObject
   *   The mock object.
   *
   * @throws \Acquia\Wip\Exception\TaskOverwriteException
   *   The exception.
   */
  private function mockInfiniteWipPool($task_count) {
    // Wip pool simulates given number of tasks in the pool.
    $wip = new BasicWip();
    $wip_iterator = WipFactory::getObject('acquia.wip.iterator');
    $wip_iterator->initialize($wip);
    $tasks = array();
    for ($i = 0; $i < $task_count; $i++) {
      $tasks[] = $this->makeNewTask($wip_iterator);
    }
    $this->assertEquals(TaskStatus::NOT_STARTED, $tasks[0]->getStatus());
    $this->assertEquals(TaskExitStatus::NOT_FINISHED, $tasks[0]->getExitStatus());
    /** @var WipPoolInterface $mock_wip_pool */
    $mock_wip_pool = $this->getMockBuilder('Acquia\Wip\Runtime\WipPool')
      ->setMethods(['getNextTasks'])
      ->getMock();
    $mock_wip_pool->expects($this->any())
      ->method('getNextTasks')
      ->will($this->returnValue($tasks));
    $wip_pool_store = $mock_wip_pool->dependencyManager->getDependency('acquia.wip.storage.wippool');
    $wip_pool_store->initialize();
    foreach ($tasks as $task) {
      $wip_pool_store->save($task);
    }

    return $mock_wip_pool;
  }

}
