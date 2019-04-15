<?php

namespace Acquia\Wip\Test\PublicStable\Signal;

use Acquia\Wip\Container\ContainerProcessInterface;
use Acquia\Wip\Container\ContainerResult;
use Acquia\Wip\Environment;
use Acquia\Wip\Implementation\ContainerApi;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\Iterators\BasicIterator\WipContext;
use Acquia\Wip\Signal\ContainerCompleteSignal;
use Acquia\Wip\Signal\SignalFactory;
use Acquia\Wip\Signal\SignalInterface;
use Acquia\Wip\Signal\SignalType;
use Acquia\Wip\Storage\SignalStoreInterface;
use Acquia\Wip\WipContainerInterface;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogInterface;

/**
 * Missing summary.
 */
class ContainerApiSignalTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @var WipContainerInterface
   */
  private $containerApi;

  /**
   * Missing summary.
   *
   * @var WipLogInterface
   */
  private $wipLog;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    $this->containerApi = new ContainerApi();
    $this->wipLog = new WipLog();
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @group Signal
   */
  public function testProcessSignal() {
    $start_time = time();
    $context = new WipContext();

    $process = $this->createContainerProcess();
    $this->containerApi->setContainerProcess($process, $context, $this->wipLog);
    $this->assertNotEmpty($this->containerApi->getContainerProcesses($context));
    $unique_id = ContainerResult::createUniqueId($process->getPid(), $process->getStartTime());

    $signal = new ContainerCompleteSignal();
    $signal->setPid($process->getPid());
    $signal->setStartTime($process->getStartTime());
    $signal->setEndTime($process->getStartTime() + 20);
    $signal->setType(SignalType::COMPLETE);
    $signal->setObjectId($process->getWipId());
    $this->assertSame($unique_id, $signal->getProcessId());

    $data = new \stdClass();
    $data->exitCode = 0;
    $data->pid = $process->getPid();
    $data->wipId = $process->getWipId();
    $data->classId = '$acquia.wip.signal.container.complete';
    $data->startTime = $signal->getStartTime();
    $data->endTime = $signal->getEndTime();
    $signal->setData($data);
    $signal_data = $signal->getData();
    $this->sendSignal($signal);

    $loaded_signal = SignalFactory::getDomainSpecificSignal($this->getSignalStore()->load($signal->getId()));
    $this->containerApi->processSignal($loaded_signal, $context, $this->wipLog);

    $verify_signal = $this->getSignalStore()->load($signal->getId());
    $this->assertInstanceOf('Acquia\Wip\Container\ContainerProcessInterface', $process);
    $this->assertGreaterThanOrEqual($start_time, $verify_signal->getConsumedTime());
    $this->assertEquals($verify_signal->getData(), $signal_data);

    $this->assertEmpty($this->containerApi->getContainerProcesses($context));
    $this->assertNotEmpty($this->containerApi->getContainerResults($context));
    $this->assertArrayHasKey($unique_id, $this->containerApi->getContainerResults($context));
  }

  /**
   * Provides parameters to test signal processing with missing object members.
   *
   * @return array
   *   A two-dimensional array containing sets of parameters.
   */
  public function signalMissingDataProvider() {
    $missing_pid = new \stdClass();
    $missing_pid->startTime = time();

    $missing_start_time = new \stdClass();
    $missing_start_time->pid = sprintf('pid%d', mt_rand());

    return array(
      array(new \stdClass()),
      array($missing_pid),
      array($missing_start_time),
    );
  }

  /**
   * Missing summary.
   *
   * @return ContainerProcessInterface
   *   The container process instance.
   */
  private function createContainerProcess() {
    $pid = sprintf('pid%d', mt_rand());
    $wip_id = mt_rand();
    $timestamp = time();
    Environment::setRuntimeSitegroup('local');
    Environment::setRuntimeEnvironmentName('prod');
    $environment = Environment::getRuntimeEnvironment();

    $process = $this->getMock('Acquia\Wip\Container\ContainerProcess', array(
      'getPid',
      'getWipId',
      'getStartTime',
      'getContainerProcess',
      'getResultFromSignal',
      'getResult',
      'isRunning',
      'kill',
      'release',
    ));

    $process->expects($this->any())
      ->method('getPid')
      ->will($this->returnValue($pid));
    $process->expects($this->any())
      ->method('getWipId')
      ->will($this->returnValue($wip_id));
    $process->expects($this->any())
      ->method('getStartTime')
      ->will($this->returnValue($timestamp));
    $process->expects($this->any())
      ->method('getContainerProcess')
      ->will($this->returnValue($process));

    $result = new ContainerResult();
    $result->setEnvironment($environment);
    $result->setPid($pid);
    $result->setWipId($wip_id);
    $result->setStartTime($timestamp);
    $result->setEndTime($timestamp + 20);
    $result->setExitCode(0);

    $process->expects($this->any())
      ->method('getResultFromSignal')
      ->will($this->returnValue($result));
    $process->expects($this->any())
      ->method('getResult')
      ->will($this->returnValue($result));
    $process->expects($this->any())
      ->method('kill')
      ->will($this->returnValue(NULL));
    $process->expects($this->any())
      ->method('release')
      ->will($this->returnValue(NULL));
    $process->expects($this->any())
      ->method('isRunning')
      ->will($this->returnValue(TRUE));

    return $process;
  }

  /**
   * Missing summary.
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
   *   The signal storage instance.
   */
  private function getSignalStore() {
    return WipFactory::getObject('acquia.wip.storage.signal');
  }

}
