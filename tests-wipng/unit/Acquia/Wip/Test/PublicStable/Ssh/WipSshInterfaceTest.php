<?php

namespace Acquia\Wip\Test\PublicStable\Ssh;

use Acquia\Wip\Implementation\SshApi;
use Acquia\Wip\Iterators\BasicIterator\WipContext;
use Acquia\Wip\Ssh\SshProcessInterface;
use Acquia\Wip\Ssh\SshResult;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipSshInterface;

/**
 * Missing summary.
 */
class WipSshInterfaceTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @var WipSshInterface
   */
  private $interface = NULL;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    $this->interface = new SshApi();
    $ssh_service = $this->getMock('Acquia\Wip\Ssh\SshService');

    // Install the mock object.
    WipFactory::addMapping('acquia.wip.ssh_service', get_class($ssh_service), TRUE);
  }

  /**
   * Missing summary.
   */
  public function tearDown() {
    parent::tearDown();
    WipFactory::reset();
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testSetSshResults() {
    $ssh_result = $this->createSshResult(0, 'hello', '');
    $wip_context = new WipContext();
    $this->interface->setSshResult($ssh_result, $wip_context, SshTestSetup::createWipLog());
    $results = $this->interface->getSshResults($wip_context);
    $this->assertEquals(1, count($results));
    reset($results);
    $result = current($results);
    $this->assertInstanceOf('Acquia\Wip\Ssh\SshResult', $result);
    $this->assertEquals('hello', $result->getStdout());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testSetSshResultsNotEmpty() {
    $ssh_result = $this->createSshResult(0, 'hello', '');
    $ssh_result2 = $this->createSshResult(0, 'world', '');
    $wip_context = new WipContext();
    $this->interface->addSshResult($ssh_result, $wip_context);
    $this->interface->setSshResult($ssh_result2, $wip_context, SshTestSetup::createWipLog());
    $results = $this->interface->getSshResults($wip_context);
    $this->assertEquals(1, count($results));
    reset($results);
    $result = current($results);
    $this->assertInstanceOf('Acquia\Wip\Ssh\SshResult', $result);
    $this->assertEquals($ssh_result2->getStdout(), $result->getStdout());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testClearSshResults() {
    $wip_context = new WipContext();
    $this->interface->setSshResult($this->createSshResult(0, 'hello', ''), $wip_context, SshTestSetup::createWipLog());
    $this->interface->addSshResult($this->createSshResult(0, 'world', ''), $wip_context);
    $results = $this->interface->getSshResults($wip_context);
    $this->assertEquals(2, count($results));
    $this->interface->clearSshResults($wip_context, SshTestSetup::createWipLog());
    $results = $this->interface->getSshResults($wip_context);
    $this->assertTrue(is_array($results));
    $this->assertEquals(0, count($results));
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testRemoveSshResult() {
    $ssh_result = $this->createSshResult(0, 'hello', '');
    $wip_context = new WipContext();
    $this->interface->setSshResult($ssh_result, $wip_context, SshTestSetup::createWipLog());
    $this->interface->addSshResult($this->createSshResult(0, 'world', ''), $wip_context);
    $results = $this->interface->getSshResults($wip_context);
    $this->assertEquals(2, count($results));
    $this->interface->removeSshResult($ssh_result, $wip_context);
    $results = $this->interface->getSshResults($wip_context);
    $this->assertTrue(is_array($results));
    $this->assertEquals(1, count($results));
    $remaining_result = current($results);
    $this->assertEquals('world', $remaining_result->getStdout());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testSetSshProcess() {
    $ssh_process = $this->createSshProcess('Testing');
    $wip_context = new WipContext();
    $this->interface->setSshProcess($ssh_process, $wip_context, SshTestSetup::createWipLog());
    $processes = $this->interface->getSshProcesses($wip_context);
    $this->assertEquals(1, count($processes));
    reset($processes);
    $process = current($processes);
    $this->assertInstanceOf('Acquia\Wip\Ssh\SshProcess', $process);
    $this->assertEquals('Testing', $process->getDescription());
    $process = $this->interface->getSshProcess($ssh_process->getUniqueId(), $wip_context);
    $this->assertNotEmpty($process);
    $this->assertEquals($ssh_process->getUniqueId(), $process->getUniqueId());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testSetSshProcessesNotEmpty() {
    $ssh_process = $this->createSshProcess('Testing1');
    $ssh_process2 = $this->createSshProcess('Testing2');
    $wip_context = new WipContext();
    $this->interface->addSshProcess($ssh_process, $wip_context);
    $this->interface->setSshProcess($ssh_process2, $wip_context, SshTestSetup::createWipLog());
    $processes = $this->interface->getSshProcesses($wip_context);
    $this->assertEquals(1, count($processes));
    reset($processes);
    $process = current($processes);
    $this->assertInstanceOf('Acquia\Wip\Ssh\SshProcess', $process);
    $this->assertEquals($ssh_process2->getDescription(), $process->getDescription());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testClearSshProcesses() {
    $wip_context = new WipContext();
    $process1 = $this->createSshProcess('Testing1', TRUE);
    $process2 = $this->createSshProcess('Testing2', FALSE);
    $this->interface->setSshProcess($process1, $wip_context, SshTestSetup::createWipLog());
    $this->interface->addSshProcess($process2, $wip_context);
    $processes = $this->interface->getSshProcesses($wip_context);
    $this->assertEquals(2, count($processes));
    $this->interface->clearSshProcesses($wip_context, SshTestSetup::createWipLog());
    $processes = $this->interface->getSshResults($wip_context);
    $this->assertTrue(is_array($processes));
    $this->assertEquals(0, count($processes));
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testRemoveSshProcesses() {
    $process1 = $this->createSshProcess('Testing1', TRUE);
    $process2 = $this->createSshProcess('Testing2', FALSE);
    $wip_context = new WipContext();
    $logger = SshTestSetup::createWipLog();
    $this->interface->setSshProcess($process1, $wip_context, $logger);
    $this->interface->addSshProcess($process2, $wip_context);
    $processes = $this->interface->getSshProcesses($wip_context);
    $this->assertEquals(2, count($processes));
    $this->interface->removeSshProcess($process1, $wip_context, $logger);
    $processes = $this->interface->getSshProcesses($wip_context);
    $this->assertTrue(is_array($processes));
    $this->assertEquals(1, count($processes));
    $remaining_process = current($processes);
    $this->assertEquals('Testing2', $remaining_process->getDescription());
    $this->interface->removeSshProcess($process2, $wip_context, $logger);
    $processes = $this->interface->getSshProcesses($wip_context);
    $this->assertEmpty($processes);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testGetSshStatusUninitialized() {
    $context = new WipContext();
    $logger = SshTestSetup::createWipLog();
    $result = $this->interface->getSshStatus($context, $logger);
    $this->assertEquals('uninitialized', $result);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testGetSshStatusSuccessResults() {
    $context = new WipContext();
    $logger = SshTestSetup::createWipLog();
    $result1 = $this->createSshResult(0, 'hello', '');
    $result2 = $this->createSshResult(0, 'world', '');
    $this->interface->setSshResult($result1, $context, $logger);
    $this->interface->addSshResult($result2, $context);
    $result = $this->interface->getSshStatus($context, $logger);
    $this->assertEquals('success', $result);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testGetSshStatusFailResults() {
    $context = new WipContext();
    $logger = SshTestSetup::createWipLog();
    $result1 = $this->createSshResult(0, 'hello', '');
    $result2 = $this->createSshResult(1, 'world', '');
    $this->interface->setSshResult($result1, $context, $logger);
    $this->interface->addSshResult($result2, $context);
    $result = $this->interface->getSshStatus($context, $logger);
    $this->assertEquals('fail', $result);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testGetSshStatusSshFailResults() {
    $context = new WipContext();
    $logger = SshTestSetup::createWipLog();
    $result1 = $this->createSshResult(0, 'hello', '');
    $result2 = $this->createSshResult(255, 'world', '');
    $this->interface->setSshResult($result1, $context, $logger);
    $this->interface->addSshResult($result2, $context);
    $result = $this->interface->getSshStatus($context, $logger);
    $this->assertEquals('ssh_fail', $result);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testGetSshStatusWaitResults() {
    $context = new WipContext();
    $logger = SshTestSetup::createWipLog();
    $result1 = $this->createSshResult(0, 'hello', '');
    $process1 = $this->createSshProcess('Hello');
    $this->interface->setSshResult($result1, $context, $logger);
    $this->interface->setSshProcess($process1, $context, $logger);
    $result = $this->interface->getSshStatus($context, $logger);
    $this->assertEquals('wait', $result);
  }

  /**
   * Missing summary.
   */
  private function createSshResult($exit_code, $stdout, $stderr) {
    $result = new SshResult($exit_code, $stdout, $stderr);
    $result->setPid(mt_rand(1, PHP_INT_MAX));
    $result->setStartTime(mt_rand(1, PHP_INT_MAX));
    $result->setEnvironment(SshTestSetup::setUpLocalSsh(FALSE));
    return $result;
  }

  /**
   * Missing summary.
   *
   * @param string $description
   *   The description.
   * @param bool $has_completed
   *   Whether or not it has completed.
   *
   * @return SshProcessInterface
   *   The ssh process object.
   */
  private function createSshProcess($description, $has_completed = FALSE) {
    $environment = SshTestSetup::setUpLocalSsh(FALSE);
    $pid = mt_rand(1, PHP_INT_MAX);
    $start_time = time() - mt_rand(10, 45);
    $wip_id = 15;
    $process = $this->getMock(
      'Acquia\Wip\Ssh\SshProcess',
      array(),
      array($environment, $description, $pid, $start_time, $wip_id)
    );
    $process->expects($this->any())
      ->method('getDescription')
      ->will($this->returnValue($description));
    $process->expects($this->any())
      ->method('getPid')
      ->will($this->returnValue($pid));
    $process->expects($this->any())
      ->method('getEnvironment')
      ->will($this->returnValue($environment));
    $process->expects($this->any())
      ->method('getStartTime')
      ->will($this->returnValue($start_time));
    $result = new SshResult(0, 'hello', '');
    $result->setPid($pid);
    $result->setEnvironment($environment);
    $result->setStartTime($start_time);
    $result->setEndTime(time());
    $process->expects($this->any())
      ->method('getResult')
      ->will($this->returnValue($result));
    $process->expects($this->any())
      ->method('getUniqueId')
      ->will($this->returnValue($result->getUniqueId()));
    $this->setProcessHasCompleted($process, $has_completed);
    return $process;
  }

  /**
   * Missing summary.
   *
   * @param \PHPUnit_Framework_MockObject_MockObject $process
   *   The mock process.
   * @param bool|false $has_completed
   *   Whether or not it has completed.
   */
  private function setProcessHasCompleted(\PHPUnit_Framework_MockObject_MockObject $process, $has_completed = FALSE) {
    $process->expects($this->any())
      ->method('hasCompleted')
      ->will($this->returnValue($has_completed));
  }

}
