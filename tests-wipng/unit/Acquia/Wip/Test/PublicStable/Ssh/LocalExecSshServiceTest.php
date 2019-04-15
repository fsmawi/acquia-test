<?php

namespace Acquia\Wip\Test\PublicStable\Ssh;

use Acquia\Wip\Environment;
use Acquia\Wip\Ssh\LocalExecSshService;
use Acquia\Wip\Ssh\StatResultInterpreter;
use Acquia\Wip\WipLogEntryInterface;

/**
 * Missing summary.
 */
class LocalExecSshServiceTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   */
  public function testInstantiation() {
    $env = $this->createEnvironment();
    new LocalExecSshService($env, 'test key');

    new LocalExecSshService();
  }

  /**
   * Missing summary.
   */
  public function testExec() {
    $service = new LocalExecSshService();

    $result = $service->exec(sprintf('echo %d', $id = rand()));

    $this->assertEquals($id, trim($result->getStdout()));
    $this->assertEquals(0, $result->getExitCode());
    $this->assertEquals('', $result->getStderr());

    $this->assertTrue($result->isSuccess());
    // Should take no more than 2 sec to execute.
    $this->assertGreaterThanOrEqual(time() - 2, $result->getStartTime());
    $this->assertLessThanOrEqual(time(), $result->getStartTime());
  }

  /**
   * Missing summary.
   */
  public function testBadCommand() {
    $service = new LocalExecSshService();

    // You MUST NOT use /nonexistent as a nonexistent directory name: memcache
    // creates this directory, so it actually exists.  Someone learned this the
    // hard way.  This is being kept as legacy code even though we have removed
    // memcache dependency.
    $result = $service->exec(sprintf('ls /nonexistent%d', rand()));

    $this->assertFalse($result->isSuccess());
    $this->assertContains('No such file or directory', $result->getStderr());
    $this->assertGreaterThan(0, $result->getExitCode());
    $this->assertEquals('', $result->getStdout());
  }

  /**
   * Test that a fail is accepted as success when specified.
   */
  public function testAddSuccessCodes() {
    $service = new LocalExecSshService();

    // Accept exit code 1 and 2 as success. For file not found errors, Linux
    // tends to use 2 and Mac usually uses 1.
    $service->addSuccessExitCode(1);
    $service->addSuccessExitCode(2);

    // You MUST NOT use /nonexistent as a nonexistent directory name: memcache
    // creates this directory, so it actually exists.  Someone learned this the
    // hard way. This is being kept as legacy code even though we have removed
    // memcache dependency.
    $result = $service->exec(sprintf('ls /nonexistent%d', rand()));

    $this->assertTrue($result->isSuccess());
    $this->assertContains('No such file or directory', $result->getStderr());
    $this->assertGreaterThan(0, $result->getExitCode());
    $this->assertEquals('', $result->getStdout());
  }

  /**
   * Missing summary.
   */
  public function testAddInterpreter() {
    $ssh = new LocalExecSshService();
    $ssh->setResultInterpreter(new StatResultInterpreter('/tmp', 0));
    $interpreter = $ssh->getResultInterpreter();
    $this->assertInstanceOf('Acquia\Wip\Ssh\SshResultInterpreter', $interpreter);
    $ssh->exec('stat /tmp');
  }

  /**
   * Ensure that we can secure the ssh object.
   */
  public function testSecureExec() {
    $service = new LocalExecSshService();
    $service->setSecure(TRUE);
    $this->assertTrue($service->isSecure());

    $result = $service->exec(sprintf('echo %d', $id = rand()));

    $this->assertTrue($result->isSecure());
    $this->assertEquals(0, $result->getExitCode());
    $this->assertEquals(WipLogEntryInterface::OUTPUT_SUPPRESSED_MESSAGE, $result->getSecureStdout());
    $this->assertEquals(WipLogEntryInterface::OUTPUT_SUPPRESSED_MESSAGE, $result->getSecureStderr());
  }

  /**
   * Missing summary.
   */
  private function addServers(Environment $environment) {
    $environment->setServers(array('server1', 'server2', 'server3', 'server4'));
  }

  /**
   * Missing summary.
   */
  public function createEnvironment() {
    $result = new Environment();
    $result->setSitegroup('sitefactory');
    $result->setEnvironmentName('prod');
    $this->addServers($result);
    $result->selectNextServer();
    return $result;
  }

}
