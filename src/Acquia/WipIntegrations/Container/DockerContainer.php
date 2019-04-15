<?php

namespace Acquia\WipIntegrations\Container;

use Acquia\WipService\App;
use Acquia\Wip\Container\AbstractContainer;
use Acquia\Wip\Container\ContainerInterface;
use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\WipFactory;
use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;

/**
 * Local docker-based container implementation.
 */
class DockerContainer extends AbstractContainer implements ContainerInterface, DependencyManagedInterface {

  /**
   * The base URL of the application.
   *
   * This is used to form the REST API endpoint (for generating callback URLs
   * for signals and the logs REST resource for streaming log messages back to
   * the controller).
   *
   * @var string
   *   The base URL.
   */
  private $baseUrl;

  /**
   * The hostname or IP address of the Docker host.
   *
   * The Docker host is the machine on which Docker commands may be executed.
   *
   * @var string
   *   The host or IP address.
   */
  private $dockerHost;

  /**
   * The username on the Docker host.
   *
   * This should be the name of a user account on the Docker host (your local
   * machine) that has access to execute Docker commands.
   *
   * This implementation must be able to access the Docker host via passwordless
   * SSH.
   *
   * @var string
   *   The username.
   */
  private $dockerUsername;

  /**
   * The absolute path to the workspace on the Docker host.
   *
   * This is the wip-service workspace, which will be mounted into the container
   * on launch. Any changes to the files on the Docker host (your local machine)
   * will be immediately reflected in the container(s), making it easy to debug
   * and develop, even while tasks are running.
   *
   * @var string
   *   The path to the workspace.
   */
  private $dockerWorkspace;

  /**
   * The name of the virtual machine.
   *
   * This is only relevant for non-Linux installations where a virtual machine
   * is necessary for running Docker.
   *
   * @var string
   *   The VM name.
   */
  private $dockerMachineName;

  /**
   * The wip task container image to use.
   *
   * When using ECS for running containers or pulling a container down from
   * Docker Hub to run it locally, this should be the fully qualified container
   * name with an optional namespace to avoid clashes with other developers
   * working on the same ticket e.g. "acquia/wip-service:MS-123-namespace".
   *
   * @var string
   *   The container image.
   */
  private $dockerContainerImage;

  /**
   * The Docker system being used to launch containers.
   *
   * Defaults to "docker-machine", but could also be set to "linux".
   *
   * @var string
   *   The Docker system.
   */
  private $dockerSystem;

  /**
   * Whether the workspace should be mounted into the wip task container.
   *
   * @var bool
   */
  private $dockerMount;

  /**
   * Custom environment variable overrides.
   *
   * @var array
   */
  private $overrides = array();

  /**
   * Creates a new instance of DockerContainer.
   */
  public function __construct() {
    parent::__construct();
    $app = App::getApp();
    $this->baseUrl = $app['config.global']['base_url'];
    $this->dockerHost = $app['config.docker']['host'];
    $this->dockerUsername = $app['config.docker']['username'];
    $this->dockerWorkspace = $app['config.docker']['workspace'];
    $this->dockerMachineName = $app['config.docker']['vm_name'];
    $this->dockerContainerImage = $app['config.docker']['image'];
    $this->dockerSystem = $app['config.docker']['system'];
    $this->dockerMount = $app['config.docker']['mount'];
    $this->setDebug($app['config.docker']['debug']);
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    $parent_dependencies = parent::getDependencies();
    return array(
      'acquia.wip.ssh_service.local' => 'Acquia\Wip\Ssh\SshServiceInterface',
      'acquia.wip.ssh.client'        => 'Acquia\Wip\Ssh\SshInterface',
    ) + $parent_dependencies;
  }

  /**
   * Gets the port mappings for the wip task container.
   *
   * To enable multiple containers to be able to run concurrently, the values of
   * the host ports are randomly derived from a range of port numbers. This is
   * intended to attempt to avoid clashes with ports that are already bound on
   * the host.
   *
   * @return array
   *   An associative array, with container ports as keys and host ports as
   *   values.
   */
  private function getPortMapping() {
    return array(
      ContainerInterface::PORT_TYPE_SSH => rand(2222, 3222),
      ContainerInterface::PORT_TYPE_HTTP => rand(8081, 9081),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function addContainerOverride($key, $value, $secure = FALSE) {
    if (!is_string($key) || empty($key)) {
      throw new \InvalidArgumentException('The "key" parameter must be a non-empty string.');
    }
    if (!is_string($value) || empty($value)) {
      throw new \InvalidArgumentException('The "value" parameter must be a non-empty string.');
    }
    $this->overrides[$key] = $value;
    if ($secure) {
      $this->addSecureOverrideKey($key);
    }
  }

  /**
   * Gets the environment variable overrides for the wip task container.
   *
   * @return array
   *   An associative array, with the environment variable names as the keys and
   *   the values of the environment variables as values.
   */
  private function getEnvironmentVariableOverrides() {
    $user = App::getApp()['security.client_users']['ROLE_ADMIN'];
    $result = array(
      'ACQUIA_WIP_WIPFLUSHINGLOGSTORE_ENDPOINT' => sprintf('%s/logs', $this->baseUrl),
      'BUGSNAG_API_KEY' => App::getApp()['bugsnag.options']['apiKey'],
      'BUGSNAG_STAGE' => App::getApp()['bugsnag_stage'],
      'WIP_SERVICE_USERNAME' => $user['username'],
      'WIP_SERVICE_PASSWORD' => $user['password'],
    );

    if (!getenv('WIP_CONTAINERIZED')) {
      $result['SEGMENT_PROJECT_KEY'] = App::getApp()['segment.options']['project_key'];
      $result['SEGMENT_PROJECT_KEY'] = App::getApp()['user']->getUsername();
    }

    foreach ($this->overrides as $key => $value) {
      $result[$key] = $value;
    }

    return $result;
  }

  /**
   * Gets the volumes to map into the wip task container.
   *
   * @return array
   *   An associative array, with the container paths as the keys and the host
   *   paths as values.
   */
  private function getVolumeMapping() {
    $result = array();
    if ($this->dockerMount && !empty($this->dockerWorkspace)) {
      $result['/wip-service'] = $this->dockerWorkspace;
    }
    return $result;
  }

  /**
   * Formats the command that will be executed on the docker host.
   *
   * Handles adding any additional host-dependent statements necessary for
   * executing docker commands.
   *
   * @param string $command
   *   The docker command that will be executed.
   *
   * @return string
   *   The supplemented docker command that will be executed containing any
   *   additional host-dependent statements.
   */
  private function formatDockerCommand($command) {
    switch ($this->dockerSystem) {
      case 'docker-machine':
        $vm_name = $this->dockerMachineName;
        $env = 'export PATH=/usr/local/bin:$PATH';
        // Source the docker environment variables.
        $command = sprintf(
          '%s; eval $(docker-machine env %s) && %s',
          $env,
          $vm_name,
          $command
        );
        break;

      case 'linux':
        // @todo This may not be enough to support Linux docker.
        break;
    }

    return $command;
  }

  /**
   * {@inheritdoc}
   */
  public function setEnvironment(EnvironmentInterface $environment) {
    $environment = clone($environment::getRuntimeEnvironment());
    $environment->setServers(array('localhost'));
    $environment->selectNextServer();
    parent::setEnvironment($environment);
  }

  /**
   * Executes a command on the Docker host.
   *
   * @param string $command
   *   The command to execute.
   *
   * @return object
   *   A result object containing the following properties:
   *   - command: The command that was executed.
   *   - stdout: Output from the command invocation.
   *   - stderr: Error output from the command invocation.
   *   - exit_code: The exit status of the command invocation.
   */
  private function executeCommand($command) {
    $private_key_path = WipFactory::getString('$acquia.wip.service.private_key_path');
    $port = ContainerInterface::PORT_TYPE_SSH;
    $private_key = new RSA();
    $private_key->loadKey(file_get_contents($private_key_path));
    if ($private_key === FALSE) {
      throw new \RuntimeException(sprintf(
        'Could not load private key: %s',
        $private_key_path
      ));
    }
    $ssh = new SSH2($this->dockerHost, $port);
    $ssh_login_successful = $ssh->login($this->dockerUsername, $private_key);
    if (!$ssh_login_successful) {
      $message = 'Unable to log into the docker host. Make sure you can successfully connect to the docker host (your local machine) via passwordless SSH e.g. `ssh -i %s %s@%s -p %s`.';
      throw new \RuntimeException(sprintf(
        $message,
        $private_key_path,
        $this->dockerUsername,
        $this->dockerHost,
        $port
      ));
    }

    $stdout = $ssh->exec($this->formatDockerCommand($command));
    $result = new \stdClass();
    $result->command = $command;
    $result->stdout = trim($stdout);
    $result->stderr = $ssh->getStdError();
    $result->exit_code = $ssh->getExitStatus();
    $this->debug(sprintf(
      'Docker host SSH command result: %s',
      var_export($result, TRUE)
    ));
    return $result;
  }

  /**
   * Gets the docker run command options.
   *
   * @return string[]
   *   A numeric array of docker run command options and their values.
   */
  private function getRunCommandOptions() {
    $options = array();
    // Publish a container's port(s) to the host (host-port:container-port).
    foreach ($this->getPortMapping() as $container_port => $host_port) {
      $options[] = sprintf('-p %d:%d', $host_port, $container_port);
    }
    // Set environment variables.
    foreach ($this->getEnvironmentVariableOverrides() as $name => $value) {
      $options[] = sprintf('-e %s=%s', $name, escapeshellarg($value));
    }
    // Mount volumes.
    foreach ($this->getVolumeMapping() as $container_path => $host_path) {
      $options[] = sprintf('-v %s:%s', $host_path, $container_path);
    }
    // Run container in background (and print the container ID).
    $options[] = '-d';
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    // Run the docker container.
    $command = sprintf(
      'docker run %s %s',
      implode(' ', $this->getRunCommandOptions()),
      $this->dockerContainerImage
    );
    $result = $this->executeCommand($command);
    if ($result->exit_code !== 0 || empty($result->stdout)) {
      throw new \RuntimeException(sprintf(
        'The docker container invocation failed. Exit code: %d; Stdout: %s; Stderr: %s;',
        $result->exit_code,
        $result->stdout,
        $result->stderr
      ));
    }
    // If the configured container image is not found locally but it does exist
    // on Docker Hub, it will be pulled from there. In this case, stdout will
    // contain the output from the pull operation as well as the container ID.
    // The container ID should be the last line in both cases, so we extract
    // that here.
    $lines = explode("\n", $result->stdout);
    $container_id = array_pop($lines);
    $this->setPid($container_id);
    $this->debug(sprintf(
      'Started the docker task with PID %s',
      $this->getPid()
    ));
    $this->loadHostAndPorts();
    return parent::run();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkRunning() {
    if (!$this->getPid()) {
      throw new \RuntimeException(
        'Unable to determine if the container is running because no PID has been set.'
      );
    }

    // Check with docker that the container is running.
    $command = sprintf(
      'docker inspect --format="{{.State.Running}}" %s',
      $this->getPid()
    );
    $result = $this->executeCommand($command);
    return $result->exit_code === 0 || $result->stdout === 'true';
  }

  /**
   * {@inheritdoc}
   */
  public function kill() {
    $command = sprintf('docker kill %s', $this->getPid());
    $result = $this->executeCommand($command);

    if ($result->exit_code !== 0 || empty($result->stdout)) {
      throw new \RuntimeException(sprintf(
        'Failed to kill the container %s; Exit code: %d; Stdout: %s; Stderr: %s;',
        $this->getPid(),
        $result->exit_code,
        $result->stdout,
        $result->stderr
      ));
    }
  }

  /**
   * Establishes the local host and ports of the container.
   */
  protected function loadHostAndPorts() {
    if (empty($this->pid)) {
      throw new \RuntimeException(
        'Attempted to obtain host and ports from a Docker container that does not appear to be running.'
      );
    }

    $host = $this->getDockerHost();
    $this->setHost($host);
    $this->ports = $this->getDockerPorts();

    $data = array(
      'pid' => $this->getPid(),
      'host' => $host,
      'ports' => $this->ports,
    );
    $this->debug(sprintf(
      'Loaded the host and ports for the wip task container: %s',
      print_r($data, TRUE)
    ));
  }

  /**
   * Gets the docker host's hostname or IP address.
   *
   * @return string
   *   The docker host's hostname or IP address.
   */
  private function getDockerHost() {
    $host = $this->dockerHost;
    $command = sprintf('docker-machine ip %s', $this->dockerMachineName);
    $result = $this->executeCommand($command);
    // By the point this method is called, the most likely reason for a failure
    // executing the command is that docker-machine is not being used, at which
    // point we assume that the docker installation is most likely being used on
    // bare Linux, and so the host to SSH to is the provided Docker host.
    if ($result->exit_code === 0) {
      $host = trim($result->stdout);
    }
    return $host;
  }

  /**
   * Gets the wip task container's port mappings.
   *
   * Ports are allocated in a semi-random fashion. This method calls the docker
   * port command and ensures that the correct ports are used.
   *
   * @return array
   *   An associative array, with the container ports as the keys and the host
   *   ports as the values.
   */
  private function getDockerPorts() {
    $ports = array();

    $command = sprintf('docker port %s', $this->getPid());
    $result = $this->executeCommand($command);

    // @codingStandardsIgnoreStart
    // Parse the ports from the command output. The format of which is:
    // 22/tcp -> 0.0.0.0:2222
    // 80/tcp -> 0.0.0.0:8081
    // @codingStandardsIgnoreEnd
    $lines = explode("\n", $result->stdout);
    foreach ($lines as $line) {
      preg_match('~(\d+)[^:]+:(\d+)$~', $line, $matches);
      $container_port = $matches[1];
      $host_port = $matches[2];
      if (is_numeric($host_port)) {
        $ports[$container_port] = intval($host_port);
      }
    }
    return $ports;
  }

  /**
   * {@inheritdoc}
   */
  public function getContainerStatus($force_load = FALSE) {
    return 'RUNNING';
  }

  /**
   * {@inheritdoc}
   */
  public function getContainerNextStatus($force_load = FALSE) {
    return 'RUNNING';
  }

}
