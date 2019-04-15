<?php

namespace Acquia\Wip\Container;

use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\DependencyManager;
use Acquia\Wip\DependencyManagerInterface;
use Acquia\Wip\Environment;
use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\LockInterface;
use Acquia\Wip\Signal\SignalCallbackHttpTransportInterface;
use Acquia\Wip\Signal\UriCallback;
use Acquia\Wip\Ssh\SshFileCommands;
use Acquia\Wip\Ssh\SshKeys;
use Acquia\Wip\Ssh\SshServiceInterface;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipLogLevel;
use Acquia\Wip\WipTaskConfig;
use phpseclib\Net\SCP;
use phpseclib\Net\SSH2;

/**
 * Contains functionality common to all types of containers.
 */
abstract class AbstractContainer implements ContainerInterface, DependencyManagedInterface {

  /**
   * The path into which serialized Wip objects will be written.
   */
  const WIP_OBJECT_CONTAINER_PATH = '/tmp/wip-objects';

  /**
   * The name of the container SSH key.
   *
   * This key is used to communicate with the container.
   */
  const CONTAINER_KEY_NAME = 'container';

  /**
   * Indicates whether the container has started and is ready to be configured.
   *
   * @var bool
   */
  protected $started = FALSE;

  /**
   * Indicates whether the container has entered a running state or not.
   *
   * @var bool
   */
  protected $running = FALSE;

  /**
   * Indicates whether the container has been configured.
   *
   * Configuration includes sending the task workload to the container.
   *
   * @var bool
   */
  protected $configured = FALSE;

  /**
   * Indicates whether the container launch has failed.
   *
   * @var bool
   */
  private $launchFailed = FALSE;

  /**
   * The host where the container is running.
   *
   * This will be either an IP address, or a fully-qualified host name.
   *
   * @var string
   */
  protected $host;

  /**
   * The set of override keys that contain sensitive information.
   *
   * @var string[]
   */
  private $secureOverrideKeys = array();

  /**
   * An array of ports that are exposed by the container.
   *
   * This is an associative array, keyed by the port as seen from the container,
   * the corresponding values being the port that is exposed on the host.  For
   * example the SSH port will have a key of 22 (the SSH port that the container
   * exposes) and the value may be any automatically-assigned valid port number
   * which is forwarded to port 22 of the container.
   *
   * @var int[]
   */
  protected $ports;

  /**
   * A unique identifier for the container process.
   *
   * @var string
   */
  protected $pid;

  /**
   * The WIP task group name for the task this container runs.
   *
   * @var string
   */
  protected $groupName;

  /**
   * Indicates whether this service is in debug mode.
   *
   * @var bool
   */
  private $debug = FALSE;

  /**
   * A container for dependencies.
   *
   * @var DependencyManagerInterface
   */
  public $dependencyManager;

  /**
   * The logger instance for this object.
   *
   * @var WipLogInterface
   */
  protected $log;

  /**
   * An SSH object that can be used for testing purposes.
   *
   * @var SSH2
   */
  protected static $testSsh;

  /**
   * A SCP object that can be used for testing purposes.
   *
   * @var SCP
   */
  protected static $testScp;

  /**
   * The Wip object ID that created this container instance.
   *
   * @var int
   */
  private $wipId;

  /**
   * The Environment instance.
   *
   * @var EnvironmentInterface
   */
  private $environment;

  /**
   * Provides super class construction behavior for container implementations.
   */
  public function __construct() {
    $this->dependencyManager = new DependencyManager();
    $dependencies = $this->getDependencies();
    if (!empty($dependencies)) {
      $this->dependencyManager->addDependencies($dependencies);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.handler.signal'    => 'Acquia\Wip\Signal\SignalCallbackHttpTransportInterface',
      'acquia.wip.ssh_service'       => 'Acquia\Wip\Ssh\SshServiceInterface',
      'acquia.wip.ssh_service.local' => 'Acquia\Wip\Ssh\SshServiceInterface',
      'acquia.wip.wiplog'            => 'Acquia\Wip\WipLogInterface',
      'acquia.wip.lock.global'       => 'Acquia\Wip\LockInterface',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPublicKey() {
    $this->ensureContainerKey();
    return file_get_contents($this->getPublicKeyPath());
  }

  /**
   * Ensures the container SSH key has been generated.
   *
   * If it has not been generated it will be created.
   *
   * @throws \RuntimeException
   *   If the SSH key has not been created and the attempt to create it failed.
   */
  private function ensureContainerKey() {
    $result = NULL;
    $private_key_path = $this->getPrivateKeyPath();
    if (!file_exists($private_key_path)) {
      /** @var LockInterface $lock */
      $lock = WipFactory::getObject('acquia.wip.lock.global');
      $lock->acquire(self::CONTAINER_KEY_NAME, 3);
      if (!file_exists($private_key_path)) {
        try {
          $environment = Environment::getRuntimeEnvironment();
          $environment->setServers(array('localhost'));
          $environment->selectNextServer();
          $commands = $this->getFileCommands($environment);
          $result = $commands->createSshKey($private_key_path)->exec();
          if ($result->isSuccess()) {
            $this->getWipLog()->log(
              WipLogLevel::INFO,
              sprintf('Created the container key %s.', $private_key_path),
              $this->getWipId()
            );
          } else {
            $message = sprintf(
              'Failed to create the container key %s: %s',
              $private_key_path,
              $result->getSecureStderr()
            );
            $this->getWipLog()->log(WipLogLevel::FATAL, $message, $this->getWipId());
            throw new \RuntimeException($message);
          }
        }
        finally {
          $lock->release(self::CONTAINER_KEY_NAME);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPublicKeyPath() {
    return sprintf('%s.pub', $this->getPrivateKeyPath());
  }

  /**
   * Gets the path to the private SSH key used to access the container.
   *
   * @return string
   *   The path to the private key.
   */
  private function getPrivateKeyPath() {
    return sprintf('%s/%s', (new SshKeys())->getBasePath(), self::CONTAINER_KEY_NAME);
  }

  /**
   * Gets an SshFileCommands instance for the specified environment.
   *
   * @param EnvironmentInterface $environment
   *   The environment on which commands will be executed.
   *
   * @return SshFileCommands
   *   An instance of SshFileCommands.
   */
  private function getFileCommands(EnvironmentInterface $environment) {
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
    $ssh_service->setKeyPath($ssh_keys->getPrivateKeyPath($environment));

    return new SshFileCommands(
      $environment,
      $this->getWipId(),
      $this->getWipLog(),
      $ssh_service
    );
  }

  /**
   * Returns the Environment instance associated with this instance.
   *
   * @param bool $exception_on_fail
   *   Optional. If FALSE, no exception will be thrown and all elements of the
   *   Environment that can be set will be.
   *
   * @return EnvironmentInterface
   *   The Environment instance.
   *
   * @throws \Exception
   *   If the environment is not ready.
   */
  public function getEnvironment($exception_on_fail = TRUE) {
    $result = $this->environment;
    if (empty($result)) {
      $result = new Environment();
      $result->setSitegroup('local');
      $result->setEnvironmentName('prod');

      // The password will be the private key, but don't literally store the
      // private key in the Environment instance since that will be stored in
      // the DB. The SshService will pull this apart and load the private key
      // for a particular SSH connection.
      $result->setPassword(sprintf('ssh:%s', $this->getPrivateKeyPath()));
      try {
        $result->setServers(array($this->getHost()));
        $result->setPort($this->getPort(ContainerInterface::PORT_TYPE_SSH));
        $result->selectNextServer();
      } catch (\Exception $e) {
        $this->getWipLog()->log(
          WipLogLevel::ERROR,
          sprintf('Failed to get the hostname: %s', $e->getMessage()),
          $this->getWipId()
        );
        if ($exception_on_fail) {
          throw $e;
        }
      }
    }
    return $result;
  }

  /**
   * Sets the Environment instance associated with this instance.
   *
   * @param EnvironmentInterface $environment
   *   The Environment instance.
   *
   * @throws \InvalidArgumentException
   *   If the hosting sitegroup has not been set or if the hosting environment
   *   name has not been set.
   */
  public function setEnvironment(EnvironmentInterface $environment) {
    if (!empty($this->environment)) {
      throw new \RuntimeException('The SSH environment can only be set once.');
    }
    $sitegroup = $environment->getFullyQualifiedSitegroup();
    if (empty($sitegroup)) {
      throw new \InvalidArgumentException('The environment argument must include the hosting sitegroup.');
    }
    $env_name = $environment->getEnvironmentName();
    if (empty($env_name)) {
      throw new \InvalidArgumentException('The environment argument must include the environment name.');
    }
    $this->environment = $environment;
  }

  /**
   * {@inheritdoc}
   */
  public function isConfigured() {
    return $this->configured;
  }

  /**
   * {@inheritdoc}
   */
  public function launchFailed() {
    return $this->launchFailed;
  }

  /**
   * Sets the flag that indicates the container launch has failed.
   */
  protected function setLaunchFailed() {
    $this->launchFailed = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfigured($configured) {
    $this->configured = (bool) $configured;
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $process = new ContainerProcess();
    $process->setEnvironment($this->getEnvironment(FALSE));
    $process->setContainer($this);
    $wip_id = $this->getWipId();
    if (!empty($wip_id)) {
      $process->setWipId($this->getWipId());
    }
    $process->setStartTime(time());
    $process->setPid($this->getPid());
    return $process;
  }

  /**
   * Determines whether the container is ready to be configured.
   *
   * @return bool
   *   TRUE if the container is ready to be configured, otherwise FALSE.
   */
  protected function isStarted() {
    return $this->started;
  }

  /**
   * Sets the started state on the container object.
   *
   * @param bool $started
   *   Setting TRUE here indicates that the container is ready to be configured.
   */
  protected function setStarted($started) {
    $this->started = (bool) $started;
  }

  /**
   * Determines if the container has entered a running state.
   *
   * @return bool
   *   TRUE if the container is in a running state, otherwise FALSE.
   */
  protected function isRunning() {
    return $this->running;
  }

  /**
   * Sets the running state on this container object.
   *
   * @param bool $running
   *   TRUE if the container is in a running state, otherwise FALSE.
   */
  protected function setRunning($running) {
    $this->running = (bool) $running;
  }

  /**
   * {@inheritdoc}
   */
  public function getPid() {
    if (empty($this->pid)) {
      throw new \RuntimeException('Unable to get the PID before it has been set.');
    }
    return $this->pid;
  }

  /**
   * {@inheritdoc}
   */
  public function setPid($pid) {
    if (empty($pid) || !is_string($pid)) {
      throw new \InvalidArgumentException('The "pid" parameter must be a non-empty string.');
    }
    if (isset($this->pid)) {
      throw new \RuntimeException('The "pid" parameter can only be set once.');
    }
    $this->pid = $pid;
  }

  /**
   * {@inheritdoc}
   */
  public function setWipId($wip_id) {
    if (!is_int($wip_id) || $wip_id <= 0) {
      throw new \InvalidArgumentException('The "wip_id" parameter must be a positive integer.');
    }
    $this->wipId = $wip_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getWipId() {
    return $this->wipId;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupName() {
    if (empty($this->groupName)) {
      throw new \RuntimeException('Unable to retrieve the task group name before it has been set.');
    }
    return $this->groupName;
  }

  /**
   * {@inheritdoc}
   */
  public function setGroupName($group_name) {
    if (empty($group_name) || !is_string($group_name)) {
      throw new \InvalidArgumentException('The "group_name" parameter must be a non-empty string.');
    }
    $this->groupName = $group_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getHost() {
    if (!$this->isRunning()) {
      throw new \RuntimeException('Cannot get the host of a container that is not running.');
    }

    if (empty($this->host)) {
      $this->loadHostAndPorts();
    }
    return $this->host;
  }

  /**
   * {@inheritdoc}
   */
  public function setHost($host) {
    $this->host = $host;
  }

  /**
   * {@inheritdoc}
   */
  public function getPort($port_type_id) {
    if (!in_array($port_type_id, array(
      ContainerInterface::PORT_TYPE_HTTP,
      ContainerInterface::PORT_TYPE_HTTPS,
      ContainerInterface::PORT_TYPE_SSH,
    ))) {
      throw new \RuntimeException('Requested port type %s is not recognised.');
    }

    if (!$this->isRunning()) {
      throw new \RuntimeException('Cannot get ports from a container that is not running.');
    }

    $result = NULL;
    if (empty($this->ports)) {
      $this->loadHostAndPorts();
    }
    if (!empty($this->ports[$port_type_id])) {
      $result = $this->ports[$port_type_id];
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getPortMappings() {
    if (!$this->isRunning()) {
      throw new \RuntimeException('Cannot get ports from a container that is not running.');
    }
    if (empty($this->ports)) {
      $this->loadHostAndPorts();
    }
    return $this->ports;
  }

  /**
   * Populates the container object's host and ports.
   *
   * All container implementations must either implement a system-specific
   * version of this function that populates both host and ports OR
   * implementations may choose to override the getHost and getPort methods
   * instead in cases where that is more appropriate (e.g. if host and port are
   * directly retrieved every time, or have been previously set).
   */
  protected function loadHostAndPorts() {
    // The base implementation does nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function hasStarted() {
    // Return the cached status if we know the container started.
    if ($this->isStarted()) {
      return TRUE;
    }

    if (!$this->isRunning()) {
      if (!$this->checkRunning()) {
        return FALSE;
      }
      $this->setRunning(TRUE);
    }
    $this->setStarted(TRUE);
    return $this->isStarted();
  }

  /**
   * {@inheritdoc}
   */
  public function hasStopped() {
    return FALSE;
  }

  /**
   * Queries the container to determine whether it is running.
   *
   * Implementations of this function should check with the container backend to
   * determine whether it has entered a running state. Alternatively, container
   * implementations may override the base hasStarted() method here and not call
   * this.
   */
  protected function checkRunning() {
    // Check with the container backend whether it has entered the running
    // state.
  }

  /**
   * {@inheritdoc}
   */
  public function initializeContainer(
    ContainerProcessInterface $process,
    WipTaskConfig $configuration
  ) {
    $group_name = $this->getGroupName();

    if (empty($group_name)) {
      throw new \RuntimeException('Unable to initialize container before the group name has been set.');
    }

    // Make sure login is possible.
    $environment = $this->getEnvironment();
    $ssh = $this->getSsh();
    $ssh_login_successful = $ssh->login($environment->getUser(), $environment->getPassword());
    if (!$ssh_login_successful) {
      $message = 'Unable to log into the wip task container. This is usually normal as there is a delay between when we start a container and when it is ready to accept SSH connections. We need to poll to check if we can log in but it will inevitably fail a number of times before it is ultimately successful. Trying again...';
      throw new \RuntimeException($message);
    }

    $this->debug(sprintf('Initializing container %s with task ID %d', $this->getPid(), $this->getWipId()));

    /** @var SignalCallbackHttpTransportInterface $callback_handler */
    $callback_handler = $this->dependencyManager->getDependency('acquia.wip.handler.signal');
    $url = $callback_handler->getCallbackUrl($this->getWipId());

    // Initialize a callback that will be used when the container has completed.
    $callback = new UriCallback($url);
    $callback_data = new \stdClass();
    $callback_data->pid = $this->getPid();
    $callback_data->startTime = $process->getStartTime();
    $callback_data->classId = '$acquia.wip.signal.container.complete';
    $callback->setData($callback_data);
    try {
      $authentication = WipFactory::getObject('acquia.wip.uri.authentication');
      $callback->setAuthentication($authentication);
    } catch (\Exception $e) {
      // No authentication is being used.
    }
    $configuration->setCallback($callback);

    // Create a callback for data to be transported to/from the container.
    // The data callback can share the completion callback URI.
    $data_callback = new UriCallback($url);
    $data_callback_data = new \stdClass();
    $data_callback_data->pid = $this->getPid();
    $data_callback_data->startTime = $process->getStartTime();
    $data_callback_data->classId = 'acquia.wip.signal.cleanup';
    $data_callback->setData($callback_data);
    try {
      $authentication = WipFactory::getObject('acquia.wip.uri.authentication');
      $data_callback->setAuthentication($authentication);
    } catch (\Exception $e) {
      // No authentication is being used.
    }
    // Set the callback into the options.
    $options = $configuration->getOptions();
    $options->cleanupResourceCallback = $data_callback;

    $content = serialize($configuration);

    $scp = $this->getScp($ssh);
    $ssh->exec(sprintf('mkdir -p %s', self::WIP_OBJECT_CONTAINER_PATH));
    $scp_result = $scp->put(sprintf('%s/object', self::WIP_OBJECT_CONTAINER_PATH), $content);
    if ($scp_result === FALSE) {
      $message = 'The scp command failed to transfer the task configuration.';
      throw new \RuntimeException($message);
    }

    $ssh_result = $ssh->exec(
      sprintf(
        'cd /wip-service && bin/wipctl add %s/object',
        self::WIP_OBJECT_CONTAINER_PATH
      )
    );
    if ($ssh_result === FALSE || $ssh->getExitStatus() !== 0) {
      $message = 'Failed to issue the `wipctl add [object]` command to start the containerized WIP. Exit code: %d; stdout: %s; stderr: %s';
      $message = sprintf(
        $message,
        $ssh->getExitStatus(),
        $ssh_result,
        $ssh->getStdError()
      );
      throw new \RuntimeException($message);
    }
    $this->setConfigured(TRUE);
  }

  /**
   * Returns an SSH instance that can communicate with the container.
   *
   * @return SSH2
   *   The SSH2 instance to use for executing SSH commands to the
   *   pre-determined host and port.
   */
  protected function getSsh() {
    if (isset(static::$testSsh)) {
      return static::$testSsh;
    }

    if (!$this->getHost() || !$this->getPort(ContainerInterface::PORT_TYPE_SSH)) {
      throw new \RuntimeException('Unable to use SSH before host and port have been determined.');
    }
    $result = new SSH2($this->getHost(), $this->getPort(ContainerInterface::PORT_TYPE_SSH));
    return $result;
  }

  /**
   * Sets this container object to use the specified SSH instance for testing.
   *
   * @param SSH2 $ssh
   *   The SSH2 instance to use for transferring files.
   */
  public static function setTestSsh(SSH2 $ssh) {
    static::$testSsh = $ssh;
  }

  /**
   * Returns an SCP instance to use for transferring files.
   *
   * @param SSH2 $ssh
   *   An SSH2 instance required to construct the SCP object.
   *
   * @return SCP
   *   The SCP instance to use for transferring files.
   */
  protected function getScp(SSH2 $ssh) {
    if (isset(static::$testScp)) {
      return static::$testScp;
    }

    return new SCP($ssh);
  }

  /**
   * Sets this container object to use the specified SCP instance for testing.
   *
   * @param SCP $scp
   *   The SCP instance to use for transferring files.
   */
  public static function setTestScp(SCP $scp) {
    static::$testScp = $scp;
  }

  /**
   * Logs debugging messages.
   *
   * This will only produce actual log messages if the service is globally
   * configured to be in debug mode (@see config.global.yml).
   *
   * @param string $message
   *   The message to log.
   */
  protected function debug($message) {
    if ($this->debug) {
      $this->getWipLog()->log(WipLogLevel::DEBUG, $message, $this->getWipId());
    }
  }

  /**
   * Indicates whether this application is in debug mode.
   *
   * @return bool
   *   TRUE if the application is in debug mode, otherwise FALSE.
   */
  public function getDebug() {
    return $this->debug;
  }

  /**
   * Sets whether the application is in debug mode for containers.
   *
   * @param bool $debug
   *   TRUE if the application is in debug mode, otherwise FALSE.
   */
  public function setDebug($debug) {
    $this->debug = $debug;
  }

  /**
   * Gets the logger.
   *
   * @return WipLogInterface
   *   The logger.
   */
  public function getWipLog() {
    $result = $this->log;
    if (empty($result)) {
      $result = $this->dependencyManager->getDependency('acquia.wip.wiplog');
    }
    return $result;
  }

  /**
   * Marks the specified key as a secure key.
   *
   * This is used to secure data stored in the database, helping prevent issues
   * in which passwords or SSH keys are written to the database in clear text.
   *
   * @param string $key
   *   The key containing sensitive information.
   */
  public function addSecureOverrideKey($key) {
    if (!in_array($key, $this->secureOverrideKeys)) {
      $this->secureOverrideKeys[] = $key;
    }
  }

  /**
   * Indicates whether the value associated with the specified key is secure.
   *
   * @param string $key
   *   The key name.
   *
   * @return bool
   *   TRUE if the value is secure; FALSE otherwise.
   */
  public function isSecureOverrideKey($key) {
    return in_array($key, $this->secureOverrideKeys);
  }

}
