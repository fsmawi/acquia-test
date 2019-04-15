<?php

namespace Acquia\Wip\Test\PublicStable\Signal;

use Acquia\Wip\Implementation\SshApi;
use Acquia\Wip\Iterators\BasicIterator\WipContext;
use Acquia\Wip\Signal\SignalFactory;
use Acquia\Wip\Signal\SignalInterface;
use Acquia\Wip\Signal\SignalType;
use Acquia\Wip\Signal\SshCompleteSignal;
use Acquia\Wip\Ssh\SshProcessInterface;
use Acquia\Wip\Ssh\SshResult;
use Acquia\Wip\Storage\SignalStoreInterface;
use Acquia\Wip\Test\PublicStable\Ssh\SshTestSetup;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipSshInterface;

/**
 * Missing summary.
 */
class SshApiSignalTest extends \PHPUnit_Framework_TestCase {

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
   * @group Signal
   */
  public function testProcessSignal() {
    $start_time = time();
    $context = new WipContext();
    $logger = SshTestSetup::createWipLog();
    $result1 = $this->createSshResult(0, 'hello', '');
    $process1 = $this->createSshProcess('Hello', FALSE);
    $this->interface->setSshResult($result1, $context, $logger);
    $this->interface->setSshProcess($process1, $context, $logger);


    $signal = new SshCompleteSignal();
    $signal->setStartTime($process1->getStartTime());
    $signal->setEndTime($process1->getStartTime() + 20);
    $signal->setType(SignalType::COMPLETE);
    $signal->setObjectId($process1->getWipId());
    $data = new \stdClass();
    $data->exitCode = 0;
    $data->pid = $process1->getPid();
    $data->stdout = 'hello';
    $data->stderr = 'there';
    $data->classId = '$acquia.wip.signal.ssh.complete';
    $data->server = 'localhost';
    $data->startTime = $signal->getStartTime();
    $data->endTime = $signal->getEndTime();
    $signal->setData($data);

    $this->sendSignal($signal);

    $loaded_signal = SignalFactory::getDomainSpecificSignal($this->getSignalStore()->load($signal->getId()));
    $this->interface->processSignal($loaded_signal, $context, $logger);

    $verify_signal = $this->getSignalStore()->load($signal->getId());
    $this->assertInstanceOf('Acquia\Wip\Ssh\SshProcessInterface', $process1);
    $this->assertGreaterThanOrEqual($start_time, $verify_signal->getConsumedTime());
  }

  /**
   * Missing summary.
   *
   * @param int $exit_code
   *   The exit code.
   * @param string $stdout
   *   The stdout.
   * @param string $stderr
   *   The stderr.
   *
   * @return SshResult
   *   The ssh result.
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
   * @param bool $is_running
   *   Whether or not it is running.
   *
   * @return SshProcessInterface
   *   The ssh process object.
   */
  private function createSshProcess($description, $is_running = TRUE) {
    $environment = SshTestSetup::setUpLocalSsh(FALSE);
    $pid = mt_rand(1, PHP_INT_MAX);
    $timestamp = mt_rand(1, PHP_INT_MAX);
    $wip_id = 15;
    $process = $this->getMock(
      'Acquia\Wip\Ssh\SshProcess',
      array('getResult', 'isRunning', 'kill', 'release'),
      array($environment, $description, $pid, $timestamp, $wip_id)
    );
    $process->expects($this->any())
      ->method('getStartTime')
      ->will($this->returnValue($timestamp));
    $result = new SshResult(0, 'hello', '');
    $result->setEnvironment($environment);
    $result->setPid($pid);
    $result->setStartTime($timestamp);
    $process->expects($this->any())
      ->method('getResult')
      ->will($this->returnValue($result));
    $process->expects($this->any())
      ->method('kill')
      ->will($this->returnValue(NULL));
    $process->expects($this->any())
      ->method('release')
      ->will($this->returnValue(NULL));

    $this->setProcessRunning($process, $is_running);
    return $process;
  }

  /**
   * Missing summary.
   *
   * @param \PHPUnit_Framework_MockObject_MockObject $process
   *   The mock process.
   * @param bool|true $running
   *   Whether or not it is running.
   */
  private function setProcessRunning(\PHPUnit_Framework_MockObject_MockObject $process, $running = TRUE) {
    $process->expects($this->any())
      ->method('isRunning')
      ->will($this->returnValue($running));
  }

  /**
   * Missing summary.
   *
   * @param SignalInterface $signal
   *   The signal.
   */
  private function sendSignal(SignalInterface $signal) {
    $signal_store = WipFactory::getObject('acquia.wip.storage.signal');
    if ($signal_store instanceof SignalStoreInterface) {
      $signal_store->send($signal);
    }
  }

  /**
   * Missing summary.
   *
   * @return SignalStoreInterface
   *   The signal store object.
   */
  private function getSignalStore() {
    $signal_store = WipFactory::getObject('acquia.wip.storage.signal');
    return $signal_store;
  }

}
