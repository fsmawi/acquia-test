<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\ExitMessage;
use Acquia\Wip\WipLogLevel;

/**
 * Missing summary.
 */
class ExitMessageTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   */
  public function testAllFields() {
    $exit_message = 'exit message';
    $log_message = 'log message';
    $log_level = WipLogLevel::DEBUG;
    $message = new ExitMessage($exit_message, $log_level, $log_message);
    $this->assertEquals($exit_message, $message->getExitMessage());
    $this->assertEquals($log_message, $message->getLogMessage());
    $this->assertEquals(WipLogLevel::DEBUG, $message->getLogLevel());
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testBadExitMessage() {
    $exit_message = 1;
    $log_message = 'log message';
    $log_level = WipLogLevel::DEBUG;
    new ExitMessage($exit_message, $log_level, $log_message);
  }

  /**
   * Missing summary.
   */
  public function testEmptyExitMessage() {
    $exit_message = '';
    $log_message = 'log message';
    $log_level = WipLogLevel::DEBUG;
    $message = new ExitMessage($exit_message, $log_level, $log_message);
    $this->assertEquals($exit_message, $message->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testBadLogMessage() {
    $exit_message = 'exit_message';
    $log_message = 1;
    $log_level = WipLogLevel::DEBUG;
    new ExitMessage($exit_message, $log_level, $log_message);
  }

  /**
   * Missing summary.
   */
  public function testNullLogMessage() {
    $exit_message = 'exit_message';
    $log_message = NULL;
    $log_level = WipLogLevel::DEBUG;
    $message = new ExitMessage($exit_message, $log_level, $log_message);
    $this->assertEquals($exit_message, $message->getExitMessage());
    $this->assertEquals($exit_message, $message->getLogMessage());
    $this->assertEquals(WipLogLevel::DEBUG, $message->getLogLevel());
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testBadLogLevel() {
    $exit_message = 'exit message';
    $log_message = 'log message';
    $log_level = 12;
    new ExitMessage($exit_message, $log_level, $log_message);
  }

}
