<?php

namespace Acquia\Wip\Implementation;

use Acquia\WipService\App;
use Acquia\Wip\AcquiaCloud;
use Acquia\Wip\AcquiaCloud\CloudCredentials;
use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\DependencyManager;
use Acquia\Wip\Environment;
use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\Exception\DependencyMissingException;
use Acquia\Wip\Exception\DependencyTypeException;
use Acquia\Wip\ExitMessage;
use Acquia\Wip\ExitMessageInterface;
use Acquia\Wip\IndependentEnvironment;
use Acquia\Wip\IteratorStatus;
use Acquia\Wip\Iterators\BasicIterator\SimulationScriptInterpreter;
use Acquia\Wip\Iterators\BasicIterator\StateTableIterator;
use Acquia\Wip\Notification\NotificationInterface;
use Acquia\Wip\Notification\NotificationSeverity;
use Acquia\Wip\Objects\ParameterDocument;
use Acquia\Wip\Objects\SiteGroup;
use Acquia\Wip\RecordingInterface;
use Acquia\Wip\Runtime\WipPoolInterface;
use Acquia\Wip\Security\EncryptTrait;
use Acquia\Wip\Signal\CallbackInterface;
use Acquia\Wip\Signal\SignalInterface;
use Acquia\Wip\Signal\SignalType;
use Acquia\Wip\Signal\WipCompleteSignal;
use Acquia\Wip\Ssh\GitCommands;
use Acquia\Wip\Ssh\SshFileCommands;
use Acquia\Wip\Ssh\SshInterface;
use Acquia\Wip\Ssh\SshKeys;
use Acquia\Wip\Ssh\SshServiceInterface;
use Acquia\Wip\StateTableIteratorInterface;
use Acquia\Wip\Storage\ConfigurationStoreInterface;
use Acquia\Wip\Storage\SignalStoreInterface;
use Acquia\Wip\TaskExitStatus;
use Acquia\Wip\TaskInterface;
use Acquia\Wip\Utility\MetricsUtility;
use Acquia\Wip\WipAcquiaCloudInterface;
use Acquia\Wip\WipContainerInterface;
use Acquia\Wip\WipContextInterface;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipInterface;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipLogLevel;
use Acquia\Wip\WipSshInterface;
use Acquia\Wip\WipTaskConfig;
use Acquia\Wip\WipTaskInterface;
use GuzzleHttp\Client;

/**
 * A useful base class for creating Wip objects with PHP code.
 */
class BasicWip implements WipInterface, DependencyManagedInterface {

  use EncryptTrait;

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
   * The name of the group this task belongs to.
   *
   * @var string
   */
  private $groupName = NULL;

  /**
   * The iterator.
   *
   * @var StateTableIterator
   */
  private $iterator;

  /**
   * The Wip ID.
   *
   * @var int
   */
  private $id;

  /**
   * The UUID of the user who started the task.
   *
   * @var string
   */
  private $uuid;

  /**
   * The work ID.
   *
   * This value uniquely identifies a particular workload.
   *
   * @var string
   */
  private $workId;

  /**
   * The state table associated with this Wip instance.
   *
   * @var string
   */
  protected $stateTable = <<<EOT
  # This should be replaced.
start {
  * finish
}

failure {
  * finish
  ! finish
}

terminate {
  * failure
}
EOT;

  /**
   * An array of include files required for proper deserialization.
   *
   * @var array
   */
  private $includes = array();

  /**
   * The logger this Wip instance will use.
   *
   * This is generally set by the WipWorker.
   *
   * @var WipLogInterface
   */
  private $wipLog = NULL;

  /**
   * The level of log messages to preserve upon successful completion.
   *
   * @var int
   */
  private $logLevel = WipLogLevel::ALERT;

  /**
   * Callbacks that are to be invoked upon the completion of this instance.
   *
   * @var CallbackInterface[]
   */
  private $callbacks = array();

  /**
   * Callback Wip data.
   *
   * @var object
   */
  private $callbackWipData = NULL;

  /**
   * The parameter document that controls behavior.
   *
   * @var ParameterDocument
   */
  private $document = NULL;

  /**
   * Options that will be passed into the Wip object.
   *
   * @var object
   */
  private $options = NULL;

  /**
   * The exit message associated with this Wip object.
   *
   * @var ExitMessageInterface
   */
  private $exitMessage = NULL;

  /**
   * Indicates whether the object is running in simulation mode.
   *
   * Simulation mode causes the states and transitions to not be actually
   * invoked so the state table can be fully exercised without
   * being constrained by the implementation of the state and transition
   * methods.
   *
   * @var int
   */
  private $simulationMode = StateTableIterator::SIMULATION_DISABLED;

  /**
   * The simulation.
   *
   * This simulation will be used to exercise the state table during testing and
   * cannot be used in production.
   *
   * @var SimulationScriptInterpreter
   */
  private $simulation = NULL;

  /**
   * The set of recordings for Wip objects controlled by this instance.
   *
   * @var RecordingInterface[]
   */
  private $recordings = array();

  /**
   * The version information associated with this instance.
   *
   * The version consists of a version for every Wip in the class hierarchy.
   *
   * @var array
   */
  private $instanceVersions;

  /**
   * The time of the last write to WipStore.
   *
   * @var int
   */
  private $timestamp = 0;

  /**
   * The metric utility.
   *
   * @var MetricsUtility
   */
  private $metric;

  /**
   * Initializes a new instance of BasicWip.
   *
   * @throws DependencyTypeException
   *   If dependencies are not satisfied.
   */
  public function __construct() {
    $this->dependencyManager = new DependencyManager();
    $dependencies = $this->getDependencies();
    if (!empty($dependencies)) {
      $this->dependencyManager->addDependencies($dependencies);
    }
    $this->initializeInstanceVersions();
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.acquiacloud' => 'Acquia\Wip\WipAcquiaCloudInterface',
      'acquia.wip.api' => 'Acquia\Wip\WipTaskInterface',
      'acquia.wip.containers' => 'Acquia\Wip\WipContainerInterface',
      'acquia.wip.notification' => 'Acquia\Wip\Notification\NotificationInterface',
      'acquia.wip.ssh' => 'Acquia\Wip\WipSshInterface',
      'acquia.wip.ssh.client' => 'Acquia\Wip\Ssh\SshInterface',
      'acquia.wip.ssh_service' => 'Acquia\Wip\Ssh\SshService',
      'acquia.wip.ssh_service.local' => 'Acquia\Wip\Ssh\LocalExecSshService',
      'acquia.wip.pool' => 'Acquia\Wip\Runtime\WipPoolInterface',
    );
  }

  /**
   * Gets the metrics utility.
   *
   * @return Client
   *   The http client.
   */
  public function getMetricsUtility() {
    if (is_null($this->metric)) {
      $this->setMetricsUtility(new MetricsUtility(new Client()));
    }
    return $this->metric;
  }

  /**
   * Sets the metrics utility.
   *
   * @param MetricsUtility $metric
   *    The metrics utility.
   */
  public function setMetricsUtility(MetricsUtility $metric) {
    $this->metric = $metric;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimestamp() {
    return $this->timestamp;
  }

  /**
   * {@inheritdoc}
   */
  public function setTimestamp($timestamp) {
    if (!is_int($timestamp) || $timestamp <= 0) {
      throw new \InvalidArgumentException('The timestamp parameter must be a positive, non-zero integer.');
    }

    $this->timestamp = $timestamp;
  }

  /**
   * Gets the dependency manager.
   *
   * @return DependencyManager
   *   The dependency manager.
   */
  public function getDependencyManager() {
    return $this->dependencyManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return (sprintf('%s [%s]', 'Default title', $this->getGroup()));
  }

  /**
   * {@inheritdoc}
   */
  public function setGroup($group_name) {
    if (!is_string($group_name) || empty($group_name)) {
      throw new \InvalidArgumentException('The group_name parameter must be a non-empty string.');
    }
    $this->groupName = $group_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    $result = $this->groupName;
    if (empty($result)) {
      $result = get_class($this);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public static function getClassVersion() {
    return static::CLASS_VERSION;
  }

  /**
   * {@inheritdoc}
   */
  public static function getClassVersionForClass($class_name) {
    $versions = static::getClassVersions();
    $class = new \ReflectionClass(get_called_class());
    $find_versions = $class->getMethod('findVersion');
    return $find_versions->invoke(NULL, $versions, $class_name);
  }

  /**
   * {@inheritdoc}
   */
  public static function getClassVersions() {
    $result = array();
    $required_interface = 'Acquia\Wip\WipInterface';

    $class_name = get_called_class();
    $class_version_method = 'getClassVersion';
    if (!empty($class_name) && class_exists($class_name)) {
      $class = NULL;
      $interfaces = class_implements($class_name);
      while (is_array($interfaces) && in_array($required_interface, $interfaces)) {
        if (NULL === $class) {
          $class = new \ReflectionClass($class_name);
        }
        if ($class->hasMethod($class_version_method)) {
          $version = $class->getMethod($class_version_method);
          $class_version = $version->invoke(NULL);
          $result[] = new WipVersionElement($class_name, $class_version);
          // Set up for the next iteration:
          $class = $class->getParentClass();
          if ($class !== FALSE) {
            $class_name = $class->getName();
            $interfaces = class_implements($class_name);
          } else {
            $interfaces = FALSE;
          }
        }
      }
    }
    return array_reverse($result);
  }

  /**
   * {@inheritdoc}
   */
  public function getInstanceVersion($class_name) {
    return static::findVersion($this->getInstanceVersions(), $class_name);
  }

  /**
   * {@inheritdoc}
   */
  public static function findVersion($versions, $class_name) {
    if (!is_array($versions)) {
      throw new \InvalidArgumentException('The "versions" parameter must be an array.');
    }
    if (!is_string($class_name) || empty($class_name)) {
      throw new \InvalidArgumentException('The "class_name" parameter must be a non-empty string.');
    }
    foreach ($versions as $version_element) {
      if (!$version_element instanceof WipVersionElement) {
        throw new \InvalidArgumentException('The "versions" parameter must be an array of WipVersionElement objects.');
      }
      if ($version_element->matchesClass($class_name)) {
        return $version_element;
      }
    }
    throw new \DomainException(sprintf('Failed to find the version for class "%s".', $class_name));
  }

  /**
   * {@inheritdoc}
   */
  public function getInstanceVersions() {
    return $this->instanceVersions;
  }

  /**
   * {@inheritdoc}
   */
  public function setInstanceVersion($class_name, $version) {
    $version_entry = $this->findVersion($this->getInstanceVersions(), $class_name);
    $version_entry->setVersionNumber($version);
  }

  /**
   * Initializes all instance versions.
   */
  private function initializeInstanceVersions() {
    $this->instanceVersions = static::getClassVersions();
  }

  /**
   * {@inheritdoc}
   */
  public function getStateTable() {
    return $this->stateTable;
  }

  /**
   * Sets the state table that drives this Wip instance.
   *
   * @param string $state_table
   *   The state table.
   */
  public function setStateTable($state_table) {
    $this->stateTable = $state_table;
  }

  /**
   * {@inheritdoc}
   */
  public function start(WipContextInterface $wip_context) {
  }

  /**
   * {@inheritdoc}
   */
  public function finish(WipContextInterface $wip_context) {
  }

  /**
   * {@inheritdoc}
   */
  public function emptyTransition(WipContextInterface $wip_context) {
    // The empty string is a good choice here because it is impossible
    // to represent that in the human readable state table.
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function failure(
    WipContextInterface $wip_context,
    \Exception $exception = NULL
  ) {
    $exit_code = $this->getExitCode();
    if ($exit_code === IteratorStatus::OK) {
      $this->setExitCode(IteratorStatus::ERROR_SYSTEM);
    }
    $exit_message = $this->getExitMessage();
    if (empty($exit_message)) {
      $this->setExitMessage(new ExitMessage('Failed to complete the task.', WipLogLevel::FATAL));
    }
    $this->notifyFailure($exception);
  }

  /**
   * {@inheritdoc}
   */
  public function terminate(WipContextInterface $wip_context) {
    $message = 'The request to terminate has been processed.';
    $this->setExitMessage(new ExitMessage($message, WipLogLevel::FATAL, $message));
    $this->setExitCode(IteratorStatus::TERMINATED);
  }

  /**
   * {@inheritdoc}
   */
  public function onAdd() {
    $message = sprintf('Task %d has been added.', $this->getId());
    $this->getMetricsUtility()->startTiming('wip.system.job_time.task_start');
    $this->log(WipLogLevel::INFO, $message, TRUE);
    $recordings = $this->getIterator()->getRecordings();
    if (!empty($recordings)) {
      /** @var RecordingInterface $recording */
      $recording = reset($recordings);
      $recording->setAddTime(time());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onStart() {
    $message = sprintf('Task %d has started.', $this->getId());
    $this->log(WipLogLevel::INFO, $message, TRUE);
    // Send Job started metric.
    $this->getMetricsUtility()->endTiming('wip.system.job_time.task_start');
    $this->getMetricsUtility()->sendMetric('count', 'wip.system.job_status.job_started', 1);

    /** @var RecordingInterface $recording */
    $recordings = $this->getIterator()->getRecordings();
    if (!empty($recordings)) {
      $recording = reset($recordings);
      $recording->setStartTime(time());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onProcess() {
  }

  /**
   * {@inheritdoc}
   */
  public function onWait() {
  }

  /**
   * {@inheritdoc}
   */
  public function onFinish() {
    $exit_message = $this->getExitMessage();
    if (empty($exit_message)) {
      $exit_message = new ExitMessage('The task has completed successfully.');
      $this->setExitMessage($exit_message);
    }
    $log_message = $exit_message->getLogMessage();
    if (!empty($log_message)) {
      $this->log($exit_message->getLogLevel(), $log_message, TRUE);
    }
    /** @var RecordingInterface $recording */
    $recordings = $this->getIterator()->getRecordings();
    if (!empty($recordings)) {
      $recording = reset($recordings);
      $recording->setEndTime(time());
    }
    $this->cleanUp();

    // Send MTD system failure metric.
    $this->getMetricsUtility()->sendMtdSystemFailure(FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function onTerminate() {
    $exit_message = new ExitMessage(
      'The task was manually terminated.',
      WipLogLevel::FATAL
    );
    $this->setExitMessage($exit_message);
    $this->log(
      $exit_message->getLogLevel(),
      $exit_message->getLogMessage(),
      TRUE
    );
    /** @var RecordingInterface $recording */
    $recordings = $this->getIterator()->getRecordings();
    if (!empty($recordings)) {
      $recording = reset($recordings);
      $recording->setEndTime(time());
    }
    $this->cleanUp();

    // Send MTD system failure metric.
    $this->getMetricsUtility()->sendMtdSystemFailure(FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function onRestart() {
    $message = 'The task has been restarted.';
    $this->log(WipLogLevel::ALERT, $message, TRUE);
    $this->recordings = array();
  }

  /**
   * {@inheritdoc}
   */
  public function onUserError() {
    $this->onFail();
    // Send User error metric.
    $this->getMetricsUtility()->sendMetric('count', 'wip.system.job_status.user_error', 1);

    // Send MTD system failure metric.
    $this->getMetricsUtility()->sendMtdSystemFailure(FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function onSystemError() {
    $this->notifyFailure();

    $this->onFail();
    // Send system error metric.
    $this->getMetricsUtility()->sendMetric('count', 'wip.system.job_status.system_error', 1);

    // Send MTD system failure metric.
    $this->getMetricsUtility()->sendMtdSystemFailure();
  }

  /**
   * {@inheritdoc}
   */
  public function onFail() {
    $exit_message = $this->getExitMessage();
    if (empty($exit_message)) {
      $exit_message = new ExitMessage(
        'The task has failed.',
        WipLogLevel::FATAL
      );
      $this->setExitMessage($exit_message);
    }
    $this->log(
      $exit_message->getLogLevel(),
      $exit_message->getLogMessage(),
      TRUE
    );
    /** @var RecordingInterface $recording */
    $recordings = $this->getIterator()->getRecordings();
    if (!empty($recordings)) {
      $recording = reset($recordings);
      $recording->setEndTime(time());
    }
    $this->cleanUp();
  }

  /**
   * {@inheritdoc}
   */
  public function onSignal(SignalInterface $signal) {
  }

  /**
   * {@inheritdoc}
   */
  public function onSerialize() {
  }

  /**
   * {@inheritdoc}
   */
  public function onDeserialize() {
  }

  /**
   * {@inheritdoc}
   */
  public function onStatusChange(TaskInterface $task) {
  }

  /**
   * Performs any cleanup required before this object exits.
   */
  public function cleanUp() {
    try {
      $this->getWipLog()->getStore()->cleanUp();
    } catch (\Exception $e) {
      $this->log(
        WipLogLevel::ERROR,
        sprintf('Failed to perform log cleanup: %s.', $e->getMessage())
      );
    }
    try {
      $extra_data = NULL;
      /** @var RecordingInterface $recording */
      $recordings = $this->getIterator()->getRecordings();
      if (!empty($recordings)) {
        $recording = reset($recordings);
        $recording->setEndTime(time());

        // Include recording data in the callback.
        if (!empty($recordings)) {
          $extra_data = new \stdClass();
          $extra_data->recordings = $recordings;
        }
      }
      $this->invokeCallbacks($extra_data);
    } catch (\Exception $e) {
      $this->log(WipLogLevel::ERROR, sprintf('Failed to invoke callbacks: %s.', $e->getMessage()));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addInclude($docroot, $path) {
    if (!is_string($docroot) || empty($docroot)) {
      throw new \InvalidArgumentException('The $docroot parameter must be a non-empty string.');
    }
    if (!is_string($path) || empty($path)) {
      throw new \InvalidArgumentException('The $path parameter must be a non-empty string.');
    }
    $this->includes[] = new BasicIncludeFile($docroot, $path);
  }

  /**
   * {@inheritdoc}
   */
  public function getIncludes() {
    return $this->includes;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    if (!is_int($id)) {
      throw new \InvalidArgumentException('The "id" argument must be an integer.');
    }
    $this->id = $id;
  }

  /**
   * {@inheritdoc}
   */
  public function getUuid() {
    return $this->uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function setUuid($uuid) {
    if (empty($uuid) || !is_string($uuid)) {
      throw new \InvalidArgumentException('The "uuid" argument must be a string.');
    }
    $this->uuid = $uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkId() {
    if (empty($this->workId)) {
      $this->workId = $this->generateWorkId();
    }
    return $this->workId;
  }

  /**
   * {@inheritdoc}
   */
  public function generateWorkId() {
    // By default, consider all work items completely unique.
    $work_id = sprintf('%s:%d:%d', __CLASS__, microtime(), mt_rand());
    return sha1($work_id);
  }

  /**
   * {@inheritdoc}
   */
  public function setWipLog(WipLogInterface $wip_log) {
    $this->wipLog = $wip_log;
  }

  /**
   * {@inheritdoc}
   */
  public function getWipLog() {
    if (!isset($this->wipLog)) {
      $this->wipLog = WipFactory::getObject('acquia.wip.wiplog');
    }
    return $this->wipLog;
  }

  /**
   * {@inheritdoc}
   */
  public function setLogLevel($level) {
    if (WipLogLevel::isValid($level)) {
      $this->logLevel = $level;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLogLevel() {
    return $this->logLevel;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, $user_readable = FALSE) {
    $this->getWipLog()->log($level, $message, $this->getId(), $user_readable);
  }

  /**
   * {@inheritdoc}
   */
  public function multiLog($level, $message) {
    $args = func_get_args();
    $std_args = array($this->getId());
    call_user_func_array(
      array($this->getWipLog(), 'multiLog'),
      array_merge($std_args, $args)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getNotifier() {
    return $this->dependencyManager->getDependency('acquia.wip.notification');
  }

  /**
   * Returns the API used to work with Wip objects.
   *
   * @return WipTaskInterface
   *   The WipTaskInterface instance.
   */
  public function getWipApi() {
    /** @var WipTaskInterface $result */
    $result = $this->dependencyManager->getDependency('acquia.wip.api');
    $result->setWipId($this->getId());
    return $result;
  }

  /**
   * Returns the API used to work with Ssh.
   *
   * @return WipSshInterface
   *   The API.
   */
  public function getSshApi() {
    return $this->dependencyManager->getDependency('acquia.wip.ssh');
  }

  /**
   * Returns the API used to work with Acquia's Cloud API.
   *
   * @return WipAcquiaCloudInterface
   *   The API.
   */
  public function getAcquiaCloudApi() {
    return $this->dependencyManager->getDependency('acquia.wip.acquiacloud');
  }

  /**
   * Gets an SshService instance for the specified environment.
   *
   * @param EnvironmentInterface $environment
   *   The environment on which commands will be executed.
   *
   * @return SshServiceInterface
   *   An instance of SshServiceInterface.
   */
  public function getSshService(EnvironmentInterface $environment) {
    $current_server = $environment->getCurrentServer();
    // Ensure we have a server to operate on.
    if (empty($current_server)) {
      $environment->selectNextServer();
      $current_server = $environment->getCurrentServer();
    }
    // If the server is localhost, assume we want to use the local SSH service.
    // This also means, when using this method to get the SSH service, that the
    // server must be specified as localhost to use the local exec SSH service.
    if ($current_server === 'localhost') {
      $dependency = 'acquia.wip.ssh_service.local';
    } else {
      $dependency = 'acquia.wip.ssh_service';
    }
    /** @var SshServiceInterface $ssh_service */
    $ssh_service = $this->dependencyManager->getDependency($dependency);
    $ssh_service->setEnvironment($environment);
    $ssh_keys = new SshKeys();

    $runtime_environment = Environment::getRuntimeEnvironment();
    if ($environment->getSitegroup() === $runtime_environment->getSitegroup()
      && $environment->getEnvironmentName() === $runtime_environment->getEnvironmentName()) {
      // This is going to run on a Wip webnode.
      $ssh_service->setKeyPath(WipFactory::getObject('$acquia.wip.service.private_key_path'));
    } else {
      $ssh_service->setKeyPath($ssh_keys->getPrivateKeyPath($environment));
    }
    return $ssh_service;
  }

  /**
   * Gets the SSH command instance for executing a command.
   *
   * @param string $description
   *   The description of the SSH command that will be executed.
   * @param EnvironmentInterface $environment
   *   The environment on which commands will be executed.
   *
   * @return SshInterface
   *   An instance of SshInterface.
   */
  public function getSsh($description, EnvironmentInterface $environment) {
    /** @var SshInterface $ssh */
    $ssh = WipFactory::getObject('acquia.wip.ssh.client');
    $ssh->setSshService($this->getSshService($environment));
    $ssh->initialize($environment, $description, $this->getWipLog(), $this->getId());
    return $ssh;
  }

  /**
   * Returns the API for executing filesystem commands on the given environment.
   *
   * @param EnvironmentInterface $environment
   *   The environment on which commands will be executed.
   *
   * @return SshFileCommands
   *   A new instance of SshFileCommands for executing filesystem commands.
   */
  public function getFileCommands(EnvironmentInterface $environment) {
    $ssh_service = $this->getSshService($environment);
    return new SshFileCommands($environment, $this->getId(), $this->getWipLog(), $ssh_service);
  }

  /**
   * Returns the API for executing git commands on the given environment.
   *
   * @param EnvironmentInterface $environment
   *   The environment on which commands will be executed.
   * @param string $workspace
   *   The git workspace directory; the path to the repo on the filesystem.
   * @param bool $use_wrapper
   *   Whether to use the git wrapper to execute commands.
   *
   * @return GitCommands
   *   A new instance of GitCommands for executing git commands.
   */
  public function getGitCommands(
    EnvironmentInterface $environment,
    $workspace,
    $use_wrapper = TRUE
  ) {
    $ssh_service = $this->getSshService($environment);
    return new GitCommands($environment, $workspace, $this->getId(), $this->getWipLog(), $ssh_service, $use_wrapper);
  }

  /**
   * Returns the API used to work with containers.
   *
   * @return WipContainerInterface
   *   The Container API for interacting with containers.
   */
  public function getContainerApi() {
    return $this->dependencyManager->getDependency('acquia.wip.containers');
  }

  /**
   * Returns the status of Wip children associated with the specified context.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface instance where the child Wip IDs are stored.
   *
   * @return string
   *   'success' - All tasks have completed successfully.
   *   'wait' - One or more tasks are still running.
   *   'uninitialized' - No child Wip objects have been added to the context.
   *   'fail' - At least one task failed.
   */
  public function checkWipTaskStatus(WipContextInterface $wip_context) {
    $wip_api = $this->getWipApi();
    return $wip_api->getWipTaskStatus($wip_context, $this->getWipLog());
  }

  /**
   * Returns the status of Ssh results and processes in the specified context.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface instance where the Ssh results and/or processes
   *   are stored.
   *
   * @return string
   *   'success' - All Ssh calls were completed successfully.
   *   'wait' - One or more Ssh processes are still running.
   *   'uninitialized' - No Ssh results or processes have been added.
   *   'fail' - At least one Ssh call failed.
   *   'ssh_fail' - The Ssh command failed to connect.
   *   'no_progress' - An call is still running but no progress detected.
   */
  public function checkSshStatus(WipContextInterface $wip_context) {
    $ssh_api = $this->getSshApi();
    return $ssh_api->getSshStatus($wip_context, $this->getWipLog());
  }

  /**
   * Returns the status of Acquia Cloud task results and processes.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface instance where the Ssh results and/or processes
   *   are stored.
   *
   * @return string
   *   'success' - All cloud tasks were completed successfully.
   *   'wait' - One or more cloud processes are still running.
   *   'uninitialized' - No cloud results or processes have been added.
   *   'fail' - At least one cloud task failed.
   */
  public function checkAcquiaCloudTaskStatus(WipContextInterface $wip_context) {
    $cloud_api = $this->getAcquiaCloudApi();
    return $cloud_api->getAcquiaCloudStatus($wip_context, $this->getWipLog());
  }

  /**
   * Returns the status of container results and processes.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface instance where the container results and/or
   *   processes are stored.
   *
   * @return string
   *   'success' - The container task was completed successfully.
   *   'wait' - The container process is still running.
   *   'uninitialized' - No container results or processes have been added.
   *   'ready' - The container is up and ready to receive the task.
   *   'fail' - The container process failed.
   *   'running' - The container process is still running.
   */
  public function checkContainerStatus(WipContextInterface $wip_context) {
    $container_api = $this->getContainerApi();
    return $container_api->getContainerStatus($wip_context, $this->getWipLog());
  }

  /**
   * Returns the aggregate results of processes associated with the context.
   *
   * This can be used to process any combination of processes and results from
   * the SSH service, the Wip Task service, the Acquia Cloud service, and the
   * Container service.
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
   */
  public function checkResultStatus(WipContextInterface $wip_context) {
    $result = 'uninitialized';
    $status = array();
    $status[] = $this->checkSshStatus($wip_context);
    $status[] = $this->checkAcquiaCloudTaskStatus($wip_context);
    $status[] = $this->checkWipTaskStatus($wip_context);
    $status[] = $this->checkContainerStatus($wip_context);
    if (in_array('wait', $status)) {
      $result = 'wait';
    } elseif (in_array('running', $status)) {
      $result = 'running';
    } elseif (in_array('ssh_fail', $status)) {
      $result = 'ssh_fail';
    } elseif (in_array('fail', $status)) {
      $result = 'fail';
    } elseif (in_array('no_progress', $status)) {
      $result = 'no_progress';
    } elseif (in_array('ready', $status)) {
      $result = 'ready';
    } elseif (in_array('success', $status)) {
      $result = 'success';
    } elseif (in_array('uninitialized', $status)) {
      $result = 'uninitialized';
    } else {
      $this->log(
        WipLogLevel::FATAL,
        sprintf(
          "checkResultStatus failed to handle status:\n%s.",
          print_r($status, TRUE)
        )
      );
    }
    return $result;
  }

  /**
   * Kills all of the running processes associated with the specified context.
   *
   * @param WipContextInterface $wip_context
   *   The WipContext instance that may contain one or more processes to kill.
   */
  public function killAllProcesses(WipContextInterface $wip_context) {
    $wip_log = $this->getWipLog();
    $this->getSshApi()->clearSshProcesses($wip_context, $wip_log);
    $this->getAcquiaCloudApi()->clearAcquiaCloudProcesses($wip_context, $wip_log);
    $this->getWipApi()->clearWipTaskProcesses($wip_context, $wip_log);
    $this->getContainerApi()->clearContainerProcesses($wip_context, $wip_log);
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return $this->iterator;
  }

  /**
   * {@inheritdoc}
   */
  public function setIterator(StateTableIteratorInterface $iterator) {
    $this->iterator = $iterator;
    if ($iterator instanceof StateTableIterator) {
      $this->iterator->setSimulationMode($this->simulationMode, $this->simulation);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addCallback(CallbackInterface $callback) {
    $this->callbacks[] = $callback;
  }

  /**
   * {@inheritdoc}
   */
  public function addCallbackData($data) {
    $wip_data = $this->getCallbackData();
    foreach ($data as $key => $value) {
      $wip_data->$key = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCallbackData() {
    $result = $this->callbackWipData;
    if (NULL === $result) {
      $result = $this->callbackWipData = new \stdClass();
    }
    return $result;
  }

  /**
   * Figures out the exit status.
   *
   * @param TaskInterface $task
   *   The task representing this Wip object.
   *
   * @return int
   *   The exit code.
   */
  protected function calculateExitStatus(TaskInterface $task) {
    $result = $task->getExitStatus();
    if ($result === TaskExitStatus::NOT_FINISHED) {
      $result = IteratorStatus::toTaskExitStatus($this->getIterator()->getExitCode());
    }
    return $result;
  }

  /**
   * Invokes any callbacks that have been registered with this Wip instance.
   *
   * @param array $extra_data
   *   Data that should be sent with the callbacks.
   */
  protected function invokeCallbacks($extra_data = array()) {
    if (count($this->callbacks) > 0) {
      /** @var WipPoolInterface $wip_pool */
      $wip_pool = WipFactory::getObject('acquia.wip.pool');
      $task = $wip_pool->getTask($this->getId());
      $exit_code = $this->calculateExitStatus($task);
      $end_time = time();
      $exit_message = $this->getExitMessage();
      if (empty($exit_message)) {
        $exit_message = new ExitMessage('');
      }
      // Data that is common for all signals.
      $common_data = new \stdClass();

      $result = new \stdClass();
      $result->exitCode = $exit_code;
      $result->exitMessage = $exit_message->getExitMessage();
      $result->startTime = $task->getStartTimestamp();
      $result->endTime = $end_time;
      $result->wipId = $this->getId();
      $result->type = SignalType::COMPLETE;

      $timer = $this->getIterator()->getTimer();
      $result->timer = $timer->toJson();
      $common_data->result = $result;
      if (!empty($extra_data)) {
        $common_data->extraData = $extra_data;
      }

      foreach ($this->callbacks as $callback) {
        $signal = new WipCompleteSignal();
        $signal->setCompletedWipId($this->getId());
        $callback_data = $callback->getData();
        if (empty($callback_data)) {
          $callback_data = new \stdClass();
        }
        $callback_data->wipId = $this->getId();
        $callback_data->result = $result;
        $callback_data->wipData = $this->getCallbackData();
        $signal->setData($callback_data);
        $signal->initializeFromSignalData($result);
        $this->log(
          WipLogLevel::TRACE,
          sprintf(
            'Invoking callback for WIP %d: %s',
            $this->getId(),
            $callback->getDescription()
          )
        );
        try {
          $callback->send($signal);
          $this->log(WipLogLevel::DEBUG, sprintf('Signal sent: %s', print_r($signal->convertToObject(), TRUE)));
        } catch (\Exception $e) {
          $this->log(
            WipLogLevel::ERROR,
            sprintf('Signal send failed: %s', $e->getMessage())
          );
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setParameterDocument(ParameterDocument $document) {
    $this->document = $document;
  }

  /**
   * {@inheritdoc}
   */
  public function getParameterDocument() {
    $result = $this->document;
    if (empty($result)) {
      $result = $this->getParameterDocumentFromOptions($this->getOptions());
      $this->document = $result;
    }
    return $result;
  }

  /**
   * Extracts an Environment instance from the specified ParameterDocument.
   *
   * If provided, the site_group_name parameter will control which site group
   * is described in the resulting Environment instance. Otherwise the first
   * site group / environment set in the specified ParameterDocument will be
   * used.
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
            $environment->selectNextServer();
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
   * Gets the Acquia Cloud credentials from the specified options.
   *
   * @return CloudCredentials
   *   The cloud credentials.
   *
   * @throws \RuntimeException
   *   If there is insufficient information in the options to create a
   *   CloudCredentials instance.
   */
  protected function getCloudCredentialsFromOptions() {
    $result = NULL;
    $options = $this->getOptions();
    if (!empty($options->acquiaCloudCredentials)) {
      $credentials = $options->acquiaCloudCredentials;
      if ($credentials instanceof AcquiaCloud\CloudCredentials) {
        $result = $credentials;
      } else {
        if (is_array($credentials)) {
          $credentials = (object) $credentials;
        }
        if (empty($credentials->endpoint)) {
          throw new \DomainException('The Acquia Cloud credentials do not include an endpoint.');
        }
        if (empty($credentials->user)) {
          throw new \DomainException('The Acquia Cloud credentials do not include a user.');
        }
        if (empty($credentials->key)) {
          throw new \DomainException('The Acquia Cloud credentials do not include a key.');
        }
        if (empty($options->site)) {
          throw new \DomainException('The options do not include a site.');
        }
        $result = new AcquiaCloud\CloudCredentials(
          $credentials->endpoint,
          $credentials->user,
          $credentials->key,
          $options->site
        );
      }
    } else {
      throw new \DomainException('The Acquia Cloud credentials were not provided in the options.');
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions($options) {
    $this->options = $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  public function setWipTaskConfig(WipTaskConfig $configuration) {
    $group_name = $configuration->getGroupName();
    if (!empty($group_name)) {
      $this->setGroup($group_name);
    }
    $options = $configuration->getOptions();
    if (empty($options)) {
      $options = new \stdClass();
    }
    if (is_array($options)) {
      $options = (object) $options;
    }
    $document = $configuration->getParameterDocument();
    if (!empty($document) && empty($options->parameterDocument)) {
      $options->parameterDocument = $document;
    }
    if (!empty($options)) {
      $this->setOptions($options);
    }
    $callback = $configuration->getCallback();
    if (!empty($callback)) {
      $this->addCallback($callback);
    }
    $this->setLogLevel($configuration->getLogLevel());
  }

  /**
   * {@inheritdoc}
   */
  public function setExitMessage(ExitMessageInterface $message) {
    if ($message === NULL) {
      $this->exitMessage = NULL;
      $this->getIterator()->setExitMessage('');
    } elseif (!($message instanceof ExitMessageInterface)) {
      throw new \InvalidArgumentException('The message argument must be NULL or an instance of ExitMessageInterface.');
    } else {
      $this->getIterator()->setExitMessage($message->getExitMessage());
      $this->exitMessage = $message;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getExitMessage() {
    return $this->exitMessage;
  }

  /**
   * {@inheritdoc}
   */
  public function setExitCode($exit_code) {
    $this->getIterator()->setExitCode($exit_code);
  }

  /**
   * {@inheritdoc}
   */
  public function getExitCode() {
    return $this->getIterator()->getExitCode();
  }

  /**
   * Sends an error notification.
   *
   * @param \Exception $e
   *   The exception that caused the failure (assuming the failure was caused
   *   by an exception).
   * @param array $metadata
   *   Optional. Additional metadata to supplement the notification.
   */
  public function notifyFailure(
    \Exception $e = NULL,
    array $metadata = array()
  ) {
    /** @var NotificationInterface $notifier */
    $notifier = $this->getNotifier();
    $severity = NotificationSeverity::ERROR;
    /** @var ConfigurationStoreInterface $configuration */
    $configuration = WipFactory::getObject('acquia.wip.storage.configuration');
    $default_metadata = array(
      'wip_id' => $this->getId(),
      'group' => $this->getGroup(),
      'external_wip_id' => $configuration->get('externalWipId'),
    );
    $metadata = array_merge($default_metadata, $metadata);
    if ($e === NULL) {
      $exit_message = $this->getExitMessage();
      if ($exit_message instanceof ExitMessage) {
        $exit_message = $this->getExitMessage()->getExitMessage();
      } elseif (!is_string($exit_message) || empty($exit_message)) {
        $exit_message = 'No exit message set.';
      }
      $exit_code = $this->getExitCode();
      $type = IteratorStatus::getLabel($exit_code);
      if ($exit_code === IteratorStatus::ERROR_USER) {
        $severity = NotificationSeverity::INFO;
      }
      $notifier->notifyError($type, $exit_message, $severity, $metadata);
    } else {
      $notifier->notifyException($e, $severity, $metadata);
    }
    $log = $this->getWipLog();
    $log->log(
      WipLogLevel::DEBUG,
      sprintf(
        'Sent an error notification: %s',
        print_r(compact('type', 'severity', 'exit_message', 'metadata'), TRUE)
      ),
      $this->getId()
    );
  }

  /**
   * Gets the WipPoolInterface instance.
   *
   * The WipPoolInterface is used to add Wip objects to the system.
   *
   * @return WipPoolInterface
   *   The WipPool instance.
   *
   * @throws DependencyMissingException
   *   If the WipPoolInterface implementation could not be found.
   */
  public function getWipPool() {
    /** @var WipPoolInterface $wip_pool */
    return $this->dependencyManager->getDependency('acquia.wip.pool');
  }

  /**
   * Gets the signal storage instance to use.
   *
   * @return SignalStoreInterface
   *   The storage instance for signals.
   *
   * @throws DependencyMissingException
   *   If the SignalStoreInterface implementation could not be found.
   */
  public function getSignalStore() {
    return $this->dependencyManager->getDependency('acquia.wip.storage.signal');
  }

  /**
   * {@inheritdoc}
   */
  public function setSimulationMode(
    $simulation_mode = StateTableIterator::SIMULATION_DISABLED,
    SimulationScriptInterpreter $simulation = NULL
  ) {
    switch ($simulation_mode) {
      case StateTableIterator::SIMULATION_DISABLED:
      case StateTableIterator::SIMULATION_RANDOM:
        if (!empty($simulation)) {
          $msg = 'The specified simulation_mode does not accept a SimulationScriptInterpreter instance.';
          throw new \InvalidArgumentException($msg);
        }
        break;

      case StateTableIterator::SIMULATION_SCRIPT:
        if (empty($simulation)) {
          $msg = 'The simulation_mode SIMULATION_SCRIPT requires an instance of SimulationScriptInterpreter.';
          throw new \InvalidArgumentException($msg);
        }
        break;

      default:
        $msg = 'The simulation_mode must be one of SIMULATION_DISABLED, SIMULATION_SCRIPT, or SIMULATION_RANDOM.';
        throw new \InvalidArgumentException($msg);
    }
    $this->simulationMode = $simulation_mode;
    $this->simulation = $simulation;
    if (!empty($this->iterator) && $this->iterator instanceof StateTableIterator) {
      $this->iterator->setSimulationMode($simulation_mode, $simulation);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAllRecordings() {
    return $this->recordings;
  }

  /**
   * Can be called when an unrecoverable system error has occurred.
   *
   * This method calls out to the getExitMessageForSystemFailure method to get
   * the actual exit message, so this method generally does not have to be
   * overridden.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function systemFailure(WipContextInterface $wip_context) {
    $all_recordings = $this->getIterator()->getRecordings();
    /** @var RecordingInterface $last_recording */
    $last_recording = end($all_recordings);
    $states = $last_recording->getPreviousStates();
    $transitions = $last_recording->getPreviousTransitions();
    $exit_message = $this->getExitMessageForSystemFailure($wip_context, $states, $transitions);

    $this->setExitMessage($exit_message);
  }

  /**
   * Gets an appropriate exit message for a given system failure.
   *
   * This method is meant to be overridden to provide specific error messages
   * based on the execution context.
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
    if (!is_array($previous_states)) {
      throw new \InvalidArgumentException('The "previous_states" parameter must be an object array.');
    }
    if (!is_object($previous_transitions)) {
      throw new \InvalidArgumentException('The "previous_transitions" parameter must be an object array.');
    }
    $this->setExitCode(IteratorStatus::ERROR_SYSTEM);
    return new ExitMessage('Internal error.');
  }

}
