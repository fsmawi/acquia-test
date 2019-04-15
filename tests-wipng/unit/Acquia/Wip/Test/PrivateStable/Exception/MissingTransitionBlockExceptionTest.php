<?php

namespace Acquia\Wip\Test\Exception;

use Acquia\Wip\Exception\MissingTransitionBlockException;

/**
 * Missing summary.
 */
class MissingTransitionBlockExceptionTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @group Exceptions
   */
  public function testInstantiation() {
    $e = new MissingTransitionBlockException();
    $e->setBlock('start');
    $this->assertEquals('start', $e->getBlock());
  }

  /**
   * Missing summary.
   *
   * @group Exceptions
   *
   * @expectedException \InvalidArgumentException
   */
  public function testBadBlockName() {
    $e = new MissingTransitionBlockException();
    $e->setBlock(15);
  }

}
