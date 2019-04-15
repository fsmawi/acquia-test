<?php

namespace Acquia\Wip\Test;

use Acquia\Wip\Objects\ParameterDocument;
use Acquia\Wip\Signal\UriCallback;
use Acquia\Wip\Test\PrivateStable\Objects\ParameterDocumentTest;
use Acquia\Wip\WipLogLevel;
use Acquia\Wip\WipTaskConfig;

/**
 * Missing summary.
 */
class WipTaskConfigTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipTask
   */
  public function testClassId() {
    $task_id = 'task-id';
    $config = new WipTaskConfig();
    $config->setClassId($task_id);
    $this->assertEquals($task_id, $config->getClassId());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \InvalidArgumentException
   */
  public function testClassIdWrongType() {
    $task_id = 16;
    $config = new WipTaskConfig();
    $config->setClassId($task_id);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipTask
   */
  public function testWipId() {
    $wip_id = 16;
    $config = new WipTaskConfig();
    $config->setWipId($wip_id);
    $this->assertEquals($wip_id, $config->getWipId());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \InvalidArgumentException
   */
  public function testWipIdWrongType() {
    $wip_id = 'wrong-type';
    $config = new WipTaskConfig();
    $config->setWipId($wip_id);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipTask
   */
  public function testSetGroupName() {
    $group_name = 'wrong-type';
    $config = new WipTaskConfig();
    $config->setGroupName($group_name);
    $this->assertEquals($group_name, $config->getGroupName());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetGroupNameWrongType() {
    $group_name = 16;
    $config = new WipTaskConfig();
    $config->setGroupName($group_name);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipTask
   */
  public function testParameterDocument() {
    $parameter_document = ParameterDocumentTest::getParameterDocument();
    $config = new WipTaskConfig();
    $config->setParameterDocument($parameter_document);
    $this->assertEquals($parameter_document, $config->getParameterDocument());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipTask
   */
  public function testOptions() {
    $options = new \stdClass();
    $options->propertyOne = 'one';
    $config = new WipTaskConfig();
    $config->setOptions($options);
    $this->assertEquals($options, $config->getOptions());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipTask
   */
  public function testCallback() {
    $callback = new UriCallback('http://www.google.com');
    $config = new WipTaskConfig();
    $config->setCallback($callback);
    $this->assertEquals($callback, $config->getCallback());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipTask
   */
  public function testLogLevel() {
    $log_level = WipLogLevel::ERROR;
    $config = new WipTaskConfig();
    $config->setLogLevel($log_level);
    $this->assertEquals($log_level, $config->getLogLevel());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \InvalidArgumentException
   */
  public function testLogLevelInvalidValue() {
    $log_level = 1000;
    $config = new WipTaskConfig();
    $config->setLogLevel($log_level);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipTask
   */
  public function testCreatedTime() {
    $created_time = time();
    $config = new WipTaskConfig();
    $config->setCreatedTimestamp($created_time);
    $this->assertEquals($created_time, $config->getCreatedTimestamp());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \DomainException
   */
  public function testGetCreatedTimeNotSet() {
    $config = new WipTaskConfig();
    $config->getCreatedTimestamp();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipTask
   */
  public function testInitializeTime() {
    $time = time();
    $config = new WipTaskConfig();
    $config->setInitializeTime($time);
    $this->assertEquals($time, $config->getinitializeTime());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \DomainException
   */
  public function testGetInitializeTimeNotSet() {
    $config = new WipTaskConfig();
    $config->getInitializeTime();
  }

  /**
   * Tests get and set on uuid.
   *
   * @group Wip
   * @group WipTask
   */
  public function testSetUuid() {
    $config = new WipTaskConfig();
    $uuid = '100';
    $config->setUuid($uuid);
    $this->assertEquals($uuid, $config->getUuid());
  }

  /**
   * Tests that setUuid does not accept an invalid uuid.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetUuidInvalid() {
    $config = new WipTaskConfig();
    $uuid = 100;
    $config->setUuid($uuid);
  }

}
