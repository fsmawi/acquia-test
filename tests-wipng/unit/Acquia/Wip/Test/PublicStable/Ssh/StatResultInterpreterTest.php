<?php

namespace Acquia\Wip\Test\PublicStable\Ssh;

use Acquia\Wip\Ssh\SshResult;
use Acquia\Wip\Ssh\StatResultInterpreter;

/**
 * Missing summary.
 */
class StatResultInterpreterTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testPermissions() {
    $interpreter = new StatResultInterpreter('/', 0);
    $interpreter->setSshResult(new SshResult(0, '755', ''));
    $this->assertEquals(0755, $interpreter->getPermissions());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \RuntimeException
   */
  public function testPermissionsNoResult() {
    $interpreter = new StatResultInterpreter('/', 0);
    $interpreter->getPermissions();
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testModifiers() {
    $interpreter = new StatResultInterpreter('/', 0);
    $interpreter->setSshResult(new SshResult(0, '1755', ''));
    $this->assertEquals(01, $interpreter->getModifiers());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \RuntimeException
   */
  public function testModifiersNoResult() {
    $interpreter = new StatResultInterpreter('/', 0);
    $interpreter->getModifiers();
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \RuntimeException
   */
  public function testFileDoesNotExist() {
    $interpreter = new StatResultInterpreter('/', 0);
    $interpreter->setSshResult(new SshResult(1, '755', ''));
    $this->assertEquals(01, $interpreter->getModifiers());
  }

}
