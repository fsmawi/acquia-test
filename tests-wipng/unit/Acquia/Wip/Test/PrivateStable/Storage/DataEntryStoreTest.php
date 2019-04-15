<?php

namespace Acquia\Wip\Test\PrivateStable\Storage;

use Acquia\Wip\Implementation\SqliteDataEntryStore;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\Storage\RuntimeDataEntryStoreInterface;

/**
 * Missing summary.
 */
class DataEntryStoreTest extends \PHPUnit_Framework_TestCase {
  /**
   * The data store for the runtime data.
   *
   * @var RuntimeDataEntryStoreInterface
   */
  private $store = NULL;

  /**
   * The names of the queues used during this test.
   *
   * @var string[]
   */
  private $names = array('queue1', 'queue2');

  private $customerId = 'scratchy';

  /**
   * Missing summary.
   *
   * @var WipLog
   */
  private $wipLog = NULL;

  /**
   * Missing summary.
   *
   * @group RuntimeStats
   */
  public function setUp() {
    parent::setUp();
    $this->store = new SqliteDataEntryStore();
    foreach ($this->names as $name) {
      $this->store->delete($name);
    }
  }

  /**
   * Missing summary.
   *
   * @group RuntimeStats
   */
  public function testGetLogFilePath() {
    $log_path = $this->store->getLogFilePath();
    $this->assertNotNull($log_path);
  }

  /**
   * Missing summary.
   *
   * @group RuntimeStats
   */
  public function testEmpty() {
    $result = $this->store->load('queue1', $this->customerId);
    $this->assertEmpty($result);
  }

  /**
   * Missing summary.
   *
   * @group RuntimeStats
   */
  public function testSave() {
    $this->store->save('queue1', $this->customerId, 10);
    $this->store->load('queue1', $this->customerId);
  }

  /**
   * Missing summary.
   *
   * @group RuntimeStats
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSaveInvalidRoleNameType() {
    $this->store->save(42, $this->customerId, 10);
  }

  /**
   * Missing summary.
   *
   * @group RuntimeStats
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSaveEmptyRoleName() {
    $this->store->save('', $this->customerId, 10);
  }

  /**
   * Missing summary.
   *
   * @group RuntimeStats
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSaveInvalidCustomerIdType() {
    $this->store->save('queue1', FALSE, 10);
  }

  /**
   * Missing summary.
   *
   * @group RuntimeStats
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSaveEmptyCustomerId() {
    $this->store->save('queue1', '', 10);
  }

  /**
   * Missing summary.
   *
   * @group RuntimeStats
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSaveInvalidRunTimeType() {
    $this->store->save('queue1', $this->customerId, "I'm invalid!");
  }

  /**
   * Missing summary.
   *
   * @group RuntimeStats
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSaveNegativeRunTime() {
    $this->store->save('queue1', $this->customerId, -50);
  }

  /**
   * Missing summary.
   *
   * @group RuntimeStats
   */
  public function testUpdate() {
    $this->store->save('queue1', $this->customerId, 10);
    $this->store->save('queue1', $this->customerId, 20);
    $data = $this->store->load('queue1', $this->customerId);
    $this->assertEquals('queue1', $data->getName());
    $this->assertEquals(2, $data->getCount());
    $this->assertEquals(15, $data->getAverage());
    $this->assertEquals(500, $data->getSumOfTheDataSquared());
  }

  /**
   * Missing summary.
   *
   * @group RuntimeStats
   */
  public function testMultipleNames() {
    $this->store->save('queue1', $this->customerId, 10);
    $this->store->save('queue2', $this->customerId, 120);
    $this->store->save('queue2', $this->customerId, 45);
    $data = $this->store->load('queue2', $this->customerId);
    $this->assertEquals('queue2', $data->getName());
    $this->assertEquals(2, $data->getCount());
    $this->assertEquals(82.5, $data->getAverage());
    $this->assertEquals(16425, $data->getSumOfTheDataSquared());

    $data = $this->store->load('queue1', $this->customerId);
    $this->assertEquals('queue1', $data->getName());
    $this->assertEquals(1, $data->getCount());
    $this->assertEquals(10, $data->getAverage());
    $this->assertEquals(100, $data->getSumOfTheDataSquared());

    $wait_time = $data::getExpectedWaitTime('queue2', $this->customerId);
    $this->assertEquals(30, $wait_time);
  }

  /**
   * Missing summary.
   *
   * @group RuntimeStats
   */
  public function testDelete() {
    foreach ($this->names as $name) {
      $this->store->delete($name, $this->customerId);
      $data = $this->store->load($name, $this->customerId);
      $this->assertEmpty($data);
    }
  }

  /**
   * Missing summary.
   *
   * @group RuntimeStats
   */
  public function testDeleteWithoutCustomerId() {
    $name = $this->names[0];
    $this->store->save($name, $this->customerId, 0);
    $this->assertNotNull($this->store->load($name, $this->customerId));
    $this->store->delete($name);
    $this->assertEmpty($this->store->load($name, $this->customerId));
  }

  /**
   * Missing summary.
   *
   * @ground RuntimeStats
   *
   * @expectedException \InvalidArgumentException
   */
  public function testDeleteInvalidCustomerId() {
    $name = $this->names[0];
    $this->store->delete($name, 1234);
  }

  /**
   * Missing summary.
   *
   * @group RuntimeStats
   *
   * @expectedException \Exception
   */
  public function testMaxRuntime() {
    $this->store->save('queue1', $this->customerId, 120);
    $this->store->save('queue1', $this->customerId, 45);
    $data = $this->store->load('queue1', $this->customerId);

    // This throws an exception because there is not enough data to determine
    // the maximum run time.
    $data::getMaximumExpectedRuntime($data->getName(), $this->customerId);
  }

  /**
   * Missing summary.
   *
   * @group RuntimeStats
   *
   * @expectedException \InvalidArgumentException
   */
  public function testLoadInvalidName() {
    $this->store->load(FALSE, $this->customerId);
  }

  /**
   * Missing summary.
   *
   * @group RuntimeStats
   *
   * @expectedException \InvalidArgumentException
   */
  public function testLoadEmptyName() {
    $this->store->load('', $this->customerId);
  }

  /**
   * Missing summary.
   *
   * @group RuntimeStats
   *
   * @expectedException \InvalidArgumentException
   */
  public function testLoadInvalidCustomerId() {
    $this->store->load('queue1', 1234);
  }

  /**
   * Missing summary.
   *
   * @group RuntimeStats
   *
   * @expectedException \InvalidArgumentException
   */
  public function testLoadEmptyCustomerId() {
    $this->store->load('queue1', '');
  }

}
