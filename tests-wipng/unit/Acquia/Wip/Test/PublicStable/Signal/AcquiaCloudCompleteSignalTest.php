<?php

namespace Acquia\Wip\Test\PublicStable\Signal;

use Acquia\Wip\AcquiaCloud\AcquiaCloudResult;
use Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudTaskInfo;
use Acquia\Wip\Signal\AcquiaCloudCompleteSignal;
use Acquia\Wip\Signal\Signal;
use Acquia\Wip\Signal\SignalType;
use Acquia\Wip\Test\Utility\DataProviderTrait;

/**
 * Missing summary.
 */
class AcquiaCloudCompleteSignalTest extends \PHPUnit_Framework_TestCase {

  use DataProviderTrait;

  private $pid = 134241;
  private $queue = 'vcs_deploy';
  private $state = AcquiaCloudTaskInfo::DONE;
  private $description = 'Deploy code';
  private $created = NULL;
  private $started = NULL;
  private $completed = NULL;
  private $sender = 'scratchy';
  private $result = 'result';
  private $cookie = 'cookie';
  private $logs = '[time] started';

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    $this->created = time() - 26;
    $this->started = time() - 25;
    $this->completed = time();
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testInitializeFromSignal() {
    $signal = $this->getSignal();
    $new_signal = new AcquiaCloudCompleteSignal();
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
    $new_signal = new AcquiaCloudCompleteSignal();
    $new_signal->initializeFromSignal($signal);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testGetQueue() {
    $signal = $this->getSignal();
    $new_signal = new AcquiaCloudCompleteSignal();
    $new_signal->initializeFromSignal($signal);
    $this->assertEquals($this->queue, $new_signal->getQueue());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testGetState() {
    $signal = $this->getSignal();
    $new_signal = new AcquiaCloudCompleteSignal();
    $new_signal->initializeFromSignal($signal);
    $this->assertEquals($this->state, $new_signal->getState());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testGetDescription() {
    $signal = $this->getSignal();
    $new_signal = new AcquiaCloudCompleteSignal();
    $new_signal->initializeFromSignal($signal);
    $this->assertEquals($this->description, $new_signal->getDescription());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testGetCreated() {
    $signal = $this->getSignal();
    $new_signal = new AcquiaCloudCompleteSignal();
    $new_signal->initializeFromSignal($signal);
    $this->assertEquals($this->created, $new_signal->getCreated());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testGetStarted() {
    $signal = $this->getSignal();
    $new_signal = new AcquiaCloudCompleteSignal();
    $new_signal->initializeFromSignal($signal);
    $this->assertEquals($this->started, $new_signal->getStartTime());
    $this->assertEquals($this->started, $new_signal->getStarted());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testGetCompleted() {
    $signal = $this->getSignal();
    $new_signal = new AcquiaCloudCompleteSignal();
    $new_signal->initializeFromSignal($signal);
    $this->assertEquals($this->completed, $new_signal->getEndTime());
    $this->assertEquals($this->completed, $new_signal->getCompleted());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testGetSender() {
    $signal = $this->getSignal();
    $new_signal = new AcquiaCloudCompleteSignal();
    $new_signal->initializeFromSignal($signal);
    $this->assertEquals($this->sender, $new_signal->getSender());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testGetResult() {
    $signal = $this->getSignal();
    $new_signal = new AcquiaCloudCompleteSignal();
    $new_signal->initializeFromSignal($signal);
    $this->assertEquals($this->result, $new_signal->getResult());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testGetCookie() {
    $signal = $this->getSignal();
    $new_signal = new AcquiaCloudCompleteSignal();
    $new_signal->initializeFromSignal($signal);
    $this->assertEquals($this->cookie, $new_signal->getCookie());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testGetLogs() {
    $signal = $this->getSignal();
    $new_signal = new AcquiaCloudCompleteSignal();
    $new_signal->initializeFromSignal($signal);
    $this->assertEquals($this->logs, $new_signal->getLogs());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testGetPid() {
    $signal = $this->getSignal();
    $new_signal = new AcquiaCloudCompleteSignal();
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
   * @dataProvider nonIntegerDataProvider
   */
  public function testSetPidWrongTypes($pid) {
    $signal = $this->getSignal();
    $new_signal = new AcquiaCloudCompleteSignal();
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
    $new_signal = new AcquiaCloudCompleteSignal();
    $new_signal->initializeFromSignal($signal);
    $this->assertEquals($this->started, $new_signal->getStartTime());
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
    $new_signal = new AcquiaCloudCompleteSignal();
    $data = $signal->getData();
    $data->started = 'hello';
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
    $new_signal = new AcquiaCloudCompleteSignal();
    $data = $signal->getData();
    $data->started = -15;
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
    $new_signal = new AcquiaCloudCompleteSignal();
    $new_signal->initializeFromSignal($signal);
    $process_id = AcquiaCloudResult::createUniqueId($this->pid);
    $this->assertEquals($process_id, $new_signal->getProcessId());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testGetEndTime() {
    $signal = $this->getSignal();
    $new_signal = new AcquiaCloudCompleteSignal();
    $new_signal->initializeFromSignal($signal);
    $this->assertEquals($this->completed, $new_signal->getEndTime());
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
    $new_signal = new AcquiaCloudCompleteSignal();
    $data = $signal->getData();
    $data->completed = 'hello';
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
    $new_signal = new AcquiaCloudCompleteSignal();
    $data = $signal->getData();
    $data->completed = -15;
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
    $data->id = $this->pid;
    $data->queue = $this->queue;
    $data->state = $this->state;
    $data->description = $this->description;
    $data->created = $this->created;
    $data->started = $this->started;
    $data->completed = $this->completed;
    $data->sender = $this->sender;
    $data->result = $this->result;
    $data->cookie = $this->cookie;
    $data->logs = $this->logs;
    $data->pid = $this->pid;
    $signal->setData($data);
    return $signal;
  }

}
