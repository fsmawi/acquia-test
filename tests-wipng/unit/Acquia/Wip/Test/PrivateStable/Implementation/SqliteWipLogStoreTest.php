<?php

namespace Acquia\Wip\Test\PrivateStable\Implementation;

use Acquia\Wip\Implementation\SqliteWipLogStore;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\Implementation\WipLogEntry;
use Acquia\Wip\Storage\WipLogStoreInterface;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipLogLevel;

/**
 * Missing summary.
 */
class SqliteWipLogStoreTest extends \PHPUnit_Framework_TestCase {
  /**
   * Missing summary.
   *
   * @var WipLogStoreInterface
   */
  private $store = NULL;

  /**
   * Missing summary.
   *
   * @var WipLogInterface
   */
  private $wipLog = NULL;

  /**
   * Missing summary.
   *
   * @var string
   */
  private $filename;

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function setUp() {
    parent::setUp();
    $this->filename = 'wip';
    $this->store = new SqliteWipLogStore();
    $this->wipLog = new WipLog($this->store);
  }

  /**
   * Missing summary.
   */
  public function tearDown() {
    parent::tearDown();
    if ($this->store instanceof SqliteWipLogStore) {
      @unlink($this->store->getLogFilePath());
    }
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testEmpty() {
    $result = $this->store->load(NULL, 0, NULL);
    $this->assertEmpty($result);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testSave() {
    $message = 'Message';
    $this->wipLog->log(WipLogLevel::ALERT, $message);
    $log_messages = $this->store->load();
    $this->assertCount(1, $log_messages);
    $log_entry = $log_messages[0];
    $this->assertEquals($message, $log_entry->getMessage());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testIdIsAutomaticallyAddedOnSave() {
    $level = WipLogLevel::ALERT;
    $message = 'testing';
    $wip_log_entry = new WipLogEntry($level, $message);
    $this->assertNull($wip_log_entry->getId());
    $this->store->save($wip_log_entry);
    $log_messages = $this->store->load();
    $this->assertNotNull($log_messages[0]->getId());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testSaveAllFields() {
    $level = WipLogLevel::ALERT;
    $message = 'testing';
    $obj_id = 1234;
    $wip_log_entry = new WipLogEntry($level, $message, $obj_id);
    $this->store->save($wip_log_entry);
    $log_messages = $this->store->load();
    $this->assertEquals($obj_id, $log_messages[0]->getObjectId());
    $this->assertEquals($level, $log_messages[0]->getLogLevel());
    $this->assertEquals($message, $log_messages[0]->getMessage());
    $this->assertNotNull($log_messages[0]->getTimestamp());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testDelete() {
    $message = 'Message';
    $this->wipLog->log(WipLogLevel::ALERT, $message);
    $log_messages = $this->store->load(NULL);
    $this->assertCount(1, $log_messages);
    $this->store->delete();
    $this->assertCount(0, $this->store->load());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testDeleteWithObjectId() {
    $message = 'Message';
    $obj_id = 15;
    $this->wipLog->log(WipLogLevel::ALERT, $message, $obj_id);
    $log_messages = $this->store->load($obj_id);
    $this->assertCount(1, $log_messages);
    $this->store->delete($obj_id);
    $this->assertCount(0, $this->store->load($obj_id));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testDeleteWithNonexistentObjectId() {
    $message = 'Message';
    $obj_id = 15;
    $this->wipLog->log(WipLogLevel::ALERT, $message, $obj_id);
    $log_messages = $this->store->load($obj_id);
    $this->assertCount(1, $log_messages);
    $this->store->delete(2000);
    $this->assertCount(1, $this->store->load($obj_id));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testDeleteWithNullObjectId() {
    $message = 'Message';
    $obj_id = 15;
    $this->wipLog->log(WipLogLevel::ALERT, $message, $obj_id);
    $log_messages = $this->store->load($obj_id);
    $this->assertCount(1, $log_messages);
    $this->store->delete();
    // Everything should have been deleted.
    $this->assertCount(0, $this->store->load($obj_id));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   *
   * @expectedException \InvalidArgumentException
   */
  public function testDeleteWithInvalidObjectIdType() {
    $message = 'Message';
    $obj_id = 15;
    $this->wipLog->log(WipLogLevel::ALERT, $message, $obj_id);
    $log_messages = $this->store->load($obj_id);
    $this->assertCount(1, $log_messages);
    $this->store->delete("I'm not a valid type!");
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testDeleteWithPruneTime() {
    $message = 'Message';
    $this->wipLog->log(WipLogLevel::ALERT, $message, NULL);
    $log_messages = $this->store->load();
    $this->assertCount(1, $log_messages);
    $this->store->delete(NULL, 0);
    // Nothing should have been deleted.
    $this->assertCount(1, $this->store->load());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testMultilog() {
    $this->wipLog->multilog(
      NULL,
      WipLogLevel::FATAL,
      'fatal',
      WipLogLevel::INFO,
      'info',
      WipLogLevel::ALERT,
      'alert',
      WipLogLevel::ERROR,
      'error',
      WipLogLevel::WARN,
      'warn',
      WipLogLevel::DEBUG,
      'debug',
      WipLogLevel::TRACE,
      'trace'
    );
    $log_messages = $this->store->load();

    $log_entry = $log_messages[0];
    $this->assertEquals('fatal  error  alert  warn  info  debug  trace', trim($log_entry->getMessage()));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testMultilogWithMultipleMessagesPerLevel() {
    $this->wipLog->multilog(
      NULL,
      WipLogLevel::FATAL,
      'fatal',
      WipLogLevel::INFO,
      'info',
      WipLogLevel::ALERT,
      'alert',
      WipLogLevel::ERROR,
      'error',
      WipLogLevel::WARN,
      'warn',
      WipLogLevel::INFO,
      'info2',
      WipLogLevel::DEBUG,
      'debug',
      WipLogLevel::TRACE,
      'trace'
    );
    $log_messages = $this->store->load();

    $log_entry = $log_messages[0];
    $this->assertEquals('fatal  error  alert  warn  info  info2  debug  trace', trim($log_entry->getMessage()));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testPruneNonDefaultRange() {
    $message = 'Message';
    $message2 = 'Message 2';
    $obj_id = 15;
    $this->wipLog->log(WipLogLevel::ALERT, $message, $obj_id);
    $this->wipLog->log(WipLogLevel::TRACE, $message2, $obj_id);
    $log_messages = $this->store->load($obj_id);
    $this->assertCount(2, $log_messages);
    $this->store->prune();
    $log_messages = $this->store->load($obj_id);
    $this->assertCount(2, $log_messages);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testPrune() {
    $message = 'Message';
    $obj_id = 15;
    // This level is within the default range and should not be pruned.
    $this->wipLog->log(WipLogLevel::ALERT, $message, $obj_id);
    $log_messages = $this->store->load($obj_id);
    $this->assertCount(1, $log_messages);
    $this->store->prune();
    $log_messages = $this->store->load($obj_id);
    $this->assertCount(1, $log_messages);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testPruneWithObjectId() {
    $message = 'Message';
    $message2 = 'Message 2';
    $obj_id = 15;
    $this->wipLog->log(WipLogLevel::ALERT, $message, $obj_id);
    $this->wipLog->log(WipLogLevel::TRACE, $message2, $obj_id);
    $log_messages = $this->store->load($obj_id);
    $this->assertCount(2, $log_messages);
    $this->store->prune($obj_id, WipLogLevel::DEBUG);
    $log_messages = $this->store->load($obj_id);
    $this->assertCount(1, $log_messages);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testPruneWithNonexistentObjectId() {
    $message = 'Message';
    $obj_id = 15;
    $this->wipLog->log(WipLogLevel::ALERT, $message, $obj_id);
    $this->store->prune(9999);
    $log_messages = $this->store->load($obj_id);
    $this->assertCount(1, $log_messages);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testPruneWithNullObjectId() {
    $message = 'Message';
    $obj_id = 15;
    $this->wipLog->log(WipLogLevel::ALERT, $message, $obj_id);
    $this->store->prune(NULL);
    $log_messages = $this->store->load($obj_id);
    $this->assertCount(1, $log_messages);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testPruneMessageWithoutObjectId() {
    $message = 'Message';
    $this->wipLog->log(WipLogLevel::ALERT, $message);
    $this->store->prune(NULL);
    $log_messages = $this->store->load();
    $this->assertCount(1, $log_messages);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   *
   * @expectedException \InvalidArgumentException
   */
  public function testPruneWithInvalidObjectIdType() {
    $message = 'Message';
    $obj_id = 15;
    $this->wipLog->log(WipLogLevel::ALERT, $message, $obj_id);
    $this->store->prune("I'm an invalid ID!");
    $log_messages = $this->store->load($obj_id);
    $this->assertCount(1, $log_messages);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testPruneSystemMessages() {
    $message_count = 100;
    $message_template = 'Message %d';
    for ($i = 0; $i < $message_count; $i++) {
      $obj_id = ($i % 13) + 1;
      if ($i % 5 === 0) {
        $obj_id = NULL;
      }
      $this->wipLog->log(WipLogLevel::TRACE, sprintf($message_template, $i), $obj_id);
    }
    $log_messages = $this->store->load(NULL, 0, PHP_INT_MAX);
    $this->assertCount($message_count, $log_messages);

    // Check the count of system messages.
    $log_messages = $this->store->load(0, 0, PHP_INT_MAX);
    $this->assertCount((int) ($message_count / 5), $log_messages);
    // Prune only the system messages.
    $this->store->prune(0, WipLogLevel::DEBUG);
    $log_messages = $this->store->load(0, 0, PHP_INT_MAX);
    $this->assertCount(0, $log_messages);

    // Verify there are no remaining system messages.
    $log_messages = $this->store->load(NULL, 0, PHP_INT_MAX);
    foreach ($log_messages as $entry) {
      $this->assertNotEquals(0, $entry->getId());
    }
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testCleanUp() {
    $this->assertTrue($this->store->cleanUp());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testLoadUserReadable() {
    $false_entry = new WipLogEntry(WipLogLevel::TRACE, 'message 2', NULL, NULL, NULL, '123', FALSE);
    $true_entry = new WipLogEntry(WipLogLevel::TRACE, 'message 1', NULL, NULL, NULL, '123', TRUE);
    $this->store->save($false_entry);
    $this->store->save($true_entry);

    $this->assertCount(2, $this->store->load());
    $this->assertCount(2, $this->store->load(NULL, 0, 20, 'ASC', WipLogLevel::TRACE, WipLogLevel::FATAL, NULL));

    $true_entries = $this->store->load(NULL, 0, 20, 'ASC', WipLogLevel::TRACE, WipLogLevel::FATAL, TRUE);
    $this->assertCount(1, $true_entries);
    $this->assertEquals($true_entry->getMessage(), $true_entries[0]->getMessage());

    $false_entries = $this->store->load(NULL, 0, 20, 'ASC', WipLogLevel::TRACE, WipLogLevel::FATAL, FALSE);
    $this->assertCount(1, $false_entries);
    $this->assertEquals($false_entry->getMessage(), $false_entries[0]->getMessage());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testDeleteWithUserReadableFlag() {
    $false_entry = new WipLogEntry(WipLogLevel::TRACE, 'false', NULL, NULL, NULL, '0', FALSE);
    $true_entry = new WipLogEntry(WipLogLevel::TRACE, 'true', NULL, NULL, NULL, '0', TRUE);
    $this->store->save($false_entry);
    $this->store->save($true_entry);

    // Test delete TRUE. Entry left should have "false" as its message.
    $deleted = $this->store->delete(NULL, PHP_INT_MAX, TRUE);
    $this->assertCount(1, $deleted);
    $this->assertEquals($true_entry->getMessage(), $deleted[0]->getMessage());
    $logs = $this->store->load();
    $this->assertCount(1, $logs);
    $this->assertEquals($false_entry->getMessage(), $logs[0]->getMessage());

    // Test delete FALSE. Entry left should have "true" as its message.
    $this->store->save($true_entry);
    $deleted = $this->store->delete(NULL, PHP_INT_MAX, FALSE);
    $this->assertCount(1, $deleted);
    $this->assertEquals($false_entry->getMessage(), $deleted[0]->getMessage());
    $logs = $this->store->load();
    $this->assertCount(1, $logs);
    $this->assertEquals($true_entry->getMessage(), $logs[0]->getMessage());

    // Test delete all.
    $this->store->save($false_entry);
    $this->store->delete(NULL, PHP_INT_MAX, NULL);
    $this->assertCount(0, $this->store->load());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testPruneWithUserReadableFlag() {
    $false_entry = new WipLogEntry(WipLogLevel::TRACE, 'false', NULL, NULL, NULL, '0', FALSE);
    $true_entry = new WipLogEntry(WipLogLevel::TRACE, 'true', NULL, NULL, NULL, '0', TRUE);
    $this->store->save($false_entry);
    $this->store->save($true_entry);

    // Test prune TRUE. WARN is above TRACE, so this message will be pruned.
    $pruned = $this->store->prune(NULL, WipLogLevel::WARN, WipLogLevel::FATAL, TRUE);
    $this->assertCount(1, $pruned);
    $this->assertEquals($true_entry->getMessage(), $pruned[0]->getMessage());
    $logs = $this->store->load();
    $this->assertCount(1, $logs);
    $this->assertEquals($false_entry->getMessage(), $logs[0]->getMessage());

    // Test delete FALSE. WARN is above TRACE, so this message will be pruned.
    $this->store->save($true_entry);
    $pruned = $this->store->prune(NULL, WipLogLevel::WARN, WipLogLevel::FATAL, FALSE);
    $this->assertCount(1, $pruned);
    $this->assertEquals($false_entry->getMessage(), $pruned[0]->getMessage());
    $logs = $this->store->load();
    $this->assertCount(1, $logs);
    $this->assertEquals($true_entry->getMessage(), $logs[0]->getMessage());

    // Test delete all. WARN is above TRACE, so this message will be pruned.
    $this->store->save($false_entry);
    $this->store->prune(NULL, WipLogLevel::WARN, WipLogLevel::FATAL, NULL);
    $this->assertCount(0, $this->store->load());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testDeleteByNonexistentId() {
    $log_messages = $this->store->load();
    $this->assertCount(0, $log_messages);

    $deleted_entry = $this->store->deleteById(100);
    $this->assertNull($deleted_entry);
  }

}
