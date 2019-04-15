<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\Implementation\SqliteWipLogStore;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\Iterators\BasicIterator\StateTableIterator;
use Acquia\Wip\Iterators\BasicIterator\WipContext;
use Acquia\Wip\Test\PublicStable\Ssh\SshTestSetup;
use Acquia\Wip\WipContextInterface;
use Acquia\Wip\WipInterface;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipLogLevel;
use Acquia\Wip\WipTaskResult;

/**
 * Missing summary.
 */
class WipInterfaceTest extends \PHPUnit_Framework_TestCase {
  /**
   * The Wip object.
   *
   * @var \Acquia\Wip\WipInterface
   */
  private $wip;

  /**
   * The WipContextInterface instance.
   *
   * @var WipContextInterface
   */
  private $context;

  /**
   * Missing summary.
   *
   * @var WipLogInterface
   */
  private $logger;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    $this->logger = new WipLog(new SqliteWipLogStore());
    $this->wip = new BasicWip();
    $iterator = new StateTableIterator();
    $this->wip->setIterator($iterator);
    $this->wip->setWipLog(new WipLog(new SqliteWipLogStore()));
    $this->wip->setId(1);
    $this->context = new WipContext();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testWipInterface() {
    $this->assertTrue($this->wip instanceof WipInterface, 'The BasicWip object implements the Wip interface.');
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testGetTitle() {
    $title = $this->wip->getTitle();
    $this->assertTrue(is_string($title));
    $this->assertNotEmpty($title);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testGetGroup() {
    $group = $this->wip->getGroup();
    $this->assertTrue(is_string($group));
    $this->assertNotEmpty($group);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testStateTable() {
    $state_table = $this->wip->getStateTable();
    $this->assertTrue(is_string($state_table));
    $this->assertTrue(strlen($state_table) > 0);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testStart() {
    $this->wip->start($this->context);
    $this->assertTrue(TRUE);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testFinish() {
    $this->wip->finish($this->context);
    $this->assertTrue(TRUE);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testEmptyTransition() {
    $result = $this->wip->emptyTransition($this->context);
    $this->assertTrue(is_string($result));
    $this->assertEmpty($result);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testFailure() {
    $this->wip->failure($this->context);
    $this->assertTrue(TRUE);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testFailureWithException() {
    $exception = new \Exception('');
    $this->wip->failure($this->context, $exception);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testOnAdd() {
    $this->wip->onAdd();
    $this->assertTrue(TRUE);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testOnProcess() {
    $this->wip->onProcess();
    $this->assertTrue(TRUE);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testOnWait() {
    $this->wip->onWait();
    $this->assertTrue(TRUE);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testOnStart() {
    $this->wip->onStart();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testOnFinish() {
    $this->wip->onFinish();
    $this->assertTrue(TRUE);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testOnTerminate() {
    $this->wip->onTerminate();
    $this->assertTrue(TRUE);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testOnRestart() {
    $this->wip->onRestart();
    $this->assertTrue(TRUE);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testOnFail() {
    $this->wip->onFail();
    $this->assertTrue(TRUE);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testOnSerialize() {
    $this->wip->onSerialize();
    $this->assertTrue(TRUE);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testOnDeserialize() {
    $this->wip->onDeserialize();
    $this->assertTrue(TRUE);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testAddIncludesBadDocroot() {
    $this->wip->addInclude(NULL, 'test');
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testAddIncludesBadPath() {
    $this->wip->addInclude('/', NULL);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testAddIncludes() {
    $this->wip->addInclude('/', 'test');
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testGetIncludesEmpty() {
    $result = $this->wip->getIncludes();
    $this->assertTrue(is_array($result));
    $this->assertEmpty($result);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testGetIncludesNotEmpty() {
    $this->testAddIncludes();
    $result = $this->wip->getIncludes();
    $this->assertTrue(is_array($result));
    $this->assertNotEmpty($result);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testGetIncludesType() {
    $this->testAddIncludes();
    $result = $this->wip->getIncludes();
    $include_file = $result[0];
    $this->assertInstanceOf('Acquia\Wip\IncludeFileInterface', $include_file);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testIncludesValue() {
    $docroot = 'docroot';
    $file_path = 'file_path';
    $this->wip->addInclude($docroot, $file_path);
    $results = $this->wip->getIncludes();
    $this->assertCount(1, $results);
    $full_path = $results[0]->getFullPath();
    $this->assertTrue($full_path == sprintf('%s%s%s', $docroot, DIRECTORY_SEPARATOR, $file_path));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testSetId() {
    $id = 15;
    $this->wip->setId($id);
    $this->assertEquals($id, $this->wip->getId());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetIdWithBadId() {
    $id = 'hello';
    $this->wip->setId($id);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testSetWipLog() {
    $wip = new BasicWip();
    $wip_log = new WipLog(new SqliteWipLogStore());
    $wip->setWipLog($wip_log);
    $this->assertEquals($wip_log, $wip->getWipLog());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testSetLogLevel() {
    $original_level = $this->wip->getLogLevel();
    $this->assertTrue(WipLogLevel::isValid($original_level));
    $new_level = $original_level === WipLogLevel::TRACE ? WipLogLevel::DEBUG : WipLogLevel::TRACE;
    $this->wip->setLogLevel($new_level);
    $this->assertEquals($new_level, $this->wip->getLogLevel());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetBadLogLevel() {
    $new_level = 'hello';
    $this->wip->setLogLevel($new_level);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testLog() {
    $obj_id = 15;
    $message = 'test message';
    $wip_log = new WipLog(new SqliteWipLogStore());
    $this->wip->setWipLog($wip_log);
    $wip_store = $wip_log->getStore();
    $wip_store->delete($obj_id);
    $this->assertEquals(0, count($wip_store->load($obj_id)));
    $this->wip->setId($obj_id);
    $this->wip->log(WipLogLevel::DEBUG, $message);
    $entries = $wip_store->load($obj_id);
    $this->assertEquals(1, count($entries));
    $this->assertEquals(WipLogLevel::DEBUG, $entries[0]->getLogLevel());
    $this->assertEquals($message, $entries[0]->getMessage());
    $this->assertEquals($obj_id, $entries[0]->getObjectId());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testMultiLog() {
    $obj_id = 15;
    $fatal_message = 'fatal';
    $info_message = 'info';
    $wip_log = new WipLog(new SqliteWipLogStore());
    ;
    $this->wip->setWipLog($wip_log);
    $wip_store = $wip_log->getStore();
    $wip_store->delete($obj_id);
    $this->wip->setId($obj_id);
    $this->wip->multiLog(WipLogLevel::FATAL, $fatal_message, WipLogLevel::INFO, $info_message);
    $entries = $wip_store->load($obj_id);
    $this->assertEquals(1, count($entries));
    $this->assertEquals(WipLogLevel::FATAL, $entries[0]->getLogLevel());
    $this->assertEquals($obj_id, $entries[0]->getObjectId());
    $this->assertContains($fatal_message, $entries[0]->getMessage());
    $this->assertContains($info_message, $entries[0]->getMessage());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testSetStateTable() {
    $state_table = <<<EOT
start {
  * finish
}
EOT;

    if ($this->wip instanceof BasicWip) {
      $this->wip->setStateTable($state_table);
    }
    $this->assertEquals($state_table, $this->wip->getStateTable());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testSerialize() {
    $this->wip->setWipLog(new WipLog(new SqliteWipLogStore()));
    $data = serialize($this->wip);
    $new_wip = unserialize($data);
    $this->assertNotEmpty($new_wip);
    $this->assertInstanceOf('Acquia\Wip\WipInterface', $new_wip);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * Make sure all fields are being serialized except the set that are not
   * serializable.
   *
   * NOTE: Put "@notSerializable in the documentation block of properties that
   * are not serializable.
   */
  public function testVerifySerializableFields() {
    $this->wip->setWipLog(new WipLog(new SqliteWipLogStore()));
    $non_serializable_fields = array();
    $reflection_class = new \ReflectionClass(get_class($this->wip));
    $all_properties = $reflection_class->getProperties();
    foreach ($all_properties as $property) {
      if (strpos($property->getDocComment(), '@notSerializable') !== FALSE) {
        $non_serializable_fields[] = $property->getName();
        $property->setAccessible(TRUE);
        $this->assertNotEmpty(
          $property->getValue($this->wip),
          sprintf('Expected object property %s to not be empty.', $property->getName())
        );
      }
    }

    // Serialize, deserialize, then verify the non-serializable fields are empty.
    $new_obj = unserialize(serialize($this->wip));
    foreach ($all_properties as $property) {
      if (in_array($property->getName(), $non_serializable_fields)) {
        $property->setAccessible(TRUE);
        $this->assertEmpty($property->getValue($new_obj));
      }
    }
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testGetWipApi() {
    $this->wip->setId(15);
    $wip_api = $this->wip->getWipApi();
    $this->assertInstanceOf('Acquia\Wip\WipTaskInterface', $wip_api);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testGetSshApi() {
    $this->wip->setId(15);
    $ssh_api = $this->wip->getSshApi();

    $this->assertInstanceOf('Acquia\Wip\WipSshInterface', $ssh_api);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testWipTaskStatus() {
    if ($this->wip instanceof BasicWip) {
      $wip_api = $this->wip->getWipApi();
      $wip_context = new WipContext();
      // Create a new WIP otherwise we get a side effect as the wip already has
      // an id which result in a mismatch when adding a new task.
      $basic_wip = new BasicWip();
      $parent = clone $basic_wip;
      $basic_wip->setUuid((string) \Ramsey\Uuid\Uuid::uuid4());
      $basic_wip->setWipLog($this->logger);
      $wip_api->addChild($basic_wip, $wip_context, $parent);

      $status = $this->wip->checkWipTaskStatus($wip_context, $this->logger);
      $this->assertEquals('wait', $status);
    } else {
      $this->assertInstanceof('Acquia\Wip\Implementation\BasicWip', $this->wip);
    }
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testSshStatusEmpty() {
    $this->assertInstanceof('Acquia\Wip\Implementation\BasicWip', $this->wip);
    if ($this->wip instanceof BasicWip) {
      $this->wip->setWipLog(SshTestSetup::createWipLog());
      $wip_context = new WipContext();
      $status = $this->wip->checkSshStatus($wip_context);
      $this->assertEquals('uninitialized', $status);
    }
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testRestartTaskInvalidWipId() {
    $this->wip->getWipApi()->restartTask("not an int!", $this->context, $this->logger);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testClearWipResults() {
    $task = new \Acquia\Wip\Task();
    $task->setId(15);
    $result = WipTaskResult::fromTask($task);
    $this->wip->getWipApi()->addWipTaskResult($result, $this->context);
    $this->assertGreaterThan(0, count($this->wip->getWipApi()->getWipTaskResults($this->context)));
    $this->wip->getWipApi()->clearWipTaskResults($this->context, $this->logger);
    $this->assertEquals(0, count($this->wip->getWipApi()->getWipTaskResults($this->context)));
  }

  /**
   * Test that the instance version is initialized properly.
   *
   * @group Wip
   */
  public function testVersionInitialization() {
    $class_name = get_class($this->wip);
    $this->assertEquals(
      $this->wip->getClassVersion(),
      $this->wip->getInstanceVersion($class_name)->getVersionNumber()
    );
  }

  /**
   * Verifies the instance version can be set.
   *
   * @group Wip
   */
  public function testSetInstanceVersion() {
    $class_version = $this->wip->getClassVersion();
    $instance_version = $class_version + 1;
    $class_name = get_class($this->wip);
    $this->wip->setInstanceVersion($class_name, $instance_version);
    $this->assertEquals($class_version, $this->wip->getClassVersion());
    $this->assertEquals($instance_version, $this->wip->getInstanceVersion($class_name)->getVersionNumber());
  }

  /**
   * Verifies invalid versions are rejected.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   *
   * @dataProvider invalidVersionsProvider
   */
  public function testSetInstanceInvalidVersions($version) {
    $class_name = get_class($this->wip);
    $this->wip->setInstanceVersion($class_name, $version);
  }

  /**
   * The set of invalid instance versions.
   *
   * @return array
   *   The invalid version values.
   */
  public function invalidVersionsProvider() {
    return [
      ['Hello, world!'],
      ['1'],
      [-1],
      [0],
    ];
  }

}
