<?php

namespace Acquia\Wip\Test\PrivateStable;

use Acquia\Wip\Storage\ConfigurationStoreInterface;

/**
 * Missing summary.
 */
class ConfigurationStoreTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @var ConfigurationStoreInterface
   */
  private $configurationStore;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    $this->configurationStore = \Acquia\Wip\WipFactory::getObject('acquia.wip.storage.configuration');
  }

  /**
   * Missing summary.
   */
  public function testSetGetDelete() {
    $test_value = new \stdClass();
    $test_value->test = array('one' => 1, 'two' => 2);
    $test_key = rand(1, 1000000);

    // Test set.
    $this->configurationStore->set($test_key, $test_value);

    // Test get.
    $this->assertEquals($test_value, $this->configurationStore->get($test_key));

    // Test same key returns NULL after deletion.
    $this->configurationStore->delete($test_key);
    $this->assertNull($this->configurationStore->get($test_key));
  }

  /**
   * Just check we get a NULL for any random key.
   */
  public function testNullGet() {
    $this->assertNull($this->configurationStore->get(rand(1, 1000000)));
  }

  /**
   * Missing summary.
   */
  public function testDefault() {
    $default = rand(1, 1000000);
    $value = $this->configurationStore->get(rand(1, 1000000), $default);
    $this->assertEquals($default, $value);
  }

}
