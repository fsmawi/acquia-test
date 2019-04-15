<?php

namespace Acquia\WipService\Test;

use Acquia\Wip\Lock\WipPoolRowLock;
use Acquia\Wip\Signal\Signal;
use Acquia\Wip\Signal\SignalType;
use Acquia\Wip\Storage\SignalStoreInterface;
use Acquia\Wip\WipFactory;

/**
 * Missing summary.
 */
class SignalStoreTest extends AbstractFunctionalTest {

  /**
   * The SignalStore instance.
   *
   * @var SignalStoreInterface
   */
  private $signalStore;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    WipFactory::setConfigPath('config/config.factory.test.cfg');
    $this->signalStore = WipFactory::getObject('acquia.wip.storage.signal');
  }

  /**
   * Missing summary.
   */
  private function createRandomSignal($object_id = NULL, $consumed = NULL) {
    $signal = new Signal();

    $signal->setObjectId(isset($object_id) ? $object_id : rand(1, 1000));
    $signal->setType(SignalType::COMPLETE);
    $signal->setSentTime(time());
    $signal->setConsumedTime(isset($consumed) ? $consumed : rand(1, time()));
    $signal->setData(new \stdClass());

    return $signal;
  }

  /**
   * Missing summary.
   */
  public function testSend() {
    $signal = $this->createRandomSignal();
    $this->signalStore->send($signal);
    $this->assertEquals($signal, $this->signalStore->load($signal->getId()));
  }

  /**
   * Test for an exception when performing an operation without a lock.
   */
  public function testLocking() {
    // Test locking.
    $lock_found = TRUE;

    if ((new WipPoolRowLock(1000000))->hasLock()) {
    } else {
      $lock_found = FALSE;
    }
    $this->assertEquals(FALSE, $lock_found);
  }

  /**
   * Missing summary.
   */
  public function testLoadAll() {
    $count = rand(1, 100);
    $object_id = rand(1, 1000000);
    $signals = array();

    for ($i = 0; $i < $count; ++$i) {
      $signal = $this->createRandomSignal($object_id);
      $this->signalStore->send($signal);
      $signals[] = $signal;
    }

    $this->assertEquals($signals, $this->signalStore->loadAll($object_id));
  }

  /**
   * Missing summary.
   */
  public function testLoadAllActive() {
    $count = rand(1, 100);
    $active_signals = array();
    $inactive_signals = array();
    $object_id = rand(1, 1000000);

    // Active signals (with consumed = 0).
    for ($i = 0; $i < $count; ++$i) {
      $signal = $this->createRandomSignal($object_id, 0);
      $this->signalStore->send($signal);
      $active_signals[] = $signal;
    }
    // Inactive signals (with consumed >= 1).
    for ($i = 0; $i < $count; ++$i) {
      $signal = $this->createRandomSignal($object_id);
      $this->signalStore->send($signal);
      $inactive_signals[] = $signal;
    }

    $loaded_active = $this->signalStore->loadAllActive($object_id);
    $this->assertEquals($active_signals, $loaded_active);

    // Check all active signals have consumed = 0.
    foreach ($loaded_active as $signal) {
      $this->assertEquals(0, $signal->getConsumedTime());
    }
    // Check none of the inactive signals were loaded as active.
    foreach ($inactive_signals as $signal) {
      $this->assertNotContains($signal, $active_signals);
    }
  }

  /**
   * Missing summary.
   */
  public function testConsume() {
    $count = rand(10, 100);
    $object_id = rand(1, 1000000);
    $signals = array();

    for ($i = 0; $i < $count; ++$i) {
      $signal = $this->createRandomSignal($object_id, 0);
      $this->signalStore->send($signal);
      $signals[] = $signal;
    }

    $keys = array_rand($signals, rand(2, ceil($count / 2)));

    foreach ($keys as $key) {
      $this->signalStore->consume($signals[$key]);
      unset($signals[$key]);
    }

    // We've removed some entries from the local copy, and consumed exactly
    // those entries, so these should be equal. (array_merge() is used here to
    // reindex the array from zero).
    $this->assertEquals(array_merge($signals), $this->signalStore->loadAllActive($object_id));
  }

  /**
   * Missing summary.
   */
  public function testDelete() {
    $count = rand(10, 100);
    $object_id = rand(1, 1000000);
    $signals = array();

    for ($i = 0; $i < $count; ++$i) {
      $signal = $this->createRandomSignal($object_id);
      $this->signalStore->send($signal);
      $signals[] = $signal;
    }

    $keys = array_rand($signals, rand(2, ceil($count / 2)));

    foreach ($keys as $key) {
      $this->signalStore->delete($signals[$key]);
      unset($signals[$key]);
    }

    // We've removed some entries from the local copy, and deleted exactly those
    // entries, so these should be equal. (array_merge() is used here to reindex
    // the array from zero).
    $this->assertEquals(array_merge($signals), $this->signalStore->loadAll($object_id));
  }

  /**
   * Missing summary.
   */
  public function testNullValues() {
    $signal = new Signal();
    $signal->setType(SignalType::COMPLETE);

    // Make a copy of the original, as we'll be modifying the signal in send().
    $signal_sent = clone $signal;

    $this->signalStore->send($signal_sent);

    $this->assertNotEquals($signal, $this->signalStore->load($signal_sent->getId()));
    $this->assertEquals(0, $signal_sent->getConsumedTime());
    // 5 seconds leeway for the queries.
    $this->assertGreaterThan(time() - 5, $signal_sent->getSentTime());
    $this->assertEquals(new \stdClass(), $signal_sent->getData());
    $this->assertEquals(0, $signal_sent->getObjectId());
  }

  /**
   * Missing summary.
   */
  public function testUpdate() {
    $test_data = new \stdClass();
    $test_data->test = 'TEST';

    $signal = $this->createRandomSignal();

    $this->signalStore->send($signal);

    $signal_modified = clone $signal;
    $signal_modified->setData($test_data);

    $this->signalStore->send($signal_modified);
    $loaded_signal = $this->signalStore->load($signal->getId());

    $this->assertNotEquals($signal, $loaded_signal);
    $this->assertEquals($test_data, $loaded_signal->getData());
  }

}
