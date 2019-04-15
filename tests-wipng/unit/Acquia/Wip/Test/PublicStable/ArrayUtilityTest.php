<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\Utility\ArrayUtility;

/**
 * Missing summary.
 */
class ArrayUtilityTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   */
  public function testDiff() {
    $a = array(
      'one' => array(
        'one-one' => array(
          'one-one-one' => array(
            'one-one-one-one' => 1,
          ),
          'one-one-two' => array(
            'one-one-two-one' => 4,
            'one-one-two-two' => 5,
          ),
        ),
      ),
    );

    $b = array(
      'one' => array(
        'one-one' => array(
          'one-one-one' => array(),
          'one-one-two' => array(
            'one-one-two-one' => 4,
            'one-one-two-two' => 5,
            'one-one-two-three' => 6,
          ),
        ),
      ),
    );

    $a_copy = array(
      'one' => array(
        'one-one' => array(
          'one-one-one' => array(
            'one-one-one-one' => 1,
          ),
          'one-one-two' => array(
            'one-one-two-one' => 4,
            'one-one-two-two' => 5,
          ),
        ),
      ),
    );

    $a_plus_one = array(
      'one' => array(
        'one-one' => array(
          'one-one-one' => array(
            'one-one-one-one' => 1,
            'one-one-one-two' => 2,
          ),
          'one-one-two' => array(
            'one-one-two-one' => 4,
            'one-one-two-two' => 5,
          ),
        ),
      ),
    );

    $a_one_diff = array(
      'one' => array(
        'one-one' => array(
          'one-one-one' => array(
            'one-one-one-one' => 2,
          ),
          'one-one-two' => array(
            'one-one-two-one' => 4,
            'one-one-two-two' => 5,
          ),
        ),
      ),
    );

    // $a differs from $b, as well as from $a_plus_one and $a_one_diff.
    $this->assertTrue(ArrayUtility::arraysDiffer($a, $b));
    $this->assertTrue(ArrayUtility::arraysDiffer($a, $a_plus_one));
    $this->assertTrue(ArrayUtility::arraysDiffer($a, $a_one_diff));
    // Same tests in reverse order.
    $this->assertTrue(ArrayUtility::arraysDiffer($b, $a));
    $this->assertTrue(ArrayUtility::arraysDiffer($a_plus_one, $a));
    $this->assertTrue(ArrayUtility::arraysDiffer($a_one_diff, $a));
    // $a and $a_copy are identical.
    $this->assertFalse(ArrayUtility::arraysDiffer($a, $a_copy));
    $this->assertFalse(ArrayUtility::arraysDiffer($a_copy, $a));
  }

}
