<?php
 
namespace Acquia\Wip\Objects\Cron;

use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\IndependentEnvironment;
use Acquia\Wip\Ssh\Ssh;
use Acquia\Wip\Ssh\StatResultInterpreter;
use Acquia\Wip\WipContextInterface;
use Acquia\Wip\WipLogLevel;

/**
 * The MultisiteCron class is responsible for running cron for a single docroot.
 *
 * This instance is reused many times.
 *
 * Note: This will be kicked off by a single CronController instance for each cron
 * configuration.  There will be an instance of MultisiteCron for each docroot
 * for that particular configuration.  After this instance is complete, it may
 * be restarted by the parent CronController.
 */
class MultisiteCron extends BasicWip {

  /**
   * The version associated with this Wip class.
   */
  const CLASS_VERSION = 1;

  const DRUSH_COMMAND = '\drush6';

  /**
   * The parameter document that describes the update.
   *
   * @var IndependentEnvironment
   */
  private $environment = NULL;

  /**
   * The work.
   *
   * @var null
   */
  private $work = NULL;

  /**
   * Specifies the state table that this FSM will execute.
   *
   * @var string
   */
  protected $stateTable = <<<EOT
  start:checkEnvironmentStatus {
    success        beginNewRun
    fail           failure
  }

  # TODO: Would be great to have some sort of dependency API so writing the
  # ssh_wrapper and getting keys could be indicated as a dependency, allowing
  # each Wip object to ignore that piece and have the system resolve those
  # dependencies via child processes before continuing in the state table.
  # For now we will have to resolve these dependencies explicitly in the state
  # table.

  # Each new run starts here.
  beginNewRun:checkCronConfig {
    success        writeCronWrapper
    fail           finish
  }

  # Make sure the cron wrapper script is available on all servers.
  writeCronWrapper {
    *              chunkWork
  }

  # Break the cron work across multiple processes on multiple webs.
  chunkWork {
    *              runCron
  }

  # Run all of the cron processes then wait for all cron processes to complete
  # They will return the number of successful runs and total runtime in stdout
  # and the sites that failed in stderr.
  # All retries will occur in the script, not the wip object.
  runCron:checkSshStatus {
    wait           runCron               wait=300 exec=false
    success        deleteConfigFiles
    fail           failure
    uninitialized  deleteConfigFiles
  }

  deleteConfigFiles {
    *              finish
  }

  failure {
    *              deleteConfigFiles
  }
EOT;

  /**
   * The cron config.
   *
   * @var CronConfig
   */
  private $cronConfig = NULL;

  /**
   * The set of servers that should be excluded.
   *
   * This can happen if the cron wrapper is out of date on a server and cannot
   * be updated due to permissions.
   *
   * @var string[]
   */
  private $excludeServers;

  /**
   * Sets the environment used for this instance.
   *
   * @param IndependentEnvironment $environment
   *   The environment must contain all of the cloud credentials, etc., so
   *   the IndependentEnvironment type is used.
   */
  public function setEnvironment(IndependentEnvironment $environment) {
    $this->environment = $environment;
  }

  /**
   * Gets the environment used for this instance.
   *
   * @return IndependentEnvironment
   *   The environment.
   */
  public function getEnvironment() {
    return $this->environment;
  }

  /**
   * Sets the cron configuration this instance will run.
   *
   * @param CronConfig $cron_config
   *   The cron configuration.
   */
  public function setCronConfig(CronConfig $cron_config) {
    $this->cronConfig = $cron_config;
  }

  /**
   * Gets the cron configuration associated with this instance.
   *
   * @return CronConfig
   *   The cron configuration.
   */
  public function getCronConfig() {
    return $this->cronConfig;
  }

  /**
   * Ensures the environment has been set.
   *
   * @return string
   *   'success' - The document has been parsed and verified.
   *   'fail'    - The document has not been properly parsed.
   */
  public function checkEnvironmentStatus() {
    $result = 'fail';
    if (!empty($this->environment)) {
      $this->environment->validate();
      $site_group = $this->environment->getSitegroup();
      if (!empty($site_group)) {
        $result = 'success';
      } else {
        $this->log(WipLogLevel::ERROR, sprintf('checkDocumentStatus detected no sitegroup.'));
      }
    }
    return $result;
  }

  /**
   * Initializes this object for a new cron run.
   *
   * TODO: In the future we may want to update the server list.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function beginNewRun(WipContextInterface $wip_context) {
  }

  /**
   * Verifies the cron configuration exists.
   *
   * @return string
   *   The result.
   *   'success' - the cron configuration is fine.
   *   'fail'    - the cron configuration could not be found.
   */
  public function checkCronConfig() {
    $result = 'fail';
    if (!empty($this->cronConfig)) {
      return 'success';
    }
    return $result;
  }

  /**
   * Breaks the cron work into roughly equal parts.
   *
   * These chunks will each be executed in a separate process.
   */
  public function chunkWork() {
    $this->work = array();
    // TODO: For now we will assume this is the only cron running for this tenant.
    // Going forward we will have to have a strategy for sharing information
    // among Wip objects for the same sitegroup (or customer?)
    /*
    $available_procs = sf_cron_acquire_procs($this->getId(), $this->cronConfig->getMaxProcs(), $this->serverNames);
    if (count($available_procs) <= 0) {
    // No capacity to do anything here.
    $this->log(
    WipLogLevel::FATAL,
    sprintf('Could not run cron job %d - no available procs.', $this->cronConfig->getId()
    );
    return;
    }
     */
    /*
     * TODO: For now assume all sites are on the live environment; we need Cloud
     * API integration to make this better.
     */
    /*
    // Get all sites associated with this cron run that are not on the update environment.
    // That way we can run cron during updates safely.
    $exclude = $this->getSitesOnUpdateEnvironment();
     */
    $exclude = array();
    $servers = $this->environment->getServers();
    $available_procs = $this->getAvailableProcs($servers);
    $sites = $this->getSiteList($exclude);

    $proc_count = count($available_procs);
    $site_count = count($sites);
    $sites_per_proc = (int) ceil($site_count / $proc_count);

    // Spread the work across the set of cron process ids.
    $work = array_chunk($sites, $sites_per_proc);
    for ($i = 0; $i < count($work) && $i < $proc_count; $i++) {
      $proc = $available_procs[$i];
      $this->work[$proc->server][$proc->id] = $work[$i];
    }
  }

  /**
   * Writes the cron wrapper script to servers if required.
   */
  public function writeCronWrapper() {
    $servers = $this->environment->getServers();

    $environment = clone($this->environment);
    /*
    $servers = array_keys($this->work);
    $environment->setServers($servers);
     */

    // Failed servers holds the servers for which the script doesn't exist and
    // cannot be written.
    $failed_servers = array();
    foreach ($servers as $server) {
      $environment->setCurrentServer($server);
      if (!$this->verifyCronScript($environment)) {
        // The wrapper script does not exist or is out of date and cannot be
        // refreshed.
        $failed_servers[] = $server;
      }
    }

    // Indicate which servers must be excluded.
    $this->excludeServers = $failed_servers;
  }

  /**
   * Invokes cron.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function runCron(WipContextInterface $wip_context) {
    $wip_context->totalSites = 0;
    $wip_context->totalTime = 0;
    $wip_context->failedSites = array();
    $wip_context->startTime = time();
    $wip_context->resultCount = 0;
    $ssh_api = $this->getSshApi();
    foreach ($this->work as $server_name => $server_work) {
      foreach ($server_work as $proc_id => $work) {
        try {
          $environment = $this->getEnvironmentForServer($server_name);
          $conf_file = $this->writeWorkFile($environment, $proc_id, $work);

          // Invoke the cron script.
          $cron = new Ssh();
          $cron->initialize($environment, sprintf('Run cron with %s', $conf_file), $this->getWipLog(), $this->getId());
          $command = $this->renderCronCommand($conf_file, $environment);
          $cron_process = $cron->execAsyncCommand($command, '', NULL);
          $ssh_api->addSshProcess($cron_process, $wip_context);
        } catch (\Exception $e) {
          // Could not run the script.
          $this->log(
            WipLogLevel::ERROR,
            sprintf(
              'Error running cron on %s: %s - %s',
              $server_name,
              $e->getMessage(),
              $e->getTraceAsString()
            )
          );
        }
      }
    }
  }

  /**
   * Deletes the configuration files when they are no longer needed.
   */
  public function deleteConfigFiles() {
    foreach ($this->work as $server_name => $server_work) {
      $config_filename = NULL;
      foreach ($server_work as $proc_id => $work) {
        try {
          $environment = $this->getEnvironmentForServer($server_name);
          $file = $this->getFileCommands($environment);
          $config_filename = $this->getCronConfigFilename($environment, $proc_id);
          $unlink_result = $file->unlink($config_filename)->exec();
          if (!$unlink_result->isSuccess()) {
            $this->log(
              WipLogLevel::ALERT,
              sprintf('Failed to delete configuration file %s:%s', $server_name, $config_filename)
            );
          }
        } catch (\Exception $e) {
          // Could not run the script.
          $this->log(WipLogLevel::ERROR, sprintf(
            'Error deleting cron configuration file %s:%s %s',
            $server_name,
            $config_filename,
            $e->getMessage()
          ));
        }
      }
    }
  }

  /**
   * Gets the list of site domain names to run cron against.
   *
   * @param string[] $exclude_sites
   *   The sites to exclude from the site list.
   *
   * @return array
   *   The domain names, one per site.
   */
  private function getSiteList($exclude_sites = array()) {
    $site_list = $this->environment->getPrimaryDomainNames();
    $site_list = array_diff($site_list, $exclude_sites);
    return $site_list;
  }

  /**
   * Gets an array of servers on which cron can be executed.
   *
   * Each element of the array represents a single process, so there may be
   * duplicate server entries.
   *
   * @param string[] $servers
   *   The available servers.
   *
   * @return object[]
   *   An array of objects, each representing a single process that can be used
   *   for calling cron on Drupal sites. Each object contains the cron process
   *   ID and the server that process will execute on.
   */
  private function getAvailableProcs($servers) {
    // TODO: For now we assume 2 procs per server with a single cron instance.
    // Needs to be more robust.
    $result = array();
    $usable_servers = array_diff($servers, $this->excludeServers);
    $server_processes = array_merge($usable_servers, $usable_servers);
    $id = 0;
    foreach ($server_processes as $server) {
      // TODO: Make a formal class for this.
      $proc = new \stdClass();
      $proc->id = ++$id;
      $proc->server = $server;
      $result[] = $proc;
    }
    return $result;
  }

  /**
   * Renders the command that will run cron on a bunch of sites.
   *
   * @param string $site_file
   *   The file that indicates which sites to run cron on.
   * @param EnvironmentInterface $environment
   *   The Environment.
   *
   * @return string
   *   The command.
   */
  private function renderCronCommand($site_file, EnvironmentInterface $environment) {
    $command = sprintf(
      "%s --exec=%s --site=%s --env=%s --drush=%s --site-file=%s --drush-cache=%s",
      escapeshellarg($this->getCronPath($environment)),
      escapeshellarg($this->cronConfig->getDrushCommand()),
      escapeshellarg($environment->getSitegroup()),
      escapeshellarg($environment->getEnvironmentName()),
      escapeshellarg(self::DRUSH_COMMAND),
      escapeshellarg($site_file),
      escapeshellarg($environment->getWorkingDir())
    );
    return $command;
  }

  /**
   * Creates the configuration file that indicates which sites to operate on.
   *
   * @param EnvironmentInterface $environment
   *   The environment.
   * @param int $proc_id
   *   The sf_cron id representing the process slot to be used.
   * @param string[] $sites
   *   The sites to write into the config file.
   *
   * @return string
   *   The path to the config file.
   *
   * @throws \Exception
   *   If the config file could not be written.
   */
  private function writeWorkFile(EnvironmentInterface $environment, $proc_id, $sites) {
    $config_filename = $this->getCronConfigFilename($environment, $proc_id);
    $this->ensureDirectoryExists(dirname($config_filename), $environment);
    $this->writeFile(trim(implode("\n", $sites)), $config_filename, $environment);
    return $config_filename;
  }

  /**
   * Returns the filename for writing cron configuration data.
   *
   * This cron configuration data will be used for a single cron process on a
   * particular server.
   *
   * @param EnvironmentInterface $environment
   *   The environment.
   * @param int $proc_id
   *   The process id from the sf_cron_proc table.
   *
   * @return string The filename.
   *   The filename.
   */
  private function getCronConfigFilename(EnvironmentInterface $environment, $proc_id) {
    return sprintf("%s/%s.conf", $this->getCronDir($environment), $proc_id);
  }

  /**
   * Returns the directory cron configurations will be written to.
   *
   * @param EnvironmentInterface $environment
   *   The environment.
   *
   * @return string The path to the cron configuration directory.
   *   The path to the cron configuration directory.
   */
  private function getCronDir(EnvironmentInterface $environment) {
    return sprintf("%s/cron", $environment->getWorkingDir());
  }

  /**
   * Verifies the cron script is on the destination machine.
   *
   * If not, the script will be written.
   *
   * @param EnvironmentInterface $environment
   *   The environment.
   *
   * @return bool
   *   TRUE if the cron script exists and is executable; FALSE otherwise.
   *
   * @throws \RuntimeException
   *   If the script doesn't exist and cannot be written.
   */
  private function verifyCronScript(EnvironmentInterface $environment) {
    $result = TRUE;
    $local_wrapper_script_path = $this->findLocalCronScript();
    $correct_md5sum = md5_file($local_wrapper_script_path);
    $file = $this->getFileCommands($environment);

    // This path is used on the remote machines.
    $script_path = $this->getCronPath($environment);
    try {
      if ($correct_md5sum !== trim($file->getMd5Sum($script_path)->exec()->getStdout())) {
        // The file does not exist or is outdated; ensure the directory exists.
        $script_directory = dirname($script_path);
        $file->mkdir($script_directory)->exec();

        // Now write the script.
        $file_contents = file_get_contents($local_wrapper_script_path);
        if (!$file->writeFile($file_contents, $script_path)
          ->exec()
          ->isSuccess()) {
          // Failed to write the script on the remote machine.
          $message = sprintf(
            'Failed to write the cron script on %s.%s server %s:%s',
            $environment->getSitegroup(),
            $environment->getEnvironmentName(),
            $environment->getCurrentServer(),
            $script_path
          );
          $this->log(WipLogLevel::ERROR, $message);
          throw new \RuntimeException($message);
        }
      }

      // @var StatResultInterpreter $permissions_interpreter
      $permissions_interpreter = $file->getFilePermissions($script_path)
        ->exec()->getResultInterpreter();
      if ($permissions_interpreter instanceof StatResultInterpreter && !$permissions_interpreter->isExecutable()) {
        if (!$file->chmod(0755, $script_path)
          ->exec()
          ->isSuccess()) {
          $message = sprintf(
            'Failed to make the cron script executable on %s.%s server %s:%s',
            $environment->getSitegroup(),
            $environment->getEnvironmentName(),
            $environment->getCurrentServer(),
            $script_path
          );
          $this->log(WipLogLevel::ERROR, $message);
          $result = FALSE;
        }
      }
    } catch (\Exception $e) {
      $result = FALSE;
    }
    return $result;
  }

  /**
   * Creates the script directory if needed.
   *
   * @param string $dir
   *   The directory.
   * @param EnvironmentInterface $environment
   *   The environment.
   *
   * @throws \RuntimeException
   *   If the directory does not exist and could not be created.
   */
  private function ensureDirectoryExists($dir, EnvironmentInterface $environment) {
    $file = $this->getFileCommands($environment);
    $result = $file->getFilePermissions($dir)->exec();
    if (!$result->isSuccess()) {
      // The directory is not there; try to create it.
      $mkdir_result = $file->mkdir($dir)->exec();
      if (!$mkdir_result->isSuccess()) {
        $message = sprintf('Could not create directory %s', $dir);
        $this->log(WipLogLevel::ERROR, $message);
        throw new \RuntimeException($message);
      }
    }
  }

  /**
   * Writes the specified file contents to the specified destination.
   *
   * @param string $file_contents
   *   The contents to write to the file.
   * @param string $destination
   *   The destination path.
   * @param EnvironmentInterface $environment
   *   The environment.
   *
   * @throws \RuntimeException
   *   If the file contents could not be written to the destination.
   */
  private function writeFile($file_contents, $destination, EnvironmentInterface $environment) {
    $file = $this->getFileCommands($environment);
    $result = $file->writeFile($file_contents, $destination)->exec();
    if (!$result->isSuccess()) {
      $message = sprintf('Failed to write the file %s.', $destination);
      $this->log(WipLogLevel::FATAL, $message);
      throw new \RuntimeException($message);
    }
  }

  /**
   * Gets the local path to the cron_wrapper script.
   *
   * @return string
   *   The path.
   */
  private function findLocalCronScript() {
    $path = sprintf('%s/../../../../../scripts/multisite_cron', __DIR__);
    if (!file_exists($path)) {
      throw new \RuntimeException('Could not find multisite_cron script at %s', escapeshellarg($path));
    }
    return $path;
  }

  /**
   * Returns the absolute path to the cron wrapper script.
   *
   * @param EnvironmentInterface $environment
   *   The environment.
   *
   * @return string
   *   The absolute path to the wrapper script.
   */
  private function getCronPath(EnvironmentInterface $environment) {
    return sprintf('%s/scripts/multisite_cron', $environment->getWorkingDir());
  }

  /**
   * Creates an Environment instance for the specified server.
   *
   * @param string $server
   *   The server name.
   *
   * @return EnvironmentInterface
   *   The environment.
   */
  private function getEnvironmentForServer($server) {
    // @var EnvironmentInterface $environment
    $environment = clone($this->environment);
    $environment->setServers(array($server));
    $environment->selectNextServer();
    return $environment;
  }

}
