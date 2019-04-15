<?php

namespace unit\Acquia\WipService\Silex\ConfigValidator;

use Acquia\WipService\Silex\ConfigValidator\ServiceDescriptionConfigValidator;

/**
 * Tests the ServiceDescriptionConfigValidator class.
 */
class ServiceDescriptionConfigValidatorTest extends \PHPUnit_Framework_TestCase {

  /**
   * Data provider that provides data that are not arrays.
   *
   * @return array
   *   The provided data.
   */
  public function notArrayProvider() {
    return array(
      array('not array'),
      array(NULL),
      array(FALSE),
      array(TRUE),
      array(new \stdClass()),
      array(123),
      array(0.5),
    );
  }

  /**
   * Testing that only arrays are accepted as the validation input.
   *
   * @dataProvider notArrayProvider
   *
   * @expectedException \Acquia\WipService\Silex\ConfigValidator\InvalidConfigurationException
   *
   * @expectedExceptionMessage The service description configuration must be an array.
   */
  public function testConfigNotArray($config) {
    $validator = new ServiceDescriptionConfigValidator();
    $validator->validate($config);
  }

  // Other exceptions and validation constellations are tested using functional tests
  // in ServiceProviderUsingServiceDescriptionValidatorTest.
}
