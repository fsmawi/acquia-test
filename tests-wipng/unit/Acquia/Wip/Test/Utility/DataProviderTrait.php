<?php

namespace Acquia\Wip\Test\Utility;

/**
 * Missing summary.
 */
trait DataProviderTrait {

  /**
   * Missing summary.
   */
  public function nonIntegerDataProvider() {
    return array(
      array(NULL),
      array(TRUE),
      array(''),
      array('string'),
      array(new \stdClass()),
      array(array('string')),
    );
  }

  /**
   * Missing summary.
   */
  public function nonStringDataProvider() {
    return array(
      array(NULL),
      array(TRUE),
      array(''),
      array(8),
      array(new \stdClass()),
      array(array('string')),
    );
  }

  /**
   * Missing summary.
   */
  public function nonPositiveIntegerDataProvider() {
    return array(
      array(NULL),
      array(TRUE),
      array(0),
      array(-1),
      array(''),
      array('string'),
      array(new \stdClass()),
      array(array('string')),
    );
  }

  /**
   * Provides empty values.
   *
   * @return array
   *   Empty values.
   */
  public function emptyProvider() {
    return array(
      array(''),
      array(0),
      array(0.0),
      array('0'),
      array(NULL),
      array(FALSE),
      array(array()),
    );
  }

}
