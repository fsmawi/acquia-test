<?php

// TODO: Add DynamicDependency interface and DependencyChecker interface, add
// to implementation.
namespace Acquia\Wip\Test;

use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\WipFactory;

/**
 * Missing summary.
 */
class WipFactoryTest extends \PHPUnit_Framework_TestCase {

  private $configuration = <<<EOT
Acquia.Wip.WipInterface => Acquia\Wip\Implementation\BasicWip
acquia.wip.api => Acquia\Wip\Implementation\WipTaskApi
acquia.wip.handler.signal => \Acquia\Wip\Signal\TestCallbackHttpTransport singleton
acquia.wip.ssh => \Acquia\Wip\Implementation\SshApi
acquia.wip.ssh.client => \Acquia\Wip\Ssh\Ssh
acquia.wip.ssh_service => \Acquia\Wip\Ssh\SshService
acquia.wip.ssh_service.local => Acquia\Wip\Ssh\LocalExecSshService
acquia.wip.acquiacloud => Acquia\Wip\Implementation\AcquiaCloudApi
acquia.wip.storage.wippool => \Acquia\Wip\Storage\BasicWipPoolStore singleton
acquia.wip.pool => \Acquia\Wip\Runtime\WipPool
acquia.wip.storage.wip => Acquia\Wip\Storage\BasicWipStore singleton
acquia.wip.storage.signal => \Acquia\Wip\Implementation\SqliteSignalStore singleton
acquia.wip.containers => \Acquia\Wip\Implementation\ContainerApi
acquia.wip.wiplog => Acquia\Wip\Implementation\WipLog
acquia.wip.wiplogstore => \Acquia\Wip\Implementation\SqliteWipLogStore
acquia.wip.notification => \Acquia\Wip\Implementation\NullNotifier
acquia.wip.metrics.relay => \Acquia\Wip\Implementation\NullMetricsRelay singleton
EOT;

  private $configuration2 = <<<EOT
Acquia.Wip.Iterators.BasicIterator.StateMachine => Acquia\Wip\Iterators\BasicIterator\StateMachine
EOT;

  private $brokenConfiguration = <<<EOT
Acquia.Wip.Storage\WipStore =
EOT;

  private $singletonConfiguration = <<<EOT
SingleWip => Acquia\Wip\Implementation\BasicWip Singleton
acquia.wip.api => Acquia\Wip\Implementation\WipTaskApi
acquia.wip.pool => \Acquia\Wip\Runtime\WipPool
EOT;

  /**
   * Missing summary.
   */
  public function tearDown() {
    WipFactory::setConfigPath('tests-wipng/unit/Acquia/Wip/Test/factory.cfg');
    WipFactory::reset();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testNullConfigurationPath() {
    WipFactory::setConfigPath(NULL);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testEmptyConfigurationPath() {
    WipFactory::setConfigPath('');
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testMissingConfigurationFile() {
    WipFactory::setConfigPath('this_file_does_not_exist');
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testEqualConfigurationPath() {
    WipFactory::setConfigPath('tests-wipng/unit/Acquia/Wip/Test/factory.cfg');
    $this->assertEquals('tests-wipng/unit/Acquia/Wip/Test/factory.cfg', WipFactory::getConfigPath());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testNonEqualConfigurationPath() {
    WipFactory::setConfigPath('tests-wipng/unit/Acquia/Wip/Test/../Test/factory.cfg');
    $this->assertEquals('tests-wipng/unit/Acquia/Wip/Test/../Test/factory.cfg', WipFactory::getConfigPath());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testConfigurationPathVariableReset() {
    // Some of the configuration fields do not have a getter method, so to
    // verify that their values are being reset when a new configuration path is
    // being added, we have to force our way through using ReflectionClass.
    $wip_factory = new WipFactory();
    $reflector = new \ReflectionClass($wip_factory);
    $configuration_field = $reflector->getProperty('configuration');
    $configuration_field->setAccessible(TRUE);
    $interface_map_field = $reflector->getProperty('interfaceMap');
    $interface_map_field->setAccessible(TRUE);

    // Set some value to the configuration and the mapping variable to see that
    // they are getting wiped later.
    $value = mt_rand();
    $configuration_field->setValue($wip_factory, $value);
    $this->assertEquals($value, $configuration_field->getValue($wip_factory));
    $value = mt_rand();
    $interface_map_field->setValue($wip_factory, $value);
    $this->assertEquals($value, $interface_map_field->getValue($wip_factory));

    $wip_factory::setConfigPath('tests-wipng/unit/Acquia/Wip/Test/../Test/factory.cfg');
    $this->assertNull($configuration_field->getValue($wip_factory));
    $this->assertNull($interface_map_field->getValue($wip_factory));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testReset() {
    $interface = 'Acquia\Wip\Wip';
    $concrete_class = 'Acquia\Wip\Implementation\BasicWip';
    WipFactory::addMapping($interface, $concrete_class);
    $object_before = WipFactory::getObject($interface);
    $this->assertNotEmpty($object_before);
    WipFactory::reset();
    $object_after = WipFactory::getObject($interface);
    $this->assertEmpty($object_after);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \UnexpectedValueException
   */
  public function testResetWithBadConfig() {
    WipFactory::addConfiguration('invalid.configuration');
    WipFactory::reset();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testGetObject() {
    $interface = 'Acquia\Wip\WipInterface';
    $concrete_class = 'Acquia\Wip\Implementation\BasicWip';
    WipFactory::addMapping($interface, $concrete_class);
    $obj = WipFactory::getObject($interface);
    $this->assertInstanceOf($interface, $obj);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testAddMappingNonString() {
    $concrete_class = 'Acquia\Wip\Implementation\BasicWip';
    WipFactory::addMapping(array('non_empty' => TRUE), $concrete_class);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testAddMappingEmptyString() {
    $concrete_class = 'Acquia\Wip\Implementation\BasicWip';
    WipFactory::addMapping('', $concrete_class);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testAddMappingClassNotString() {
    $interface = 'Acquia\Wip\Wip';
    WipFactory::addMapping($interface, array('non_empty' => TRUE));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testAddMappingClassIsEmptyString() {
    $interface = 'Acquia\Wip\Wip';
    WipFactory::addMapping($interface, '');
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testAddMappingClassIsNull() {
    $interface = 'Acquia\Wip\Wip';
    WipFactory::addMapping($interface, NULL);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testAddMappingBadSingleton() {
    $interface = 'Acquia\Wip\Wip';
    $concrete_class = 'Acquia\Wip\Implementation\BasicWip';

    // The singleton argument must be a boolean.
    WipFactory::addMapping($interface, $concrete_class, mt_rand());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testMultipleInstanceObject() {
    $interface = 'Acquia\Wip\Wip';
    $concrete_class = 'Acquia\Wip\Implementation\BasicWip';
    $singleton = FALSE;
    WipFactory::addMapping($interface, $concrete_class, $singleton);
    $obj1 = WipFactory::getObject($interface);
    $obj2 = WipFactory::getObject($interface);
    $this->assertFalse($obj1 === $obj2);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testSingletonObject() {
    $interface = 'Acquia\Wip\Wip';
    $concrete_class = 'Acquia\Wip\Implementation\BasicWip';
    $singleton = TRUE;
    WipFactory::addMapping($interface, $concrete_class, $singleton);
    $obj1 = WipFactory::getObject($interface);
    $obj2 = WipFactory::getObject($interface);
    $this->assertTrue($obj1 === $obj2);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testSetConfiguration() {
    // The configuration field is private and does not have a getter method, so
    // to be able to check its content we have to force our way using
    // ReflectionClass.
    $wip_factory = new WipFactory();
    $reflector = new \ReflectionClass($wip_factory);
    $configuration_field = $reflector->getProperty('configuration');
    $configuration_field->setAccessible(TRUE);

    $wip_factory::setConfiguration($this->configuration);
    $obj = $wip_factory::getObject('Acquia.Wip.WipInterface');
    $this->assertInstanceOf('Acquia\Wip\WipInterface', $obj);
    $this->assertEquals($this->configuration, $configuration_field->getValue($wip_factory));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testNullConfiguration() {
    WipFactory::setConfiguration(NULL);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testEmptyConfiguration() {
    WipFactory::setConfiguration('');
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testAddConfigurationNonString() {
    WipFactory::addConfiguration(15);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testAddConfigurationNull() {
    WipFactory::addConfiguration(NULL);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testAddConfigurationEmpty() {
    WipFactory::addConfiguration('');
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testAddConfiguration() {
    // The configuration field is private and does not have a getter method, so
    // to be able to reset its content we have to force our way using
    // ReflectionClass.
    $wip_factory = new WipFactory();
    $reflector = new \ReflectionClass($wip_factory);
    $configuration_field = $reflector->getProperty('configuration');
    $configuration_field->setAccessible(TRUE);
    $configuration_field->setValue($wip_factory, NULL);
    $this->assertNull($configuration_field->getValue($wip_factory));

    $wip_factory::addConfiguration($this->configuration);

    $obj = $wip_factory::getObject('Acquia.Wip.WipInterface');
    $this->assertInstanceOf('Acquia\Wip\WipInterface', $obj);
    $this->assertContains($this->configuration, $configuration_field->getValue($wip_factory));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testAddConfigurationAfterSetConfiguration() {
    // The configuration field is private and does not have a getter method, so
    // to be able to check its content we have to force our way using
    // ReflectionClass.
    $wip_factory = new WipFactory();
    $reflector = new \ReflectionClass($wip_factory);
    $configuration_field = $reflector->getProperty('configuration');
    $configuration_field->setAccessible(TRUE);

    $wip_factory::setConfiguration($this->configuration);
    $wip_factory::addConfiguration($this->configuration2);
    $obj = $wip_factory::getObject('Acquia.Wip.WipInterface');
    $this->assertInstanceOf('Acquia\Wip\WipInterface', $obj);
    $obj2 = $wip_factory::getObject('Acquia.Wip.Iterators.BasicIterator.StateMachine');
    $this->assertInstanceOf('Acquia\Wip\Iterators\BasicIterator\StateMachine', $obj2);
    $this->assertEquals(
      $this->configuration . "\n" . $this->configuration2,
      $configuration_field->getValue($wip_factory)
    );
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testAddConfigurationWithEmptyConfiguration() {
    WipFactory::addConfiguration($this->configuration);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testAddConfigurationVariableReset() {
    // Some of the configuration fields do not have a getter method, so to
    // verify their values after a new configuration is being added, we have to
    // force our way through using ReflectionClass.
    $wip_factory = new WipFactory();
    $reflector = new \ReflectionClass($wip_factory);
    $configuration_path_field = $reflector->getProperty('configurationPath');
    $configuration_path_field->setAccessible(TRUE);
    $interface_map_field = $reflector->getProperty('interfaceMap');
    $interface_map_field->setAccessible(TRUE);

    // Add something to the configuration path field.
    $wip_factory::setConfigPath('tests-wipng/unit/Acquia/Wip/Test/../Test/factory.cfg');
    $value = mt_rand();
    $interface_map_field->setValue($wip_factory, $value);
    $this->assertEquals($value, $interface_map_field->getValue($wip_factory));

    // Add configuration and ensure check that the other variables are being
    // wiped.
    $wip_factory::addConfiguration($this->configuration);
    $this->assertNotNull($configuration_path_field->getValue($wip_factory));
    $this->assertTrue(is_array($interface_map_field->getValue($wip_factory)));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testGetObjectNonStringInterface() {
    WipFactory::getObject(array('not_empty' => TRUE));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testGetObjectEmptyString() {
    WipFactory::getObject('');
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testGetObjectInvalidClass() {
    WipFactory::getObject('non-existent-class');
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \Exception
   */
  public function testBadConfiguration() {
    WipFactory::addConfiguration($this->brokenConfiguration);
    WipFactory::getObject('Acquia.Wip.Storage.WipStore');
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testConfigurationSingleton() {
    $state_table = <<<EOT
    start {
      * finish
    }
EOT;

    WipFactory::addConfiguration($this->singletonConfiguration);
    $wip1 = WipFactory::getObject('SingleWip');
    $wip1->setStateTable($state_table);
    $wip2 = WipFactory::getObject('SingleWip');
    $this->assertEquals($wip1, $wip2);

    $wip1 = new BasicWip();
    $iterator1 = WipFactory::getObject('acquia.wip.iterator');
    $iterator1->initialize($wip1);
    $iterator2 = WipFactory::getObject('acquia.wip.iterator');
    $this->assertNotEquals($iterator1, $iterator2);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testString() {
    $this->assertEquals('TEST STRING', WipFactory::getObject('$acquia.wip.test.teststring'));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testModifyString() {
    $config = <<<EOT
\$acquia.wip.test.teststring => 0
EOT;

    WipFactory::addConfiguration($config);
    $this->assertEquals('0', WipFactory::getObject('$acquia.wip.test.teststring'));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testGetString() {
    // Test that the getString() retrieves the proper value.
    $this->assertEquals('TEST STRING', WipFactory::getString('$acquia.wip.test.teststring', 'default value'));
    // Test that the getString() trims the value.
    WipFactory::addConfiguration('$acquia.wip.test.teststring =>     stuff   ');
    $this->assertEquals('stuff', WipFactory::getString('$acquia.wip.test.teststring', 'default value'));
    // Test that the default value is being used when the config does not exist.
    $this->assertEquals('default value', WipFactory::getString('$acquia.wip.test.does.not.exist', 'default value'));
  }

  /**
   * Tests the getBool method.
   *
   * @param bool $value
   *   The value to test.
   *
   * @group Wip
   *
   * @dataProvider booleanProvider
   */
  public function testGetBool($value) {
    $resource = '$acquia.wip.test.testbool';
    WipFactory::addConfiguration(sprintf('%s => %s ', $resource, $value ? 'true' : 'false'));
    // Test that the getBool() retrieves the proper value.
    $this->assertEquals($value, WipFactory::getBool($resource));
  }

  /**
   * Tests the getBool method.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testGetBoolBadValue() {
    $resource = '$acquia.wip.test.testbool';
    WipFactory::addConfiguration(sprintf('%s => %s ', $resource, 'badValue'));
    // Test that the getBool() retrieves the proper value.
    WipFactory::getBool($resource);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testGetInt() {
    $value = mt_rand();
    WipFactory::addConfiguration('$acquia.wip.test.testint => ' . $value);
    // Test that the getInt() retrieves the proper value.
    $this->assertEquals($value, WipFactory::getInt('$acquia.wip.test.testint', -1));
    $this->assertInternalType('int', WipFactory::getInt('$acquia.wip.test.testint', -1));
    // Test that the default value is being used when the config does not exist.
    $value = mt_rand();
    $this->assertEquals($value, WipFactory::getInt('$acquia.wip.test.does.not.exist', $value));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testGetIntArray() {
    $array = array(
      mt_rand(),
      mt_rand(),
      mt_rand(),
      mt_rand(),
    );
    WipFactory::addConfiguration('$acquia.wip.test.int.array => [' . implode(', ', $array) . ']');
    // Test that the getIntArray() retrieves the proper value.
    $this->assertEquals($array, WipFactory::getIntArray('$acquia.wip.test.int.array', array()));
    $this->assertInternalType('array', WipFactory::getIntArray('$acquia.wip.test.int.array', array()));
    // Test that the default value is being used when the config does not exist.
    $array = array(
      mt_rand(),
      mt_rand(),
      mt_rand(),
      mt_rand(),
    );
    $this->assertEquals($array, WipFactory::getInt('$acquia.wip.test.does.not.exist', $array));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testClearSingleton() {
    $min_id = 1000000;
    $max_id = 2000000;
    $config = <<<_EOT
my.test.singleton => \Acquia\Wip\Test\PublicStable\Resource\TestObject singleton
_EOT;

    WipFactory::setConfiguration($config);
    $instance = WipFactory::getObject('my.test.singleton');
    $instance->id = mt_rand($min_id, $max_id);

    $second_instance = WipFactory::getObject('my.test.singleton');
    $this->assertEquals($instance->id, $second_instance->id);

    WipFactory::clearSingleton('my.test.singleton');

    $new = WipFactory::getObject('my.test.singleton');
    // Sanity check to ensure we're not comparing NULL == NULL.
    $this->assertGreaterThanOrEqual($min_id, $instance->id);
    $this->assertLessThanOrEqual($max_id, $instance->id);
    $this->assertNotEquals($instance->id, $new->id);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testRemoveConfiguration() {
    $interface = 'Acquia\Wip\WipInterface';
    $concrete_class = 'Acquia\Wip\Implementation\BasicWip';
    try {
      WipFactory::addMapping($interface, $concrete_class);
      $instance = WipFactory::getObject($interface);
      $this->assertNotEmpty($instance);
      WipFactory::removeMapping($interface);
    } catch (\Exception $e) {
      // This should not have happened.
      $this->fail(sprintf('Caught unexpected exception %s.', $e->getMessage()));
    }
    WipFactory::getObject($interface);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testGetPath() {
    $property_name = '$acquia.wip.test.path';
    $absolute_path = '/tmp/test.txt';
    WipFactory::addConfiguration(sprintf('%s => %s', $property_name, $absolute_path));
    $this->assertEquals($absolute_path, WipFactory::getPath($property_name));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testGetPathRelative() {
    $current_dir = getcwd();
    $property_name = '$acquia.wip.test.path';
    $relative_path = 'test.txt';
    WipFactory::addConfiguration(sprintf('%s => %s', $property_name, $relative_path));
    $this->assertEquals($current_dir . DIRECTORY_SEPARATOR . $relative_path, WipFactory::getPath($property_name));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testGetPathUseDefaultValue() {
    $property_name = '$acquia.wip.test.path.does.not.exist';
    $default_value = '/tmp/test.txt';
    $this->assertEquals($default_value, WipFactory::getPath($property_name, $default_value));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \RuntimeException
   */
  public function testGetPathInvalidCharater() {
    $property_name = '$acquia.wip.test.path';
    $absolute_path = '/tmp/test&.txt';
    WipFactory::addConfiguration(sprintf('%s => %s', $property_name, $absolute_path));
    $this->assertEquals($absolute_path, WipFactory::getPath($property_name));
  }

  /**
   * Provides a list of valid boolean values.
   *
   * @return array
   *   The array of values provided.
   */
  public function booleanProvider() {
    return array(
      array(TRUE),
      array(FALSE),
    );
  }

}
