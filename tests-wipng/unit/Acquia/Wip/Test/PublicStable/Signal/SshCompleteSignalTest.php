<?php

namespace Acquia\Wip\Test\PublicStable\Signal;

use Acquia\Wip\Signal\Signal;
use Acquia\Wip\Signal\SignalType;
use Acquia\Wip\Signal\SshCompleteSignal;
use Acquia\Wip\Ssh\SshResult;
use Acquia\Wip\Test\Utility\DataProviderTrait;

/**
 * Missing summary.
 */
class SshCompleteSignalTest extends \PHPUnit_Framework_TestCase {

  use DataProviderTrait;

  private $pid = 134241;
  private $state = 'start';
  private $startTime = NULL;
  private $server = 'web-43';
  private $endTime = NULL;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    $this->startTime = time() - 25;
    $this->endTime = time();
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testInitializeFromSignal() {
    $signal = $this->getSignal();
    $new_signal = new SshCompleteSignal();
    $new_signal->initializeFromSignal($signal);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInitializeFromSignalWrongType() {
    $signal = $this->getSignal();
    $signal->setType(SignalType::TERMINATE);
    $new_signal = new SshCompleteSignal();
    $new_signal->initializeFromSignal($signal);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testGetState() {
    $signal = $this->getSignal();
    $new_signal = new SshCompleteSignal();
    $new_signal->initializeFromSignal($signal);
    $this->assertEquals($this->state, $new_signal->getState());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testGetPid() {
    $signal = $this->getSignal();
    $new_signal = new SshCompleteSignal();
    $new_signal->initializeFromSignal($signal);
    $this->assertEquals($this->pid, $new_signal->getPid());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @expectedException \InvalidArgumentException
   *
   * @dataProvider nonPositiveIntegerDataProvider
   */
  public function testSetPidWrongTypes($pid) {
    $signal = $this->getSignal();
    $new_signal = new SshCompleteSignal();
    $new_signal->initializeFromSignal($signal);
    $new_signal->setPid($pid);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testGetStartTime() {
    $signal = $this->getSignal();
    $new_signal = new SshCompleteSignal();
    $new_signal->initializeFromSignal($signal);
    $this->assertEquals($this->startTime, $new_signal->getStartTime());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @expectedException \InvalidArgumentException
   */
  public function testNonIntStartTime() {
    $signal = $this->getSignal();
    $new_signal = new SshCompleteSignal();
    $data = $signal->getData();
    $data->startTime = 'hello';
    $signal->setData($data);
    $new_signal->initializeFromSignal($signal);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @expectedException \InvalidArgumentException
   */
  public function testNegativeStartTime() {
    $signal = $this->getSignal();
    $new_signal = new SshCompleteSignal();
    $data = $signal->getData();
    $data->startTime = -15;
    $signal->setData($data);
    $new_signal->initializeFromSignal($signal);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testGetProcessId() {
    $signal = $this->getSignal();
    $new_signal = new SshCompleteSignal();
    $new_signal->initializeFromSignal($signal);
    $process_id = SshResult::createUniqueId($this->server, $this->pid, $this->startTime);
    $this->assertEquals($process_id, $new_signal->getProcessId());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testGetEndTime() {
    $signal = $this->getSignal();
    $new_signal = new SshCompleteSignal();
    $new_signal->initializeFromSignal($signal);
    $this->assertEquals($this->endTime, $new_signal->getEndTime());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testMissingEndTime() {
    $signal = $this->getSignal();
    $new_signal = new SshCompleteSignal();
    $data = $signal->getData();
    unset($data->endTime);
    $signal->setData($data);
    $new_signal->initializeFromSignal($signal);
    $this->assertEquals(NULL, $new_signal->getEndTime());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @expectedException \InvalidArgumentException
   */
  public function testNonIntEndTime() {
    $signal = $this->getSignal();
    $new_signal = new SshCompleteSignal();
    $data = $signal->getData();
    $data->endTime = 'hello';
    $signal->setData($data);
    $new_signal->initializeFromSignal($signal);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @expectedException \InvalidArgumentException
   */
  public function testNegativeEndTime() {
    $signal = $this->getSignal();
    $new_signal = new SshCompleteSignal();
    $data = $signal->getData();
    $data->endTime = -15;
    $signal->setData($data);
    $new_signal->initializeFromSignal($signal);
  }

  /**
   * Missing summary.
   */
  private function getSignal() {
    $signal = new Signal();
    $signal->setType(SignalType::COMPLETE);
    $signal->setSentTime(time());
    $signal->setObjectId(11);
    $data = new \stdClass();
    $data->state = $this->state;
    $data->server = $this->server;
    $data->pid = $this->pid;
    $data->startTime = $this->startTime;
    $data->endTime = $this->endTime;
    $signal->setData($data);
    return $signal;
  }

}
