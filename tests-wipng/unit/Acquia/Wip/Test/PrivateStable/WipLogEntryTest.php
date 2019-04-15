<?php

namespace Acquia\Wip\Test;

use Acquia\Wip\Implementation\WipLogEntry;
use Acquia\Wip\WipLogLevel;

/**
 * Missing summary.
 */
class WipLogEntryTest extends \PHPUnit_Framework_TestCase {

  private $containerId = "123";
  private $level = WipLogLevel::ALERT;
  private $message = 'testing';
  private $objectId = 51;
  private $id = 1234;
  private $timestamp = 1433252746;

  /**
   * Provides invalid userReadable values for testing.
   *
   * @return array
   *   The array of values provided.
   */
  public function invalidUserReadableProvider() {
    return array(
      array(1234),
      array('not a boolean'),
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
      array(1234),
    );
  }

  /**
   * Provides invalid object ID values for testing.
   *
   * @return array
   *   The array of values provided.
   */
  public function invalidObjectIdProvider() {
    return array(
      array(NULL),
      array(-5),
      array("not a number"),
      array(NAN),
    );
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testInstantiation() {
    new WipLogEntry($this->level, $this->message);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInstantiationWithBadLevel() {
    $bad_level = 17;
    new WipLogEntry($bad_level, $this->message);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInstantiationWithBadMessage() {
    $bad_msg = '';
    new WipLogEntry($this->level, $bad_msg);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testGetLogLevel() {
    $wip_log_entry = new WipLogEntry($this->level, $this->message);
    $this->assertEquals($this->level, $wip_log_entry->getLogLevel());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testGetMessage() {
    $wip_log_entry = new WipLogEntry($this->level, $this->message);
    $this->assertEquals($this->message, $wip_log_entry->getMessage());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testGetObjectId() {
    $wip_log_entry = new WipLogEntry($this->level, $this->message, $this->objectId);
    $this->assertEquals($this->objectId, $wip_log_entry->getObjectId());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   *
   * @expectedException \InvalidArgumentException
   */
  public function testBadObjectId() {
    $bad_obj = 'whatever';
    new WipLogEntry($this->level, $this->message, $bad_obj);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testGetTimestamp() {
    $wip_log_entry = new WipLogEntry($this->level, $this->message, $this->objectId, $this->timestamp);
    $this->assertEquals($this->timestamp, $wip_log_entry->getTimestamp());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   *
   * @expectedException \InvalidArgumentException
   */
  public function testBadTimestamp() {
    $bad_timestamp = "this is a string";
    new WipLogEntry($this->level, $this->message, $this->objectId, $bad_timestamp);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testGetId() {
    $wip_log_entry = new WipLogEntry($this->level, $this->message, $this->objectId, $this->timestamp, $this->id);
    $this->assertEquals($this->id, $wip_log_entry->getId());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   *
   * @expectedException \InvalidArgumentException
   */
  public function testBadId() {
    $bad_id = "this is a string";
    new WipLogEntry($this->level, $this->message, $this->objectId, $this->timestamp, $bad_id);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testSetContainerId() {
    $entry = new WipLogEntry(
      $this->level,
      $this->message,
      $this->objectId,
      $this->timestamp,
      $this->id,
      $this->containerId
    );
    $this->assertEquals($this->containerId, $entry->getContainerId());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testDefaultContainerId() {
    $entry = new WipLogEntry($this->level, $this->message, $this->objectId, $this->timestamp, $this->id);
    $this->assertEquals('0', $entry->getContainerId());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testSerialize() {
    $id = time();
    $level = WipLogLevel::ALERT;
    $message = 'testing';
    $object_id = 51;
    $entry = new WipLogEntry($level, $message, $object_id, NULL, $id, '123', FALSE);

    $expected = array();
    $expected['level'] = $level;
    $expected['message'] = $message;
    $expected['object_id'] = $object_id;
    $expected['timestamp'] = $entry->getTimestamp();
    $expected['id'] = $id;
    $expected['container_id'] = $entry->getContainerId();
    $expected['user_readable'] = FALSE;

    $this->assertEquals($expected, $entry->jsonSerialize());
    $this->assertEquals(json_encode($expected), json_encode($entry->jsonSerialize()));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   *
   * @dataProvider invalidContainerIdProvider
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidContainerId($invalid) {
    new WipLogEntry($this->level, $this->message, NULL, NULL, NULL, $invalid);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testSetUserReadable() {
    $log = new WipLogEntry($this->level, $this->message, NULL, NULL, NULL, '123', TRUE);
    $this->assertTrue(boolval($log->getUserReadable()));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testDefaultUserReadable() {
    $log = new WipLogEntry($this->level, $this->message);
    $this->assertFalse($log->getUserReadable());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   *
   * @dataProvider invalidUserReadableProvider
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidUserReadable($invalid) {
    new WipLogEntry($this->level, $this->message, NULL, NULL, NULL, '123', $invalid);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   */
  public function testSetObjectId() {
    $log = new WipLogEntry($this->level, $this->message);
    $old_object_id = $log->getObjectId();
    $new_object_id = 4321;
    $this->assertNotEquals($new_object_id, $old_object_id);

    $log->setObjectId($new_object_id);
    $this->assertEquals($new_object_id, $log->getObjectId());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipLog
   *
   * @dataProvider invalidObjectIdProvider
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetInvalidObjectId($invalid) {
    $log = new WipLogEntry($this->level, $this->message);
    $log->setObjectId($invalid);
  }

}
