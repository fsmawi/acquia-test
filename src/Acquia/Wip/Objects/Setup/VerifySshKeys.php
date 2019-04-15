<?php

namespace Acquia\Wip\Objects\Setup;

use Acquia\Wip\AcquiaCloud\AcquiaCloud;
use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\DependencyManager;
use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\IndependentEnvironment;
use Acquia\Wip\Objects\ParameterDocument;
use Acquia\Wip\Objects\SiteGroup;
use Acquia\Wip\Ssh\Ssh;
use Acquia\Wip\Ssh\SshKeys;
use Acquia\Wip\WipContextInterface;
use Acquia\Wip\WipLogLevel;

/**
 * The VerifySshKeys creates ssh keys if needed.
 *
 * For now, it also deploys the ssh_wrapper script.
 */
class VerifySshKeys extends BasicWip implements DependencyManagedInterface {

  /**
   * The version associated with this Wip class.
   */
  const CLASS_VERSION = 1;

  /**
   * The DependencyManager instance.
   *
   * @var DependencyManager
   */
  protected $dependencyManager;

  /**
   * The parameter document.
   *
   * @var ParameterDocument
   */
  private $parameterDocument;

  /**
   * Stores whether affected sitegroups have their SSH keys.
   *
   * @var array
   */
  private $keysCreated = array();

  /**
   * Stores whether affected sitegroups have their SSH wrappers deployed.
   *
   * @var array
   */
  private $wrappersCreated = array();

  /**
   * Specifies the state table that this FSM will execute.
   *
   * @var string
   */
  protected $stateTable = <<<EOT
  start:checkDocumentStatus {
    success        verifySshKeys
    fail           failure
  }

  # Verify necessary Ssh keys are available; create them if needed.
  verifySshKeys:checkSshKeys {
    init           verifySshKeys         wait=30 max=3
    wait           verifySshKeys         wait=30 exec=false
    fail           verifySshKeys         wait=30 max=3
    ssh_fail       verifySshKeys         wait=30 max=3
    *              verifySshWrapper
  }

  verifySshWrapper:checkSshWrappers {
    retry          verifySshWrapper      wait=30 max=3
    *              finish
  }

  failure {
    *              finish
    !              finish
  }
EOT;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    parent::__construct();
    $this->dependencyManager = new DependencyManager();
    $dependencies = $this->getDependencies();
    if (!empty($dependencies)) {
      $this->dependencyManager->addDependencies($this->getDependencies());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.ssh_service' => 'Acquia\Wip\Ssh\SshServiceInterface',
    ) + parent::getDependencies();
  }

  /**
   * {@inheritdoc}
   */
  public function start(WipContextInterface $wip_context) {
    $this->keysCreated = array();
    $this->wrappersCreated = array();
  }

  /**
   * Sets the parameter document that describes which sites to verify keys for.
   *
   * @param ParameterDocument $document
   *   The parameter document.
   */
  public function setParameterDocument(ParameterDocument $document) {
    $this->parameterDocument = $document;
  }

  /**
   * Gets the parameter document that describes where keys will be verified.
   *
   * @return ParameterDocument
   *   The document.
   */
  public function getParameterDocument() {
    return $this->parameterDocument;
  }

  /**
   * Verifies the SSH keys for all sitegroups have been established.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function verifySshKeys(WipContextInterface $wip_context) {
    $cloud_api = $this->getAcquiaCloudApi();
    $cloud_api->clearAcquiaCloudProcesses($wip_context, $this->getWipLog());
    $cloud_api->clearAcquiaCloudResults($wip_context, $this->getWipLog());
    $keys = new SshKeys();
    $site_groups = $this->getSiteGroups();
    foreach ($site_groups as $site_group) {
      /** @var IndependentEnvironment $environment */
      $environment = $this->parameterDocument->extract(array(
        'siteGroup' => $site_group->getName(),
        'environment' => $site_group->getLiveEnvironment(),
      ));
      if (!$environment->getCurrentServer()) {
        $environment->selectNextServer();
      }
      $cloud = new AcquiaCloud($environment, $this->getWipLog());
      $process = NULL;

      // The keysCreated field for the sitegroup may be
      // - uninitialized (we haven't checked it yet, or there were issues while
      //   contacting Cloud API),
      // - TRUE (key is present on the WIP server and Hosting already has it),
      // - the hosting task id which is distributing the SSH key.
      if (empty($this->keysCreated[$site_group->getName()])) {
        // Check if the SSH key for the sitegroup has been already generated on
        // the WIP server and just needs distribution via Cloud API.
        if ($keys->hasKey($environment)) {
          $this->log(WipLogLevel::DEBUG, sprintf('SSH key has been already generated for %s.', $site_group->getName()));
          // Check via Cloud API if Hosting also has the SSH key uploaded and it
          // has the expected content. Since one can only retrieve individual
          // SSH keys by ID which we do not have available, we have to fetch all
          // the keys. If there is an issue using Cloud API then we will retry
          // on the next run since no data is stored in keysCreated.
          $result = $cloud->listSshKeys();
          if ($result->isSuccess()) {
            $ssh_keys = $result->getData();
            $public_key_found = FALSE;
            $public_key = file_get_contents($keys->getPublicKeyPath($environment));
            foreach ($ssh_keys as $ssh_key) {
              // To avoid public key mismatch due to white spaces such as new
              // lines, apply trim() before comparison.
              if ($ssh_key->getName() === SshKeys::WIP_KEY_NAME &&
                  trim($ssh_key->getPublicKey()) === trim($public_key)) {
                $public_key_found = TRUE;
                $this->keysCreated[$site_group->getName()] = TRUE;
                $this->log(
                  WipLogLevel::DEBUG,
                  sprintf('The SSH key for %s has been already distributed.', $site_group->getName())
                );
                break;
              }
            }
            // The key exists on the WIP server but was not distributed to the
            // webnodes or Hosting has the wrong key. Create a new hosting task
            // to add the key.
            if (!$public_key_found) {
              $this->log(WipLogLevel::DEBUG, sprintf('Redistributing SSH key for %s.', $site_group->getName()));
              $process = $keys->registerKey($environment, $this->getWipLog());
            }
          } else {
            $this->log(
              WipLogLevel::DEBUG,
              sprintf('Unable to fetch the available SSH keys via Cloud API for %s.', $site_group->getName())
            );
          }
        } else {
          // The key does not exist; create it now.
          try {
            $this->log(
              WipLogLevel::DEBUG,
              sprintf('Generating and distributing the SSH key for %s.', $site_group->getName())
            );
            $keys->createKey($environment);
            $process = $keys->registerKey($environment, $this->getWipLog());
          } catch (\Exception $e) {
            // Could not run the script.
            $this->log(
              WipLogLevel::ERROR,
              sprintf('Error establishing SSH key for %s: %s.', $site_group->getName(), $e->getMessage())
            );
          }
        }
      }

      if (!empty($process) && $process->getPid()) {
        $cloud_api->addAcquiaCloudProcess($process, $wip_context);
        $this->keysCreated[$site_group->getName()] = $process->getPid();
      }
    }
  }

  /**
   * Checks that every sitegroup has a valid SSH key deployed.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   *
   * @return string
   *   'init' - Have to retry adding Cloud API task.
   *   'success' - All tasks were completed successfully.
   *   'wait' - One or more processes are still running.
   *   'uninitialized' - No results or processes have been added.
   *   'fail' - At least one task failed.
   *   'ssh_fail' - An Ssh command failed to connect.
   */
  public function checkSshKeys(WipContextInterface $wip_context) {
    // Check if every SSH key is already present or has a hosting task.
    $init_done = TRUE;
    $site_groups = $this->getSiteGroups();
    foreach ($site_groups as $site_group) {
      if (empty($this->keysCreated[$site_group->getName()])) {
        $init_done = FALSE;
        break;
      }
    }

    $result = 'init';
    if ($init_done) {
      $result = $this->checkResultStatus($wip_context);
      // Clear the context if there is any fail during processing so when we
      // retry the failed ones will not spoil the future checks.
      if ($result == 'fail') {
        $cloud_api = $this->getAcquiaCloudApi();
        $cloud_api->clearAcquiaCloudProcesses($wip_context, $this->getWipLog());
        $cloud_api->clearAcquiaCloudResults($wip_context, $this->getWipLog());
        $this->keysCreated = array();
      }
    }
    return $result;
  }

  /**
   * Verifies the ssh wrapper script exists and is up to date.
   *
   * The ssh wrapper script will be written for any sitegroup that doesn't
   * have it or has an out of date copy.
   */
  public function verifySshWrapper() {
    $wrapper_path = $this->findLocalWrapperScript();
    $ssh_wrapper = file_get_contents($wrapper_path);
    $md5 = md5($ssh_wrapper);
    $this->log(WipLogLevel::TRACE, sprintf('md5sum of local wrapper is %s', $md5));

    $site_groups = $this->getSiteGroups();
    foreach ($site_groups as $site_group) {
      /** @var IndependentEnvironment $environment */
      $environment = clone($this->parameterDocument->extract(array(
        'siteGroup' => $site_group->getName(),
        'environment' => $site_group->getLiveEnvironment(),
      )));
      $server = $environment->getCurrentServer();
      if (empty($server)) {
        $environment->selectNextServer();
      }
      if (!isset($this->wrappersCreated[$site_group->getName()])) {
        try {
          // Marking the sitegroup done for the time being, but if there are any
          // issues then this will get unset.
          $this->wrappersCreated[$site_group->getName()] = TRUE;
          if ($this->wrapperNeedsUpdate($md5, $environment)) {
            $this->log(
              WipLogLevel::DEBUG,
              sprintf('Server %s needs an updated ssh_wrapper.', $environment->getCurrentServer())
            );
            if (!$this->writeWrapper($ssh_wrapper, $environment)) {
              unset($this->wrappersCreated[$site_group->getName()]);
              $this->log(
                WipLogLevel::ERROR,
                sprintf('Failed to write the ssh_wrapper on server %s.', $environment->getCurrentServer())
              );
            }
          }
        } catch (\RuntimeException $e) {
          // Failed to log into the server. Maybe the server is having temporary
          // issues, try again later.
          $message = 'Unable to verify that the ssh_wrapper has been deployed on server %s. Error message: %s.';
          $this->log(
            WipLogLevel::ERROR,
            sprintf($message, $environment->getCurrentServer(), $e->getMessage())
          );
          unset($this->wrappersCreated[$site_group->getName()]);
        }
      }
    }
  }

  /**
   * Checks that every sitegroup has its SSH wrapper sorted.
   *
   * @return string
   *   'retry' - There were issues contacting a server, retry.
   *   'success' - All tasks were completed successfully.
   */
  public function checkSshWrappers() {
    // Check if every SSH key is already present or has a hosting task.
    $checks_done = TRUE;
    $site_groups = $this->getSiteGroups();
    foreach ($site_groups as $site_group) {
      if (empty($this->wrappersCreated[$site_group->getName()])) {
        $checks_done = FALSE;
        break;
      }
    }
    return $checks_done ? 'success' : 'retry';
  }

  /**
   * Verifies a ParameterDocument has been set.
   *
   * @return string
   *   'success' - The ParameterDocument has been set.
   *   'fail'    - The ParameterDocument has not been set.
   */
  public function checkDocumentStatus() {
    $result = 'fail';
    if (!empty($this->parameterDocument)) {
      $result = 'success';
    }
    return $result;
  }

  /**
   * Checks the md5 sum of the wrapper to determine if it needs to be updated.
   *
   * @param string $md5
   *   The MD5 sum of the correct version of the wrapper script.
   * @param EnvironmentInterface $environment
   *   The environment.
   *
   * @return bool
   *   TRUE if the wrapper needs updated; FALSE otherwise.
   */
  private function wrapperNeedsUpdate($md5, EnvironmentInterface $environment) {
    $ssh_service = $this->getSshService($environment);
    $wrapper_path = Ssh::getSshWrapper($environment);

    // Construct the command such that only the md5sum is returned.
    $command = sprintf('\md5sum %s | cut -d %s -f1', escapeshellarg($wrapper_path), escapeshellarg(' '));
    $result = $ssh_service->exec($command);
    $this->log(
      WipLogLevel::DEBUG,
      sprintf('Result of checking ssh_wrapper on %s: %s', $environment->getCurrentServer(), print_r($result, TRUE))
    );
    $sum = trim($result->getStdout());
    return ($md5 != $sum);
  }

  /**
   * Writes the ssh_wrapper script.
   *
   * Note this does not use the SshFileCommands convenience class because we
   * cannot assume that the wrapper script is there. The SshService is used
   * instead.
   *
   * @param string $ssh_wrapper
   *   The wrapper script to write.
   * @param EnvironmentInterface $environment
   *   The environment.
   *
   * @return bool
   *   TRUE if the write is successful; FALSE otherwise.
   */
  private function writeWrapper($ssh_wrapper, EnvironmentInterface $environment) {
    $this->log(WipLogLevel::ALERT, sprintf('Writing wrapper on server %s', $environment->getCurrentServer()));
    $ssh_service = $this->getSshService($environment);
    $wrapper_path = Ssh::getSshWrapper($environment);
    $wrapper_dir = dirname($wrapper_path);
    $command = sprintf(
      'mkdir -p %s; echo %s|base64 --decode > %3$s && chmod a+x %3$s',
      escapeshellarg($wrapper_dir),
      escapeshellarg(base64_encode($ssh_wrapper)),
      escapeshellarg($wrapper_path)
    );
    $result = $ssh_service->exec($command);
    return $result->isSuccess();
  }

  /**
   * Finds the local ssh wrapper script.
   *
   * @return string
   *   The path to the script.
   */
  private function findLocalWrapperScript() {
    $path = sprintf('%s/../../../../../scripts/ssh_wrapper', __DIR__);
    if (!file_exists($path)) {
      throw new \RuntimeException('Could not find ssh_wrapper script at %s', escapeshellarg($path));
    }
    return $path;
  }

  /**
   * Gets the site groups from the associated parameter document.
   *
   * @return SiteGroup[]
   *   The site groups.
   */
  private function getSiteGroups() {
    $result = NULL;
    if (isset($this->parameterDocument->siteGroups)) {
      $result = $this->parameterDocument->siteGroups;
    }
    return $result;
  }

}
