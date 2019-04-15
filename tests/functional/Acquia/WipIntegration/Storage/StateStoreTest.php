<?php

namespace Acquia\WipService\Test;

use Acquia\WipIntegrations\DoctrineORM\StateStore;

/**
 * Tests that StateStore performs as expected.
 */
class StateStoreTest extends AbstractFunctionalTest {

  /**
   * The state storage instance.
   *
   * @var StateStore
   */
  private $storage;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // For some reason the singleton produced by the WipFactory makes tests go
    // peculiar - after a few tests, it complains about the entity manager being
    // "closed". Leave this as a concrete object for now.
    $this->storage = new StateStore();
  }

  /**
   * Missing summary.
   */
  public function testCrud() {
    $test_key = (string) rand(1, 1000000);
    $test_value = new \stdClass();
    $test_value->test = array(
      'one' => rand(1, 1000000),
      'two' => rand(1, 1000000),
    );

    // Test NULL for a not-found key.
    $this->assertNull($this->storage->get($test_key));

    // Test adding and retrieving.
    $this->storage->set($test_key, $test_value);
    $this->assertEquals($test_value, $this->storage->get($test_key));

    // Test delete and verify no longer found.
    $this->storage->delete($test_key);
    $this->assertNull($this->storage->get($test_key));

    $test_value2 = new \stdClass();
    $test_value2->test = array(
      'one' => rand(1, 1000000),
      'two' => rand(1, 1000000),
    );

    // Test modify existing.
    $this->storage->set($test_key, $test_value);
    $this->assertEquals($test_value, $this->storage->get($test_key));
    $this->storage->set($test_key, $test_value2);
    $this->assertEquals($test_value2, $this->storage->get($test_key));
  }

  /**
   * Missing summary.
   */
  public function testMulti() {
    for ($i = 0; $i < 20; ++$i) {
      $test_key = (string) rand(1, PHP_INT_MAX);
      $test_value = new \stdClass();
      $test_value->test = array(
        'one' => rand(1, PHP_INT_MAX),
        'two' => rand(1, PHP_INT_MAX),
      );

      // Test adding and retrieving.
      $this->storage->set($test_key, $test_value);
      $this->assertEquals($test_value, $this->storage->get($test_key));
    }
  }

}
