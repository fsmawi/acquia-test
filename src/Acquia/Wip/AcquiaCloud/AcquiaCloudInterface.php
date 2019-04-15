<?php

namespace Acquia\Wip\AcquiaCloud;

use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudBackupArrayResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudBackupResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudDatabaseArrayResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudDatabaseResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudEnvironmentArrayResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudEnvironmentResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudServerArrayResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudServerResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudSiteResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudSshKeyResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudStringArrayResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudStringResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudTaskArrayResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudTaskResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudVcsUserArrayResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudVcsUserResult;
use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\WipLogInterface;

/**
 * The AcquiaCloudInterface describes interactions with the Cloud API.
 */
interface AcquiaCloudInterface {

  /**
   * Initializes this instance of AcquiaCloudInterface.
   *
   * @param EnvironmentInterface $environment
   *   The environment.
   * @param WipLogInterface $logger
   *   The logger.
   * @param int $wip_id
   *   The Wip task ID.
   *
   * @return AcquiaCloudInterface
   *   The initialized instance.
   */
  public function initialize(
    EnvironmentInterface $environment,
    WipLogInterface $logger,
    $wip_id
  );

  /**
   * Retrieves Drush aliases for all sites accessible by the caller.
   *
   * TODO: This is not yet implemented in the SDK.
   *
   * @return AcquiaCloudResult
   *   The result, containing the Drush aliases.
   */
  public function getDrushAliases();

  /**
   * Gets the list of tasks for the associated site.
   *
   * @return AcquiaCloudTaskArrayResult
   *   The array of task information.
   */
  public function getTasks();

  /**
   * Gets the information for the specified hosting task.
   *
   * @param int $task_id
   *   The hosting task ID.
   *
   * @return AcquiaCloudTaskResult
   *   The result.
   */
  public function getTaskInfo($task_id);

  /**
   * Deletes the specified domain from the associated environment.
   *
   * Note this is an asynchronous call.
   *
   * @param string $domain
   *   The domain name to remove.
   *
   * @return AcquiaCloudProcessInterface
   *   The process.
   */
  public function deleteDomain($domain);

  /**
   * Purges entries from the varnish cache for the specified domain.
   *
   * Note this is an asynchronous call.
   *
   * @param string $domain
   *   The domain for which varnish entries should be cleared.
   *
   * @return AcquiaCloudProcessInterface
   *   The process.
   */
  public function purgeVarnish($domain);

  /**
   * Returns the list of domains associated with the environment.
   *
   * @return AcquiaCloudStringArrayResult
   *   The domain names.
   */
  public function listDomains();

  /**
   * Gets information about the specified domain in the associated environment.
   *
   * @param string $domain
   *   The domain.
   *
   * @return AcquiaCloudStringResult
   *   The result.
   */
  public function getDomainInfo($domain);

  /**
   * Adds the specified domain to the associated environment.
   *
   * Note this is an asynchronous call.
   *
   * @param string $domain
   *   The domain name to add.
   *
   * @return AcquiaCloudProcessInterface
   *   The process.
   */
  public function addDomain($domain);

  /**
   * List the servers associated with a particular environment.
   *
   * @return AcquiaCloudServerArrayResult
   *   The server data.
   */
  public function listServers();

  /**
   * Gets information about the specified server.
   *
   * @param string $server
   *   The short name for the server (eg: 'web-3').
   *
   * @return AcquiaCloudServerResult
   *   The server result.
   */
  public function getServerInfo($server);

  /**
   * Deletes the specified ssh key.
   *
   * Note this is an asynchronous call.
   *
   * @param string $ssh_key_id
   *   The ssh key to delete.
   *
   * @return AcquiaCloudProcessInterface
   *   The process.
   */
  public function deleteSshKey($ssh_key_id);

  /**
   * Lists all ssh keys associated with the site.
   *
   * @return AcquiaCloudStringArrayResult
   *   The ssh keys.
   */
  public function listSshKeys();

  /**
   * Gets the ssh key associated with the specified ID.
   *
   * @param int $id
   *   The key ID.
   *
   * @return AcquiaCloudSshKeyResult
   *   The key result.
   */
  public function getSshKey($id);

  /**
   * Adds the specified ssh key.
   *
   * Note this is an asynchronous call.
   *
   * @param string $name
   *   The name associated with the key.
   * @param string $public_key
   *   The public key.
   * @param bool $shell_access
   *   Set to TRUE if the new key will have ssh access.
   * @param bool $vcs_access
   *   Sot to TRUE if the new key will have access to the VCS repository.
   * @param string[] $blacklist
   *   A list of environments that the key will not have access to.
   *
   * @return AcquiaCloudProcess
   *   The process object that represents the running hosting task.
   */
  public function addSshKey(
    $name,
    $public_key,
    $shell_access = TRUE,
    $vcs_access = TRUE,
    $blacklist = array()
  );

  /**
   * Deletes the specified database.
   *
   * Note this is an asynchronous call.
   *
   * @param string $db_role
   *   The database role name.
   * @param bool $backup
   *   TRUE if a backup should be created before the database is deleted.
   *
   * @return AcquiaCloudProcess
   *   The process representing the database deletion task.
   */
  public function deleteDatabase($db_role, $backup = TRUE);

  /**
   * Deletes the specified database backup.
   *
   * Note this is an asynchronous call.
   *
   * @param string $db_role
   *   The database role name.
   * @param int $backup_id
   *   The database backup ID.
   *
   * @return AcquiaCloudProcess
   *   The process representing the hosting task.
   */
  public function deleteDatabaseBackup($db_role, $backup_id);

  /**
   * Lists all database role names associated with the site.
   *
   * @return AcquiaCloudStringArrayResult
   *   The result containing the list of database role names.
   */
  public function getSiteDatabases();

  /**
   * Gets environment database information about the specified database.
   *
   * @param string $db_role
   *   The database role name.
   *
   * @return AcquiaCloudDatabaseResult
   *   The result containing the database information, or an error if the
   *   database role does not exist.
   */
  public function getDatabaseEnvironmentInfo($db_role);

  /**
   * Lists information for all databases in the associated environment.
   *
   * @return AcquiaCloudDatabaseArrayResult
   *   The result containing the database information list.
   */
  public function listDatabaseEnvironmentInfo();

  /**
   * Gets a list of database backups for the specified database role name.
   *
   * @param string $db_role
   *   The database role name.
   *
   * @return AcquiaCloudBackupArrayResult
   *   The result containing the list of backups.
   */
  public function listDatabaseBackups($db_role);

  /**
   * Gets information about a particular database backup.
   *
   * @param string $db_role
   *   The database role name.
   * @param int $backup
   *   The backup ID.
   *
   * @return AcquiaCloudBackupResult
   *   The result containing information about the specified backup.
   */
  public function getDatabaseBackupInfo($db_role, $backup);

  /**
   * Creates a new database for the associated site.
   *
   * Note this is an asynchronous call.
   *
   * @param string $db_role
   *   The database role name.
   * @param array $cluster_map
   *   Optional. A mapping containing all environments and the cluster to which
   *   the associated database should be created. Each entry consists of the
   *   environment name as the key and the database cluster ID as the value.
   *   Note that if more than one cluster is associated with a site group,
   *   this map is required.
   *
   * @return AcquiaCloudProcess
   *   The process instance representing the running hosting task.
   */
  public function addDatabase($db_role, $cluster_map = NULL);

  /**
   * Backs up the database.
   *
   * The database will be backed up for the associated site and environment.
   *
   * Note this is an asynchronous call.
   *
   * @param string $db_role
   *   The database role name associated with the database to back up.
   *
   * @return AcquiaCloudProcess
   *   The process instance representing the running hosting task.
   */
  public function backupDatabase($db_role);

  /**
   * Restores the specified backup.
   *
   * Note this is an asynchronous call.
   *
   * @param string $db_role
   *   The database role name.
   * @param int $backup
   *   The backup ID.
   *
   * @return AcquiaCloudProcess
   *   The process instance representing the running hosting task.
   */
  public function restoreDatabase($db_role, $backup);

  /**
   * Deploys code from the source environment to the target environment.
   *
   * Note this is an asynchronous call.
   *
   * @param string $source_env
   *   The source environment name.
   * @param string $target_env
   *   The target environment name.
   *
   * @return AcquiaCloudProcess
   *   The process instance representing the running hosting task.
   */
  public function deployCode($source_env, $target_env);

  /**
   * Copies a database from one environment to another.
   *
   * Note this is an asynchronous call.
   *
   * @param string $db_role
   *   The database role name.
   * @param string $source_env
   *   The source environment name.
   * @param string $target_env
   *   The target environment name.
   *
   * @return AcquiaCloudProcess
   *   The process instance representing the running hosting task.
   */
  public function copyDatabase($db_role, $source_env, $target_env);

  /**
   * Moves domains atomically from the current environment to the target.
   *
   * Note this is an asynchronous call.
   *
   * @param string|array $domains
   *   The domain name(s) as an array of strings, or the string '*' to move all
   *   domains.
   * @param string $target_env
   *   The destination environment for the domain.
   * @param bool $skip_site_update
   *   Optional. If set to TRUE this will inhibit running
   *   fields-config-web.php for this domain move.
   *
   * @return AcquiaCloudProcess
   *   The process instance representing the running hosting task.
   */
  public function moveDomains($domains, $target_env, $skip_site_update = FALSE);

  /**
   * Moves all domains on a site atomically from the current environment.
   *
   * This is just a special case of moveDomain, using the wildcard as domain.
   *
   * Note this is an asynchronous call.
   *
   * @param string $target_env
   *   The hosting environment to move domains to.
   * @param bool $skip_site_update
   *   Optional. If set to TRUE this will inhibit running
   *   fields-config-web.php for this domain move.
   *
   * @return AcquiaCloudProcess
   *   The process instance representing the running hosting task.
   */
  public function moveAllDomains($target_env, $skip_site_update = FALSE);

  /**
   * Deploy a specific VCS branch or tag to the associated environment.
   *
   * Note this is an asynchronous call.
   *
   * @param string $vcs_path
   *   The name of the branch or tag (e.g. master or tags/tag_name).
   *
   * @return AcquiaCloudProcess
   *   The process instance representing the running hosting task.
   */
  public function deployCodePath($vcs_path);

  /**
   * Copy files from one site environment to another.
   *
   * Note this is an asynchronous call.
   *
   * @param string $source_env
   *   The source environment name.
   * @param string $target_env
   *   The target environment name.
   *
   * @return AcquiaCloudProcess
   *   The process instance representing the running hosting task.
   */
  public function copyFiles($source_env, $target_env);

  /**
   * Delete a VCS user.
   *
   * Note this is an asynchronous call.
   *
   * @param string $user_id
   *   The VCS user ID.
   *
   * @return AcquiaCloudProcess
   *   The process instance representing the running hosting task.
   */
  public function deleteVcsUser($user_id);

  /**
   * List a site's VCS users.
   *
   * @return AcquiaCloudVcsUserArrayResult
   *   The result that holds the set of VCS users.
   */
  public function listVcsUsers();

  /**
   * Get VCS user information.
   *
   * @param int $user_id
   *   The VCS user ID.
   *
   * @return AcquiaCloudVcsUserResult
   *   The result which contains the VCS user information.
   */
  public function getVcsUser($user_id);

  /**
   * Add a VCS user.
   *
   * Note this is an asynchronous call.
   *
   * @param string $username
   *   The VCS user name.
   * @param string $password
   *   The user's password.
   *
   * @return AcquiaCloudProcess
   *   The process instance representing a running hosting task.
   */
  public function addVcsUser($username, $password);

  /**
   * Lists all site groups accessible with the provided Cloud credentials.
   *
   * @return AcquiaCloudStringArrayResult
   *   The result which contains an array of site names.
   */
  public function listSites();

  /**
   * Gets a sitegroup record.
   *
   * @param string $site_group
   *   The site group name, retrieved via the listSites method.
   *
   * @return AcquiaCloudSiteResult
   *   The result which includes the site record.
   */
  public function getSiteRecord($site_group);

  /**
   * List all environments for the associated site group.
   *
   * @return AcquiaCloudEnvironmentArrayResult
   *   The result which includes records for each environment.
   */
  public function listEnvironments();

  /**
   * Gets an environment record.
   *
   * @param string $env
   *   The environment name (ex: prod, dev).
   *
   * @return AcquiaCloudEnvironmentResult
   *   The result which contains the environment record.
   */
  public function getEnvironmentInfo($env);

  /**
   * Retrieve an authenticated command to stream log files.
   *
   * @todo: The SDK does not support this method yet.
   *
   * Note this is an asynchronous call.
   *
   * @return AcquiaCloudResult
   *   The result which includes the log stream.
   */
  public function getEnvironmentLogStream();

  /**
   * Installs a Drupal distribution into the associated environment.
   *
   * Note this is an asynchronous call.
   *
   * @param string $type
   *   The source type, either 'distro_url' or 'make_url'.
   * @param string $source
   *   The source URL where the Drush make file or Drupal distribution can be
   *   retrieved.
   *
   * @return AcquiaCloudProcess
   *   The process instance representing the running hosting task.
   */
  public function installEnvironment($type, $source);

  /**
   * Configures live development on the associated environment.
   *
   * Note this is an asynchronous call.
   *
   * @param string $action
   *   Either 'enable' or 'disable', depending on whether live development is
   *   being enabled or disabled.
   * @param bool $discard
   *   If TRUE, any uncommitted changes will be discarded.  This only occurs
   *   when the action is set to 'disable'.
   *
   * @return AcquiaCloudProcess
   *   The process instance representing the running hosting task.
   */
  public function configureLiveDevelopment($action, $discard = FALSE);

  /**
   * Returns the Environment instance associated with this object.
   *
   * @return EnvironmentInterface
   *   The Environment instance.
   */
  public function getEnvironment();

  /**
   * Sets the Environment instance associated with this instance.
   *
   * The Environment instance can only be set once.  If there is a need to call
   * a Cloud API call with a different environment, a new instance of
   * AcquiaCloud must be created.
   *
   * @param EnvironmentInterface $environment
   *   The Environment instance.
   *
   * @throw \RuntimeException
   *   If the Cloud API credentials have already been set.
   */
  public function setEnvironment(EnvironmentInterface $environment);

}
