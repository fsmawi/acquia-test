<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\Implementation\SqliteWipLogStore;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\WipLogLevel;

/**
 * Missing summary.
 */
class WipLogTest extends \PHPUnit_Framework_TestCase {
  /**
   * Missing summary.
   *
   * @var WipLog
   */
  private $wipLog;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    $store = new SqliteWipLogStore();
    $store->delete();
    $this->wipLog = new WipLog($store);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testInstantiation() {
    $this->assertInstanceOf('Acquia\Wip\WipLogInterface', $this->wipLog);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testLog() {
    $level = WipLogLevel::FATAL;
    $message = 'Log message here.';
    $this->wipLog->log($level, $message);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   *
   * @expectedException \InvalidArgumentException
   */
  public function testLogWithBadLevel() {
    $level = 15;
    $message = 'This should not log.';
    $this->wipLog->log($level, $message);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   *
   * @expectedException \InvalidArgumentException
   */
  public function testLogWithBadMessageType() {
    $level = WipLogLevel::FATAL;
    $message = 7;
    $this->wipLog->log($level, $message);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   *
   * @expectedException \InvalidArgumentException
   */
  public function testLogWithEmptyMessage() {
    $level = WipLogLevel::FATAL;
    $message = '';
    $this->wipLog->log($level, $message);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testLogWithObjectId() {
    $level = WipLogLevel::ALERT;
    $message = 'testing';
    $obj_id = 51;
    $this->assertTrue($this->wipLog->log($level, $message, $obj_id));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testMultiLogWithOneMessage() {
    $this->wipLog->multiLog(
      NULL,
      WipLogLevel::ALERT,
      'Alert message'
    );
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testMultiLogWithTwoMessages() {
    $this->wipLog->multiLog(
      NULL,
      WipLogLevel::ALERT,
      'Alert message',
      WipLogLevel::ERROR,
      'Error message'
    );
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   *
   * @expectedException \Exception
   */
  public function testMultiLogWithLevelMessageMismatch() {
    $this->wipLog->multiLog(
      NULL,
      WipLogLevel::ALERT,
      'Alert message',
      WipLogLevel::ERROR,
      'Error message',
      WipLogLevel::DEBUG
    );
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   *
   * @expectedException \Exception
   */
  public function testMultiLogWithIncorrectLevelType() {
    $this->wipLog->multiLog(
      NULL,
      WipLogLevel::ALERT,
      'Alert message',
      WipLogLevel::ERROR,
      'Error message',
      '' . WipLogLevel::DEBUG,
      'Debug message'
    );
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   *
   * @expectedException \Exception
   */
  public function testMultiLogWithIncorrectMessageType() {
    $this->wipLog->multiLog(
      NULL,
      WipLogLevel::ALERT,
      'Alert message',
      WipLogLevel::ERROR,
      'Error message',
      WipLogLevel::DEBUG,
      15
    );
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   *
   * @expectedException \Exception
   */
  public function testMultiLogWithEmptyMessage() {
    $this->wipLog->multiLog(
      NULL,
      WipLogLevel::ALERT,
      'Alert message',
      WipLogLevel::ERROR,
      'Error message',
      WipLogLevel::DEBUG,
      ''
    );
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testMultilogWithNullLevel() {
    $this->wipLog->multiLog(
      NULL,
      WipLogLevel::ALERT,
      'Alert message',
      WipLogLevel::ERROR,
      'Error message',
      WipLogLevel::DEBUG,
      'debug'
    );
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testEnsureWipLogCannotBeSerialized() {
    $data = serialize($this->wipLog);
    $this->assertEmpty(unserialize($data));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testLogUserReadable() {
    $this->wipLog->log(WipLogLevel::TRACE, 'false', NULL, FALSE);
    $this->wipLog->log(WipLogLevel::TRACE, 'true', NULL, TRUE);

    $store = $this->wipLog->getStore();
    $this->assertCount(1, $store->load(NULL, 0, 20, 'ASC', WipLogLevel::TRACE, WipLogLevel::FATAL, FALSE));
    $this->assertCount(1, $store->load(NULL, 0, 20, 'ASC', WipLogLevel::TRACE, WipLogLevel::FATAL, TRUE));
  }

}
