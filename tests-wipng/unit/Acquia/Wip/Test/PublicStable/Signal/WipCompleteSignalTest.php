<?php

namespace Acquia\Wip\Test\PublicStable\Signal;

use Acquia\Wip\Signal\Signal;
use Acquia\Wip\Signal\SignalType;
use Acquia\Wip\Signal\WipCompleteSignal;
use Acquia\Wip\WipTaskResult;

/**
 * Missing summary.
 */
class WipCompleteSignalTest extends \PHPUnit_Framework_TestCase {

  private $wipId = 134;
  private $state = 'start';
  private $startTime = NULL;
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
    $new_signal = new WipCompleteSignal();
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
    $new_signal = new WipCompleteSignal();
    $new_signal->initializeFromSignal($signal);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testGetState() {
    $signal = $this->getSignal();
    $new_signal = new WipCompleteSignal();
    $new_signal->initializeFromSignal($signal);
    $this->assertEquals($this->state, $new_signal->getState());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testGetStartTime() {
    $signal = $this->getSignal();
    $new_signal = new WipCompleteSignal();
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
    $new_signal = new WipCompleteSignal();
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
    $new_signal = new WipCompleteSignal();
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
    $new_signal = new WipCompleteSignal();
    $new_signal->initializeFromSignal($signal);
    $process_id = WipTaskResult::createUniqueId($this->wipId);
    $this->assertEquals($process_id, $new_signal->getProcessId());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testGetCompletedWipId() {
    $signal = $this->getSignal();
    $new_signal = new WipCompleteSignal();
    $new_signal->initializeFromSignal($signal);
    $this->assertEquals($this->wipId, $new_signal->getCompletedWipId());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @expectedException \InvalidArgumentException
   */
  public function testNonIntCompleteWidId() {
    $signal = $this->getSignal();
    $new_signal = new WipCompleteSignal();
    $data = $signal->getData();
    $data->completedWipId = 'hello';
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
  public function testNegativeCompleteWidId() {
    $signal = $this->getSignal();
    $new_signal = new WipCompleteSignal();
    $data = $signal->getData();
    $data->completedWipId = -15;
    $signal->setData($data);
    $new_signal->initializeFromSignal($signal);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testGetEndTime() {
    $signal = $this->getSignal();
    $new_signal = new WipCompleteSignal();
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
    $new_signal = new WipCompleteSignal();
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
    $new_signal = new WipCompleteSignal();
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
    $new_signal = new WipCompleteSignal();
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
    $data->completedWipId = $this->wipId;
    $data->state = $this->state;
    $data->startTime = $this->startTime;
    $data->endTime = $this->endTime;
    $signal->setData($data);
    return $signal;
  }

}
