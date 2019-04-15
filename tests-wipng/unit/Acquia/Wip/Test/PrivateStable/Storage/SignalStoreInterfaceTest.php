<?php

namespace Acquia\Wip\Test\PrivateStable\Storage;

use Acquia\Wip\Implementation\SqliteSignalStore;
use Acquia\Wip\Signal\Signal;
use Acquia\Wip\Signal\SignalType;
use Acquia\Wip\Storage\SignalStoreInterface;

/**
 * Missing summary.
 */
class SignalStoreInterfaceTest extends \PHPUnit_Framework_TestCase {

  /**
   * The signal store.
   *
   * @var SignalStoreInterface
   */
  private $signalStore;

  /**
   * The object ID.
   *
   * @var int
   */
  private $objectId = 11;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    $this->signalStore = new SqliteSignalStore();
    $signals = $this->signalStore->loadAll($this->objectId);
    foreach ($signals as $signal) {
      $this->signalStore->delete($signal);
    }
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testSend() {
    $signal = $this->createSignal();
    $this->signalStore->send($signal);

    $this->assertTrue($signal->getId() != 0);

    $signals = $this->signalStore->loadAllActive(11);
    $this->assertEquals(1, count($signals));

    $this->assertEquals($signal, $signals[0]);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testLoadById() {
    $signal = $this->createSignal();
    $this->signalStore->send($signal);
    $this->assertTrue($signal->getId() != 0);
    $new_signal = $this->signalStore->load($signal->getId());
    $this->assertEquals($signal, $new_signal);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testConsume() {
    $signal = $this->createSignal();
    $this->signalStore->send($signal);

    $this->assertEquals(0, $signal->getConsumedTime());
    $this->signalStore->consume($signal);
    $this->assertNotEquals(0, $signal->getConsumedTime());

    $loaded_signal = $this->signalStore->load($signal->getId());
    $this->assertEquals($signal, $loaded_signal);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testSendIncomplete() {
    $signal = new Signal();
    $signal->setType(SignalType::COMPLETE);
    $data = new \stdClass();
    $data->state = 'start';
    $signal->setData($data);
    $this->signalStore->send($signal);
    $this->assertEquals(0, $signal->getObjectId());
    $this->assertNotEmpty($signal->getSentTime());
  }

  /**
   * Missing summary.
   */
  private function createSignal() {
    $signal = new Signal();
    $signal->setType(SignalType::COMPLETE);
    $signal->setSentTime(time());
    $signal->setObjectId($this->objectId);
    $data = new \stdClass();
    $data->state = 'start';
    $signal->setData($data);
    return $signal;
  }

}
