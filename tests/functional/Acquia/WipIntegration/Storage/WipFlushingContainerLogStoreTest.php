<?php

namespace Acquia\WipIntegrations\DoctrineORM\Entities;

use Acquia\WipIntegrations\DoctrineORM\WipFlushingContainerLogStore;
use Acquia\WipService\Test\AbstractFunctionalTest;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\Implementation\WipLogEntry;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogEntryInterface;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipLogLevel;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

/**
 * Missing summary.
 */
class WipFlushingContainerLogStoreTest extends AbstractFunctionalTest {

  /**
   * Missing summary.
   *
   * @var WipFlushingContainerLogStore
   */
  private $store = NULL;

  /**
   * Missing summary.
   *
   * @var WipLogInterface
   */
  private $wipLog = NULL;

  private $containerId = '123';
  private $level = WipLogLevel::WARN;
  private $message = 'testing';
  private $objId = 51;
  private $id = 1234;
  private $bufferSize = 10;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();

    WipFactory::setConfigPath('config/config.factory.test.cfg');

    $this->store = new WipFlushingContainerLogStore($this->app);
    $this->store->delete();
    $this->wipLog = new WipLog($this->store);

    $this->store->setBufferSize($this->bufferSize);

    $mock = new MockHandler();
    // At most $bufferSize number of flush requests can be sent. Load
    // $bufferSize mock responses to the mock handler for testing.
    for ($i = 0; $i < $this->bufferSize; $i++) {
      $mock->append(new Response(200, array(), '{"success":true,"message":null,"logged_ids":[1,2,3,4,5,6,7,8,9,10]}'));
    }

    $mock_handler = HandlerStack::create($mock);

    $this->store->setClient(new Client(['handler' => $mock_handler]));
  }

  /**
   * Provides invalid bufferSize values for testing.
   *
   * @return array
   *   The array of values provided.
   */
  public function invalidBufferSizeProvider() {
    return array(
        array(NULL),
        array('not a number'),
        array(-23),
    );
  }

  /**
   * Provides invalid container ID values for testing.
   *
   * @return array
   *   The array of values provided.
   */
  public function invalidContainerIdProvider() {
    return array(
      array(NULL),
      array(123),
      array(''),
    );
  }

  /**
   * Provides log levels for testing.
   *
   * @return array
   *   The array of values provided.
   */
  public function logLevelProvider() {
    return array(
      array(WipLogLevel::ALERT),
      array(WipLogLevel::DEBUG),
      array(WipLogLevel::ERROR),
      array(WipLogLevel::FATAL),
      array(WipLogLevel::INFO),
      array(WipLogLevel::TRACE),
      array(WipLogLevel::WARN),
    );
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
  private function extractMessages($entries) {
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
   *
   * @dataProvider logLevelProvider
   */
  public function testSettersAndGetters($level) {
    $size = $this->store->getBufferSize();
    $this->store->setBufferSize($size + 1);
    $this->assertEquals($size + 1, $this->store->getBufferSize());

    $max = $this->store->getMaxBufferSize();
    $this->store->setMaxBufferSize($max + 1);
    $this->assertEquals($max + 1, $this->store->getMaxBufferSize());

    $this->store->setLogLevel($level);
    $this->assertEquals($level, $this->store->getLogLevel());

    $endpoint = $this->store->getEndpoint();
    $this->store->setEndpoint($endpoint . 'changed');
    $this->assertEquals($endpoint . 'changed', $this->store->getEndpoint());

    $container_id = $this->store->getContainerId();
    $this->store->setContainerId($container_id . 'changed');
    $this->assertEquals($container_id . 'changed', $this->store->getContainerId());

    $client = $this->store->getClient();
    $this->store->setClient(new Client());
    $this->assertNotEquals($client, $this->store->getClient());
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   *
   * @dataProvider invalidBufferSizeProvider
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetInvalidBufferSize($value) {
    $this->store->setBufferSize($value);
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   *
   * @dataProvider invalidBufferSizeProvider
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetInvalidMaxBufferSize($value) {
    $this->store->setMaxBufferSize($value);
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetNullEndpoint() {
    $this->store->setEndpoint(NULL);
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetInvalidLogLevel() {
    $this->store->setLogLevel(9999);
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   *
   * @dataProvider invalidContainerIdProvider
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetInvalidContainerId($value) {
    $this->store->setContainerId($value);
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetInvalidClient() {
    $this->store->setClient(NULL);
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidConfigFile() {
    WipFactory::removeMapping('$acquia.wip.wipflushinglogstore.endpoint');
    $this->store = new WipFlushingContainerLogStore($this->app);
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   */
  public function testLog() {
    $timestamp = time();

    $this->wipLog->log($this->level, $this->message, $this->objId);

    $entries = $this->store->load($this->objId);
    $this->assertCount(1, $entries);
    $entry = $entries[0];

    $this->assertEquals($this->objId, $entry->getObjectId());
    $this->assertEquals($this->message, $entry->getMessage());
    $this->assertEquals($this->level, $entry->getLogLevel());
    // One-second leeway for the timestamp.
    $this->assertEquals($timestamp, $entry->getTimestamp(), '', 1);

    $this->store->delete($this->objId);
    $entries = $this->store->load($this->objId);
    $this->assertCount(0, $entries);
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   */
  public function testLogWithoutObjectId() {
    $timestamp = time();
    $this->wipLog->log($this->level, $this->message, NULL);

    $entries = $this->store->load();
    $this->assertCount(1, $entries);
    $entry = $entries[0];

    $this->assertEquals($this->message, $entry->getMessage());
    $this->assertEquals($this->level, $entry->getLogLevel());
    // One-second leeway for the timestamp.
    $this->assertEquals($timestamp, $entry->getTimestamp(), '', 1);

    $this->store->delete();
    $entries = $this->store->load();
    $this->assertCount(0, $entries);
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   */
  public function testPruneLogMessagesByLevel() {
    $this->wipLog->log(WipLogLevel::TRACE, 'trace', $this->objId);
    $this->wipLog->log(WipLogLevel::DEBUG, 'debug', $this->objId);
    $this->wipLog->log(WipLogLevel::INFO, 'info', $this->objId);
    $this->wipLog->log(WipLogLevel::WARN, 'warning', $this->objId);
    $this->wipLog->log(WipLogLevel::ALERT, 'alert', $this->objId);
    $entries = $this->store->load($this->objId);
    $this->assertCount(5, $entries);

    // Prune the log messages.
    $this->store->prune($this->objId, WipLogLevel::TRACE, WipLogLevel::INFO);
    $entries = $this->store->load($this->objId);
    $this->assertCount(3, $entries);
    $messages = $this->extractMessages($entries);
    $this->assertEquals('trace, debug, info', implode(', ', $messages));
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   */
  public function testPruneLogMessagesByAge() {
    $one_day = 60 * 60 * 24;

    // Last year:
    $timestamp = time() - (365 * $one_day);
    $entry = new WipLogEntry($this->level, 'last year', $this->objId, $timestamp, $this->id, $this->containerId);
    $this->store->save($entry);

    // Last month:
    $timestamp = time() - (31 * $one_day);
    $entry = new WipLogEntry($this->level, 'last month', $this->objId, $timestamp, $this->id, $this->containerId);
    $this->store->save($entry);

    // Last week:
    $timestamp = time() - (7 * $one_day);
    $entry = new WipLogEntry($this->level, 'last week', $this->objId, $timestamp, $this->id, $this->containerId);
    $this->store->save($entry);

    // Yesterday:
    $timestamp = time() - ($one_day);
    $entry = new WipLogEntry($this->level, 'yesterday', $this->objId, $timestamp, $this->id, $this->containerId);
    $this->store->save($entry);

    // Now:
    $timestamp = time();
    $entry = new WipLogEntry($this->level, 'now', $this->objId, $timestamp, $this->id, $this->containerId);
    $this->store->save($entry);

    $entries = $this->store->load($this->objId);
    $this->assertCount(5, $entries);

    $prune_time = time() - (3 * $one_day);
    $this->store->delete($this->objId, $prune_time);

    $entries = $this->store->load($this->objId);
    $messages = $this->extractMessages($entries);
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
    $entry = new WipLogEntry($this->level, 'last year - 1', 1, $timestamp, $this->id, $this->containerId);
    $this->store->save($entry);

    // Last month:
    $timestamp = time() - (31 * $one_day);
    $entry = new WipLogEntry(WipLogLevel::WARN, 'last month - 2', 2, $timestamp, $this->id, $this->containerId);
    $this->store->save($entry);

    // Last week:
    $timestamp = time() - (7 * $one_day);
    $entry = new WipLogEntry(WipLogLevel::WARN, 'last week - 3', 3, $timestamp, $this->id, $this->containerId);
    $this->store->save($entry);

    // Yesterday:
    $timestamp = time() - ($one_day);
    $entry = new WipLogEntry(WipLogLevel::WARN, 'yesterday - 4', 4, $timestamp, $this->id, $this->containerId);
    $this->store->save($entry);

    // Now:
    $timestamp = time();
    $entry = new WipLogEntry(WipLogLevel::WARN, 'now - 5', 5, $timestamp, $this->id, $this->containerId);
    $this->store->save($entry);

    $entries = $this->store->load(NULL);
    $this->assertCount(5, $entries);

    $prune_time = time() - (3 * $one_day);
    $this->store->delete(NULL, $prune_time);

    $entries = $this->store->load(NULL);
    $messages = $this->extractMessages($entries);
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
    $this->wipLog->log($this->level, $this->message, $this->objId);
    $log_messages = $this->store->load();
    $this->assertCount(1, $log_messages);
    $log_entry = $log_messages[0];
    $this->assertEquals($this->message, $log_entry->getMessage());
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   */
  public function testDelete() {
    $this->wipLog->log(WipLogLevel::ALERT, $this->message, $this->objId);
    $log_messages = $this->store->load();
    $this->assertCount(1, $log_messages);
    $this->store->delete();
    $this->assertCount(0, $this->store->load());
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   */
  public function testPrune() {
    $message = 'Message';
    $message2 = 'Message 2';
    $this->wipLog->log(WipLogLevel::ALERT, $message, $this->objId);
    $this->wipLog->log(WipLogLevel::TRACE, $message2, $this->objId);
    $log_messages = $this->store->load($this->objId);
    $this->assertCount(2, $log_messages);
    // DEBUG is between ALERT and TRACE.
    $this->store->prune($this->objId, WipLogLevel::DEBUG);
    $log_messages = $this->store->load($this->objId);
    $this->assertCount(1, $log_messages);
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   */
  public function testPruneWithNull() {
    $message = 'Message';
    $message2 = 'Message 2';
    $this->wipLog->log(WipLogLevel::ALERT, $message, $this->objId);
    $this->wipLog->log(WipLogLevel::TRACE, $message2, $this->objId);
    $log_messages = $this->store->load();
    $this->assertCount(2, $log_messages);
    // DEBUG is between ALERT and TRACE.
    $this->store->prune(NULL, WipLogLevel::DEBUG);
    $log_messages = $this->store->load();
    $this->assertCount(1, $log_messages);
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   */
  public function testPruneSystemMessages() {
    $message_count = $this->store->getBufferSize() - 1;
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
    $this->assertCount((int) ($message_count / 5 + 1), $log_messages);
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
   * @group WipLog
   */
  public function testMultilog() {
    $this->wipLog->multilog(
      NULL,
      WipLogLevel::INFO,
      'info',
      WipLogLevel::ALERT,
      'alert',
      WipLogLevel::WARN,
      'warn',
      WipLogLevel::DEBUG,
      'debug',
      WipLogLevel::TRACE,
      'trace'
    );
    $log_messages = $this->store->load();

    $log_entry = $log_messages[0];
    $this->assertEquals('alert  warn  info  debug  trace', trim($log_entry->getMessage()));
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   */
  public function testMultilogWithMultipleMessagesPerLevel() {
    $this->wipLog->multilog(
      NULL,
      WipLogLevel::INFO,
      'info',
      WipLogLevel::ALERT,
      'alert',
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
    $this->assertEquals('alert  warn  info  info2  debug  trace', trim($log_entry->getMessage()));
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   */
  public function testFlushOnBufferSizeReached() {
    $buffer_size = $this->store->getBufferSize();

    for ($i = 0; $i <= $buffer_size; $i++) {
      $this->wipLog->log($this->level, 'message');
    }

    $log_messages = $this->store->load();
    $this->assertCount(1, $log_messages);
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   */
  public function testFlushOnLevelReached() {
    $mock = new MockHandler();
    $mock->append(new Response(200, array(), '{"success":true,"message":null,"logged_ids":[1,2,3,4,5,6]}'));
    $mock->append(new Response(200, array(), '{"success":true,"message":null,"logged_ids":[7]}'));
    $handler = HandlerStack::create($mock);
    $this->store->setClient(new Client(['handler' => $handler]));

    $this->wipLog->log(WipLogLevel::TRACE, 'trace', $this->objId);
    $this->wipLog->log(WipLogLevel::DEBUG, 'debug', $this->objId);
    $this->wipLog->log(WipLogLevel::INFO, 'info', $this->objId);
    $this->wipLog->log(WipLogLevel::WARN, 'warning', $this->objId);
    $this->wipLog->log(WipLogLevel::ALERT, 'alert', $this->objId);
    $this->wipLog->log(WipLogLevel::ERROR, 'error', $this->objId);
    $this->wipLog->log(WipLogLevel::FATAL, 'fatal', $this->objId);
    $entries = $this->store->load($this->objId);
    // Everything should have been flushed because of the error and fatal.
    $this->assertCount(0, $entries);
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   */
  public function testCleanUp() {
    $this->wipLog->log(WipLogLevel::TRACE, 'trace', $this->objId);
    $this->wipLog->log(WipLogLevel::DEBUG, 'debug', $this->objId);
    $this->wipLog->log(WipLogLevel::INFO, 'info', $this->objId);

    $this->assertTrue($this->store->cleanUp());
  }

  /**
   * Missing summary.
   *
   * @group WipLog
   */
  public function testFlushOnUserReadable() {
    $history = [];
    $mock = new MockHandler();
    $mock->append(new Response(200, array(), '{"success":true,"message":null,"logged_ids":[1, 2]}'));
    $handler = HandlerStack::create($mock);
    $handler->push(Middleware::history($history));
    $this->store->setClient(new Client(['handler' => $handler]));

    $not_readable = new WipLogEntry(WipLogLevel::TRACE, 'false', NULL, NULL, NULL, '0', FALSE);
    $readable = new WipLogEntry(WipLogLevel::TRACE, 'true', NULL, NULL, NULL, '0', TRUE);

    // Make sure that there is one message in the store and that no HTTP calls
    // were made.
    $this->store->save($not_readable);
    $this->assertCount(1, $this->store->load());
    $this->assertCount(0, $history);

    // Make sure that after the readable log is logged, the store was flushed so
    // that there are no more logs left, and that one HTTP call was made to the
    // POST endpoint.
    $this->store->save($readable);
    $this->assertCount(0, $this->store->load());
    $this->assertCount(1, $history);
  }

}
