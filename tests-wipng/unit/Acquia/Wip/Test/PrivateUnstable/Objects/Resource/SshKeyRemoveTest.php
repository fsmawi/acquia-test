<?php

namespace Acquia\Wip\Test\PrivateUnstable\Objects;

use Acquia\Wip\Objects\Resource\SshKeyRemove;
use Acquia\Wip\WipTaskConfig;

/**
 * Missing summary.
 */
class SshKeyRemoveTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @var SshKeyRemove
   */
  private $wip = NULL;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    $this->wip = new SshKeyRemove();
  }

  /**
   * Missing summary.
   */
  private function createWipTaskConfig($options) {
    $result = new WipTaskConfig();
    $result->setClassId('Acquia\Wip\Objects\Resource\SshKeyRemove');
    $result->setOptions($options);
    return $result;
  }

  /**
   * Missing summary.
   */
  private function createTaskOptions($key_name = NULL) {
    $result = new \stdClass();
    if (!empty($key_name)) {
      $result->keyName = $key_name;
    }
    return $result;
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @group Wip
   */
  public function testSetConfig() {
    $key_name = 'test_key_name';
    $config = $this->createWipTaskConfig($this->createTaskOptions($key_name));
    $this->wip->setWipTaskConfig($config);

    $this->assertEquals($key_name, $this->wip->getKeyName());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @group Wip
   *
   * @expectedException \DomainException
   */
  public function testGetWorkIdWithNoConfigSet() {
    $this->wip->getWorkId();
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @group Wip
   */
  public function testGetWorkIdEqual() {
    $key_name = 'test_key_name';
    $config = $this->createWipTaskConfig($this->createTaskOptions($key_name));
    $this->wip->setWipTaskConfig($config);
    $work_id = $this->wip->getWorkId();

    // Now make another Wip and compare work IDs.
    $new_wip = new SshKeyRemove();
    $new_wip->setWipTaskConfig($config);
    $this->assertEquals($work_id, $new_wip->getWorkId());
  }

}
