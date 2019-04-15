<?php

namespace Acquia\Wip\Test\PrivateStable;

use Acquia\Wip\State\GlobalPause;

/**
 * Tests the GlobalPause class.
 */
class GlobalPauseTest extends \PHPUnit_Framework_TestCase {

  /**
   * Tests that the GlobalPause class properly validates global pause values.
   *
   * @dataProvider valuesProvider
   */
  public function testValidValues($value, $expectation) {
    $this->assertEquals($expectation, GlobalPause::isValidValue($value));
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
      array(GlobalPause::$defaultValue, TRUE),
      array(GlobalPause::OFF, TRUE),
      array(GlobalPause::SOFT_PAUSE, TRUE),
      array(GlobalPause::HARD_PAUSE, TRUE),
      // Invalid values.
      array('random', FALSE),
      array(NULL, FALSE),
      array(12345, FALSE),
      array(FALSE, FALSE),
      array(array(), FALSE),
    );
  }

}
