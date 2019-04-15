<?php

namespace Acquia\Wip\Test\PublicStable\Signal;

use Acquia\Wip\Signal\ContainerCompleteSignal;
use Acquia\Wip\Signal\Signal;
use Acquia\Wip\Signal\SignalType;

/**
 * Missing summary.
 */
class ContainerCompleteSignalTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @group ContainerApi
   */
  public function testInitializeFromSignal() {
    $signal = $this->getSignal();
    $new_signal = new ContainerCompleteSignal();
    $new_signal->initializeFromSignal($signal);
    // @todo test contents of signal?
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInitializeFromSignalWrongType() {
    $signal = $this->getSignal();
    $signal->setType(SignalType::TERMINATE);
    $new_signal = new ContainerCompleteSignal();
    $new_signal->initializeFromSignal($signal);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @group ContainerApi
   */
  public function testGetProcessId() {
    $signal = $this->getSignal();
    $new_signal = new ContainerCompleteSignal();
    $new_signal->initializeFromSignal($signal);
    $process_id = $new_signal->getProcessId();
    $this->assertNotNull($process_id);
  }

  /**
   * Missing summary.
   */
  private function getSignal() {
    $signal = new Signal();
    $signal->setType(SignalType::COMPLETE);
    $signal->setSentTime(time());
    $signal->setObjectId(mt_rand());
    $data = new \stdClass();
    $data->state = 'start';
    $data->server = 'localhost';
    $data->startTime = time();
    $data->endTime = time() + 20;
    $signal->setData($data);
    return $signal;
  }

}
