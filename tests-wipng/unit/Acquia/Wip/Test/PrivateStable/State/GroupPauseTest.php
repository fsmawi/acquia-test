<?php

namespace Acquia\Wip\Test\PrivateStable;

use Acquia\Wip\State\GroupPause;

/**
 * Tests the GroupPause class.
 */
class GroupPauseTest extends \PHPUnit_Framework_TestCase {

  /**
   * Tests that the GroupPause class properly validates group pause values.
   *
   * @dataProvider valuesProvider
   */
  public function testValidValues($value, $expectation) {
    $this->assertEquals($expectation, GroupPause::isValidValue($value));
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
      array('', TRUE),
      array('test', TRUE),
      array('one,two,three', TRUE),
      // Invalid values.
      array(NULL, FALSE),
      array(12345, FALSE),
      array(FALSE, FALSE),
      array(array('one', 'two', 'three'), FALSE),
    );
  }

}
