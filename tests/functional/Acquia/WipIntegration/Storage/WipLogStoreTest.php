<?php

namespace Acquia\WipIntegrations\DoctrineORM\Entities;

use Acquia\WipIntegrations\DoctrineORM\WipLogStore;
use Acquia\WipService\Test\AbstractFunctionalTest;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\Implementation\WipLogEntry;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipLogLevel;

/**
 * Missing summary.
 */
class WipLogStoreTest extends AbstractFunctionalTest {

  /**
   * Missing summary.
   *
   * @var WipLogStore
   */
  private $store = NULL;

  /**
   * Missing summary.
   *
   * @var WipLogInterface
   */
  private $wipLog = NULL;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    WipFactory::setConfigPath('config/config.factory.test.cfg');

    $this->store = new WipLogStore($this->app);
    $this->wipLog = new WipLog($this->store);
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   */
  public function testLog() {
    $obj_id = 15;
    $message = 'Message';
    $level = WipLogLevel::WARN;
    $timestamp = time();
    $this->wipLog->log($level, $message, $obj_id);

    $entries = $this->store->load($obj_id);
    $this->assertEquals(1, count($entries));
    $entry = $entries[0];

    $this->assertEquals($obj_id, $entry->getObjectId());
    $this->assertEquals($message, $entry->getMessage());
    $this->assertEquals($level, $entry->getLogLevel());
    $this->assertLessThanOrEqual(1, abs($entry->getTimestamp() - $timestamp));

    $this->store->delete($obj_id);
    $entries = $this->store->load($obj_id);
    $this->assertEquals(0, count($entries));
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   */
  public function testLogWithoutWipId() {
    $message = 'Message';
    $level = WipLogLevel::WARN;
    $timestamp = time();
    $this->wipLog->log($level, $message);

    $entries = $this->store->load();
    $this->assertEquals(1, count($entries));
    $entry = $entries[0];

    $this->assertEquals($message, $entry->getMessage());
    $this->assertEquals($level, $entry->getLogLevel());
    $this->assertEquals($timestamp, $entry->getTimestamp());

    $this->store->delete();
    $entries = $this->store->load();
    $this->assertEquals(0, count($entries));
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   */
  public function testPruneLogMessagesByLevel() {
    $obj_id = 15;
    $this->wipLog->log(WipLogLevel::TRACE, 'trace', $obj_id);
    $this->wipLog->log(WipLogLevel::DEBUG, 'debug', $obj_id);
    $this->wipLog->log(WipLogLevel::INFO, 'info', $obj_id);
    $this->wipLog->log(WipLogLevel::WARN, 'warning', $obj_id);
    $this->wipLog->log(WipLogLevel::ALERT, 'alert', $obj_id);
    $this->wipLog->log(WipLogLevel::ERROR, 'error', $obj_id);
    $this->wipLog->log(WipLogLevel::FATAL, 'fatal', $obj_id);
    $entries = $this->store->load($obj_id);
    $this->assertEquals(7, count($entries));

    // Prune the log messages.
    $this->store->prune($obj_id, WipLogLevel::TRACE, WipLogLevel::INFO);
    $entries = $this->store->load($obj_id);
    $this->assertEquals(3, count($entries));
    $messages = $this->getMessages($entries);
    $this->assertEquals('trace, debug, info', implode(', ', $messages));
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   */
  public function testPruneLogMessagesByAge() {
    $obj_id = 15;
    $one_day = 60 * 60 * 24;

    // Last year:
    $timestamp = time() - (365 * $one_day);
    $entry = new WipLogEntry(WipLogLevel::WARN, 'last year', $obj_id, $timestamp);
    $this->store->save($entry);

    // Last month:
    $timestamp = time() - (31 * $one_day);
    $entry = new WipLogEntry(WipLogLevel::WARN, 'last month', $obj_id, $timestamp);
    $this->store->save($entry);

    // Last week:
    $timestamp = time() - (7 * $one_day);
    $entry = new WipLogEntry(WipLogLevel::WARN, 'last week', $obj_id, $timestamp);
    $this->store->save($entry);

    // Yesterday:
    $timestamp = time() - ($one_day);
    $entry = new WipLogEntry(WipLogLevel::WARN, 'yesterday', $obj_id, $timestamp);
    $this->store->save($entry);

    // Now:
    $timestamp = time();
    $entry = new WipLogEntry(WipLogLevel::WARN, 'now', $obj_id, $timestamp);
    $this->store->save($entry);

    $entries = $this->store->load($obj_id);
    $this->assertEquals(5, count($entries));

    $prune_time = time() - (3 * $one_day);
    $this->store->delete($obj_id, $prune_time);

    $entries = $this->store->load($obj_id);
    $messages = $this->getMessages($entries);
    $this->assertEquals('yesterday, now', implode(', ', $messages));
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   */
  public function testPruneLogMessagesByAgeAcrossAllObjectIds() {
    $one_day = 60 * 60 * 24;

    // Last year:
    $timestamp = time() - (365 * $one_day);
    $entry = new WipLogEntry(WipLogLevel::WARN, 'last year - 1', 1, $timestamp);
    $this->store->save($entry);

    // Last month:
    $timestamp = time() - (31 * $one_day);
    $entry = new WipLogEntry(WipLogLevel::WARN, 'last month - 2', 2, $timestamp);
    $this->store->save($entry);

    // Last week:
    $timestamp = time() - (7 * $one_day);
    $entry = new WipLogEntry(WipLogLevel::WARN, 'last week - 3', 3, $timestamp);
    $this->store->save($entry);

    // Yesterday:
    $timestamp = time() - ($one_day);
    $entry = new WipLogEntry(WipLogLevel::WARN, 'yesterday - 4', 4, $timestamp);
    $this->store->save($entry);

    // Now:
    $timestamp = time();
    $entry = new WipLogEntry(WipLogLevel::WARN, 'now - 5', 5, $timestamp);
    $this->store->save($entry);

    $entries = $this->store->load(NULL);
    $this->assertEquals(5, count($entries));

    $prune_time = time() - (3 * $one_day);
    $this->store->delete(NULL, $prune_time);

    $entries = $this->store->load(NULL);
    $messages = $this->getMessages($entries);
    $this->assertEquals('yesterday - 4, now - 5', implode(', ', $messages));
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   */
  public function testEmpty() {
    $result = $this->store->load(NULL, 0, NULL);
    $this->assertEmpty($result);
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   */
  public function testSave() {
    $message = 'Message';
    $this->wipLog->log(WipLogLevel::ALERT, $message);
    $log_messages = $this->store->load();
    $this->assertEquals(1, count($log_messages));
    /* var WipLogEntry $log_entry */
    $log_entry = $log_messages[0];
    $this->assertEquals($message, $log_entry->getMessage());
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   */
  public function testDelete() {
    $message = 'Message';
    $this->wipLog->log(WipLogLevel::ALERT, $message);
    $log_messages = $this->store->load();
    $this->assertEquals(1, count($log_messages));
    $this->store->delete();
    $this->assertEquals(0, count($this->store->load()));
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   */
  public function testPrune() {
    $message = 'Message';
    $message2 = 'Message 2';
    $obj_id = 15;
    $this->wipLog->log(WipLogLevel::ALERT, $message, $obj_id);
    $this->wipLog->log(WipLogLevel::TRACE, $message2, $obj_id);
    $log_messages = $this->store->load($obj_id);
    $this->assertEquals(2, count($log_messages));
    $this->store->prune($obj_id, WipLogLevel::DEBUG);
    $log_messages = $this->store->load($obj_id);
    $this->assertEquals(1, count($log_messages));
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   */
  public function testPruneWithNull() {
    $message = 'Message';
    $message2 = 'Message 2';
    $this->wipLog->log(WipLogLevel::ALERT, $message);
    $this->wipLog->log(WipLogLevel::TRACE, $message2);
    $log_messages = $this->store->load();
    $this->assertEquals(2, count($log_messages));
    $this->store->prune(NULL, WipLogLevel::DEBUG);
    $log_messages = $this->store->load();
    $this->assertEquals(1, count($log_messages));
  }

  /**
   * Missing summary.
   *
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
    $this->assertEquals($message_count, count($log_messages));

    // Check the count of system messages.
    $log_messages = $this->store->load(0, 0, PHP_INT_MAX);
    $this->assertEquals((int) ($message_count / 5), count($log_messages));
    // Prune only the system messages.
    $this->store->prune(0, WipLogLevel::DEBUG);
    $log_messages = $this->store->load(0, 0, PHP_INT_MAX);
    $this->assertEquals(0, count($log_messages));

    // Verify there are no remaining system messages.
    $log_messages = $this->store->load(NULL, 0, PHP_INT_MAX);
    foreach ($log_messages as $entry) {
      $this->assertNotEquals(0, $entry->getId());
    }
  }

  /**
   * Missing summary.
   *
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

    /* var WipLogEntry $log_entry */
    $log_entry = $log_messages[0];
    $this->assertEquals('fatal  error  alert  warn  info  debug  trace', trim($log_entry->getMessage()));
  }

  /**
   * Missing summary.
   *
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

    /* var WipLogEntry $log_entry */
    $log_entry = $log_messages[0];
    $this->assertEquals('fatal  error  alert  warn  info  info2  debug  trace', trim($log_entry->getMessage()));
  }

  /**
   * Extracts just the mesaages from a set of Wip log entries.
   *
   * @param WipLogEntryInterface[] $entries
   *   An array of log message entries.
   *
   * @return string[]
   *   An array of messages.
   */
  private function getMessages($entries) {
    $messages = array();
    foreach ($entries as $entry) {
      $messages[] = $entry->getMessage();
    }
    return $messages;
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   */
  public function testDeleteById() {
    $message = 'Message';
    $this->wipLog->log(WipLogLevel::ALERT, $message);
    $message2 = 'Message 2';
    $this->wipLog->log(WipLogLevel::ALERT, $message2);

    $log_messages = $this->store->load();
    $this->assertCount(2, $log_messages);

    $first_id = $log_messages[0]->getId();
    $second_id = $log_messages[1]->getId();
    $this->store->deleteById($first_id);

    $log_messages = $this->store->load();
    $this->assertCount(1, $log_messages);
    $this->assertEquals($second_id, $log_messages[0]->getId());
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   */
  public function testCleanUp() {
    $this->assertTrue($this->store->cleanUp());
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   */
  public function testCount() {
    $level = WipLogLevel::FATAL;
    $message = 'Log message here.';
    $this->wipLog->log($level, $message);
    $this->wipLog->log($level, $message);

    $this->assertEquals(2, $this->store->count());
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   */
  public function testCountWithObjectId() {
    $level = WipLogLevel::FATAL;
    $message = 'Log message here.';
    $this->wipLog->log($level, $message, 123);
    $this->wipLog->log($level, $message, 123);

    $this->assertEquals(2, $this->store->count(123));
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   *
   * @expectedException \InvalidArgumentException
   */
  public function testCountInvalidObjectId() {
    $this->store->count('invalid id');
  }

  /**
   * Missing summary.
   *
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
   * @group WipLog
   */
  public function testCountWithUserReadableFlag() {
    $false_entry = new WipLogEntry(WipLogLevel::TRACE, 'false', NULL, NULL, NULL, '0', FALSE);
    $true_entry = new WipLogEntry(WipLogLevel::TRACE, 'true', NULL, NULL, NULL, '0', TRUE);
    $this->store->save($false_entry);
    $this->store->save($true_entry);

    $this->assertEquals(2, $this->store->count(NULL, WipLogLevel::TRACE, WipLogLevel::FATAL, NULL));
    $this->assertEquals(1, $this->store->count(NULL, WipLogLevel::TRACE, WipLogLevel::FATAL, TRUE));
    $this->assertEquals(1, $this->store->count(NULL, WipLogLevel::TRACE, WipLogLevel::FATAL, FALSE));
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   */
  public function testDeleteByNonexistentId() {
    $log_messages = $this->store->load();
    $this->assertCount(0, $log_messages);
    $deleted_entry = $this->store->deleteById(100);
    $this->assertNull($deleted_entry);
  }

}
