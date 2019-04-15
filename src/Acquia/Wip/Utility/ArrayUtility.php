<?php

namespace Acquia\Wip\Utility;

/**
 * Contains some generally useful functions for handling arrays.
 */
class ArrayUtility {

  /**
   * Determines whether 2 arrays have any differences, including nested arrays.
   *
   * Note that this function detects by using the keys of the first array, and
   * so to detect added keys in the second array, this function needs to be run
   * twice: the second time, the order of the array arguments should be
   * reversed.  This function will abort as soon any difference is detected.
   *
   * @param array $a
   *   The first array to test.
   * @param array $b
   *   The second array to test.
   *
   * @return bool
   *   TRUE if the first array contains (also at nested levels) keys not present
   *   in the second, or if the values differ at any point for the same keys.
   *   FALSE if the second array contains all the same keys and values as the
   *   first (though the second may also contain extra keys).
   */
  public static function arrayDiff($a, $b) {
    foreach ($a as $key => $val) {
      if (isset($b[$key])) {
        if (is_array($val) && is_array($b[$key])) {
          // The key was found in the second array, and they are both arrays:
          // Recurse and check the nested arrays.
          if (static::arrayDiff($val, $b[$key])) {
            // If any nested check returns TRUE, we will return TRUE to
            // short-circuit checking the rest.
            return TRUE;
          }
        } // One or both is not an array.  If the values are not identical, then
        // return TRUE, short-circuiting checking the rest.
        elseif ($val !== $b[$key]) {
          return TRUE;
        }
      } else {
        // We did not find the current key in the second array: returning TRUE
        // here short-circuits checking the rest.
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Determines if there are any differences in the passed (nested) arrays.
   *
   * @param array $a
   *   The first array to compare.
   * @param array $b
   *   The second array to compare.
   *
   * @return bool
   *   TRUE if there is a difference, otherwise FALSE.
   */
  public static function arraysDiffer($a, $b) {
    return (static::arrayDiff($a, $b) || static::arrayDiff($b, $a));
  }

}
