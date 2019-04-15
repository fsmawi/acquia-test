<?php

namespace Acquia\Wip\Test\Integration\PublicStable\AcquiaCloud;

use Acquia\Cloud\Api\Response\Task;
use Acquia\Wip\AcquiaCloud\AcquiaCloud;
use Acquia\Wip\AcquiaCloud\AcquiaCloudResult;
use Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudTaskInfo;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudTaskArrayResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudTaskResult;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogInterface;

/**
 * Missing summary.
 */
class AcquiaCloudTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @var int
   */
  private $wipId = NULL;

  /**
   * Missing summary.
   *
   * @var AcquiaCloud
   */
  private $cloud = NULL;

  /**
   * Missing summary.
   *
   * @var WipLogInterface
   */
  private $logger = NULL;

  /**
   * The number of times a failed request will retry.
   *
   * @var int
   */
  private $retry = NULL;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    if ($this->retry === NULL) {
      $this->retry = WipFactory::getInt('$acquia.wip.acquiacloud.retrycount', 0);
    }
    $this->logger = AcquiaCloudTestSetup::createWipLog();
    $this->wipId = mt_rand(1, PHP_INT_MAX);
    $this->cloud = new AcquiaCloud(AcquiaCloudTestSetup::getEnvironment(), $this->logger, $this->wipId);
  }

  /**
   * Missing summary.
   */
  public function tearDown() {
    parent::tearDown();
    WipFactory::addConfiguration(sprintf('$acquia.wip.acquiacloud.retrycount => %s', $this->retry));
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   *
   * There should be some tasks in hosting simply as a matter of setting up
   * the system.  Ensure we can fetch the list of tasks.
   */
  public function testGetTasks() {
    // @TODO after the Acquia Hosting 1.90 release, we cannot list tasks.
    // The following includes several mocks to avoid querying.
    // $result = $this->cloud->getTasks();
    $result = new AcquiaCloudTaskArrayResult(TRUE);

    $data['id'] = 1216414;
    $data['queue'] = "create-db-backup-ondemand";
    $data['state'] = "done";
    $data['description'] = "Backup database wipservice in test environment.";
    $data['created'] = 1461778441;
    $data['started'] = 1461778442;
    $data['completed'] = 1461778451;
    $data['sender'] = "qa.woodypride@acquia.com";
    $data['result'] = new \stdClass();
    $data['result']->backupid = 463206;
    $data['cookie'] = NULL;
    $data['logs'] = "[17:34:02] [17:34:02] Started\n[17:34:11] [17:34:11] Done\n";
    $task_data = new Task($data);

    $mock_task = new AcquiaCloudTaskInfo($task_data);
    $tasks = [$mock_task];

    $result->setExitCode(AcquiaCloudResult::EXIT_CODE_SUCCESS);
    $result->setData($tasks);

    if (!$result->isSuccess()) {
      $this->fail(sprintf('Failed to fetch Acquia Cloud tasks: %s', $result->getExitMessage()));
    }
    $tasks = $result->getData();

    if (count($tasks) > 0) {
      // @TODO disabled.
      // $task_index = mt_rand(0, count($tasks) - 1);
      $task_index = 0;

      /** @var AcquiaCloudTaskInfo $task */
      $task = $tasks[$task_index];
      // @TODO disabled.
      // $task2 = $this->cloud->getTaskInfo($task->getId());
      $mock_task_2 = new AcquiaCloudTaskInfo($task_data);
      $task2 = new AcquiaCloudTaskResult();
      $task2->setData($mock_task_2);
      $task2->setExitCode(AcquiaCloudResult::EXIT_CODE_SUCCESS);
      $this->assertTrue($task2->isSuccess());
      $task_info = $task2->getData();
      $this->assertInstanceof('Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudTaskInfo', $task_info);
      $this->assertEquals($task->getId(), $task_info->getId());
      $this->assertEquals($task->getCompleted(), $task_info->getCompleted());
      $this->assertEquals($task->getCookies(), $task_info->getCookies());
      $this->assertEquals($task->getCreated(), $task_info->getCreated());
      $this->assertEquals($task->getDescription(), $task_info->getDescription());
      $this->assertEquals($task->getLogs(), $task_info->getLogs());
      $this->assertEquals($task->getQueue(), $task_info->getQueue());
      $this->assertEquals($task->getResult(), $task_info->getResult());
      $this->assertEquals($task->getSender(), $task_info->getSender());
      $this->assertEquals($task->getStarted(), $task_info->getStarted());
      $this->assertEquals($task->getState(), $task_info->getState());
      $this->assertEquals($task->hasStarted(), $task_info->hasStarted());
      $this->assertEquals($task->isSuccess(), $task_info->isSuccess());
      $this->assertEquals($task->isFailure(), $task_info->isFailure());
      $this->assertEquals($task->isRunning(), $task_info->isRunning());
      $json = $task2->toJson();
      $this->assertNotEmpty($json);
    }
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testListDomains() {
    $result = $this->cloud->listDomains();
    if (!$result->isSuccess()) {
      $this->fail(sprintf('Failed to list domains: %s', $result->getExitMessage()));
    }
    $domains = $result->getData();
    $this->assertTrue($result->isSuccess());
    $this->assertTrue(count($domains) > 0);
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testGetDomain() {
    $domains = $this->cloud->listDomains();
    if (!$domains->isSuccess()) {
      $this->fail(sprintf('Failed to list domains: %s', $domains->getExitMessage()));
    }
    $domain_name = $domains->getData()[0];
    $result = $this->cloud->getDomainInfo($domain_name);
    $this->assertTrue($result->isSuccess());
    $this->assertEquals($domain_name, $result->getData());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testGetDomainWithBadDomain() {
    $cloud = $this->getNoRetryCloud();
    $domain_name = 'bad_domain';
    $result = $cloud->getDomainInfo($domain_name);
    $this->assertFalse($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testListServers() {
    $server_response = $this->cloud->listServers();
    if (!$server_response->isSuccess()) {
      $this->fail(sprintf('Failed to list servers: %s', $server_response->getExitMessage()));
    }
    $servers = $server_response->getData();
    $this->assertTrue(count($servers) > 0);
    foreach ($servers as $server) {
      $this->assertInstanceof('Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudServerInfo', $server);
      $server_info = $this->cloud->getServerInfo($server->getName())->getData();
      $this->assertInstanceof('Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudServerInfo', $server_info);
      $this->assertEquals($server->getName(), $server_info->getName());
      $this->assertEquals($server->getAmiType(), $server_info->getAmiType());
      $this->assertEquals($server->getAvailabilityZone(), $server_info->getAvailabilityZone());
      $this->assertEquals($server->getFullyQualifiedDomainName(), $server_info->getFullyQualifiedDomainName());
      $this->assertEquals($server->getRegion(), $server_info->getRegion());
      $this->assertEquals($server->getServices(), $server_info->getServices());
      $this->assertNotEmpty($server->jsonSerialize());
    }
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testListSshKeys() {
    $key_response = $this->cloud->listSshKeys();
    if (!$key_response->isSuccess()) {
      $this->fail(sprintf('Failed to list ssh keys: %s', $key_response->getExitMessage()));
    }
    $keys = $key_response->getData();
    $this->assertTrue(count($keys) > 0);
    foreach ($keys as $key) {
      $this->assertInstanceof('Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudSshKeyInfo', $key);
      $key_info = $this->cloud->getSshKey($key->getId())->getData();
      $this->assertInstanceOf('Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudSshKeyInfo', $key_info);
      $this->assertEquals($key->getId(), $key_info->getId());
      $this->assertEquals($key->getName(), $key_info->getName());
      $this->assertEquals($key->getBlacklist(), $key_info->getBlacklist());
      $this->assertEquals($key->getPublicKey(), $key_info->getPublicKey());
      $this->assertEquals($key->hasShellAccess(), $key_info->hasShellAccess());
      $this->assertEquals($key->hasVcsAccess(), $key_info->hasVcsAccess());
      $this->assertNotEmpty($key->jsonSerialize());
    }
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testAddSshKeyBadKey() {
    $expected_result_class = 'Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudAddSshKeyTaskInfo';
    $cloud = $this->getNoRetryCloud();
    $process = $cloud->addSshKey('Bad key name', 'Invalid ssh key');
    $this->assertEquals($expected_result_class, $process->getTaskInfoClass());
    $this->assertTrue($process->hasCompleted($this->logger));
    $result = $process->getResult($this->logger);
    $this->assertFalse($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testDeleteDatabaseBadRoleName() {
    $cloud = $this->getNoRetryCloud();
    $process = $cloud->deleteDatabase('Bad role name', FALSE);
    $this->assertTrue($process->hasCompleted($this->logger));
    $result = $process->getResult($this->logger);
    $this->assertFalse($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testDeleteDatabaseBackupBadRoleName() {
    $cloud = $this->getNoRetryCloud();
    $process = $cloud->deleteDatabaseBackup('Bad role name', -3);
    $this->assertTrue($process->hasCompleted($this->logger));
    $result = $process->getResult($this->logger);
    $this->assertFalse($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testAddDatabaseBadRoleName() {
    $cloud = $this->getNoRetryCloud();
    $process = $cloud->addDatabase('Bad role name');
    $this->assertTrue($process->hasCompleted($this->logger));
    $result = $process->getResult($this->logger);
    $this->assertFalse($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testBackupDatabaseBadRoleName() {
    $cloud = $this->getNoRetryCloud();
    $process = $cloud->backupDatabase('Bad role name');
    $this->assertTrue($process->hasCompleted($this->logger));
    $result = $process->getResult($this->logger);
    $this->assertFalse($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testRestoreDatabaseBadRoleName() {
    $cloud = $this->getNoRetryCloud();
    $process = $cloud->restoreDatabase('Bad role name', -3);
    $this->assertTrue($process->hasCompleted($this->logger));
    $result = $process->getResult($this->logger);
    $this->assertFalse($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testDeployCodeBadSourceEnv() {
    $cloud = $this->getNoRetryCloud();
    $process = $cloud->deployCode('Invalid env name', 'Invalid env name 2');
    $this->assertTrue($process->hasCompleted($this->logger));
    $result = $process->getResult($this->logger);
    $this->assertFalse($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testCopyDatabaseBadSourceEnv() {
    $cloud = $this->getNoRetryCloud();
    $process = $cloud->copyDatabase('Bad db role', 'Invalid env name', 'Invalid env name 2');
    $this->assertTrue($process->hasCompleted($this->logger));
    $result = $process->getResult($this->logger);
    $this->assertFalse($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testMoveDomainsBadDomainName() {
    $cloud = $this->getNoRetryCloud();
    $process = $cloud->moveDomains(array('Invalid domain name'), 'Invalid env name', TRUE);
    $this->assertTrue($process->hasCompleted($this->logger));
    $result = $process->getResult($this->logger);
    $this->assertFalse($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testMoveAllDomainsBadTargetEnv() {
    $cloud = $this->getNoRetryCloud();
    $process = $cloud->moveAllDomains('bad environment name');
    $this->assertTrue($process->hasCompleted($this->logger));
    $result = $process->getResult($this->logger);
    $this->assertFalse($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testCopyFilesBadTargetEnv() {
    $cloud = $this->getNoRetryCloud();
    $process = $cloud->copyFiles('bad source env', 'bad target env name');
    $this->assertTrue($process->hasCompleted($this->logger));
    $result = $process->getResult($this->logger);
    $this->assertFalse($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testDeleteVcsUserBadUserId() {
    $cloud = $this->getNoRetryCloud();
    $process = $cloud->deleteVcsUser(-1);
    $this->assertTrue($process->hasCompleted($this->logger));
    $result = $process->getResult($this->logger);
    $this->assertFalse($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testAddVcsUserBadUserName() {
    $cloud = $this->getNoRetryCloud();
    $process = $cloud->addVcsUser('Illegal user name!', '');
    $this->assertTrue($process->hasCompleted($this->logger));
    $result = $process->getResult($this->logger);
    $this->assertFalse($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testGetSiteDatabases() {
    $database_response = $this->cloud->getSiteDatabases();
    if (!$database_response->isSuccess()) {
      $this->fail(sprintf('Failed to list site databases: %s', $database_response->getExitMessage()));
    }
    $databases = $database_response->getData();
    $this->assertTrue(count($databases) > 0);
    foreach ($databases as $database_name) {
      $db_info = $this->cloud->getDatabaseEnvironmentInfo($database_name)->getData();
      $this->assertInstanceOf('Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudDatabaseInfo', $db_info);
      $this->assertEquals($database_name, $db_info->getRoleName());
      $this->assertTrue($db_info->getCluster() > 0);
      $this->assertNotEmpty($db_info->getUsername());
      $this->assertNotEmpty($db_info->getPassword());
      $this->assertNotempty($db_info->getHostName());
      $this->assertNotEmpty($db_info->getInstanceName());
      $json = $db_info->jsonSerialize();
      $this->assertNotEmpty($json);
    }
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testGetEnvironmentDatabases() {
    $database_response = $this->cloud->listDatabaseEnvironmentInfo();
    if (!$database_response->isSuccess()) {
      $this->fail(sprintf('Failed to list database environment information: %s', $database_response->getExitMessage()));
    }
    $databases = $database_response->getData();
    foreach ($databases as $database) {
      $this->assertInstanceOf('Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudDatabaseInfo', $database);
      $this->assertNotEmpty($database->getRoleName());
      $this->assertNotEmpty($database->getHostName());
      $this->assertNotEmpty($database->getInstanceName());
      $this->assertNotEmpty($database->getPassword());
      $this->assertNotEmpty($database->getUsername());
      $this->assertTrue($database->getCluster() > 0);
    }
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testListDatabaseBackups() {
    $database_response = $this->cloud->getSiteDatabases();
    if (!$database_response->isSuccess()) {
      $this->fail(sprintf('Failed to list site databases: %s', $database_response->getExitMessage()));
    }
    $databases = $database_response->getData();
    $this->assertTrue(count($databases) > 0);
    foreach ($databases as $database_name) {
      $backups = $this->cloud->listDatabaseBackups($database_name)->getData();
      foreach ($backups as $backup) {
        $this->assertInstanceOf('Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudBackupInfo', $backup);
        $backup_info = $this->cloud->getDatabaseBackupInfo($database_name, $backup->getId())->getData();
        $this->assertInstanceOf('Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudBackupInfo', $backup_info);
        $this->assertEquals($backup->getCompleted(), $backup_info->getCompleted());
        $this->assertEquals($backup->getId(), $backup_info->getId());
        $this->assertEquals($backup->getChecksum(), $backup_info->getChecksum());
        $this->assertEquals($backup->getName(), $backup_info->getName());
        $this->assertEquals($backup->getPath(), $backup_info->getPath());
        $this->assertEquals($backup->getStarted(), $backup_info->getStarted());
        $this->assertEquals($backup->getType(), $backup_info->getType());
        $this->assertEquals($backup->isDeleted(), $backup_info->isDeleted());

        // Note: The link includes a timestamp and a unique code. Strip that off
        // for the comparison.
        $matches = array();
        preg_match('/^(http.*:\/\/.*sql.gz).*$/', $backup->getLink(), $matches);
        $this->assertTrue(count($matches) > 1);
        $link_prefix = $matches[1];
        $this->assertStringStartsWith($link_prefix, $backup_info->getLink());
        $json_data = json_encode($backup->jsonSerialize());
        $this->assertNotEmpty($json_data);
      }
    }
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testListSvnUsers() {
    $result = $this->cloud->listVcsUsers();
    if (!$result->isSuccess()) {
      $this->fail(sprintf('Failed to list VCS users: %s', $result->getExitMessage()));
    }
    $users = $result->getData();
    $this->assertTrue(count($users) > 0);
    foreach ($users as $user) {
      $this->assertTrue($user->getId() > 0);
      $this->assertNotEmpty(($user->getUsername()));
      $user_info = $this->cloud->getVcsUser($user->getId())->getData();
      $this->assertEquals($user->getId(), $user_info->getId());
      $this->assertEquals($user->getUsername(), $user_info->getUsername());
      $this->assertNotEmpty($user->jsonSerialize());
    }
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testListSites() {
    $site_response = $this->cloud->listSites();
    if (!$site_response->isSuccess()) {
      $this->fail(sprintf('Failed to list sites: %s', $site_response->getExitMessage()));
    }

    $realm = AcquiaCloudTestSetup::getRealm();
    $sitegroup = AcquiaCloudTestSetup::getSitegroup();
    $site = sprintf('%s:%s', $realm, $sitegroup);

    $this->assertTrue(strlen($site) > 1);
    $site_info = $this->cloud->getSiteRecord($site)->getData();
    $this->assertInstanceOf('Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudSiteInfo', $site_info);
    $this->assertEquals($site, $site_info->getName());
    $this->assertNotEmpty($site_info->getUnixUsername());
    $this->assertNotEmpty($site_info->getUuid());
    $this->assertNotEmpty($site_info->getVcsType());
    $this->assertNotEmpty($site_info->getVcsUrl());
    $this->assertTrue(is_bool($site_info->isProductionMode()));
    $this->assertNotEmpty($site_info->jsonSerialize());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testListEnvironments() {
    $environment_response = $this->cloud->listEnvironments();
    if (!$environment_response->isSuccess()) {
      $this->fail(sprintf('Failed to list environments: %s', $environment_response->getExitMessage()));
    }
    $environments = $environment_response->getData();
    foreach ($environments as $environment) {
      $this->assertInstanceof('Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudEnvironmentInfo', $environment);
      $env_info = $this->cloud->getEnvironmentInfo($environment->getName())->getData();
      $this->assertEquals($environment->getName(), $env_info->getName());
      $this->assertEquals($environment->getDbClusters(), $env_info->getDbClusters());
      $this->assertEquals($environment->getDefaultDomain(), $env_info->getDefaultDomain());
      $this->assertEquals($environment->getSshHost(), $env_info->getSshHost());
      $this->assertEquals($environment->getVcsPath(), $env_info->getVcsPath());
      $this->assertEquals($environment->isLiveDev(), $env_info->isLiveDev());
      $json = $environment->jsonSerialize();
      $this->assertNotEmpty($json);
    }
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testDeleteDomainBadDomain() {
    $cloud = $this->getNoRetryCloud();
    $process = $cloud->deleteDomain('domain_does_not_exist');
    $this->assertTrue($process->hasCompleted($this->logger));
    $result = $process->getResult($this->logger);
    $this->assertFalse($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testPurgeVarnishBadDomain() {
    $cloud = $this->getNoRetryCloud();
    $process = $cloud->purgeVarnish('domain_does_not_exist');
    $this->assertTrue($process->hasCompleted($this->logger));
    $result = $process->getResult($this->logger);
    $this->assertFalse($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testAddIllegalDomain() {
    $cloud = $this->getNoRetryCloud();
    $process = $cloud->addDomain('This-domain%is@not:legal');
    $this->assertTrue($process->hasCompleted($this->logger));
    $result = $process->getResult($this->logger);
    $this->assertFalse($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testDeleteSshKeyBadKeyId() {
    $cloud = $this->getNoRetryCloud();
    $process = $cloud->deleteSshKey('Bad key name.');
    $this->assertTrue($process->hasCompleted($this->logger));
    $result = $process->getResult($this->logger);
    $this->assertFalse($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   *
   * @expectedException \RuntimeException
   */
  public function testLogStreamNotImplemented() {
    $this->cloud->getEnvironmentLogStream();
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   *
   * @expectedException \RuntimeException
   */
  public function testGetDrushAliases() {
    $this->cloud->getDrushAliases();
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testInstallEnvironmentBadType() {
    $cloud = $this->getNoRetryCloud();
    $process = $cloud->installEnvironment('wrong type', 'bad source url');
    $this->assertTrue($process->hasCompleted($this->logger));
    $result = $process->getResult($this->logger);
    $this->assertFalse($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testConfigureLiveDevelopmentBadAction() {
    $cloud = $this->getNoRetryCloud();
    $process = $cloud->configureLiveDevelopment('invalid action', TRUE);
    $this->assertTrue($process->hasCompleted($this->logger));
    $result = $process->getResult($this->logger);
    $this->assertFalse($result->isSuccess());
  }

  /**
   * Missing summary.
   */
  private function getNoRetryCloud() {
    $configuration = '$acquia.wip.acquiacloud.retrycount => 0';
    WipFactory::addConfiguration($configuration);
    return new AcquiaCloud(AcquiaCloudTestSetup::getEnvironment(), $this->logger, $this->wipId);
  }

}
