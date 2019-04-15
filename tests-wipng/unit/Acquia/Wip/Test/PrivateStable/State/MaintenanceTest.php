<?php

namespace Acquia\Wip\Test\PrivateStable;

use Acquia\Wip\State\Maintenance;

/**
 * Tests the Maintenance class.
 */
class MaintenanceTest extends \PHPUnit_Framework_TestCase {

  /**
   * Tests that the Maintenance class properly validates maintenance mode values.
   *
   * @dataProvider valuesProvider
   */
  public function testValidValues($value, $expectation) {
    $this->assertEquals($expectation, Maintenance::isValidValue($value));
  }

  /**
   * Provides valid and invalid values with expectations in an array as parameters.
   *
   * @return array
   *   A multidimensional array of parameters.
   */
  public function valuesProvider() {
    return array(
      // Valid values.
      array(Maintenance::$defaultValue, TRUE),
      array(Maintenance::OFF, TRUE),
      array(Maintenance::FULL, TRUE),
      // Invalid values.
      array('random', FALSE),
      array(NULL, FALSE),
      array(12345, FALSE),
      array(FALSE, FALSE),
      array(array(), FALSE),
    );
  }

}
