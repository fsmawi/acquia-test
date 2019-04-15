<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\Environment;
use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\IteratorStatus;
use Acquia\Wip\Iterators\BasicIterator\StateTableIterator;
use Acquia\Wip\Iterators\BasicIterator\WipContext;
use Acquia\Wip\Ssh\SshKeys;
use Acquia\Wip\WipLogLevel;

/**
 * Missing summary.
 */
class BasicWipTest extends \PHPUnit_Framework_TestCase {

  /**
   * The Wip object being tested.
   *
   * @var BasicWip
   */
  private $wip;

  /**
   * The iterator.
   *
   * @var StateTableIterator
   */
  private $iterator;

  /**
   * The state table.
   *
   * @var string
   */
  private $stateTable = <<<EOT
  start {
    * finish
  }
EOT;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    $this->wip = new BasicWip();
    $this->iterator = new StateTableIterator();
    $this->iterator->initialize($this->wip);
    $this->iterator->compileStateTable();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   */
  public function testSetStateTable() {
    $this->wip->setStateTable($this->stateTable);
    $state_table = $this->wip->getStateTable();
    $this->assertEquals($this->stateTable, $state_table);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   */
  public function testGetWorkId() {
    $work_id = $this->wip->getWorkId();
    $this->assertNotEmpty($work_id);
    $this->assertEquals($work_id, $this->wip->getWorkId());
    $this->assertNotEquals($work_id, (new Basicwip())->getWorkId());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   */
  public function testGetTitle() {
    $this->assertContains('Default title', $this->wip->getTitle());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   */
  public function testSetGroup() {
    $group_name = 'group';
    $this->wip->setGroup($group_name);
    $this->assertEquals($group_name, $this->wip->getGroup());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetGroupEmpty() {
    $group_name = '';
    $this->wip->setGroup($group_name);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetGroupWrongType() {
    $group_name = 15;
    $this->wip->setGroup($group_name);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   */
  public function testStart() {
    $this->wip->start(new WipContext());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   */
  public function testFinish() {
    $this->wip->finish(new WipContext());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   */
  public function testEmptyTransition() {
    $this->assertEquals('', $this->wip->emptyTransition(new WipContext()));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   */
  public function testFailure() {
    $this->assertEmpty($this->wip->getExitMessage());
    $this->wip->failure(new WipContext());
    $this->assertNotEmpty($this->wip->getExitMessage());
    $this->assertEquals(IteratorStatus::ERROR_SYSTEM, $this->wip->getExitCode());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   */
  public function testOnAdd() {
    $this->wip->onAdd();
  }

  /**
   * Missing summary.
   *
   * @group Wip
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
   *
   * @group Wip
   */
  public function testOnProcess() {
    $this->wip->onProcess();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   */
  public function testOnWait() {
    $this->wip->onWait();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   */
  public function testOnFinish() {
    $this->wip->onFinish();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   */
  public function testOnTerminate() {
    $this->wip->onTerminate();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   */
  public function testOnRestart() {
    $this->wip->onRestart();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   */
  public function testOnFail() {
    $this->assertEmpty($this->wip->getExitMessage());
    $this->wip->onFail(new WipContext());
    $this->assertNotEmpty($this->wip->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   */
  public function testOnSerialize() {
    $this->wip->onSerialize();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   */
  public function testOnDeserialize() {
    $this->wip->onDeserialize();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   */
  public function testSetId() {
    $id = 15;
    $this->iterator->setId($id);
    $this->assertEquals($id, $this->wip->getId());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetIdWrongType() {
    $id = "15";
    $this->wip->setId($id);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   */
  public function testSetWipLog() {
    $wip_log = new WipLog();
    $this->wip->setWipLog($wip_log);
    $this->assertEquals($wip_log, $this->wip->getWipLog());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   */
  public function testLogLevel() {
    $log_level = WipLogLevel::ALERT;
    $this->wip->setLogLevel($log_level);
    $this->assertEquals($log_level, $this->wip->getLogLevel());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   */
  public function testLogLevelInvalidValue() {
    $log_level = 1500;
    $this->wip->setLogLevel($log_level);
    $this->assertNotEquals($log_level, $this->wip->getLogLevel());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   */
  public function testMultiLog() {
    $this->wip->multiLog(WipLogLevel::ALERT, 'Testing alert message', WipLogLevel::ERROR, 'Testing error message');
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   */
  public function testGetWipApi() {
    $this->wip->setId(15);
    $api = $this->wip->getWipApi();
    $this->assertInstanceOf('Acquia\Wip\Implementation\WipTaskApi', $api);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   */
  public function testGetSshApi() {
    $this->wip->setId(15);
    $api = $this->wip->getSshApi();
    $this->assertInstanceOf('Acquia\Wip\Implementation\SshApi', $api);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   */
  public function testGetCloudApi() {
    $this->wip->setId(15);
    $api = $this->wip->getAcquiaCloudApi();
    $this->assertInstanceOf('Acquia\Wip\Implementation\AcquiaCloudApi', $api);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   */
  public function testGetSshService() {
    $environment = $this->getTestSshEnvironment();
    $this->wip->setId(15);
    $api = $this->wip->getSshService($environment);
    $this->assertInstanceOf('Acquia\Wip\Ssh\SshService', $api);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   */
  public function testGetSsh() {
    $description = 'Description';
    $environment = $this->getTestSshEnvironment();
    $this->wip->setId(15);
    $ssh = $this->wip->getSsh($description, $environment);
    $this->assertEquals($description, $ssh->getDescription());
    $this->assertEquals($environment, $ssh->getEnvironment());
    $this->assertInstanceOf('Acquia\Wip\Ssh\Ssh', $ssh);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   */
  public function testGetFileCommands() {
    $description = 'Description';
    $environment = $this->getTestSshEnvironment();
    $this->wip->setId(15);
    $file_commands = $this->wip->getFileCommands($environment);
    $this->assertInstanceOf('Acquia\Wip\Ssh\SshFileCommands', $file_commands);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   */
  public function testGetGitCommands() {
    $description = 'Description';
    $environment = $this->getTestSshEnvironment();
    $this->wip->setId(15);
    $git_commands = $this->wip->getGitCommands($environment, 'workspace');
    $this->assertInstanceOf('Acquia\Wip\Ssh\GitCommands', $git_commands);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @group Wip
   */
  public function testGetContainerApi() {
    $api = $this->wip->getContainerApi();
    $this->assertInstanceOf('Acquia\Wip\Implementation\ContainerApi', $api);
  }

  /**
   * Prepares and returns an environment for unit testing.
   *
   * @return EnvironmentInterface
   *   The environment.
   */
  private function getTestSshEnvironment() {
    SshKeys::setBasePath(sys_get_temp_dir());
    $environment = new Environment();
    $environment->setSitegroup('sitegroup');
    $environment->setEnvironmentName('environment');
    $environment->setServers(array('localhost'));
    return $environment;
  }

  /**
   * Tests that setUuid does not accept an invalid uuid.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetUuidInvalid() {
    $config = new BasicWip();
    $uuid = 100;
    $config->setUuid($uuid);
  }

}
