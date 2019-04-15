<?php

namespace Acquia\Wip\Test\PrivateStable;

use Acquia\Wip\Storage\BasicStateStore;

/**
 * Missing summary.
 */
class BasicStateStoreTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @var BasicStateStore
   */
  private $stateStore;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    $this->stateStore = new BasicStateStore();
  }

  /**
   * Missing summary.
   */
  public function testSetGetDelete() {
    $test_value = new \stdClass();
    $test_value->test = array('one' => 1, 'two' => 2);
    $test_key = (string) rand(1, 1000000);

    // Test set.
    $this->stateStore->set($test_key, $test_value);

    // Test get.
    $this->assertEquals($test_value, $this->stateStore->get($test_key));

    // Test same key returns NULL after deletion.
    $this->stateStore->delete($test_key);
    $this->assertNull($this->stateStore->get($test_key));
  }

  /**
   * Just check we get a NULL for any random key.
   */
  public function testNullGet() {
    $this->assertNull($this->stateStore->get((string) rand(1, 1000000)));
  }

}
