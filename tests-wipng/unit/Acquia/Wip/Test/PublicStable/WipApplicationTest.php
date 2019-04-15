<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\WipApplicationStatus;
use Acquia\Wip\WipFactory;

/**
 * Missing summary.
 */
class WipApplicationTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @var \Acquia\Wip\Runtime\WipApplicationInterface
   */
  private $wipApplication;

  /**
   * Missing summary.
   */
  public function setup() {
    $this->wipApplication = WipFactory::getObject('acquia.wip.application');
  }

  /**
   * Missing summary.
   */
  public function testIdMutator() {
    $id = mt_rand(1, 10000000);
    $this->wipApplication->setId($id);
    $this->assertEquals($id, $this->wipApplication->getId());
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidIdNonNumeric() {
    $this->wipApplication->setId(NULL);
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidIdNumeric() {
    $this->wipApplication->setId(0);
  }

  /**
   * Missing summary.
   *
   * @expectedException \Acquia\Wip\Exception\WipApplicationOverwriteException
   */
  public function testIdOverwrite() {
    $this->wipApplication->setId(1);
    $this->wipApplication->setId(1);
  }

  /**
   * Missing summary.
   */
  public function testHandlerMutator() {
    $handler = md5(mt_rand());
    $this->wipApplication->setHandler($handler);
    $this->assertEquals($handler, $this->wipApplication->getHandler());
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidHandlerNonString() {
    $this->wipApplication->setHandler(NULL);
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidHandlerEmptyString() {
    $this->wipApplication->setHandler('   ');
  }

  /**
   * Missing summary.
   */
  public function testStatusMutator() {
    $status = WipApplicationStatus::ENABLED;
    $this->wipApplication->setStatus($status);
    $this->assertEquals($status, $this->wipApplication->getStatus());
  }

  /**
   * Missing summary.
   */
  public function testDefaultStatus() {
    $this->assertInternalType('integer', $this->wipApplication->getStatus());
    $this->assertEquals(WipApplicationStatus::DISABLED, $this->wipApplication->getStatus());
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidStatus() {
    $this->wipApplication->setStatus('invalid');
  }

}
