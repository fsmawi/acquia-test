<?php

namespace Acquia\Wip\Test\PublicStable\Signal;

use Acquia\Wip\Signal\DataSignal;
use Acquia\Wip\Signal\SignalType;

/**
 * Missing summary.
 */
class DataSignalTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testInstantiation() {
    $data_signal = new DataSignal();
    $this->assertNotEmpty($data_signal);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testPayload() {
    $id = mt_rand();
    $payload = new \stdClass();
    $payload->id = $id;
    $data_signal = new DataSignal();
    $data_signal->setPayload($payload);
    $payload = $data_signal->getPayload();
    $this->assertEquals($id, $payload->id);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testPayloadBeforeInitialization() {
    $data_signal = new DataSignal();
    $this->assertNotNull($data_signal->getPayload());
    $this->assertInstanceOf('\stdClass', $data_signal->getPayload());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetPayloadBadType() {
    $data_signal = new DataSignal();
    $data_signal->setPayload(15);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testInitializeFromSignalData() {
    $id = mt_rand();
    $payload = new \stdClass();
    $payload->id = $id;
    $signal_data = new \stdClass();
    $signal_data->payload = $payload;

    $data_signal = new DataSignal();
    $data_signal->initializeFromSignalData($signal_data);
    $payload = $data_signal->getPayload();
    $this->assertEquals($id, $payload->id);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testGetType() {
    $signal = new DataSignal();
    $this->assertEquals(SignalType::DATA, $signal->getType());
  }

}
