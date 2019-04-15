<?php

namespace Acquia\WipIntegrations;

/**
 * Class MockQuery A Mock object for NativeQuery.
 *
 * Because NativeQuery is declared final, it cannot be mocked by PHPUnit. This
 * class provides a usable mock that always returns a configured result.
 */
class MockQuery {
  private $result;

  /**
   * Missing summary.
   */
  public function __construct($result) {
    $this->result = $result;
  }

  /**
   * Missing summary.
   */
  public function getResult() {
    return array(array('lock' => $this->result));
  }

  /**
   * Missing summary.
   */
  public function setParameter() {
  }

}
