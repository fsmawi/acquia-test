<?php

namespace Acquia\WipIntegrations\DoctrineORM\Entities;

use Acquia\Wip\Implementation\WipLogEntry;
use Acquia\Wip\WipLogLevel;

/**
 * Missing summary.
 */
class WipLogStoreEntryTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @group WipLog
   */
  public function testConvertFromWipLogEntry() {
    $id = 15;
    $timestamp = time();
    $object_id = 37;
    $level = WipLogLevel::DEBUG;
    $message = 'hello';
    $log_entry = new WipLogEntry($level, $message, $object_id, $timestamp, $id);

    $wip_log_store_entry = WipLogStoreEntry::fromWipLogEntry($log_entry);
    $result = $wip_log_store_entry->toWipLogEntry();

    $this->assertEquals($id, $result->getId());
    $this->assertEquals($timestamp, $result->getTimestamp());
    $this->assertEquals($object_id, $result->getObjectId());
    $this->assertEquals($level, $result->getLogLevel());
    $this->assertEquals($message, $result->getMessage());
  }

}
