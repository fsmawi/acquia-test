<?php

namespace Acquia\Wip\Test\PublicStable\Exception;

use Acquia\Wip\Exception\WipParseException;

/**
 * Missing summary.
 */
class WipParseExceptionTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @group Exceptions
   */
  public function testInstantiate() {
    new WipParseException('Issue', 1);
  }

  /**
   * Missing summary.
   *
   * @expectedException Acquia\Wip\Exception\WipParseException
   *
   * @group Exceptions
   */
  public function testThrow() {
    throw new WipParseException('Issue', 1);
  }

  /**
   * Missing summary.
   *
   * @group Exceptions
   */
  public function testMessage() {
    $message = 'Missing brace';
    $line_number = 12;
    $expected_message = sprintf('%s on line %d', $message, $line_number);
    try {
      throw new WipParseException('Missing brace', $line_number);
    } catch (WipParseException $e) {
      $this->assertEquals($expected_message, $e->getMessage());
    }
  }

  /**
   * Missing summary.
   *
   * @group Exceptions
   */
  public function testLineNumber() {
    $message = 'Missing brace';
    $line_number = 12;
    $expected_message = sprintf('%s on line %d', $message, $line_number);
    try {
      throw new WipParseException('Missing brace', $line_number);
    } catch (WipParseException $e) {
      $this->assertEquals($line_number, $e->getStateTableLineNumber());
    }
  }

  /**
   * Missing summary.
   *
   * @group Exceptions
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidLineNumber() {
    throw new WipParseException('Message', -1);
  }

}
