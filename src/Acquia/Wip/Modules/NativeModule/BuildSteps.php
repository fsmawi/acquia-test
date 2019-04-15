<?php

namespace Acquia\Wip\Modules\NativeModule;

use Acquia\Wip\AcquiaCloud\AcquiaCloud;
use Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudAddSshKeyTaskInfo;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudTaskResult;
use Acquia\Wip\Container\ContainerInterface;
use Acquia\Wip\Environment;
use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\Exception\AcquiaCloudApiException;
use Acquia\Wip\ExitMessage;
use Acquia\Wip\IndependentEnvironment;
use Acquia\Wip\IteratorStatus;
use Acquia\Wip\Iterators\BasicIterator\WipUpdateCoordinatorInterface;
use Acquia\Wip\Objects\BuildSteps\AbstractPipelineWip;
use Acquia\Wip\Objects\ParameterDocument;
use Acquia\Wip\Objects\ParameterDocumentBuilder;
use Acquia\Wip\Objects\SiteGroup;
use Acquia\Wip\Signal\SshCompleteSignal;
use Acquia\Wip\Ssh\GitCommands;
use Acquia\Wip\Ssh\GitKey;
use Acquia\Wip\Ssh\GitKeys;
use Acquia\Wip\Ssh\SshKeys;
use Acquia\Wip\Ssh\SshProcessInterface;
use Acquia\Wip\Ssh\SshResult;
use Acquia\Wip\Ssh\SshResultInterface;
use Acquia\Wip\WipContextInterface;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogLevel;

/**
 * Executes build steps.
 *
 * This class is responsible for turning build instructions and associated
 * resources into a deployment image.
 */
class BuildSteps extends AbstractPipelineWip {

  /**
   * The version associated with this Wip class.
   */
  const CLASS_VERSION = 6;

  /**
   * The prefix of the SSH key name registered in Cloud for accessing the git repo.
   */
  const SSH_KEY_NAME_PREFIX = 'Pipelines';

  /**
   * The WipContext field that indicates whether a cleanup request was sent.
   */
  const CLEANUP_REQUEST_FIELD = 'cleanupRequestSent';

  /**
   * The local unix user who is executing the build.
   */
  const BUILDSTEPS_UNIX_USER = 'local';

  /**
   * The location of the environment file in the container.
   */
  const PIPELINE_ENV_VAR_FILE_PATH = '/home/local/pipeline_env_vars';

  /**
   * The description used in the build process.
   */
  const BUILD_PROCESS_DESCRIPTION = 'Build the deployment image';

  /**
   * Command to valid ssh keys.
   */
  const KEY_CHECKER = "/usr/bin/ssh-keygen -y -P '' -f %s >/dev/null 2>&1";

  /**
   * The default filename for the deploy git wrapper.
   */
  const DEPLOY_WRAPPER_FILENAME = 'git_workspace_wrapper';

  /**
   * The default filename of the hosting private key file.
   */
  const DEPLOY_SSH_PRIVATE_KEY_FILENAME = 'id_rsa_ws';

  /**
   * The GitKey name associated with the deploy key.
   */
  const DEPLOY_KEY_NAME = 'deploy';

  /**
   * The GitKey name associated with the source key.
   */
  const SOURCE_KEY_NAME = 'source';

  /**
   * The deploy key filename.
   */
  const DEPLOY_KEY_FILENAME = 'id_rsa_acquia_deploy';

  /**
   * The source key filename.
   */
  const SOURCE_KEY_FILENAME = 'id_rsa_acquia_source';

  /**
   * The version of the encryption scheme to use.
   */
  const VARIABLE_ENCRYPTION_VERSION = 1;

  /**
   * The default build event in the executor.
   */
  const DEFAULT_BUILD_EVENT = 'build';

  /**
   * Indicates the branch or tag containing the build instructions.
   *
   * @var string
   */
  private $buildVcsPath;

  /**
   * Indicates the branch that is the destination of the build.
   *
   * @var string
   */
  private $deployVcsPath;

  /**
   * The environment used to interact with Cloud API.
   *
   * @var EnvironmentInterface
   */
  private $environment;

  /**
   * The local environment used to build the workspace inside the container.
   *
   * @var EnvironmentInterface
   */
  private $local;

  /**
   * The URI to the source git repository.
   *
   * @var string
   */
  private $sourceVcsUri = NULL;

  /**
   * Indicates whether the source git commands require a wrapper.
   *
   * @var bool
   */
  private $sourceRequiresWrapper = TRUE;

  /**
   * The URI to the destination git repository.
   *
   * @var string
   */
  private $deployVcsUri = NULL;

  /**
   * Indicates whether the deploy git commands require a wrapper.
   *
   * @var bool
   */
  private $deployRequiresWrapper = TRUE;

  /**
   * The asymmetric private key for decrypting encrypted build document values.
   *
   * @var string
   */
  private $secureAsymmetricPrivateKey;

  /**
   * The name of the SSH key.
   *
   * @var string
   */
  private $sshKeyName = NULL;

  /**
   * The ID of the SSH key.
   *
   * @var int
   */
  private $sshKeyId = NULL;

  /**
   * The name of the asymmetric private key.
   *
   * @var string
   */
  private $privateKeyName;

  /**
   * The environment variables provided by the user.
   *
   * @var array
   */
  private $secureUserEnvironmentVariables;

  /**
   * The git wrapper filename template that uses the deploy key.
   *
   * @var string
   */
  private $deployWrapperFilename = 'git_deploy_wrapper';

  /**
   * The git wrapper filename that uses the source key.
   *
   * @var string
   */
  private $sourceWrapperFilename = 'git_source_wrapper';

  /**
   * The set of SSH keys used to work with git.
   *
   * @var GitKeys
   */
  private $gitKeys = NULL;

  /**
   * The application ssh key content.
   *
   * @var string
   */
  private $applicationPrivateKey;

  /**
   * Initializes a new instance of BuildSteps.
   */
  public function __construct() {
    parent::__construct();
    $this->gitKeys = new GitKeys();
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->getGroup();
  }

  /**
   * Prevent multiple simultaneous builds of the same VCS path.
   *
   * Building more than once for the same VCS path to the same destination at a
   * time could result in unpredictable results.  The destination VCS path is a
   * singleton resource and writes to it must be done one at a time.
   *
   * @return string
   *   The value that uniquely identifies a particular workload.
   */
  public function generateWorkId() {
    $vcs_uri = $this->getDeployVcsUri();
    if (empty($vcs_uri)) {
      throw new \DomainException('The source VCS URI must be set before generating a work ID.');
    }
    $deploy_path = $this->getDeployVcsPath();
    if (empty($deploy_path)) {
      throw new \DomainException('The deploy VCS path must be set before generating a work ID.');
    }

    $work_id = sprintf('%s:%s:%s', __CLASS__, $vcs_uri, $deploy_path);
    return sha1($work_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    $dependencies = parent::getDependencies();
    $dependencies['acquia.wip.signal.cleanup'] = 'Acquia\Wip\Signal\CleanupSignal';
    $dependencies['acquia.wip.notification'] = 'Acquia\Wip\Notification\NotificationInterface';
    return $dependencies;
  }

  /**
   * Gets the URI from which the build instructions will be cloned.
   *
   * @return string
   *   The source VCS URI.
   */
  public function getSourceVcsUri() {
    return $this->sourceVcsUri;
  }

  /**
   * Sets the URI from which the build instructions will be cloned.
   *
   * @param string $uri
   *   The git repository URI.
   */
  public function setSourceVcsUri($uri) {
    if (empty($uri) || !is_string($uri)) {
      throw new \InvalidArgumentException('The "uri" parameter must be a non-empty string.');
    }
    $this->sourceVcsUri = $uri;
  }

  /**
   * Gets the URI to which the build artifact will be pushed.
   *
   * @return string
   *   The destination VCS URI.
   */
  public function getDeployVcsUri() {
    return $this->deployVcsUri;
  }

  /**
   * Sets the URI to which the build artifact will be pushed.
   *
   * @param string $uri
   *   The git repository URI.
   */
  public function setDeployVcsUri($uri) {
    if (empty($uri) || !is_string($uri)) {
      throw new \InvalidArgumentException('The "uri" parameter must be a non-empty string.');
    }
    $this->deployVcsUri = $uri;
  }

  /**
   * Sets the VCS path representing the build instructions.
   *
   * @param string $vcs_path
   *   The VCS path pointing to the build instructions to execute.
   */
  public function setBuildVcsPath($vcs_path) {
    $this->buildVcsPath = $vcs_path;
  }

  /**
   * Gets the VCS path that will be used to build the deployment image.
   *
   * @return string
   *   The VCS path to build.
   */
  public function getBuildVcsPath() {
    return $this->buildVcsPath;
  }

  /**
   * Sets the VCS path that will contain the deploy image.
   *
   * @param string $vcs_path
   *   The VCS path indicating the destination of the build.
   */
  public function setDeployVcsPath($vcs_path) {
    $this->deployVcsPath = $vcs_path;
  }

  /**
   * Gets the VCS that will contain the deploy image.
   *
   * @return string
   *   The VCS path indicating the destination of the build.
   */
  public function getDeployVcsPath() {
    return $this->deployVcsPath;
  }

  /**
   * Sets the application private key.
   *
   * @param string $key
   *   The application private key content.
   */
  public function setApplicationPrivateKey($key) {
    $this->applicationPrivateKey = $key;
  }

  /**
   * Gets application private key content.
   *
   * @return string
   *   The application private key.
   */
  public function getApplicationPrivateKey() {
    return $this->applicationPrivateKey;
  }

  /**
   * Sets the asymmetric private key.
   *
   * @param string $private_key
   *   The asymmetric private key.
   */
  public function setAsymmetricPrivateKey($private_key) {
    $this->secureAsymmetricPrivateKey = $this->encrypt($private_key);
  }

  /**
   * Gets the private key used for decoding secure variables and SSH keys.
   *
   * Note this value must never be stored in an instance variable to prevent it
   * from being serialized in the database.
   *
   * @return string
   *   The private key contents.
   */
  private function getAsymmetricPrivateKey() {
    if (empty($this->secureAsymmetricPrivateKey)) {
      $key_name = $this->getPrivateKeyName();
      if (!empty($key_name)) {
        $keys = new SshKeys();
        $keys->setRelativeKeyPath($key_name);
        $environment = Environment::getRuntimeEnvironment();

        if (!$keys->hasKey($environment)) {
          $this->log(WipLogLevel::INFO, 'Creating encryption keys');
          $keys->createKey($environment, NULL, 'BuildSteps encryption key');
        }

        $key = file_get_contents($keys->getPrivateKeyPath($environment));
        $this->setAsymmetricPrivateKey($key);
      }
    }

    return $this->decrypt($this->secureAsymmetricPrivateKey);
  }

  /**
   * Sets the asymmetric private key name.
   *
   * The assumption here is that the key is available on the Wip webnodes.
   *
   * @param string $private_key_name
   *   The private key name.
   */
  public function setPrivateKeyName($private_key_name) {
    $this->privateKeyName = $private_key_name;
  }

  /**
   * Gets the asymmetric private key name.
   *
   * @return string
   *   The private key name.
   */
  public function getPrivateKeyName() {
    return $this->privateKeyName;
  }

  /**
   * Sets any user provided environment variables.
   *
   * @param array $environment_variables
   *   A list of environment variables.
   */
  public function setUserEnvironmentVariables($environment_variables) {
    $this->secureUserEnvironmentVariables = [];
    foreach ($environment_variables as $key => $value) {
      $this->secureUserEnvironmentVariables[$key] = $this->encrypt($value);
    }
  }

  /**
   * Determines if encrypted variables are available.
   *
   * @return bool
   *   Are encrypted variables available.
   */
  private function encryptedVariablesAvailable() {
    if (isset($this->secureUserEnvironmentVariables['PIPELINES_ENCRYPTED_VARIABLES_AVAILABLE'])) {
      $available = $this->decrypt($this->secureUserEnvironmentVariables['PIPELINES_ENCRYPTED_VARIABLES_AVAILABLE']);
      // We get a string when we decode make sure it is true.
      return strtolower($available) === '1';
    }
    return TRUE;
  }

  /**
   * Determines if we should keep the container process alive.
   *
   * An assumption is made that if it is set we want to keep it alive.
   *
   * @return bool
   *   Should the container be kept alive.
   */
  private function keepProcessAlive() {
    return isset($this->secureUserEnvironmentVariables['PIPELINE_PROCESS_KEEP_ALIVE']);
  }

  /**
   * Gets the user provided environment variables.
   *
   * @return array
   *   A list of environment variables.
   */
  public function getUserEnvironmentVariables() {
    $values = [];
    if (!empty($this->secureUserEnvironmentVariables)) {
      foreach ($this->secureUserEnvironmentVariables as $key => $value) {
        $values[$key] = $this->decrypt($value);
      }
    }
    return $values;
  }

  /**
   * Gets the ParameterDocument from the options.
   *
   * @param object $options
   *   The options object.
   *
   * @return ParameterDocument
   *   The parameter document.
   *
   * @throws \DomainException
   *   If there is insufficient information in the options to get or create a
   *   parameter document.
   */
  protected function getParameterDocumentFromOptions($options) {
    $result = NULL;
    if (!empty($options->parameterDocument)) {
      // The parameter document has been provided in the options.
      if (is_string($options->parameterDocument)) {
        // The parameter document is in JSON format.
        $result = new ParameterDocument(
          $options->parameterDocument,
          array('siteGroups' => 'Acquia\Wip\Objects\SiteGroup')
        );
      } elseif ($options->parameterDocument instanceof ParameterDocument) {
        // The parameter document has already been constructed.
        $result = $options->parameterDocument;
      } else {
        $error_message = <<<EOT
The parameterDocument option must be of type ParameterDocument or a string containing the JSON form of the document.
EOT;
        throw new \DomainException($error_message);
      }
    } else {
      // No parameter document has been provided.  See if there are sufficient
      // options to create one.
      if (empty($options->site)) {
        throw new \DomainException(
          'The options must include a "site" value in order to generate a parameter document.'
        );
      }
      $cloud_credentials = $this->getCloudCredentialsFromOptions();
      $document_builder = new ParameterDocumentBuilder($cloud_credentials);
      $document_builder->setCloudCalls(FALSE);
      $result = $document_builder->build(array('siteGroups' => 'Acquia\Wip\Objects\SiteGroup'));
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getStateTable() {
    $start_container = $this->getContainerStartTable('ensureBuildUser', 'failure', 'containerTerminated');
    $start_container_state = $this->getContainerStartState();
    $stop_container = $this->getContainerStopTable('containerStopped', 'containerStopFailed');
    $stop_container_state = $this->getContainerStopState();

    // Calculate the interval at which build progress will be checked.
    $max_idle_duration_interval = max(
      min(intval($this->getMaximumBuildNoProgressTime() / 2), 60),
      5
    );

    return <<<EOT

start:checkDeployPathType {
  branch             $start_container_state
  uninitialized      $start_container_state
  tag                deployPathIsTag
}

deployPathIsTag {
  *                  finish
}

$start_container

# Verifies the build user has been established in the container.
ensureBuildUser:checkContainerResultStatus {
  success            ensureVcsUri
  wait               ensureBuildUser wait=10 exec=false
  uninitialized      ensureBuildUser wait=10 max=3
  no_information     ensureBuildUser wait=30 max=3
  fail               systemFailure
  terminated         containerTerminated

  # Unexpected transition.
  *                  ensureBuildUser wait=10 max=3
  !                  systemFailure
}

# Verifies the source and deploy VCS URIs have been set. If they are missing an
# attempt will be made to figure them out using the Acquia Cloud API.
ensureVcsUri:verifyVcsUri [acquiaCloudApi] {
  success            establishWorkspaceSshKey
  fail               ensureVcsUri wait=30 max=3
  !                  systemFailure
}

# Establishes the workspace keys for both the source and deploy git
# repositories. Usually this involves copying the keys that were provided by
# the caller. If the necessary keys were not provided, this method will create
# a new SSH key and add it to the necessary hosting site groups using the
# Acquia Cloud API.
establishWorkspaceSshKey:checkContainerResultStatus [acquiaCloudApi] {
  success            extractSshKeyId
  wait               establishWorkspaceSshKey wait=10 exec=false
  uninitialized      establishWorkspaceSshKey wait=10 max=3
  no_information     establishWorkspaceSshKey wait=30 max=3
  fail               establishWorkspaceSshKey wait=30 max=3
  terminated         containerTerminated

  # Unexpected transition.
  *                  establishWorkspaceSshKey wait=30 max=3
  !                  systemFailure
}

# Gets the Acquia Cloud SSH key ID from the Acquia Cloud call. This will be
# used during cleanup to delete the temporary key(s). Note that it is safe to
# call this method even if the keys were provided by the caller and no Acquia
# Cloud API call was performed.
extractSshKeyId [acquiaCloudApi] {
  *                  createGitWrapper
}

# Creates all necessary git wrapper scripts for accessing remote git
# repositories. It is necessary to use the appropriate git wrapper script when
# working with a git repository because it ensures the correct SSH key is used.
createGitWrapper:checkContainerResultStatus {
  success            writeUserEnvironmentVars
  wait               createGitWrapper wait=10 exec=false
  uninitialized      createGitWrapper wait=10 max=3
  no_information     createGitWrapper wait=30 max=3
  fail               createGitWrapper wait=10 max=3
  terminated         containerTerminated

  # Unexpected transition.
  *                  createGitWrapper wait=10 max=3
  !                  systemFailure
}

# Writes user environment vars into the container.
writeUserEnvironmentVars:checkContainerResultStatus [user] {
  success            reportPipelinesMetaData
  wait               writeUserEnvironmentVars wait=30 exec=false
  uninitialized      writeUserEnvironmentVars wait=10 max=3
  no_information     writeUserEnvironmentVars wait=30 max=3
  fail               writeUserEnvironmentVars wait=10 max=3
  terminated         containerTerminated

  # Unexpected transition.
  *                  writeUserEnvironmentVars wait=10 max=3
  !                  systemFailure
}

# Reports job metadata back to the pipeline-api.
reportPipelinesMetaData {
  *                  exportBuildFileAsJson
}

# Uses the pipeline tool to export the build script so that the
# encrypted elements can be decoded.
# TODO merge build and export logic.
exportBuildFileAsJson:checkContainerResultStatus [user] {
  success            executeBuildScript
  wait               exportBuildFileAsJson wait=30 exec=false
  uninitialized      exportBuildFileAsJson wait=10 max=3
  no_information     exportBuildFileAsJson wait=30 max=3
  no_progress        exportBuildFileAsJson wait=30 max=3
  fail               jsonExportFailed
  terminated         containerTerminated

  # Unexpected transition.
  *                  exportBuildFileAsJson wait=10 max=3
  !                  systemFailure
}

# Set the exit message and code.
jsonExportFailed [user] {
  *                  failure
}

# Takes a workspace directory, a build directory path, and the yaml document and
# builds into the build directory.
executeBuildScript:checkContainerResultStatus [user] {
  success            logBuildSuccessMessage
  wait               executeBuildScript wait=30 exec=false
  fail               logBuildFailureMessage
  uninitialized      executeBuildScript wait=10 max=3
  no_information     executeBuildScript wait=30 exec=false max=3
  no_progress        noBuildProgress wait=10
  terminated         containerTerminated

  # Unexpected transition.
  *                  executeBuildScript wait=10 exec=false max=3

  # Indicate a system failure because normal build failures will be handled
  # through other transitions. Because we don't retry the failed build, the
  # only reason the maximum transition count would be exceeded is because of
  # too many retries of getting the build started.
  !                  systemFailure
}

# If no build progress was detected, allow the build to continue for a while
# before failing the build.
noBuildProgress:checkBuildProgress [user] {
  *                  executeBuildScript wait=10 exec=false
  no_progress        noBuildProgress wait=$max_idle_duration_interval exec=false

  # Indicates the build has executed too long with no output.
  no_progress_fail   noBuildProgressFailure
}

logBuildSuccessMessage:commitInExecutor {
  *          success
}

# Set the exit message and code.
logBuildFailureMessage {
  *                  failure
}

# Force-fails the build process.
noBuildProgressFailure [user] {
  *                  failure
}

success {
  *                  releaseWorkspaceSshKey
}

containerTerminated {
  *                  failure
}

systemFailure {
  *                  failure
}

terminate {
  *                  failure
}

failure {
  *                  releaseWorkspaceSshKey
  !                  releaseWorkspaceSshKey
}

releaseWorkspaceSshKey [acquiaCloudApi] {
  *                  $stop_container_state
  !                  $stop_container_state
}

$stop_container

containerStopped {
  *                  finish
  !                  finish
}

containerStopFailed {
  *                  finish
  !                  finish
}

EOT;
  }

  /**
   * Extracts an Environment instance from the specified ParameterDocument.
   *
   * If provided, the site_group_name parameter will control which site group
   * is described in the resulting Environment instance. Otherwise the first
   * site group / environment set in the specified ParameterDocument will be
   * used.
   *
   * This is similar to the parent but it makes no calls to get the next server.
   *
   * @param ParameterDocument $document
   *   The ParameterDocument instance that holds the environment information.
   * @param string $site_group_name
   *   Optional. If provided it identifies the Hosting site group from which the
   *   environment data should be extracted. This can be the simple site group
   *   name, or be fully qualified with the realm.
   *
   * @return Environment
   *   The environment instance.
   */
  public function extractEnvironment(
    ParameterDocument $document,
    $site_group_name = NULL
  ) {
    $result = NULL;
    /** @var SiteGroup $site_group */
    if (!empty($document->siteGroups)) {
      foreach ($document->siteGroups as $site_group) {
        if (!empty($site_group_name)) {
          // The caller provided the site group name, so reject any site group
          // that does not match that name.
          if ($site_group->getFullyQualifiedName() !== $site_group_name &&
            $site_group->getName() !== $site_group_name
          ) {
            continue;
          }
        }
        try {
          /** @var IndependentEnvironment $environment */
          $environment = $document->extract(array(
            'siteGroup' => $site_group->getFullyQualifiedName(),
            'environment' => $site_group->getLiveEnvironment(),
          ));
          if (!empty($environment)) {
            $result = $environment;
            break;
          }
        } catch (\Exception $e) {
        }
      }
    }
    return $result;
  }

  /**
   * Handles the start state.
   *
   * @param WipContextInterface $wip_context
   *   The context.
   */
  public function start(WipContextInterface $wip_context) {
    parent::start($wip_context);

    $parameter_document = $this->getParameterDocument();
    $this->environment = $this->extractEnvironment($parameter_document);
    $this->initializeLocalEnvironment();

    if ($iterator = $this->getIterator()) {
      $context = $iterator->getWipContext('extractSshKeyId');
      $context->linkContext('establishWorkspaceSshKey');
      $context = $iterator->getWipContext('jsonExportFailed');
      $context->linkContext('exportBuildFileAsJson');
      $context = $iterator->getWipContext('noBuildProgress');
      $context->linkContext('executeBuildScript');
      $context = $iterator->getWipContext('noBuildProgressFailure');
      $context->linkContext('executeBuildScript');
      $context = $iterator->getWipContext('checkBuildProgress');
      $context->linkContext('executeBuildScript');
    }
  }

  /**
   * Logs a user-readable fail message.
   *
   * Note that we must handle logging the generic fail message here instead
   * of in onFinish() like the success case does, because the ContainerDelegate
   * will take over as soon as a fail occurs and we will never reach the finish
   * state in this wip object.
   */
  public function onFail() {
    $message = $this->getExitMessage();
    if (empty($message)) {
      $message = new ExitMessage('The build task has failed.', WipLogLevel::FATAL);
      $this->setExitMessage($message);
    }
    $this->log($message->getLogLevel(), $message->getLogMessage(), TRUE);
    $this->cleanUp();
  }

  /**
   * Logs a user-readable message for successful builds.
   */
  public function logBuildSuccessMessage() {
    $wip_context = $this->getIterator()->getWipContext('executeBuildScript');
    $results = $this->getSshApi()->getSshResults($wip_context);
    /** @var SshResultInterface $result */
    $result = reset($results);

    if (!empty($result)) {
      $message = sprintf(
        "%s\n%s",
        $result->getStdout(),
        $result->getStderr()
      );
      // No longer send this message to Pipelines by reverting user readable to FALSE.
      $this->log(WipLogLevel::INFO, $message, FALSE);
    }
    $this->setExitMessage(new ExitMessage('Successfully executed the build script.'));
  }

  /**
   * Logs a user-readable message for failed builds.
   */
  public function logBuildFailureMessage() {
    $message = $detailed_message = 'Failed while running the build script.';
    $this->logBuildFailure($message);
  }

  /**
   * Logs a user-readable message for failed builds.
   *
   * @param string $exit_message
   *   The brief exit message.
   * @param string $detailed_message
   *   The detailed exit message.
   */
  public function logBuildFailure($exit_message, $detailed_message = '') {
    $wip_context = $this->getIterator()->getWipContext('executeBuildScript');
    if (empty($detailed_message)) {
      $detailed_message = $exit_message;
    }

    /** @var SshResultInterface $result */
    $results = $this->getSshApi()->getSshResults($wip_context);
    $result = reset($results);
    if (!empty($result)) {
      $error = $result->getStderr();

      $detailed_message = sprintf(
        "%s Output:\n%s",
        $exit_message,
        $result->getStdout()
      );

      // Strip the Symphony exception since that repeats the stdout and
      // includes formatting that will not appear correctly.
      if (preg_match(
        '/(.*)\[Symfony\\\Component\\\Process\\\Exception\\\ProcessFailedException\].*/s',
        $error,
        $matches
      ) === 1 && count($matches) > 1) {
        $error = $matches[1];
      }
      if (!empty($error)) {
        $detailed_message .= "\n$error";
      }
    }
    $this->setExitCode(IteratorStatus::ERROR_USER);
    $this->setExitMessage(new ExitMessage($exit_message, WipLogLevel::FATAL, $detailed_message));
  }

  /**
   * Fails the build due to no build progress.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function noBuildProgressFailure(WipContextInterface $wip_context) {
    $ssh_api = $this->getSshApi();
    $no_progress_time = $this->getMaximumBuildNoProgressTime();
    $message = sprintf(
      'Failed while running the build script; no build progress detected in %s seconds.',
      $no_progress_time
    );

    // Internal log to indicate how long the process was actually idle.
    if (isset($wip_context->buildProgressTimestamp)) {
      $no_progress_time = time() - $wip_context->buildProgressTimestamp;
    }
    $this->log(WipLogLevel::INFO, sprintf('%s Actual idle time: %d seconds.', $message, $no_progress_time));


    /** @var SshResultInterface $result */
    $processes = $this->getSshApi()->getSshProcesses($wip_context);
    $process = reset($processes);
    $logger = $this->getWipLog();
    if (!empty($process) && $process instanceof SshProcessInterface) {
      $process->forceFail($message, $logger);

      // This process completed; convert it to a result.
      /** @var SshResult $ssh_result */
      $ssh_result = $process->getResult($logger, FALSE);
      $ssh_api->addSshResult($ssh_result, $wip_context);
      $ssh_api->removeSshProcess($process, $wip_context, $logger);
    }
    $this->logBuildFailure($message);
  }

  /**
   * Retrieves the event that will be executed for the build.
   *
   * This will return 'build' unless overridden by the user's environment
   * variable PIPELINES_EVENT.
   *
   * @return string
   *   The event.
   */
  public function getEventToExecute() {
    $env_vars = $this->getUserEnvironmentVariables();
    return !empty($env_vars['PIPELINES_EVENT']) ? $env_vars['PIPELINES_EVENT'] : self::DEFAULT_BUILD_EVENT;
  }

  /**
   * Determines whether or not to display a message indicating no commit has happened.
   */
  public function commitInExecutor() {
    if (!$this->shouldCommitBuildArtifact()) {
      $this->log(
        WipLogLevel::INFO,
        sprintf('Note: The event "%s" will not commit to your Acquia Cloud repository.', $this->getEventToExecute()),
        TRUE
      );
    }
    return 'no_commit';
  }

  /**
   * Whether or not this build requires the artifact to be committed.
   *
   * @return bool
   *   TRUE if the artifact should be committed, otherwise FALSE.
   */
  public function shouldCommitBuildArtifact() {
    if ($this->getEventToExecute() == self::DEFAULT_BUILD_EVENT) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Ensure that the build user exists.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function ensureBuildUser(WipContextInterface $wip_context) {
    $ssh_api = $this->getSshApi();
    $ssh = $this->getSsh('Ensure the build user exists.', $this->getContainerEnvironment());
    $result = $ssh->execCommand(sprintf('id -u %s', escapeshellarg(self::BUILDSTEPS_UNIX_USER)));
    $ssh_api->setSshResult($result, $wip_context, $this->getWipLog());
  }

  /**
   * Ensures the VCS URIs have been set and if not tries to set them.
   *
   * This handles both the source URI and the deploy URI.
   *
   * @param bool $throw_exception
   *   Boolean indicating whether an exception should be thrown if there is
   *   an error getting the VCS URI.
   *
   * @throws \Acquia\Wip\Exception\AcquiaCloudApiException
   *   Thrown if there is an error getting the VCS URI and $throw_exception
   *   is set to true.
   */
  public function ensureVcsUri($throw_exception = FALSE) {
    $vcs_uri = NULL;
    try {
      $deploy_vcs_uri = $this->getDeployVcsUri();
      if (empty($deploy_vcs_uri)) {
        $vcs_uri = $this->fetchVcsUri();
        $this->setDeployVcsUri($vcs_uri);
      }
    } catch (AcquiaCloudApiException $e) {
      $this->log(WipLogLevel::ERROR, sprintf('Failed to find the deploy VCS URI: %s', $e->getMessage()));
      if ($throw_exception) {
        throw $e;
      }
    }
    try {
      $source_vcs_uri = $this->getSourceVcsUri();
      if (empty($source_vcs_uri)) {
        if (empty($vcs_uri)) {
          $vcs_uri = $this->fetchVcsUri();
        }
        $this->setSourceVcsUri($vcs_uri);
      }
    } catch (AcquiaCloudApiException $e) {
      $this->log(WipLogLevel::ERROR, sprintf('Failed to find the source VCS URI: %s', $e->getMessage()));
      if ($throw_exception) {
        throw $e;
      }
    }
  }

  /**
   * Uses the Cloud API to fetch the VCS URI.
   *
   * @return string
   *   The VCS URI.
   *
   * @throws AcquiaCloudApiException
   *   If the API call fails.
   */
  private function fetchVcsUri() {
    if (empty($this->environment)) {
      $parameter_document = $this->getParameterDocument();
      $this->environment = $this->extractEnvironment($parameter_document);
    }
    $cloud = new AcquiaCloud($this->environment, $this->getWipLog());
    $site_group = $this->environment->getFullyQualifiedSitegroup();
    $site_response = $cloud->getSiteRecord($site_group);
    if ($site_response->isSuccess()) {
      $result = $site_response->getData()->getVcsUrl();
    } else {
      $response_message = $site_response->getExitMessage();
      $response_code = $site_response->getExitCode();

      $log_message = sprintf(
        'Failed to get site record for site "%s". Response code: %d.',
        $site_group,
        $response_code
      );
      if (!empty($response_message)) {
        $log_message = sprintf('%s Reason: "%s".', $log_message, $response_message);
      }
      // Log the detailed message for debugging.
      $this->log(WipLogLevel::ERROR, $log_message);

      throw new AcquiaCloudApiException($site_response);
    }
    return $result;
  }

  /**
   * Verifies the source and deploy VCS URIs have been set.
   *
   * @return string
   *   'success' - The source and deploy VCS URIs have been set.
   *   'fail' - At least one of the VCS URIs has not been set.
   */
  public function verifyVcsUri() {
    $result = 'success';
    $source_vcs_uri = $this->getSourceVcsUri();
    if (empty($source_vcs_uri)) {
      $result = 'fail';
    }
    $deploy_vcs_uri = $this->getDeployVcsUri();
    if (empty($deploy_vcs_uri)) {
      $result = 'fail';
    }
    return $result;
  }

  /**
   * Establishes the workspace key pair.
   *
   * If keys have not been provided, this method will create a key and register
   * it using the Cloud API. Otherwise all keys that have been provided will be
   * copied into the container.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function establishWorkspaceSshKey(WipContextInterface $wip_context) {
    $logger = $this->getWipLog();
    $ssh_api = $this->getSshApi();
    $ssh_api->clearSshResults($wip_context, $logger);
    $cloud_api = $this->getAcquiaCloudApi();
    $cloud_api->clearAcquiaCloudResults($wip_context, $logger);

    // Create the key pair if it doesn't already exist. We can retry this method
    // and it will only try to register the key with Cloud again if the key
    // already exists.
    $container_environment = $this->getContainerEnvironment();

    // Keep track of all keys written into the container.
    $keys_copied = array();

    $has_deploy_key = $this->gitKeys->hasKey(self::DEPLOY_KEY_NAME);
    $has_source_key = $this->gitKeys->hasKey(self::SOURCE_KEY_NAME);
    if (!$has_deploy_key || !$has_source_key) {
      // Create a new SSH key in the container and register it using the
      // Cloud API.
      $public_key = $this->sshKeyGen($container_environment);
      $private_key_file = basename($this->getPrivateKeyPath());
      $keys_copied[] = $private_key_file;
      if (!$has_deploy_key && $this->deployRequiresWrapper()) {
        // Note that the private SSH key has already been written, so no need
        // to add it here.
        $git_key = new GitKey(
          self::DEPLOY_KEY_NAME,
          $private_key_file,
          self::DEPLOY_WRAPPER_FILENAME
        );
        $this->gitKeys->addKey($git_key);
      }
      if (!$has_source_key && $this->sourceRequiresWrapper()) {
        // Note that the private SSH key has already been written, so no need
        // to add it here.
        $git_key = new GitKey(
          self::SOURCE_KEY_NAME,
          $private_key_file,
          self::DEPLOY_WRAPPER_FILENAME
        );
        $this->gitKeys->addKey($git_key);
      }
      // Add the new key to hosting using the Cloud API.
      $cloud_api = $this->getAcquiaCloudApi();
      $cloud = new AcquiaCloud($this->environment, $logger);
      $process = $cloud->addSshKey(
        $this->getSshKeyName(),
        $public_key,
        FALSE,
        TRUE
      );
      $cloud_api->setAcquiaCloudProcess($process, $wip_context, $logger);
    }
    // Copy all of the keys into the container.
    $file_commands = $this->getFileCommands($container_environment);
    foreach ($this->gitKeys->getAllKeys() as $git_key) {
      $filename = $git_key->getPrivateKeyFilename();
      if (!in_array($filename, $keys_copied)) {
        $private_key = $git_key->getKey();
        $private_key_path = $this->getKeyPath($git_key->getPrivateKeyFilename());
        $exists_result = $file_commands->exists(
          $private_key_path,
          sprintf(
            "Check to see if the SSH key '%s' [%s] already exists.",
            $private_key_path,
            $git_key->getName()
          )
        )->exec();
        if ($exists_result->isSuccess()) {
          $this->log(
            WipLogLevel::DEBUG,
            sprintf(
              'The SSH key "%s" has already been written.',
              $private_key_path
            )
          );
          $ssh_api->addSshResult($exists_result, $wip_context);
          continue;
        } elseif (empty($private_key)) {
          $this->log(
            WipLogLevel::ERROR,
            sprintf(
              'No private key associated with git key "%s". Key was not written.',
              $git_key->getName()
            )
          );
          continue;
        }

        $this->copyContentsToContainer(
          $wip_context,
          $private_key,
          $private_key_path,
          0600,
          sprintf(
            "Copy key '%s' [%s] into the container.",
            $private_key_path,
            $git_key->getName()
          )
        );
        $keys_copied[] = $filename;

        // Add the identity to ~/.ssh/config.
        $key_directory = sprintf('/home/%s/.ssh', self::BUILDSTEPS_UNIX_USER);
        $ssh_config_filepath = sprintf('%s/config', $key_directory);
        $identity = sprintf("\nIdentityFile %s", $private_key_path);
        $result = $file_commands->writeFile($identity, $ssh_config_filepath, TRUE)
          ->exec();
        $ssh_api->addSshResult($result, $wip_context);
        if (!$result->isSuccess()) {
          return;
        }

        $this->log(WipLogLevel::TRACE, sprintf('Added %s to the SSH config', $private_key_path));
      }
    }
  }

  /**
   * Gets the temporary git key's private key path.
   *
   * @param string $user
   *   The user.
   *
   * @return string
   *   The private key path.
   */
  private function getPrivateKeyPath($user = 'local') {
    return sprintf("/home/%s/.ssh/%s", $user, self::DEPLOY_SSH_PRIVATE_KEY_FILENAME);
  }

  /**
   * Gets the temporary git key's public key path.
   *
   * @param string $user
   *   The user.
   *
   * @return string
   *   The public key path.
   */
  public function getPublicKeyPath($user = 'local') {
    return $this->getPrivateKeyPath($user) . '.pub';
  }

  /**
   * Generates an SSH key.
   *
   * @param EnvironmentInterface $environment
   *   The Environment used to create the SSH key.
   * @param string $comment
   *   Optional. The comment to use for the SSH key.
   *
   * @return string
   *   The public key that was created.
   *
   * @throws \Exception
   *   If the key could not be generated or the wrapper script could not be
   *   written.
   */
  private function sshKeyGen(EnvironmentInterface $environment, $comment = NULL) {
    $private_key_path = $this->getPrivateKeyPath();
    $public_key_path = $this->getPublicKeyPath();

    // Only generate a key if it doesn't already exist.
    if (!$this->getFileCommands($environment)->exists($private_key_path)->exec()->isSuccess()) {
      // Execute ssh-keygen to generate the keys.
      $options = array(
        '-q', // Silence ssh-keygen output.
        sprintf('-f %s', escapeshellarg($private_key_path)), // Key filepath.
        '-b 4096', // Key bit length.
        "-N ''", // No passphrase.
      );
      if ($comment !== NULL) {
        $options[] = sprintf('-C %s', escapeshellarg($comment)); // Key comment.
      }
      $command = sprintf('/usr/bin/ssh-keygen %s 2> /dev/null', implode(' ', $options));
      $ssh = $this->getSsh('Create a workspace key.', $environment);
      $create_key_result = $ssh->execCommand($command);
      if (!$create_key_result->isSuccess()) {
        throw new \Exception(sprintf(
          'Could not generate SSH key on %s, command: "%s"',
          $environment->getCurrentServer(),
          $command
        ));
      }
    }

    // Return the public key.
    $command = sprintf('cat %s', escapeshellarg($public_key_path));
    $ssh = $this->getSsh('Fetch the public key.', $environment)
      ->setSecure(TRUE);
    $cat_result = $ssh->execCommand($command);
    if (!$cat_result->isSuccess()) {
      throw new \Exception(sprintf(
        'Could not read the public key %s on %s',
        $public_key_path,
        $environment->getCurrentServer()
      ));
    }
    return trim($cat_result->getStdout());
  }

  /**
   * Extract the SSH key from the Cloud response.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function extractSshKeyId(WipContextInterface $wip_context) {
    // The application private key has not been provided by the caller.
    $cloud_api = $this->getAcquiaCloudApi();
    $results = $cloud_api->getAcquiaCloudResults($wip_context);
    // Find the first task result that contains the SSH key ID.
    foreach ($results as $result) {
      if ($result->isSuccess() && $result instanceof AcquiaCloudTaskResult) {
        $data = $result->getData();
        if ($data instanceof AcquiaCloudAddSshKeyTaskInfo) {
          $key_id = $data->getKeyId();
          if (NULL !== $key_id) {
            $this->setSshKeyId($key_id);
            break;
          }
        }
      }
    }
  }

  /**
   * Releases the workspace key pair from Hosting.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function releaseWorkspaceSshKey(WipContextInterface $wip_context) {
    $ssh_key_id = $this->getSshKeyId();
    if (NULL === $ssh_key_id) {
      // The application private key has been provided by the caller and must
      // not be removed.
    } else {
      // The SSH key was generated in the container and must be removed.
      $logger = $this->getWipLog();
      $cloud_api = $this->getAcquiaCloudApi();
      $cloud_api->clearAcquiaCloudProcesses($wip_context, $logger);
      $cloud_api->clearAcquiaCloudResults($wip_context, $logger);
      $cloud = new AcquiaCloud($this->environment, $logger);
      $process = $cloud->deleteSshKey($ssh_key_id);
      $cloud_api->setAcquiaCloudProcess($process, $wip_context, $logger);
    }
  }

  /**
   * Creates all necessary git wrapper scripts.
   *
   * These scripts will be used for git commands to explicitly use a particular
   * SSH key for a given command to prevent a failure that occurs if too many
   * keys exist in the ~/.ssh directory.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function createGitWrapper(WipContextInterface $wip_context) {
    $ssh_api = $this->getSshApi();
    $logger = $this->getWipLog();
    $ssh_api->clearSshResults($wip_context, $logger);
    $wrappers_created = array();
    foreach ($this->gitKeys->getAllKeys() as $git_key) {
      $wrapper_filename = $git_key->getWrapperFilename();
      if (!in_array($wrapper_filename, $wrappers_created)) {
        $this->createWrapper($git_key, $wip_context);
        $wrappers_created[] = $wrapper_filename;
      }
    }
  }

  /**
   * Creates a git wrapper that uses a specific SSH key.
   *
   * @param GitKey $git_key
   *   The GitKey instance that indicates the appropriate key, wrapper, and key
   *   filename.
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   * @param string $user
   *   Optional. The user.
   */
  private function createWrapper(
    GitKey $git_key,
    WipContextInterface $wip_context,
    $user = self::BUILDSTEPS_UNIX_USER
  ) {
    $ssh_api = $this->getSshApi();
    $container_environment = $this->getContainerEnvironment();
    $file = $this->getFileCommands($container_environment);
    $private_key_path = $this->getKeyPath($git_key->getPrivateKeyFilename(), $user);
    $wrapper = <<<EOT
#!/bin/bash
ssh -i $private_key_path -o StrictHostKeyChecking=no "$@"
EOT;
    if ($git_key->getWrapperFilename() == self::DEPLOY_WRAPPER_FILENAME) {
      $wrapper = <<<EOT
#!/bin/bash
if [ -z "\$PIPELINE_SSH_KEY" ]; then
# if PIPELINE_SSH_KEY is not specified, run ssh using default keyfile
ssh -o StrictHostKeyChecking=no "$@"
else
ssh -i "\$PIPELINE_SSH_KEY" -o StrictHostKeyChecking=no "$@"
fi
EOT;
    }
    $wrapper_path = sprintf('/home/%s/%s', $user, $git_key->getWrapperFilename());
    $this->log(
      WipLogLevel::ALERT,
      sprintf(
        "Creating SSH wrapper script '%s' for SSH key '%s'",
        $wrapper_path,
        $git_key->getPrivateKeyFilename()
      )
    );
    $result = $file->writeFile($wrapper, $wrapper_path)->exec();
    $ssh_api->addSshResult($result, $wip_context);
    if ($result->isSuccess()) {
      $result = $file->chmod(0755, $wrapper_path)->exec();
      $ssh_api->addSshResult($result, $wip_context);
    }
  }

  /**
   * Gets the build file in JSON format.
   *
   * This Wip object is not responsible for any validation. If the document
   * does not conform to the specification the json export will fail. Otherwise
   * this will provide in an internal format of that document so any secure
   * values can be replaced.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function exportBuildFileAsJson(WipContextInterface $wip_context) {
    $wip_context->setReportOnNoProgress(TRUE);
    $ssh_api = $this->getSshApi();
    $environment = $this->getContainerEnvironment();
    $ssh = $this->getSsh('Get the build file in JSON form.', $environment);
    $ports = $this->getContainer()->getPortMappings();

    // The export command will exit with 2 if there is a warning.
    $ssh->addSuccessExitCode(2);
    $options = array(
      '--source-vcs-uri=' . $this->getSourceVcsUri(),
      '--deploy-vcs-uri=' . $this->getDeployVcsUri(),
      '--source-vcs-path=' . $this->getBuildVcsPath(),
      '--deploy-vcs-path=' . $this->getDeployVcsPath(),
      '--port-8007=' . $ports[8007],
      '--port-22=' . $ports[22],
    );
    if (!empty($this->getGithubMergeRef())) {
      $options[] = '--merge-ref=' . $this->getGithubMergeRef();
    }
    $command = sprintf(
      '%s export %s',
      $this->getBuildstepsToolPath(),
      implode(' ', $options)
    );
    $ssh_process = $ssh->setSecure(TRUE)
      ->execAsyncCommand($command);
    $ssh_api->setSshProcess($ssh_process, $wip_context, $this->getWipLog());
  }

  /**
   * Writes user environment variables to the container.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   *
   * @return SshResultInterface
   *   The SshResult instance representing the scp call.
   */
  private function addUserVariables(WipContextInterface $wip_context) {
    $user_environment_variables = $this->getUserEnvironmentVariables();
    $user_variables_string = '';
    foreach ($user_environment_variables as $key => $value) {
      // Create an empty string for NULL values.
      if ($value === NULL || strlen($value) === 0) {
        $user_variables_string .= sprintf('export %s="";', $key);
      } else {
        if ($value{0} === '"' || $value{0} === "'") {
          // The value is already in quotes.
          $user_variables_string .= sprintf('export %s=%s;', $key, $value);
        } else {
          $user_variables_string .= sprintf('export %s="%s";', $key, $value);
        }
      }
    }
    // Send to the container we will source them there to prevent echoing.
    $json_destination_path = sprintf('%s/.variables.script', $this->getContainerEnvironment()->getWorkingDir());
    return $this->copyContentsToContainer(
      $wip_context,
      $user_variables_string,
      $json_destination_path
    );
  }

  /**
   * Writes the decrypted build file.
   *
   * The file is represented as an object and may have secure variables or SSH
   * keys. As part of writing the build file these elements must be converted.
   * Note that this is all done in a single step to prevent secure variables
   * from being recorded in the database in clear text.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function writeUserEnvironmentVars(WipContextInterface $wip_context) {
    $logger = $this->getWipLog();
    $ssh_api = $this->getSshApi();
    $ssh_api->clearSshResults($wip_context, $logger);

    $scp_result = $this->addUserVariables($wip_context);
    if (!$scp_result->isSuccess()) {
      $this->log(
        WipLogLevel::ERROR,
        sprintf(
          'Failed to copy the user environment variables build document to the container: %s',
          $scp_result->getSecureStderr()
        )
      );
    }
  }

  /**
   * Called when the JSON export failed.
   */
  public function jsonExportFailed() {
    $wip_context = $this->getIterator()->getWipContext('jsonExportFailed');
    $results = $this->getSshApi()->getSshResults($wip_context);
    /** @var SshResultInterface $result */
    $result = reset($results);
    $error = $result->getStderr();
    $detailed_message = $short_message = 'Failed to parse the build file.';
    // Provide the parse error if one exists and display to the user.
    if (!empty($error)) {
      $detailed_message = sprintf('Failed to parse the build file. %s', $result->getStderr());
    }

    // Create a user-facing log entry to make it very clear.
    $this->log(
      WipLogLevel::ERROR,
      $detailed_message,
      TRUE
    );

    // Flag JSON export errors as a user error and provide a meaningful exit message.
    $this->setExitCode(IteratorStatus::ERROR_USER);
    $this->log(WipLogLevel::FATAL, sprintf("Failed to export the build file to JSON format."));
    $this->setExitMessage(new ExitMessage($short_message, WipLogLevel::FATAL, $detailed_message));
  }

  /**
   * Reports job metadata back to the pipeline-api.
   */
  public function reportPipelinesMetaData() {
    $auth_token = $this->getPipelineAuthToken();
    if (!empty($auth_token)) {
      $uri = sprintf('/api/v1/ci/jobs/%s/metadata', $this->getPipelineJobId());

      foreach ($this->getPipelinesMetaData() as $key => $value) {
        $data = new \stdClass();
        $data->applications = [$this->getPipelineApplicationId()];
        $data->key = $key;
        $data->value = $value;
        $data->auth_token = $auth_token;
        try {
          $this->pipelineRequest('PUT', $uri, $data, $auth_token);
        } catch (\Exception $e) {
          $this->log(WipLogLevel::ERROR, sprintf('Pipelines job metadata failed to send: %s', $e->getMessage()));
        }
      }
    }
  }

  /**
   * Retrieves any metadata that BuildSteps wishes to report.
   *
   * @return array
   *   A list of key value pairs.
   */
  public function getPipelinesMetaData() {
    $meta = [];

    foreach ($this->getContainer()->getPortMappings() as $port => $mapping) {
      $meta['container_port_' . $port] = $mapping;
    }

    return $meta;
  }

  /**
   * Builds the workspace using the build document.
   *
   * @todo This will fail if the 'docroot' directory already exists.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function executeBuildScript(WipContextInterface $wip_context) {
    $wip_context->setReportOnNoProgress(TRUE);
    $ssh_api = $this->getSshApi();
    $wip_log = $this->getWipLog();
    $ssh_api->clearSshResults($wip_context, $wip_log);

    // For now we're building into the same workspace as the source.
    $ssh = $this->getSsh(self::BUILD_PROCESS_DESCRIPTION, $this->getContainerEnvironment());
    $options = array(
      sprintf('--vcs-path=%s', escapeshellarg($this->getBuildVcsPath())),
      sprintf('--deploy-vcs-path=%s', escapeshellarg($this->getDeployVcsPath())),
      sprintf('--event=%s', escapeshellarg($this->getEventToExecute())),
      sprintf('--application-private-key=%s', escapeshellarg($this->getApplicationPrivateKey())),
    );
    $command = sprintf(
      '%s local-build %s',
      $this->getBuildstepsToolPath(),
      implode(' ', $options)
    );
    $process = $ssh->setSecure(TRUE)
      ->execAsyncCommand($command);
    $ssh_api->setSshProcess($process, $wip_context, $this->getWipLog());
  }

  /**
   * Called when no progress is detected during the build.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function noBuildProgress(WipContextInterface $wip_context) {
    // Clear the transition count so that if the process has large delays of no
    // output several times during the build, it doesn't cause the build to
    // fail. Only a condition in which no progress is detected over a 5 minute
    // period would cause it to fail; not 3 instances of 4 minute delays
    // between output.
    $this->getIterator()->clearTransitionCount('noBuildProgress', 'no_progress');

    // Record the initial time that no progress was detected.
    $wip_context->buildProgressTimestamp = time();
  }

  /**
   * Returns the results of container processes associated with the context.
   *
   * This can be used to process any combination of processes and results from
   * the SSH service, the Wip Task service, the Acquia Cloud service, and the
   * Container service. If the container status has changed this will be
   * reflected in the result.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface instance where the results and/or processes are
   *   stored.
   *
   * @return string
   *   'success' - All tasks were completed successfully.
   *   'wait' - One or more processes are still running.
   *   'uninitialized' - No results or processes have been added.
   *   'fail' - At least one task failed.
   *   'ssh_fail' - An Ssh command failed to connect.
   *   'ready' - The container is up and ready to receive the task.
   *   'running' - The container process is still running.
   *   'no_progress' - An call is still running but no progress detected.
   *   'no_information' - Could not retrieve information about the running container.
   *   'terminated' - The container has terminated.
   *   'no_progress_fail' - The build has failed due to no progress.
   */
  public function checkBuildProgress(WipContextInterface $wip_context) {
    $result = $this->checkContainerResultStatus($wip_context);

    if ($result === 'no_progress') {
      // Verify the maximum allowable time with no progress has not been
      // exceeded.
      $max_idle_duration = $this->getMaximumBuildNoProgressTime();
      if (!isset($wip_context->buildProgressTimestamp)) {
        $wip_context->buildProgressTimestamp = time();
      }
      $no_progress_start_time = $wip_context->buildProgressTimestamp;
      if (time() - $no_progress_start_time > $max_idle_duration) {
        $result = 'no_progress_fail';
      }
    }
    return $result;
  }

  /**
   * Gets the maximum allowable time with no build progress before failure.
   *
   * @return int
   *   The maximum duration measured in seconds.
   */
  private function getMaximumBuildNoProgressTime() {
    return WipFactory::getInt('$acquia.pipeline.buildsteps.max_build_idle_time', 600);
  }

  /**
   * The state in the FSM that indicates a successful run.
   */
  public function success() {
    $this->setExitCode(IteratorStatus::OK);
    $this->setExitMessage(new ExitMessage('Successfully completed.'));
  }

  /**
   * Called when the container is terminated.
   */
  public function containerTerminated() {
    // The container may have been terminated because a constraint was violated
    // by the user's job. This is handled by sending a signal prior to
    // terminating the container. Look for that signal.
    $termination_signal = $this->getContainerTerminatedSignal();
    if (NULL !== $termination_signal) {
      $message = $termination_signal->getLog();
      $exit_message = $termination_signal->getExitLog();
      $this->setExitMessage(new ExitMessage($exit_message, WipLogLevel::FATAL, $message));
      $this->setExitCode(IteratorStatus::ERROR_USER);
      $this->getSignalStore()->consume($termination_signal);
    } elseif ($this->getContainer()->getContainerNextStatus() === ContainerInterface::STOPPED) {
      $exit_message = 'The container terminated unexpectedly.';
      $log_message = sprintf('The container terminated while building %s.', $this->getBuildVcsPath());
      $this->setExitMessage(new ExitMessage($exit_message, WipLogLevel::FATAL, $log_message));
      $this->setExitCode(IteratorStatus::ERROR_SYSTEM);
    }
  }

  /**
   * Handles the failure state.
   *
   * @param WipContextInterface $wip_context
   *   The current WIP context.
   * @param \Exception|null $exception
   *   The received exception.
   */
  public function failure(WipContextInterface $wip_context, \Exception $exception = NULL) {
    $exit_message = $this->getExitMessage();
    if (empty($exit_message)) {
      $exit_code = $this->getExitCode();
      if ($exit_code === IteratorStatus::OK) {
        $this->setExitMessage(new ExitMessage('Failed to complete the build task.', WipLogLevel::FATAL));
      }
    }
    parent::failure($wip_context, $exception);
  }

  /**
   * {@inheritdoc}
   */
  public function terminate(WipContextInterface $wip_context) {
    $message = sprintf(
      'The request to terminate Pipelines job %s has been processed.',
      $this->getPipelineJobId()
    );
    $this->setExitMessage(new ExitMessage($message, WipLogLevel::FATAL, $message));
    $this->setExitCode(IteratorStatus::TERMINATED);
  }

  /**
   * Initializes the local environment used for running SSH commands locally.
   */
  private function initializeLocalEnvironment() {
    $local = Environment::getRuntimeEnvironment();
    $local->setServers(array('localhost'));
    $local->selectNextServer();
    $this->local = $local;
  }

  /**
   * Checks for the existence of the asymmetric key.
   *
   * The asymmetric key is needed to decrypt the symmetric key that was used to
   * encrypt confidential data in the build document. The asymmetric private key
   * is located on the WIP service hosting site and passed into this task if it
   * exists when it is started.
   *
   * @return bool
   *   Whether the asymmetric key exists.
   */
  public function hasAsymmetricKey() {
    $private_key = $this->getAsymmetricPrivateKey();
    return !empty($private_key);
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions($options) {
    parent::setOptions($options);
    if (!empty($options->vcsPath)) {
      $vcs_path = $options->vcsPath;
      $this->setBuildVcsPath($vcs_path);
    }
    if (!empty($options->deployVcsPath)) {
      $deploy_vcs_path = $options->deployVcsPath;
      $this->setDeployVcsPath($deploy_vcs_path);
    }

    // Set up the source and destination URIs.
    if (!empty($options->deployVcsUri)) {
      $this->setDeployVcsUri($options->deployVcsUri);
    }
    if (!empty($options->sourceVcsUri)) {
      $this->setSourceVcsUri($options->sourceVcsUri);
    }
    if (!empty($options->vcsUri)) {
      $source_uri = $this->getSourceVcsUri();
      if (empty($source_uri)) {
        $this->setSourceVcsUri($options->vcsUri);
      }
      $deploy_uri = $this->getDeployVcsUri();
      if (empty($deploy_uri)) {
        $this->setDeployVcsUri($options->vcsUri);
      }
    }

    if (!empty($options->privateKey)) {
      $this->setAsymmetricPrivateKey($options->privateKey);
      unset($options->privateKey);
    }

    if (!empty($options->privateKeyName)) {
      $this->setPrivateKeyName($options->privateKeyName);
    }

    // Set up private keys for the source and deploy git repositories.
    if (isset($options->useDeployKey)) {
      $this->setDeployRequiresWrapper(boolval($options->useDeployKey));
    }
    if (isset($options->useSourceKey)) {
      $this->setSourceRequiresWrapper(boolval($options->useSourceKey));
    }
    if (isset($options->deployPrivateKey)) {
      if (empty($options->deployPrivateKey)) {
        // An empty key indicates an SSH key should not be used.
        $this->setDeployRequiresWrapper(FALSE);
      } else {
        $git_key = new GitKey(
          self::DEPLOY_KEY_NAME,
          self::DEPLOY_KEY_FILENAME,
          $this->deployWrapperFilename,
          $options->deployPrivateKey
        );
        $this->gitKeys->addKey($git_key);
      }
      unset($options->deployPrivateKey);
    }
    if (isset($options->sourcePrivateKey)) {
      if (empty($options->sourcePrivateKey)) {
        $this->setSourceRequiresWrapper(FALSE);
      } else {
        $git_key = new GitKey(
          self::SOURCE_KEY_NAME,
          self::SOURCE_KEY_FILENAME,
          $this->sourceWrapperFilename,
          $options->sourcePrivateKey
        );
        $this->gitKeys->addKey($git_key);
      }
      unset($options->sourcePrivateKey);
    }
    // If the source and/or deploy git private keys were not specified
    // individually, fall back to using the hosting private key for both.
    if (!empty($options->applicationPrivateKey)) {
      $this->setApplicationPrivateKey($options->applicationPrivateKey);
      if ($this->deployRequiresWrapper() && !$this->gitKeys->hasKey(self::DEPLOY_KEY_NAME)) {
        $git_key = new GitKey(
          self::DEPLOY_KEY_NAME,
          self::DEPLOY_SSH_PRIVATE_KEY_FILENAME,
          self::DEPLOY_WRAPPER_FILENAME,
          $options->applicationPrivateKey
        );
        $this->gitKeys->addKey($git_key);
      }

      // For now this is also used as the source key.
      if ($this->sourceRequiresWrapper() && !$this->gitKeys->hasKey(self::SOURCE_KEY_NAME)) {
        $git_key = new GitKey(
          self::SOURCE_KEY_NAME,
          self::DEPLOY_SSH_PRIVATE_KEY_FILENAME,
          self::DEPLOY_WRAPPER_FILENAME,
          $options->applicationPrivateKey
        );
        $this->gitKeys->addKey($git_key);
      }
      // The options are stored in BasicWip. Remove this key for security.
      unset($options->applicationPrivateKey);
    }
    if (!empty($options->environmentVariables)) {
      $this->setUserEnvironmentVariables($options->environmentVariables);
      unset($options->environmentVariables);
    }
  }

  /**
   * Returns the unique key name.
   *
   * This method generates the unique key name lazily at its first access.
   *
   * @return string
   *   The SSH key name.
   */
  public function getSshKeyName() {
    if (NULL === $this->sshKeyName) {
      // Generate unique key.
      $key_name = $this->generateUniqueSshKeyName();
      $this->setSshKeyName($key_name);
    }
    return $this->sshKeyName;
  }

  /**
   * Sets the SSH key name.
   *
   * @param string $ssh_key_name
   *   The SSH key name to set.
   */
  private function setSshKeyName($ssh_key_name) {
    if (NULL !== $ssh_key_name && !is_string($ssh_key_name)) {
      throw new \InvalidArgumentException('The SSH key name must be a string, or NULL.');
    }
    $this->sshKeyName = $ssh_key_name;
  }

  /**
   * Generates a unique SSH key.
   *
   * Example: BuildSteps-1234567890-realm.sitegroupName-wl4uhi5l48
   *
   * @return string
   *   The generated SSH key name.
   */
  protected function generateUniqueSshKeyName() {
    $timestamp = time();
    $environment = $this->environment;
    $random_hash = md5(mt_rand());
    $realm = $environment->getRealm();
    if (empty($realm)) {
      $environment_name = $environment->getSitegroup();
    } else {
      $environment_name = sprintf('%s.%s', $environment->getRealm(), $environment->getSitegroup());
    }
    $new_key = sprintf('%s-%s-%s-%s', self::SSH_KEY_NAME_PREFIX, $timestamp, $environment_name, $random_hash);
    return $new_key;
  }

  /**
   * Get the SSH key ID.
   *
   * @return int|null
   *   The ID of the SSH key.
   */
  public function getSshKeyId() {
    return $this->sshKeyId;
  }

  /**
   * Set the SSH key ID.
   *
   * @param int|null $ssh_key_id
   *   The ID of the SSH key.
   */
  private function setSshKeyId($ssh_key_id) {
    if (NULL !== $ssh_key_id && !is_int($ssh_key_id)) {
      throw new \InvalidArgumentException('The SSH key ID must be an integer, or NULL.');
    }
    $this->sshKeyId = $ssh_key_id;
  }

  /**
   * Called when the container has stopped.
   */
  public function containerStopped() {
    if ($this->getReleaseContainerUponCompletion()) {
      $this->log(WipLogLevel::INFO, 'The container stopped successfully.');
    } else {
      $this->log(WipLogLevel::WARN, 'The container was not released due to configuration.');
    }
  }

  /**
   * Called when the container failed to stop.
   */
  public function containerStopFailed() {
    $message = 'Failed to stop the container.';
    if ($this->getExitCode() === IteratorStatus::OK) {
      $this->setExitCode(IteratorStatus::WARNING);
      $this->setExitMessage(new ExitMessage($message, WipLogLevel::ERROR, $message));
    } else {
      $this->log(WipLogLevel::ERROR, $message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function finish(WipContextInterface $wip_context) {
    parent::finish($wip_context);

    // Log container use data if available.
    $signal = $this->getContainerDataSignal();
    if ($signal) {
      $time = sprintf("%d seconds", $signal->getTime());
      $disk = sprintf("%.1fGB", $signal->getWorkloadDiskUse());
      $container_disk = sprintf("%.1fGB", $signal->getContainerDiskUse());
      $message = <<<EOT
--------- Container use ---------
Workload time: {$time}
Workload disk use: {$disk}
Container disk use: {$container_disk}
EOT;
      $this->log(WipLogLevel::ALERT, $message);
      $this->getSignalStore()->consume($signal);
    }
    $build_process = $this->getIncompleteBuildProcess();
    if ($build_process && $this->processIncompleteBuild($build_process)) {
      $this->logBuildInterruptedMessage();
    }
  }

  /**
   * Logs the build output after the build was interrupted.
   */
  private function logBuildInterruptedMessage() {
    $wip_context = $this->getIterator()->getWipContext('executeBuildScript');
    $results = $this->getSshApi()->getSshResults($wip_context);
    /** @var SshResultInterface $result */
    $result = reset($results);

    if (!empty($result)) {
      $error = $result->getStderr();
      // Strip the Symphony exception since that repeats the stdout and
      // includes formatting that will not appear correctly.
      if (preg_match(
        '/(.*)\[Symfony\\\Component\\\Process\\\Exception\\\ProcessFailedException\].*/s',
        $error,
        $matches
      ) === 1 && count($matches) > 1) {
        $error = $matches[1];
      }
      $detailed_message = sprintf(
        "%s\nSTDOUT:\n%s\nSTDERR:\n%s",
        'The build has been interrupted.',
        $result->getStdout(),
        $error
      );
      $this->log(WipLogLevel::ERROR, $detailed_message, TRUE);
    }
  }

  /**
   * Changes the specified build process into an SshResult and logs a failure.
   *
   * @param SshProcessInterface $process
   *   The build process.
   *
   * @return bool
   *   TRUE if the process has been converted into a result; FALSE otherwise.
   */
  private function processIncompleteBuild(SshProcessInterface $process) {
    $result = FALSE;
    $wip_context = $this->getIterator()->getWipContext('executeBuildScript');

    $signal_store = $this->getSignalStore();
    $signals = $signal_store->loadAllActive($this->getId());
    foreach ($signals as $signal) {
      // Unprocessed signals.
      if (!($signal instanceof SshCompleteSignal)) {
        continue;
      }
      $signal_data = $signal->getData();
      if ($process->getPid() == $signal_data->pid) {
        // Found the completion signal associated with the process. Since this
        // process did not complete gracefully, the signal will not contain all
        // of the expected fields. Populate the signal based on the process and
        // convert it into a result.
        $signal->setStartTime($process->getStartTime());
        $signal_data->startTime = $process->getStartTime();
        $signal_data->server = $process->getEnvironment()->getCurrentServer();
        $signal->setExitCode($signal_data->result->exitCode);
        $signal->setExitMessage('The build was interrupted.');

        // Convert the process and signal into a result.
        if ($this->getSshApi()->processCompletionSignal($signal, $wip_context, $this->getWipLog())) {
          $result = TRUE;
        }

        // Finally, consume the signal so it won't be used again.
        $signal_store->consume($signal);
      }
    }
    return $result;
  }

  /**
   * Gets the process associated with an incomplete build, if it exists.
   *
   * @return \Acquia\Wip\Ssh\SshProcessInterface
   *   The SshProcessInterface instance representing the incomplete build or
   *   NULL if the build did complete.
   */
  private function getIncompleteBuildProcess() {
    $result = NULL;
    $ssh_api = $this->getSshApi();
    $wip_context = $this->getIterator()->getWipContext('executeBuildScript');
    $processes = $ssh_api->getSshProcesses($wip_context);
    foreach ($processes as $process) {
      if ($process->getDescription() === self::BUILD_PROCESS_DESCRIPTION) {
        $result = $process;
        break;
      }
    }
    return $result;
  }

  /**
   * Gets the path to the buildsteps tool inside the container.
   *
   * @return string
   *   The full path to the buildsteps tool within the container.
   */
  public function getBuildstepsToolPath() {
    return sprintf('/home/%s/bin/wipstepexecutor', self::BUILDSTEPS_UNIX_USER);
  }

  /**
   * Copies contents into a file in the container.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   * @param string $contents
   *   The contents of the file.
   * @param string $destination
   *   The destination file path where the contents will be written.
   * @param int $file_permissions
   *   Optional. The file permissions that will be applied to the new file.
   * @param string $description
   *   Optional. The description that will be used when copying.
   *
   * @return SshResultInterface
   *   The SshResult instance representing the scp call.
   */
  private function copyContentsToContainer(
    WipContextInterface $wip_context,
    $contents,
    $destination,
    $file_permissions = 0644,
    $description = NULL
  ) {
    if (!is_string($contents)) {
      throw new \InvalidArgumentException('The "contents" parameter must be a string.');
    }
    if (!is_string($destination) || empty($destination)) {
      throw new \InvalidArgumentException('The "destination" parameter must be non-empty string.');
    }
    if (!is_int($file_permissions)) {
      throw new \InvalidArgumentException('The "file_permissions" parameter must be an integer.');
    }
    if ($file_permissions & 0777 !== $file_permissions || $file_permissions === 0) {
      throw new \InvalidArgumentException(
        'The "file_permissions" parameter must be a non-zero integer with a maximum value of 0777.'
      );
    }
    if ($description !== NULL && !is_string($description)) {
      throw new \InvalidArgumentException('The "description" parameter must be NULL or a string.');
    }

    // Initialize the result with a failed SshResult that will be replaced by
    // the result of the scp call.
    $result = $this->createSshFailureResult('Failed to call scp.');
    try {
      $temp_file = tempnam(sys_get_temp_dir(), 'buildsteps');
      if ($temp_file === FALSE) {
        throw new \RuntimeException('Failed to create a temporary file.');
      }
      if (FALSE === file_put_contents($temp_file, $contents)) {
        throw new \DomainException('Failed to write the contents to a temporary file.');
      }

      // SCP the file to the container. We will source it prior to building.
      $file_local = $this->getFileCommands($this->local);
      $result = $file_local->scp(
        $this->getContainerEnvironment(),
        $temp_file,
        $destination,
        NULL,
        self::BUILDSTEPS_UNIX_USER,
        $description
      )
        ->setSecure()
        ->exec();

      // Ensure that the file mode is correct.
      $file_container = $this->getFileCommands($this->getContainerEnvironment());
      $chmod_result = $file_container->chmod($file_permissions, $destination)->exec();

      $ssh_api = $this->getSshApi();
      $ssh_api->addSshResult($result, $wip_context);
      $ssh_api->addSshResult($chmod_result, $wip_context);
    } catch (\Exception $e) {
      $this->log(WipLogLevel::ERROR, $e->getMessage());
    } finally {
      // Delete the temp file.
      if (!empty($temp_file)) {
        @unlink($temp_file);
        if (file_exists($temp_file)) {
          $this->log(WipLogLevel::ERROR, sprintf('Failed to remove temporary file %s.', $temp_file));
        }
      }
    }
    return $result;
  }

  /**
   * Creates an SshResult instance that indicates failure.
   *
   * @param string $error_message
   *   The error message that will be associated with the SSH result.
   *
   * @return SshResultInterface
   *   The SshResult instance.
   */
  private function createSshFailureResult($error_message) {
    // Use a process ID that is out of range. This is 2^32.
    $pid = 4294967296;
    $result = new SshResult(SshResult::FORCE_FAIL_EXIT_CODE, '', $error_message);
    $result->setEnvironment($this->getContainerEnvironment());
    $result->setPid($pid);
    $result->setStartTime(time());
    return $result;
  }

  /**
   * Indicates whether the source git repository requires a wrapper script.
   *
   * @return bool
   *   TRUE if a wrapper is required; FALSE otherwise.
   */
  public function sourceRequiresWrapper() {
    return $this->sourceRequiresWrapper;
  }

  /**
   * Sets whether the source git repository requires a wrapper script.
   *
   * @param bool $required
   *   TRUE if a wrapper script is required; FALSE otherwise.
   */
  private function setSourceRequiresWrapper($required) {
    if (!is_bool($required)) {
      throw new \InvalidArgumentException('The "required" parameter must be a boolean value.');
    }
    $this->sourceRequiresWrapper = $required;
  }

  /**
   * Indicates whether the deploy git repository requires a wrapper script.
   *
   * @return bool
   *   TRUE if a wrapper is required; FALSE otherwise.
   */
  public function deployRequiresWrapper() {
    return $this->deployRequiresWrapper;
  }

  /**
   * Sets whether the deploy git repository requires a wrapper script.
   *
   * @param bool $required
   *   TRUE if a wrapper script is required; FALSE otherwise.
   */
  private function setDeployRequiresWrapper($required) {
    if (!is_bool($required)) {
      throw new \InvalidArgumentException('The "required" parameter must be a boolean value.');
    }
    $this->deployRequiresWrapper = $required;
  }

  /**
   * Gets the home directory of the user in the container.
   *
   * @param string $user
   *   Optional. The unix user.
   *
   * @return string
   *   The absolute path of the user's home directory.
   */
  private function getHomeDirectory($user = self::BUILDSTEPS_UNIX_USER) {
    return sprintf('/home/%s', $user);
  }

  /**
   * Gets the path for the specified SSH private key.
   *
   * @param string $filename
   *   The filename of the private key.
   * @param string $user
   *   Optional. The user name.
   *
   * @return string
   *   The absolute path for the SSH private key.
   */
  private function getKeyPath($filename, $user = self::BUILDSTEPS_UNIX_USER) {
    return sprintf('%s/.ssh/%s', $this->getHomeDirectory($user), $filename);
  }

  /**
   * Updates to version 6.
   *
   * @param WipUpdateCoordinatorInterface $coordinator
   *   The update coordinator.
   */
  public function updateBuildStepsNg6(WipUpdateCoordinatorInterface $coordinator) {
    switch ($coordinator->getCurrentState()) {
      case 'createWorkingDirectory':
      case 'cloneWorkspace':
      case 'verifyBuildPathExists':
      case 'addGitRemote':
      case 'forceCheckout':
      case 'verifyBuildFileExists':
      case 'cloneDeployWorkspace':
      case 'verifyDeployBranchExists':
      case 'checkoutDeployBranch':
      case 'createDeployBranch':
      case 'exportBuildFileAsJson':
      case 'writeDecryptedBuildFile':
      case 'writeSshKeys':
        $new_state = 'verifyDeployUri';
        break;

      case 'cloneWorkspaceFailed':
        $new_state = 'cloneSourceWorkspaceFailed';
        break;

      case 'buildFileMissing':
        $new_state = 'buildFileIsMissing';
        break;

      case 'forcePushWorkspace':
        $new_state = 'pushWorkspace';
        break;

      default:
        $new_state = $coordinator->getCurrentState();
    }

    if ($new_state !== $coordinator->getCurrentState()) {
      $coordinator->setNewState($new_state);
      $coordinator->resetAllCounters();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function addContainerOverrides(ContainerInterface $container) {
    parent::addContainerOverrides($container);

    $user_id = $this->getUuid();
    $container->addContainerOverride('SEGMENT_USER_ID', $user_id);

    try {
      $application_id = $this->getPipelineApplicationId();
    } catch (\DomainException $e) {
      $application_id = 'unknown';
      $this->log(
        WipLogLevel::WARN,
        sprintf('%s. SEGMENT_APPLICATION_ID will be set to "%s".', $e->getMessage(), $application_id)
      );
    }
    $container->addContainerOverride('SEGMENT_APPLICATION_ID', $application_id);

    // Add flag to keep container alive if requested by the build.
    if ($this->keepProcessAlive()) {
      $container->addContainerOverride('PIPELINE_PROCESS_KEEP_ALIVE', 'TRUE');
    }
    $container->addContainerOverride('PIPELINES_CLOUD_REALM', $this->environment->getRealm());
    $container->addContainerOverride('PIPELINES_CLOUD_SITE', $this->environment->getSitegroup());
    if ($this->encryptedVariablesAvailable()) {
      $container->addContainerOverride('PIPELINES_ENCRYPTED_VARIABLES_AVAILABLE', 'TRUE');
    }

    // Set GIT_SSH to git_workspace_wrapper script.
    $wrapper_path = sprintf('/home/%s/%s', self::BUILDSTEPS_UNIX_USER, self::DEPLOY_WRAPPER_FILENAME);
    $container->addContainerOverride('PIPELINES_GIT_SSH', $wrapper_path);

    // These variables will be passed to the user-space.
    $container->addContainerOverride('INHERIT_VARIABLES', 'SEGMENT_PROJECT_KEY,SEGMENT_USER_ID,SEGMENT_APPLICATION_ID');
  }

  /**
   * Called for errors that constitute a system failure.
   *
   * This method identifies the problem area and provides an internal facing
   * log message that is specific to the failure as well as an external facing
   * exit message. the exit code is always set to indicate a system error.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   * @param object[] $previous_states
   *   An object array in which each element is populated with fields 'state',
   *   'exec', and 'timestamp' indicating which state encountered the problem.
   * @param object[] $previous_transitions
   *   An object array in which each element is populated with fields 'method',
   *   'value', and 'timestamp' indicating which transition method was called
   *   and what value it returned.
   *
   * @return ExitMessage
   *   The exit message.
   */
  protected function getExitMessageForSystemFailure(
    WipContextInterface $wip_context,
    $previous_states,
    $previous_transitions
  ) {
    $this->log(WipLogLevel::FATAL, __METHOD__);
    if (!is_array($previous_states)) {
      throw new \InvalidArgumentException('The "previous_states" parameter must be an object array.');
    }
    if (!is_array($previous_transitions)) {
      throw new \InvalidArgumentException('The "previous_transitions" parameter must be an object array.');
    }
    $previous_state = reset($previous_states);
    $previous_transition = reset($previous_transitions);
    if ($previous_transition->value === '!' && count($previous_transitions) > 1) {
      // Ignore the ! transition to provide more context for the failure.
      $previous_transition = $previous_transitions[1];
    }

    $exit_message = $detailed_exit_message = 'Internal system error.';
    $exit_code = IteratorStatus::ERROR_SYSTEM;
    $state = $previous_state->state;
    $value = $previous_transition->value;
    $transition_method = $previous_transition->method;

    $internal_message = $this->lookupSystemErrorMessage($previous_state->state, $previous_transition->value);
    $internal_log_entry = <<<EOT
$internal_message
[$state] => '$value' {{$transition_method}}
EOT;

    $this->log(WipLogLevel::FATAL, $internal_log_entry);
    $this->setExitCode($exit_code);
    return new ExitMessage($exit_message, WipLogLevel::FATAL, $detailed_exit_message);
  }

  /**
   * Gets a suitable system error message.
   *
   * This method provides a specific message for logging system errors to make
   * it easier to identify the source of the problem.
   *
   * @param string $state
   *   The state that encountered the problem.
   * @param string $value
   *   The transition method value.
   *
   * @return string
   *   A suitable internal system error message.
   */
  private function lookupSystemErrorMessage($state, $value) {
    $result = 'An unknown system failure occurred.';

    $known_messages = array(
      'ensureBuildUser' => array(
        'StatePurpose' => 'verify the container build user exists',
      ),
      'ensureVcsUri' => array(
        'StatePurpose' => 'verify the source and deploy git URIs have been set',
      ),
      'establishWorkspaceSshKey' => array(
        'StatePurpose' => 'establish the workspace SSH key',
      ),
      'createGitWrapper' => array(
        'StatePurpose' => 'create the git SSH wrappers',
      ),
      'exportBuildFileAsJson' => array(
        'StatePurpose' => 'export the build file',
      ),
      'writeUserEnvironmentVars' => array(
        'StatePurpose' => 'write the user environment vars',
      ),
      'executeBuildScript' => array(
        'StatePurpose' => 'execute the build script',
      ),
    );

    if (isset($known_messages[$state])) {
      if (isset($known_messages[$state][$value])) {
        $result = $known_messages[$state][$value];
      } elseif (isset($known_messages[$state]['StatePurpose'])) {
        switch ($value) {
          case 'uninitialized':
            $result = sprintf(
              'The Wip context was uninitialized when trying to %s.',
              $known_messages[$state]['StatePurpose']
            );
            break;

          case 'ssh_fail':
            $result = sprintf(
              'An SSH error occurred when trying to %s.',
              $known_messages[$state]['StatePurpose']
            );
            break;

          case 'fail':
            $result = sprintf(
              'Failed to %s.',
              $known_messages[$state]['StatePurpose']
            );
            break;

          default:
            $result = sprintf(
              'An unknown failure occurred when trying to %s.',
              $known_messages[$state]['StatePurpose']
            );
        }
      } elseif (isset($known_messages[$state]['default'])) {
        $result = $known_messages[$state]['default'];
      }
    }
    return $result;
  }

  /**
   * Called if the deploy path is a tag.
   *
   * This constitutes a user error since we currently only deploy to a branch.
   */
  public function deployPathIsTag() {
    $vcs_path = $this->getDeployVcsPath();
    $summary_message = 'The specified deploy VCS path is a tag.';
    $detail_message = <<<EOT
The specified deploy VCS path ({$vcs_path}) identifies a tag, which is
unsupported. Tags are only supported for the source VCS path.
EOT;
    $message = new ExitMessage($summary_message, WipLogLevel::FATAL, $detail_message);
    $this->setExitMessage($message);
    $this->setExitCode(IteratorStatus::ERROR_USER);
  }

  /**
   * Indicates the type of the deploy VCS path - a tag, branch, or unknown.
   *
   * @return string
   *   'branch' - The deploy VCS path is a branch.
   *   'tag' - The deploy VCS path is a tag.
   *   'uninitialized' - The deploy VCS path has not yet been set.
   */
  public function checkDeployPathType() {
    $result = 'uninitialized';
    if (!empty($this->deployVcsPath)) {
      if (GitCommands::containsTagsPrefix($this->deployVcsPath)) {
        $result = 'tag';
      } else {
        $result = 'branch';
      }
    }
    return $result;
  }

}
