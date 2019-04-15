<?php

namespace Acquia\Wip\AcquiaCloud;

use Acquia\Cloud\Api\CloudApiClient;
use Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudBackupInfo;
use Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudDatabaseInfo;
use Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudEnvironmentInfo;
use Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudServerInfo;
use Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudSiteInfo;
use Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudSshKeyInfo;
use Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudTaskInfo;
use Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudVcsUserInfo;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudBackupArrayResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudBackupResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudDatabaseArrayResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudDatabaseResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudEnvironmentArrayResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudEnvironmentResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudServerArrayResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudServerResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudSiteResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudSshKeyArrayResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudSshKeyResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudStringArrayResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudStringResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudTaskArrayResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudTaskResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudVcsUserArrayResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudVcsUserResult;
use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\DependencyManager;
use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\Objects\SiteGroup;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogInterface;
use Guzzle\Plugin\Backoff\BackoffPlugin;

/**
 * The AcquiaCloud class is responsible for interacting with the CloudAPI.
 */
class AcquiaCloud implements AcquiaCloudInterface, DependencyManagedInterface {

  /**
   * The ID of the associated Wip task.
   *
   * @var int
   */
  private $wipId = NULL;

  /**
   * The Environment instance, which provides the sitegroup and server.
   *
   * @var EnvironmentInterface
   */
  private $environment;

  /**
   * The logger.
   *
   * @var WipLogInterface
   */
  private $logger;

  /**
   * The SDK client instance used to make cloud calls.
   *
   * @var CloudApiClient
   */
  private $cloudClient;

  /**
   * Creates a new instance of AcquiaCloud.
   *
   * @param EnvironmentInterface $environment
   *   The environment this instance will work on.
   * @param WipLogInterface $logger
   *   The WipLogInterface instance that this instance will log to.
   * @param int $wip_id
   *   The Wip task ID.
   */
  public function __construct(
    EnvironmentInterface $environment = NULL,
    WipLogInterface $logger = NULL,
    $wip_id = 0
  ) {
    $this->dependencyManager = new DependencyManager();
    $dependencies = $this->getDependencies();
    if (!empty($dependencies)) {
      $this->dependencyManager->addDependencies($dependencies);
    }
    $this->initialize($environment, $logger, $wip_id);
  }

  /**
   * {@inheritdoc}
   */
  public function initialize(
    EnvironmentInterface $environment = NULL,
    WipLogInterface $logger = NULL,
    $wip_id = 0
  ) {
    $this->wipId = $wip_id;
    if (!empty($environment)) {
      $this->setEnvironment($environment);
    }
    if (!empty($logger)) {
      $this->logger = $logger;
    }
    if (!is_int($wip_id)) {
      throw new \InvalidArgumentException('The wip_id argument must be an integer.');
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.wiplog' => 'Acquia\Wip\WipLogInterface',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDrushAliases() {
    throw new \RuntimeException('The getDrushAliases functionality is not yet implemented.');
  }

  /**
   * {@inheritdoc}
   */
  public function getTasks() {
    $result = new AcquiaCloudTaskArrayResult(TRUE);
    $result->setWipId($this->wipId);
    $result->setEnvironment($this->getEnvironment());
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $task_data = $client->tasks($sitegroup_name)->getIterator();
      $tasks = array();
      foreach ($task_data as $task) {
        $tasks[] = new AcquiaCloudTaskInfo($task);
      }
      $result->setData($tasks);
      $result->setExitCode(AcquiaCloudResult::EXIT_CODE_SUCCESS);
    } catch (\Exception $e) {
      $result->setError($e);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getTaskInfo($task_id, $result_class_name = NULL) {
    $result = new AcquiaCloudTaskResult(TRUE);
    $result->setEnvironment($this->getEnvironment());
    $result->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $task_info = $client->task($sitegroup_name, $task_id);
      $result->setData(
        AcquiaCloudTaskInfo::instantiate(
          $task_info,
          $result_class_name
        )
      );
      $result->setExitCode(AcquiaCloudResult::EXIT_CODE_SUCCESS);
    } catch (\Exception $e) {
      $result->setError($e);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteDomain($domain) {
    $process = new AcquiaCloudProcess();
    $process->setEnvironment($this->getEnvironment());
    $process->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $task_id = $client->deleteDomain(
        $sitegroup_name,
        $this->environment->getEnvironmentName(),
        $domain
      )->id();
      $process->setPid(intval($task_id));
      $process->setStartTime(time());
    } catch (\Exception $e) {
      $process->setPid(0);
      $process->setError($e, $this->getWipLog());
    }
    return $process;
  }

  /**
   * {@inheritdoc}
   */
  public function purgeVarnish($domain) {
    $process = new AcquiaCloudProcess();
    $process->setEnvironment($this->getEnvironment());
    $process->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $task_id = $client->purgeVarnishCache(
        $sitegroup_name,
        $this->environment->getEnvironmentName(),
        $domain
      )->id();
      $process->setPid(intval($task_id));
      $process->setStartTime(time());
    } catch (\Exception $e) {
      $process->setPid(0);
      $process->setError($e, $this->getWipLog());
    }
    return $process;
  }

  /**
   * {@inheritdoc}
   */
  public function listDomains() {
    $result = new AcquiaCloudStringArrayResult(TRUE);
    $result->setEnvironment($this->getEnvironment());
    $result->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $domain_data = $client->domains(
        $sitegroup_name,
        $this->environment->getEnvironmentName()
      )->getIterator();
      $domains = array();
      foreach ($domain_data as $domain_name => $domain_info) {
        $domains[] = $domain_name;
      }
      $result->setData($domains);
      $result->setExitCode(AcquiaCloudResult::EXIT_CODE_SUCCESS);
    } catch (\Exception $e) {
      $result->setError($e);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getDomainInfo($domain) {
    $result = new AcquiaCloudStringResult(TRUE);
    $result->setEnvironment($this->getEnvironment());
    $result->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $domain_data = $client->domain(
        $sitegroup_name,
        $this->environment->getEnvironmentName(),
        $domain
      );
      $result->setData($domain_data->name());
      $result->setExitCode(AcquiaCloudResult::EXIT_CODE_SUCCESS);
    } catch (\Exception $e) {
      $result->setError($e);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function addDomain($domain) {
    $process = new AcquiaCloudProcess();
    $process->setEnvironment($this->getEnvironment());
    $process->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $task_id = $client->addDomain(
        $sitegroup_name,
        $this->environment->getEnvironmentName(),
        $domain
      )->id();
      $process->setPid(intval($task_id));
      $process->setStartTime(time());
    } catch (\Exception $e) {
      $process->setPid(0);
      $process->setError($e, $this->getWipLog());
    }
    return $process;
  }

  /**
   * {@inheritdoc}
   */
  public function listServers() {
    $result = new AcquiaCloudServerArrayResult(TRUE);
    $result->setEnvironment($this->getEnvironment());
    $result->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $servers = array();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $server_data = $client->servers(
        $sitegroup_name,
        $this->environment->getEnvironmentName()
      )->getIterator();
      foreach ($server_data as $server) {
        $servers[] = new AcquiaCloudServerInfo($server);
      }
      $result->setData($servers);
      $result->setExitCode(AcquiaCloudResult::EXIT_CODE_SUCCESS);
    } catch (\Exception $e) {
      $result->setError($e);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getServerInfo($server) {
    $result = new AcquiaCloudServerResult(TRUE);
    $result->setEnvironment($this->getEnvironment());
    $result->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $server_data = $client->server(
        $sitegroup_name,
        $this->environment->getEnvironmentName(),
        $server
      );
      $result->setData(new AcquiaCloudServerInfo($server_data));
      $result->setExitCode(AcquiaCloudResult::EXIT_CODE_SUCCESS);
    } catch (\Exception $e) {
      $result->setError($e);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteSshKey($ssh_key_id) {
    $process = new AcquiaCloudProcess();
    $process->setEnvironment($this->getEnvironment());
    $process->setWipId($this->wipId);
    if (!isset($ssh_key_id) || !is_int($ssh_key_id)) {
      $process->setPid(0);
      $e = new \InvalidArgumentException('The SSH key id must be an integer.');
      $process->setError($e, $this->getWipLog());
    } else {
      try {
        $client = $this->getCloudClient();
        $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
        $this->validateSitegroup($sitegroup_name);
        $task_id = $client->deleteSshKey($sitegroup_name, $ssh_key_id)
          ->id();
        $process->setPid(intval($task_id));
        $process->setStartTime(time());
      } catch (\Exception $e) {
        $process->setPid(0);
        $process->setError($e, $this->getWipLog());
      }
    }
    return $process;
  }

  /**
   * {@inheritdoc}
   */
  public function listSshKeys() {
    $result = new AcquiaCloudSshKeyArrayResult(TRUE);
    $result->setEnvironment($this->getEnvironment());
    $result->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $key_data = $client->sshKeys($sitegroup_name)
        ->getIterator();
      $keys = array();
      foreach ($key_data as $key) {
        $keys[] = new AcquiaCloudSshKeyInfo($key);
      }
      $result->setData($keys);
      $result->setExitCode(AcquiaCloudResult::EXIT_CODE_SUCCESS);
    } catch (\Exception $e) {
      $result->setError($e);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getSshKey($id) {
    $result = new AcquiaCloudSshKeyResult(TRUE);
    $result->setEnvironment($this->getEnvironment());
    $result->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $key_data = $client->sshKey($sitegroup_name, $id);
      $key = new AcquiaCloudSshKeyInfo($key_data);
      $result->setData($key);
      $result->setExitCode(AcquiaCloudResult::EXIT_CODE_SUCCESS);
    } catch (\Exception $e) {
      $result->setError($e);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function addSshKey($name, $public_key, $shell_access = TRUE, $vcs_access = TRUE, $blacklist = array()) {
    $process = new AcquiaCloudProcess();
    $process->setTaskInfoClass('Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudAddSshKeyTaskInfo');
    $process->setEnvironment($this->getEnvironment());
    $process->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $task_id = $client->addSshKey(
        $sitegroup_name,
        $public_key,
        $name,
        $shell_access,
        $vcs_access,
        $blacklist
      )->id();
      $process->setPid(intval($task_id));
      $process->setStartTime(time());
    } catch (\Exception $e) {
      $process->setPid(0);
      $process->setError($e, $this->getWipLog());
    }
    return $process;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteDatabase($db_role, $backup = TRUE) {
    $process = new AcquiaCloudProcess();
    $process->setEnvironment($this->getEnvironment());
    $process->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $task_id = $client->deleteDatabase(
        $sitegroup_name,
        $db_role,
        $backup
      )->id();
      $process->setPid(intval($task_id));
      $process->setStartTime(time());
    } catch (\Exception $e) {
      $process->setPid(0);
      $process->setError($e, $this->getWipLog());
    }
    return $process;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteDatabaseBackup($db_role, $backup_id) {
    $process = new AcquiaCloudProcess();
    $process->setEnvironment($this->getEnvironment());
    $process->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $task_id = $client->deleteDatabaseBackup(
        $sitegroup_name,
        $this->environment->getEnvironmentName(),
        $db_role,
        $backup_id
      )->id();
      $process->setPid(intval($task_id));
      $process->setStartTime(time());
    } catch (\Exception $e) {
      $process->setPid(0);
      $process->setError($e, $this->getWipLog());
    }
    return $process;
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteDatabases() {
    $result = new AcquiaCloudStringArrayResult(TRUE);
    $result->setEnvironment($this->getEnvironment());
    $result->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $db_data = $client->databases($sitegroup_name)
        ->getIterator();
      $names = array();
      foreach ($db_data as $db) {
        $names[] = $db->name();
      }
      $result->setData($names);
      $result->setExitCode(AcquiaCloudResult::EXIT_CODE_SUCCESS);
    } catch (\Exception $e) {
      $result->setError($e);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getDatabaseEnvironmentInfo($db_role) {
    $result = new AcquiaCloudDatabaseResult(TRUE);
    $result->setEnvironment($this->getEnvironment());
    $result->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $db_data = $client->environmentDatabase(
        $sitegroup_name,
        $this->environment->getEnvironmentName(),
        $db_role
      );
      $result->setData(new AcquiaCloudDatabaseInfo($db_data));
      $result->setExitCode(AcquiaCloudResult::EXIT_CODE_SUCCESS);
    } catch (\Exception $e) {
      $result->setError($e);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function listDatabaseEnvironmentInfo() {
    $result = new AcquiaCloudDatabaseArrayResult(TRUE);
    $result->setEnvironment($this->getEnvironment());
    $result->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $db_data = $client->environmentDatabases(
        $sitegroup_name,
        $this->environment->getEnvironmentName()
      )->getIterator();
      $dbs = array();
      foreach ($db_data as $db) {
        $dbs[] = new AcquiaCloudDatabaseInfo($db);
      }
      $result->setData($dbs);
      $result->setExitCode(AcquiaCloudResult::EXIT_CODE_SUCCESS);
    } catch (\Exception $e) {
      $result->setError($e);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function listDatabaseBackups($db_role) {
    $result = new AcquiaCloudBackupArrayResult(TRUE);
    $result->setEnvironment($this->getEnvironment());
    $result->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $backup_data = $client->databaseBackups(
        $sitegroup_name,
        $this->environment->getEnvironmentName(),
        $db_role
      )->getIterator();
      $backups = array();
      foreach ($backup_data as $backup) {
        $backups[] = new AcquiaCloudBackupInfo($backup);
      }
      $result->setData($backups);
      $result->setExitCode(AcquiaCloudResult::EXIT_CODE_SUCCESS);
    } catch (\Exception $e) {
      $result->setError($e);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getDatabaseBackupInfo($db_role, $backup) {
    $result = new AcquiaCloudBackupResult(TRUE);
    $result->setEnvironment($this->getEnvironment());
    $result->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $backup_data = $client->databaseBackup(
        $sitegroup_name,
        $this->environment->getEnvironmentName(),
        $db_role,
        $backup
      );
      $result->setData(new AcquiaCloudBackupInfo($backup_data));
      $result->setExitCode(AcquiaCloudResult::EXIT_CODE_SUCCESS);
    } catch (\Exception $e) {
      $result->setError($e);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function addDatabase($db_role, $cluster_map = NULL) {
    $process = new AcquiaCloudProcess();
    $process->setEnvironment($this->getEnvironment());
    $process->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $task_id = $client->addDatabase(
        $sitegroup_name,
        $db_role,
        $cluster_map
      )->id();
      $process->setPid(intval($task_id));
      $process->setStartTime(time());
    } catch (\Exception $e) {
      $process->setPid(0);
      $process->setError($e, $this->getWipLog());
    }
    return $process;
  }

  /**
   * {@inheritdoc}
   */
  public function backupDatabase($db_role) {
    $process = new AcquiaCloudProcess();
    $process->setEnvironment($this->getEnvironment());
    $process->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $task_id = $client->createDatabaseBackup(
        $sitegroup_name,
        $this->environment->getEnvironmentName(),
        $db_role
      )->id();
      $process->setPid(intval($task_id));
      $process->setStartTime(time());
    } catch (\Exception $e) {
      $process->setPid(0);
      $process->setError($e, $this->getWipLog());
    }
    return $process;
  }

  /**
   * {@inheritdoc}
   */
  public function restoreDatabase($db_role, $backup) {
    $process = new AcquiaCloudProcess();
    $process->setEnvironment($this->getEnvironment());
    $process->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $task_id = $client->restoreDatabaseBackup(
        $sitegroup_name,
        $this->environment->getEnvironmentName(),
        $db_role,
        $backup
      )->id();
      $process->setPid(intval($task_id));
      $process->setStartTime(time());
    } catch (\Exception $e) {
      $process->setPid(0);
      $process->setError($e, $this->getWipLog());
    }
    return $process;
  }

  /**
   * {@inheritdoc}
   */
  public function deployCode($source_env, $target_env) {
    $process = new AcquiaCloudProcess();
    $process->setEnvironment($this->getEnvironment());
    $process->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $task_id = $client->deployCode(
        $sitegroup_name,
        $source_env,
        $target_env
      )->id();
      $process->setPid(intval($task_id));
      $process->setStartTime(time());
    } catch (\Exception $e) {
      $process->setPid(0);
      $process->setError($e, $this->getWipLog());
    }
    return $process;
  }

  /**
   * {@inheritdoc}
   */
  public function copyDatabase($db_role, $source_env, $target_env) {
    $process = new AcquiaCloudProcess();
    $process->setEnvironment($this->getEnvironment());
    $process->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $task_id = $client->copyDatabase(
        $sitegroup_name,
        $db_role,
        $source_env,
        $target_env
      )->id();
      $process->setPid(intval($task_id));
      $process->setStartTime(time());
    } catch (\Exception $e) {
      $process->setPid(0);
      $process->setError($e, $this->getWipLog());
    }
    return $process;
  }

  /**
   * {@inheritdoc}
   */
  public function moveDomains(
    $domains,
    $target_env,
    $skip_site_update = FALSE
  ) {
    $process = new AcquiaCloudProcess();
    $process->setEnvironment($this->getEnvironment());
    $process->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $task_id = $client->moveDomain(
        $sitegroup_name,
        $domains,
        $this->environment->getEnvironmentName(),
        $target_env,
        $skip_site_update
      )->id();
      $process->setPid(intval($task_id));
      $process->setStartTime(time());
    } catch (\Exception $e) {
      $process->setPid(0);
      $process->setError($e, $this->getWipLog());
    }
    return $process;
  }

  /**
   * {@inheritdoc}
   */
  public function moveAllDomains($target_env, $skip_site_update = FALSE) {
    return $this->moveDomains('*', $target_env, $skip_site_update);
  }

  /**
   * {@inheritdoc}
   */
  public function deployCodePath($vcs_path) {
    $process = new AcquiaCloudProcess();
    $process->setEnvironment($this->getEnvironment());
    $process->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $task_id = $client->pushCode(
        $sitegroup_name,
        $this->environment->getEnvironmentName(),
        $vcs_path
      )->id();
      $process->setPid(intval($task_id));
      $process->setStartTime(time());
    } catch (\Exception $e) {
      $process->setPid(0);
      $process->setError($e, $this->getWipLog());
    }
    return $process;
  }

  /**
   * {@inheritdoc}
   */
  public function copyFiles($source_env, $target_env) {
    $process = new AcquiaCloudProcess();
    $process->setEnvironment($this->getEnvironment());
    $process->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $task_id = $client->copyFiles(
        $sitegroup_name,
        $source_env,
        $target_env
      )->id();
      $process->setPid(intval($task_id));
      $process->setStartTime(time());
    } catch (\Exception $e) {
      $process->setPid(0);
      $process->setError($e, $this->getWipLog());
    }
    return $process;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteVcsUser($user_id) {
    $process = new AcquiaCloudProcess();
    $process->setEnvironment($this->getEnvironment());
    $process->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $task_id = $client->deleteSvnUser(
        $sitegroup_name,
        $user_id
      )->id();
      $process->setPid(intval($task_id));
      $process->setStartTime(time());
    } catch (\Exception $e) {
      $process->setPid(0);
      $process->setError($e, $this->getWipLog());
    }
    return $process;
  }

  /**
   * {@inheritdoc}
   */
  public function listVcsUsers() {
    $result = new AcquiaCloudVcsUserArrayResult(TRUE);
    $result->setEnvironment($this->getEnvironment());
    $result->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $user_data = $client->svnUsers($sitegroup_name)
        ->getIterator();
      $users = array();
      foreach ($user_data as $user) {
        $users[] = new AcquiaCloudVcsUserInfo($user);
      }
      $result->setData($users);
      $result->setExitCode(AcquiaCloudResult::EXIT_CODE_SUCCESS);
    } catch (\Exception $e) {
      $result->setError($e);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getVcsUser($svn_user_id) {
    $result = new AcquiaCloudVcsUserResult(TRUE);
    $result->setEnvironment($this->getEnvironment());
    $result->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $user_data = $client->svnUser($sitegroup_name, $svn_user_id);
      $result->setData(new AcquiaCloudVcsUserInfo($user_data));
      $result->setExitCode(AcquiaCloudResult::EXIT_CODE_SUCCESS);
    } catch (\Exception $e) {
      $result->setError($e);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function addVcsUser($username, $password) {
    $process = new AcquiaCloudProcess();
    $process->setEnvironment($this->getEnvironment());
    $process->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $task_id = $client->addSvnUser(
        $sitegroup_name,
        $username,
        $password
      )->id();
      $process->setPid(intval($task_id));
      $process->setStartTime(time());
    } catch (\Exception $e) {
      $process->setPid(0);
      $process->setError($e, $this->getWipLog());
    }
    return $process;
  }

  /**
   * {@inheritdoc}
   */
  public function listSites() {
    $result = new AcquiaCloudStringArrayResult(TRUE);
    $result->setEnvironment($this->getEnvironment());
    $result->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $site_data = $client->sites()->getIterator();
      $sites = array();
      foreach ($site_data as $site) {
        $sites[] = $site->name();
      }
      $result->setData($sites);
      $result->setExitCode(AcquiaCloudResult::EXIT_CODE_SUCCESS);
    } catch (\Exception $e) {
      $result->setError($e);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteRecord($site_group) {
    $result = new AcquiaCloudSiteResult(TRUE);
    $result->setEnvironment($this->getEnvironment());
    $result->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $site_data = $client->site($site_group);
      $result->setData(new AcquiaCloudSiteInfo($site_data));
      $result->setExitCode(AcquiaCloudResult::EXIT_CODE_SUCCESS);
    } catch (\Exception $e) {
      $result->setError($e);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function listEnvironments() {
    $result = new AcquiaCloudEnvironmentArrayResult(TRUE);
    $result->setEnvironment($this->getEnvironment());
    $result->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $env_data = $client->environments($sitegroup_name);
      $envs = array();
      foreach ($env_data as $env) {
        $envs[] = new AcquiaCloudEnvironmentInfo($env);
      }
      $result->setData($envs);
      $result->setExitCode(AcquiaCloudResult::EXIT_CODE_SUCCESS);
    } catch (\Exception $e) {
      $result->setError($e);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnvironmentInfo($env) {
    $result = new AcquiaCloudEnvironmentResult(TRUE);
    $result->setEnvironment($this->getEnvironment());
    $result->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $env_data = $client->environment($sitegroup_name, $env);
      $result->setData(new AcquiaCloudEnvironmentInfo($env_data));
      $result->setExitCode(AcquiaCloudResult::EXIT_CODE_SUCCESS);
    } catch (\Exception $e) {
      $result->setError($e);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnvironmentLogStream() {
    throw new \RuntimeException('The getEnvironmentLogStream functionality is not supported.');
  }

  /**
   * {@inheritdoc}
   */
  public function installEnvironment($type, $source) {
    $process = new AcquiaCloudProcess();
    $process->setEnvironment($this->getEnvironment());
    $process->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $task_id = $client->installDistro(
        $sitegroup_name,
        $this->environment->getEnvironmentName(),
        $type,
        $source
      )->id();
      $process->setPid(intval($task_id));
      $process->setStartTime(time());
    } catch (\Exception $e) {
      $process->setPid(0);
      $process->setError($e, $this->getWipLog());
    }
    return $process;
  }

  /**
   * {@inheritdoc}
   */
  public function configureLiveDevelopment($action, $discard = FALSE) {
    $process = new AcquiaCloudProcess();
    $process->setEnvironment($this->getEnvironment());
    $process->setWipId($this->wipId);
    try {
      $client = $this->getCloudClient();
      $sitegroup_name = $this->environment->getFullyQualifiedSitegroup();
      $this->validateSitegroup($sitegroup_name);
      $task_id = $client->liveDev(
        $sitegroup_name,
        $this->environment->getEnvironmentName(),
        $action,
        $discard
      )->id();
      $process->setPid(intval($task_id));
      $process->setStartTime(time());
    } catch (\Exception $e) {
      $process->setPid(0);
      $process->setError($e, $this->getWipLog());
    }
    return $process;
  }

  /**
   * Sets the Environment instance associated with this instance.
   *
   * The Environment instance can only be set once.  If there is a need to
   * call a Cloud API call with a different environment, a new instance of
   * AcquiaCloud must be created.
   *
   * @param EnvironmentInterface $environment
   *   The Environment instance.
   *
   * @throw \RuntimeException
   *   If the Cloud API credentials have already been set.
   */
  public function setEnvironment(EnvironmentInterface $environment) {
    if (!empty($this->environment)) {
      throw new \RuntimeException('AcquiaCloud environment can only be set once.');
    }
    $this->environment = $environment;
  }

  /**
   * Returns the Environment instance associated with this object.
   *
   * @return EnvironmentInterface
   *   The Environment instance.
   */
  public function getEnvironment() {
    return $this->environment;
  }

  /**
   * Returns a new CloudApiClient instance.
   *
   * @return CloudApiClient
   *   The CloudApiClient instance.
   */
  private function getCloudClient() {
    if (empty($this->cloudClient)) {
      $credentials = $this->getEnvironment()->getCloudCredentials();
      $initialize = array(
        'base_url' => $credentials->getEndpoint(),
        'username' => $credentials->getUsername(),
        'password' => $credentials->getPassword(),
      );
      $client = CloudApiClient::factory($initialize);
      $retry_count = $this->getRetryCount();
      $retry_codes = $this->getRetryErrorCodes();
      $back_off = BackoffPlugin::getExponentialBackoff(
        $retry_count,
        $retry_codes
      );
      $client->addSubscriber($back_off);
      try {
        $verify = WipFactory::getBool(
          '$acquia.wip.ssl.verifyCertificate',
          TRUE
        );
        if (!$verify) {
          $client->setSslVerification(FALSE, FALSE);
        }
      } catch (\Exception $e) {
        // No special SSL verification parameters exist.
      }
      $this->cloudClient = $client;
    }
    return $this->cloudClient;
  }

  /**
   * Gets the number of retries for each failed Cloud API call.
   *
   * This value can be configured in the factory.cfg file using the
   * $acquia.wip.acquiacloud.retrycount property.
   *
   * @return int
   *   The number of retries.
   */
  private function getRetryCount() {
    return WipFactory::getInt('$acquia.wip.acquiacloud.retrycount', 3);
  }

  /**
   * Returns the HTTP error codes that will result in a retry.
   *
   * Note: When the Cloud API is unhappy you can get HTTP error codes that
   * would normally not be retried.
   *
   * This set of error codes can be overridden in the factory.cfg file using
   * the
   * $acquia.wip.acquiacloud.retrycodes property.
   *
   * @return int[]
   *   The HTTP error codes.
   */
  public static function getRetryErrorCodes() {
    return WipFactory::getIntArray(
      '$acquia.wip.acquiacloud.retrycodes',
      array(403, 404, 500, 502, 503, 504)
    );
  }

  /**
   * The sitegroup must be fully qualified for correct use in production.
   *
   * @param string $sitegroup_name
   *   The hosting sitegroup name.
   *
   * @throws \InvalidArgumentException
   *   If the sitegroup name is not fully qualified.
   */
  private function validateSitegroup($sitegroup_name) {
    if (!self::sitegroupIsFullyQualified($sitegroup_name)) {
      throw new \InvalidArgumentException(
        sprintf(
          'The sitegroup name %s is not fully qualified.',
          $sitegroup_name
        )
      );
    }
  }

  /**
   * Indicates whether the specified sitegroup includes the realm.
   *
   * @param string $sitegroup_name
   *   The hosting sitegroup name.
   *
   * @return bool
   *   TRUE if the specified sitegroup name includes the realm; FALSE
   *     otherwise.
   */
  public static function sitegroupIsFullyQualified($sitegroup_name) {
    $result = FALSE;
    $sitegroup_obj = SiteGroup::separateSitegroupName($sitegroup_name);
    if (!empty($sitegroup_obj->realm)) {
      $result = TRUE;
    }
    return $result;
  }

  /**
   * Gets the Wip logger.
   *
   * @return WipLogInterface
   *   The logger.
   */
  private function getWipLog() {
    $result = $this->logger;
    if (empty($result)) {
      $result = $this->dependencyManager->getDependency('acquia.wip.wiplog');
    }
    return $result;
  }

}
