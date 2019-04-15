<?php

namespace Acquia\Wip\Test\PublicStable\Signal;

use Acquia\Wip\Signal\SignalType;
use Acquia\Wip\Signal\WipCallback;
use Acquia\Wip\Signal\WipCompleteSignal;
use Acquia\Wip\Storage\SignalStoreInterface;
use Acquia\Wip\WipFactory;

/**
 * Missing summary.
 */
class WipCallbackTest extends \PHPUnit_Framework_TestCase {
  private $wipId = 13;

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testConstructor() {
    $callback = new WipCallback($this->wipId);
    $description = $callback->getDescription();
    $this->assertNotEmpty($description);
    $this->assertContains(get_class($callback), $description);
    $this->assertContains(strval($this->wipId), $description);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testSend() {
    $this->deleteAllSignals();

    $callback = new WipCallback($this->wipId);
    $signal = new WipCompleteSignal();
    $signal->setType(SignalType::COMPLETE);
    $callback->send($signal);
    $signal_store = $this->getSignalStore();
    $signals = $signal_store->loadAll($this->wipId);
    $this->assertCount(1, $signals);
    /** @var WipCompleteSignal $result_signal */
    $result_signal = reset($signals);
    $this->assertEquals($this->wipId, $result_signal->getObjectId());
    $this->assertEquals(SignalType::COMPLETE, $result_signal->getType());
    $this->assertEquals(0, $result_signal->getConsumedTime());
    $this->assertInstanceOf('Acquia\Wip\Signal\WipCompleteSignal', $result_signal);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testGetData() {
    $this->deleteAllSignals();

    $callback = new WipCallback($this->wipId);
    $data = new \stdClass();
    $data->value = 'hello';
    $callback->setData($data);
    $new_data = $callback->getdata();
    $this->assertEquals($data, $new_data);
  }

  /**
   * Missing summary.
   */
  private function deleteAllSignals() {
    /*  @var SignalStoreInterface $signal_store */
    $signal_store = $this->getSignalStore();
    $signals = $signal_store->loadAll($this->wipId);
    foreach ($signals as $signal) {
      $signal_store->delete($signal);
    }
  }

  /**
   * Gets the signal storage object.
   *
   * @return SignalStoreInterface
   *   The signal store instance.
   */
  private function getSignalStore() {
    return WipFactory::getObject('acquia.wip.storage.signal');
  }

}
