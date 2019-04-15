<?php

namespace Acquia\Wip\Objects;

use Acquia\WipService\Resource\v1\TaskResource;
use Acquia\Wip\Environment;
use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\ExitMessage;
use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\Ssh\GitCommands;
use Acquia\Wip\Ssh\GitKey;
use Acquia\Wip\Ssh\SshResult;
use Acquia\Wip\WipContextInterface;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogLevel;
use Acquia\Wip\WipModuleInterface;

/**
 * Adds the module code to a single webnode.
 */
class AddModuleToWebnode extends BasicWip {

  /**
   * The filename of the git wrapper script.
   */
  const MODULE_DEPLOY_WRAPPER = 'module_deploy_wrapper';

  /**
   * The state table that will be executed by this Wip object.
   *
   * @var string
   */
  protected $stateTable = <<<EOT
start:checkConfiguration {
  success             createSshWrapper
  fail                configurationFailure
}

# Create the git wrapper that uses the SSH key if needed.
createSshWrapper:checkResultStatus {
  success             verifyCloneExists
  wait                createSshWrapper wait=30 exec=false
  uninitialized       createSshWrapper wait=5 max=3
  fail                failure

  # Unexpected transition.
  *                   failure
  !                   failure
}

configurationFailure {
  *                   failure
}

verifyCloneExists:checkResultStatus {
  success             checkoutVcsPath
  wait                verifyCloneExists wait=30 exec=false
  uninitialized       verifyCloneExists wait=5 max=3
  fail                cloneWorkspace

  # Unexpected transition.
  *                   failure
  !                   failure
}

cloneWorkspace:checkResultStatus {
  success             checkoutVcsPath
  wait                cloneWorkspace wait=30 exec=false
  uninitialized       cloneWorkspace wait=5 max=3
  fail                cloneWorkspace wait=30 max=3

  # Unexpected transition.
  *                   failure
  !                   failure
}

checkoutVcsPath:checkResultStatus {
  success             readModuleConfiguration
  wait                checkoutVcsPath wait=30 exec=false
  uninitialized       checkoutVcsPath wait=5 max=3
  fail                checkoutVcsPath wait=30 max=3

  # Unexpected transition.
  *                   failure
  !                   failure
}

readModuleConfiguration:checkResultStatus {
  success             finish
  wait                readModuleConfiguration wait=15 exec=false
  uninitialized       readModuleConfiguration wait=5 max=3
  fail                readModuleConfiguration wait=30 max=3

  # Unexpected transition.
  *                   failure
  !                   failure
}

failure {
  *                   finish
}
EOT;

  /**
   * The webnode on which the module will be installed.
   *
   * @var string
   */
  private $webnode;

  /**
   * The module being installed.
   *
   * @var WipModuleInterface
   */
  private $module = NULL;

  /**
   * The environment used to work with the associated webnode.
   *
   * @var Environment
   */
  private $environment = NULL;

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    $name = 'Unknown';
    $webnode = $this->getWebnode();
    $module = $this->getModule();
    if (!empty($module)) {
      $name = $module->getName();
    }
    return (sprintf('Deploy module %s to %s', $name, $webnode));
  }

  /**
   * Verifies the configuration is complete.
   *
   * @return string
   *   'success' - This instance is configured.
   *   'fail' - The configuration is not complete.
   */
  public function checkConfiguration() {
    $result = 'fail';
    $module = $this->getModule();
    $path = $module->getVcsPath();
    $uri = $module->getVcsUri();
    $webnode = $this->getWebnode();
    if (!empty($path)
      && !empty($uri)
      && !empty($webnode)
      && !empty($module)
    ) {
      $result = 'success';
    }
    return $result;
  }

  /**
   * Called if there is insufficient configuration to deploy the module.
   */
  public function configurationFailure() {
    $message = new ExitMessage(
      'Incomplete configuration',
      WipLogLevel::FATAL,
      'The module configuration is incomplete.'
    );
    $this->setExitMessage($message);
  }

  /**
   * Creates an SSH wrapper so git can be used with a specific SSH key.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function createSshWrapper(WipContextInterface $wip_context) {
    $ssh_api = $this->getSshApi();
    $deploy_key = $this->getModuleDeployKey();
    $environment = $this->getModuleEnvironment();
    $file = $this->getFileCommands($environment);

    $private_key_path = $this->getPrivateKeyPath($environment);
    $git_key = new GitKey(
      TaskResource::ENCODING_KEY_NAME,
      $private_key_path,
      $this->getWrapperPath()
    );

    $exists_result = $file->exists($deploy_key->getWrapperFilename())->exec();
    if ($exists_result->isSuccess()) {
      $ssh_api->setSshResult($exists_result, $wip_context, $this->getWipLog());
    } else {
      $wrapper = <<<EOT
#!/bin/bash
ssh -i $private_key_path -o StrictHostKeyChecking=no $1 $2
EOT;
      $this->log(
        WipLogLevel::ALERT,
        sprintf(
          "Creating SSH wrapper script '%s' for SSH key '%s'",
          $git_key->getWrapperFilename(),
          $git_key->getPrivateKeyFilename()
        )
      );
      $result = $file->writeFile($wrapper, $git_key->getWrapperFilename())->exec();
      $ssh_api->setSshResult($result, $wip_context, $this->getWipLog());
      if ($result->isSuccess()) {
        $result = $file->chmod(0755, $git_key->getWrapperFilename())->exec();
        $ssh_api->addSshResult($result, $wip_context);
      }
    }
  }

  /**
   * Verifies the workspace clone exists.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function verifyCloneExists(WipContextInterface $wip_context) {
    $ssh_api = $this->getSshApi();
    $environment = $this->getModuleEnvironment();
    $module = $this->getModule();
    $dir = $module->getAbsolutePath($module->getDirectory());

    $git = $this->getGit($environment, $dir);
    $remotes_result = $git->listRemotes()->exec();
    if ($remotes_result->isSuccess()) {
      $remote_name = $git->getRemoteName($remotes_result->getStdOut(), $module->getVcsUri(), 'fetch');
      if ($remote_name === NULL) {
        // Force fail the call since the desired remote is not present.
        $reason = sprintf('The remote for repository "%s" was not found.', $module->getVcsUri());
        $remotes_result->forceFail($reason);
      }
    }
    $ssh_api->setSshResult($remotes_result, $wip_context, $this->getWipLog());
  }

  /**
   * Clones the workspace.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function cloneWorkspace(WipContextInterface $wip_context) {
    $ssh_api = $this->getSshApi();
    $environment = $this->getModuleEnvironment();
    $module = $this->getModule();
    $dir = $module->getAbsolutePath($module->getDirectory());
    $file = $this->getFileCommands($environment);

    // Ensure the module directory does not exist.
    $file->forceRemove($dir)->exec();
    if ($file->exists($dir)->exec()->isSuccess()) {
      $exists_result = new SshResult(
        SshResult::FORCE_FAIL_EXIT_CODE,
        '',
        sprintf("Failed to remove directory %s", $dir)
      );
      $ssh_api->setSshResult($exists_result, $wip_context, $this->getWipLog());
      return;
    }

    // Clone the workspace.
    $git = $this->getGit($environment, $dir);
    $clone_process = $git->cloneWorkspace($module->getVcsUri())->execAsync();
    $ssh_api->setSshProcess($clone_process, $wip_context, $this->getWipLog());
  }

  /**
   * Checks out the specified VCS path.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function checkoutVcsPath(WipContextInterface $wip_context) {
    $ssh_api = $this->getSshApi();
    $environment = $this->getModuleEnvironment();
    $module = $this->getModule();
    $dir = $module->getAbsolutePath($module->getDirectory());

    $git = $this->getGit($environment, $dir);
    $checkout_process = $git->forceCheckoutBranch($module->getVcsPath())->execAsync();
    $ssh_api->setSshProcess($checkout_process, $wip_context, $this->getWipLog());
  }

  /**
   * Reads the module configuration.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function readModuleConfiguration(WipContextInterface $wip_context) {
    $ssh_api = $this->getSshApi();
    $config_file = $this->getModule()->getConfigFile();
    $file_commands = $this->getFileCommands($this->getModuleEnvironment());
    $cat_result = $file_commands->cat($config_file)
      ->setSecure()
      ->exec();
    if ($cat_result->isSuccess()) {
      $module_configuration = file_get_contents($config_file);
      $callback_data = new \stdClass();
      $callback_data->moduleConfig = $module_configuration;
      $this->addCallbackData($callback_data);
    }
    $ssh_api->setSshResult($cat_result, $wip_context, $this->getWipLog());
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions($options) {
    if (!empty($options->webnode)) {
      $this->setWebnode(strval($options->webnode));
    }
  }

  /**
   * Sets the webnode where the module will be checked out.
   *
   * @param string $webnode
   *   The webnode.
   */
  public function setWebnode($webnode) {
    if (!is_string($webnode) || trim($webnode) == FALSE) {
      throw new \InvalidArgumentException('The "webnode" parameter must be a non-empty string.');
    }
    $this->webnode = trim($webnode);
  }

  /**
   * Gets the webnode where the module will be checked out.
   *
   * @return string
   *   The webnode.
   */
  public function getWebnode() {
    return $this->webnode;
  }

  /**
   * Sets the module instance.
   *
   * @param WipModuleInterface $module
   *   The module instance.
   */
  public function setModule(WipModuleInterface $module) {
    $this->module = $module;
  }

  /**
   * Gets the module instance.
   *
   * @return WipModuleInterface
   *   The module instance.
   */
  public function getModule() {
    return $this->module;
  }

  /**
   * Gets the absolute path to the private key.
   *
   * @param Environment $environment
   *   The environment.
   *
   * @return null|string
   *   The private key path.
   */
  private function getPrivateKeyPath(Environment $environment) {
    $result = sprintf(
      '/mnt/files/%s.%s/nobackup/%s',
      $environment->getSitegroup(),
      $environment->getEnvironmentName(),
      TaskResource::ENCODING_KEY_NAME
    );
    return $result;
  }

  /**
   * Gets the GitKey instance that points to the deploy wrapper script.
   *
   * @return GitKey
   *   The GitKey instance.
   */
  private function getModuleDeployKey() {
    $environment = Environment::getRuntimeEnvironment();
    $wrapper_path = $this->getWrapperPath();

    $git_key = new GitKey(
      TaskResource::ENCODING_KEY_NAME,
      $this->getPrivateKeyPath($environment),
      $wrapper_path
    );
    return $git_key;
  }

  /**
   * Gets the path for the specified git wrapper.
   *
   * @return string
   *   The absolute path for the wrapper script.
   */
  private function getWrapperPath() {
    $environment = Environment::getRuntimeEnvironment();
    return sprintf(
      '/home/%s/%s',
      $environment->getSitegroup(),
      $this->getWrapperFilename()
    );
  }

  /**
   * Gets the wrapper filename.
   *
   * @return string
   *   The filename.
   */
  private function getWrapperFilename() {
    $environment = Environment::getRuntimeEnvironment();
    return sprintf(
      '%s_%s',
      self::MODULE_DEPLOY_WRAPPER,
      $environment->getEnvironmentName()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function failure(WipContextInterface $wip_context, \Exception $exception = NULL) {
    $exit_message = $this->getExitMessage();
    if (empty($exit_message)) {
      $module_name = $this->getModule()->getName();
      $this->setExitMessage(
        new ExitMessage(
          sprintf('Failed to deploy module "%s" to webnode "%s".', $module_name, $this->getWebnode()),
          WipLogLevel::FATAL
        )
      );
    }
    parent::failure($wip_context, $exception);
  }

  /**
   * Gets the environment used to work with the associated webnode.
   *
   * @return Environment
   *   The environment.
   */
  private function getModuleEnvironment() {
    if (empty($this->environment)) {
      $environment = Environment::getRuntimeEnvironment();
      $key_path = WipFactory::getString('$acquia.wip.service.private_key_path');
      $environment->setPassword(sprintf('ssh:%s', $key_path));
      $environment->setServers(array($this->getWebnode()));
      $environment->selectNextServer();
      $this->environment = $environment;
    }
    return $this->environment;
  }

  /**
   * Gets the API for executing git commands.
   *
   * @param EnvironmentInterface $environment
   *   The environment.
   * @param string $dir
   *   The git directory.
   *
   * @return GitCommands An instance of GitCommands for executing git commands.
   *   An instance of GitCommands for executing git commands.
   */
  private function getGit(EnvironmentInterface $environment, $dir) {
    $deploy_key = $this->getModuleDeployKey();
    $result = $this->getGitCommands($environment, $dir, TRUE);
    $result->setWrapperPathTemplate($deploy_key->getWrapperFilename());
    return $result;
  }

}
