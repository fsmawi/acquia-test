<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\Environment;
use Acquia\Wip\Exception\DependencyTypeException;
use Acquia\Wip\Runtime\Server;
use Acquia\Wip\Runtime\Thread;
use Acquia\Wip\Ssh\SshInterface;
use Acquia\Wip\Ssh\SshProcess;
use Acquia\Wip\Ssh\SshResult;
use Acquia\Wip\Storage\BasicThreadStore;
use Acquia\Wip\Task;
use Acquia\Wip\Test\PrivateStable\Runtime\Resource\BasicMutableThreadStore;
use Acquia\Wip\Test\PublicStable\Ssh\SshTestSetup;
use Acquia\Wip\ThreadStatus;
use Acquia\Wip\WipFactory;

/**
 * Missing summary.
 */
class ThreadTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @var Thread
   */
  private $thread;

  /**
   * Implicitly tests the constructor - no need to test this separately.
   */
  public function setup() {
    $server = new Server('test.example.com');
    $server_storage = WipFactory::getObject('acquia.wip.storage.server');
    $server_storage->initialize();
    $server_storage->save($server);

    $this->thread = new Thread();
    $this->thread->setServerId($server->getId());
  }

  /**
   * Missing summary.
   *
   * @expectedException \Acquia\Wip\Exception\ThreadIncompleteException
   */
  public function testDispatchMissingServer() {
    $thread = new Thread();
    $task = new Task();
    $thread->dispatch($task);
  }

  /**
   * Missing summary.
   *
   * @expectedException \Acquia\Wip\Exception\ThreadIncompleteException
   */
  public function testDispatchInvalidServer() {
    $thread = new Thread();
    $thread->setServerId(10000);

    $task = new Task();
    $thread->dispatch($task);
  }

  /**
   * Missing summary.
   */
  public function testDispatch() {
    /** @var SshInterface $mock_ssh */
    $mock_ssh = $this->getMock('\Acquia\Wip\Ssh\Ssh');
    $mock_ssh_service = $this->getMock('\Acquia\Wip\Ssh\SshService');
    $mock_ssh->expects($this->any())
      ->method('getSshService')
      ->will($this->returnValue($mock_ssh_service));
    $task = new Task();
    $task->setId(12345);
    $this->thread->setWipId(12345);

    Environment::setRuntimeSitegroup('sitegroup');
    Environment::setRuntimeEnvironmentName('prod');
    $env = new Environment();
    $ssh_process = new SshProcess($env, 'TEST', rand(), time());
    $mock_ssh->expects($this->once())
      ->method('execAsync')
      ->will($this->returnValue($ssh_process));
    $thread_store = new BasicMutableThreadStore($mock_ssh);
    $thread_store->save($this->thread);

    $this->thread->dependencyManager->swapDependency('acquia.wip.ssh.client', $mock_ssh);
    $this->thread->dependencyManager->swapDependency('acquia.wip.storage.thread', $thread_store);

    $this->thread->dispatch($task);
  }

  /**
   * Missing summary.
   */
  public function testSshDispatch() {
    // Just check that it's the SshService exec method that is called with normal
    // config, and not the LocalExecSshService.
    /** @var SshInterface $mock_ssh */
    $mock_ssh = $this->getMock('\Acquia\Wip\Ssh\Ssh', array('getSshService'));
    $mock_ssh_service = $this->getMock('\Acquia\Wip\Ssh\SshService');
    $mock_ssh_service->expects($this->once())
      ->method('exec')
      // Returns from the execAsync call an "OK" result, and output implying
      // that a remote process was started with PID 1.
      ->will($this->returnValue(new SshResult(0, '1', '')));
    $mock_ssh->expects($this->any())
      ->method('getSshService')
      ->will($this->returnValue($mock_ssh_service));
    $task = new Task();
    $task->setId(12345);
    $this->thread->setWipId(12345);

    Environment::setRuntimeSitegroup('sitegroup');
    Environment::setRuntimeEnvironmentName('prod');
    $thread_store = new BasicMutableThreadStore($mock_ssh);
    $thread_store->save($this->thread);

    $this->thread->dependencyManager->swapDependency('acquia.wip.ssh.client', $mock_ssh);
    $this->thread->dependencyManager->swapDependency('acquia.wip.storage.thread', $thread_store);
    $mock_local_ssh = $this->getMock('Acquia\Wip\Ssh\LocalExecSshService');
    $mock_local_ssh->expects($this->never())
      ->method('exec');
    $this->thread->dependencyManager->swapDependency('acquia.wip.ssh_service.local', $mock_local_ssh);

    $this->thread->dispatch($task);
  }

  /**
   * Missing summary.
   */
  public function testLocalDispatch() {
    // Reconfigure to set local execution. Just check that when configured for
    // local execution, we call the exec method of the LocalExecSshService, not
    // SshService.
    WipFactory::addConfiguration('$acquia.wip.worker_exec_method => local');

    $task = new Task();
    $task->setId(12345);
    $this->thread->setWipId(12345);

    $mock_local_ssh = $this->getMock('Acquia\Wip\Ssh\LocalExecSshService');
    $mock_local_ssh->expects($this->once())
      ->method('exec')
      ->will($this->returnValue(new SshResult(0, '1', '')));

    $thread_store = new BasicThreadStore();
    $thread_store->save($this->thread);

    $this->thread->dependencyManager->swapDependency('acquia.wip.ssh_service.local', $mock_local_ssh);
    $this->thread->dependencyManager->swapDependency('acquia.wip.storage.thread', $thread_store);

    $this->thread->dispatch($task);
  }

  /**
   * Missing summary.
   */
  public function testDependencies() {
    $this->thread->getDependencies();
  }

  /**
   * Missing summary.
   */
  public function testSwapDependencies() {
    $exception = FALSE;
    try {
      $this->thread->dependencyManager->swapDependency('acquia.wip.ssh.client', new \stdClass());
    } catch (DependencyTypeException $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception);

    // Test a mock that should be the correct type.
    $mock_ssh_client = $this->getMock('Acquia\Wip\Ssh\Ssh');
    $this->thread->dependencyManager->swapDependency('acquia.wip.ssh.client', $mock_ssh_client);
  }

  /**
   * Missing summary.
   */
  public function testIdMutator() {
    $id = rand();
    $this->thread->setId($id);
    $this->assertEquals($id, $this->thread->getId());
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidIdString() {
    $this->thread->setId('invalid');
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidIdNumeric() {
    $this->thread->setId(0);
  }

  /**
   * Missing summary.
   *
   * @expectedException \Acquia\Wip\Exception\ThreadOverwriteException
   */
  public function testIdOverwrite() {
    $this->thread->setId(1);
    $this->thread->setId(1);
  }

  /**
   * Missing summary.
   */
  public function testServerIdMutator() {
    $id = rand();
    $thread = new Thread();
    $thread->setServerId($id);
    $this->assertEquals($id, $thread->getServerId());
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidServerIdString() {
    $this->thread->setServerId('invalid');
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidServerIdNumeric() {
    $this->thread->setServerId(0);
  }

  /**
   * Missing summary.
   *
   * @expectedException \Acquia\Wip\Exception\ThreadOverwriteException
   */
  public function testServerIdOverwrite() {
    $this->thread->setServerId(1);
  }

  /**
   * Missing summary.
   */
  public function testWipIdMutator() {
    $id = rand();
    $this->thread->setWipId($id);
    $this->assertEquals($id, $this->thread->getWipId());
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidWipIdString() {
    $this->thread->setWipId('invalid');
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidWipIdNumeric() {
    $this->thread->setWipId(0);
  }

  /**
   * Missing summary.
   *
   * @expectedException \Acquia\Wip\Exception\ThreadOverwriteException
   */
  public function testWipIdOverwrite() {
    $this->thread->setWipId(1);
    $this->thread->setWipId(2);
  }

  /**
   * Missing summary.
   */
  public function testDefaultPid() {
    $this->assertTrue(is_int($this->thread->getPid()));
    $this->assertEquals(0, $this->thread->getPid());
  }

  /**
   * Missing summary.
   */
  public function testPidMutator() {
    $id = rand();
    $this->thread->setPid($id);
    $this->assertEquals($id, $this->thread->getPid());
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidPidString() {
    $this->thread->setPid('invalid');
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidPidNumeric() {
    $this->thread->setPid(-1);
  }

  /**
   * Missing summary.
   *
   * @expectedException \Acquia\Wip\Exception\ThreadOverwriteException
   */
  public function testPidOverwrite() {
    $this->thread->setPid(1);
    $this->thread->setPid(1);
  }

  /**
   * Missing summary.
   */
  public function testDefaultCreated() {
    $this->assertTrue(is_int($this->thread->getCreated()));
    $this->assertGreaterThan(0, $this->thread->getCreated());
  }

  /**
   * Missing summary.
   */
  public function testCreatedMutator() {
    $timestamp = rand();
    $this->thread->setCreated($timestamp);
    $this->assertEquals($timestamp, $this->thread->getCreated());
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidCreatedString() {
    $this->thread->setCreated('invalid');
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidCreatedNumeric() {
    $this->thread->setCreated(0);
  }

  /**
   * Missing summary.
   */
  public function testDefaultCompleted() {
    $this->assertTrue(is_int($this->thread->getCompleted()));
    $this->assertEquals(0, $this->thread->getCompleted());
  }

  /**
   * Missing summary.
   */
  public function testCompletedMutator() {
    $timestamp = rand();
    $this->thread->setCompleted($timestamp);
    $this->assertEquals($timestamp, $this->thread->getCompleted());
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidCompletedString() {
    $this->thread->setCompleted('invalid');
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidCompletedNumeric() {
    $this->thread->setCompleted(-1);
  }

  /**
   * Missing summary.
   */
  public function testDefaultStatus() {
    $this->assertEquals(ThreadStatus::RESERVED, $this->thread->getStatus());
  }

  /**
   * Missing summary.
   */
  public function testStatusMutator() {
    $this->thread->setStatus(ThreadStatus::RUNNING);
    $this->assertEquals(ThreadStatus::RUNNING, $this->thread->getStatus());
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidStatus() {
    $this->thread->setStatus(NULL);
  }

  /**
   * Missing summary.
   */
  public function testDefaultSshOutput() {
    $this->assertTrue(is_string($this->thread->getSshOutput()));
    $this->assertEquals('', $this->thread->getSshOutput());
  }

  /**
   * Missing summary.
   */
  public function testSshOutputMutator() {
    $text = rand() . ' - ' . rand();
    $this->thread->setSshOutput($text);
    $this->assertEquals($text, $this->thread->getSshOutput());
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidSshOutput() {
    $this->thread->setSshOutput(NULL);
  }

  /**
   * Missing summary.
   */
  public function testDirectoryPrefix() {
    $text = rand() . ' - ' . rand();
    $this->thread->setDirectoryPrefix($text);
    $this->assertEquals($text, $this->thread->getDirectoryPrefix());
  }

  /**
   * Tests that setting a process works.
   */
  public function testSetProcess() {
    $pid = '1234';
    $start_time = time();
    $wip_id = 15;
    $environment = new Environment();
    $process = new SshProcess($environment, 'test', $pid, $start_time, $wip_id);
    $this->thread->setProcess($process);
    $this->assertEquals($process, $this->thread->getProcess());
  }

}
