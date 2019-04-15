<?php

namespace Acquia\Wip\Ssh;

use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\DependencyManager;
use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\Security\AuthenticationInterface;
use Acquia\Wip\Security\SecureTrait;
use Acquia\Wip\Signal\SignalCallbackHttpTransportInterface;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogEntryInterface;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipLogLevel;

/**
 * Invokes commands on remote webnodes using SSH.
 *
 * This class always uses the Ssh wrapper script, and decodes the result.
 */
class Ssh implements SshInterface, DependencyManagedInterface {

  use SecureTrait;

  /**
   * The property name in the WipFactory configuration file for exec redirect.
   *
   * Generally this will not be defined, and defaults to /dev/null.  For
   * debugging purposes it is often useful to retrieve the output of the exec
   * call.  Simply set this property in the configuration file.
   */
  const PROPERTY_COMMAND_REDIRECT_FILE = '$acquia.wip.ssh.command_redirect_file';

  /**
   * The property name for the temporary directory override.
   *
   * This property overrides the standard hosting temp directory.
   */
  const PROPERTY_TEMP_DIR_OVERRIDE = '$acquia.wip.ssh.tempdir';

  /**
   * The DependencyManager instance.
   *
   * @var DependencyManager
   */
  private $dependencyManager;

  /**
   * The Environment instance, which provides the sitegroup and server.
   *
   * @var EnvironmentInterface
   */
  private $environment;

  /**
   * A bit of text that reveals the purpose of this Ssh instance.
   *
   * @var string
   */
  private $description;

  /**
   * The logger.
   *
   * @var WipLogInterface
   */
  private $logger;

  /**
   * The ID this instance will log against.
   *
   * @var int
   */
  private $wipId;

  /**
   * The command to execute (does not include the SSH wrapper).
   *
   * @var string
   */
  private $command;

  /**
   * The user who should execute the command.
   *
   * @var string
   */
  private $user = NULL;

  /**
   * The SshServiceInterface instance to use.
   *
   * @var SshServiceInterface
   */
  private $sshService;

  /**
   * The wrapper that executes the command and gathers output.
   *
   * For now we will assume the wrapper is in /mnt/tmp. Eventually we want to
   * get this into hosting so we can simply use '\ssh_wrapper'.
   *
   * This value is replaceable for unit testing.
   *
   * @var string
   */
  private static $sshWrapper = NULL;

  /**
   * Options that are passed in to the ssh_wrapper on every call.
   *
   * This is used for unit testing to change the storage location for the log
   * files. This is required because the unit tests generally are run outside of
   * the standard hosting environment.
   *
   * @var string
   */
  private static $globalExecOptions = '';

  /**
   * The exit codes that indicate a successful Ssh execution.
   *
   * @var int[]
   */
  private $successCodes = array(0);

  /**
   * The result interpreter.
   *
   * @var SshResultInterpreterInterface
   */
  private $interpreter = NULL;

  /**
   * The default log level.
   *
   * @var int
   */
  private $logLevel = WipLogLevel::INFO;

  /**
   * Creates a new instance of Ssh for executing commands on a remote server.
   */
  public function __construct() {
    $this->dependencyManager = new DependencyManager();
    $dependencies = $this->getDependencies();
    if (!empty($dependencies)) {
      $this->dependencyManager->addDependencies($this->getDependencies());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function initialize(EnvironmentInterface $environment, $description, WipLogInterface $logger, $id) {
    $this->setEnvironment($environment);
    $this->setDescription($description);
    $this->setLogger($logger);
    $this->setWipId($id);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.ssh_service' => 'Acquia\Wip\Ssh\SshServiceInterface',
      'acquia.wip.handler.signal' => 'Acquia\Wip\Signal\SignalCallbackHttpTransportInterface',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEnvironment() {
    return $this->environment;
  }

  /**
   * Sets the Environment instance associated with this Ssh instance.
   *
   * @param EnvironmentInterface $environment
   *   The Environment instance.
   *
   * @throws \InvalidArgumentException
   *   If the hosting sitegroup has not been set or if the hosting environment
   *   name has not been set or if there is no current server.
   */
  protected function setEnvironment(EnvironmentInterface $environment) {
    if (!empty($this->environment)) {
      throw new \RuntimeException('Ssh environment can only be set once.');
    }
    $sitegroup = $environment->getSitegroup();
    if (empty($sitegroup)) {
      throw new \InvalidArgumentException('The environment argument must include the hosting sitegroup.');
    }
    $env_name = $environment->getEnvironmentName();
    if (empty($env_name)) {
      throw new \InvalidArgumentException('The environment argument must include the environment name.');
    }
    $server = $environment->getCurrentServer();
    if (empty($server)) {
      throw new \InvalidArgumentException('The environment argument must include a selected server.');
    }
    $this->environment = $environment;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * Sets the description which indicates the purpose of this Ssh instance.
   *
   * @param string $description
   *   The description.
   *
   * @throws \InvalidArgumentException
   *   If the description argument is not a string.
   */
  protected function setDescription($description) {
    if (!empty($this->description)) {
      throw new \RuntimeException('Ssh description can only be set once.');
    }
    if (!is_string($description) || empty($description)) {
      throw new \InvalidArgumentException('The description argument must be a non-empty string.');
    }
    $this->description = $description;
  }

  /**
   * {@inheritdoc}
   */
  public function getLogger() {
    return $this->logger;
  }

  /**
   * Sets the logger used with this Ssh instance.
   *
   * @param WipLogInterface $logger
   *   The logger.
   */
  public function setLogger(WipLogInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * Sets the ID this object will log against.
   *
   * @param int $wip_id
   *   The ID.
   */
  public function setWipId($wip_id) {
    // Checking this for consistency with other properties - remove if at any
    // point it no longer makes sense.
    if (!empty($this->wipId)) {
      throw new \RuntimeException('The ssh wip ID can only be set once.');
    }
    if (!is_int($wip_id)) {
      throw new \InvalidArgumentException('The wip_id argument must be an integer.');
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
  public function setCommand($command) {
    $this->command = $command;
  }

  /**
   * {@inheritdoc}
   */
  public function switchUser($user) {
    $this->user = $user;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUser() {
    return $this->user;
  }

  /**
   * {@inheritdoc}
   */
  public function getCommand() {
    return $this->command;
  }

  /**
   * {@inheritdoc}
   */
  public function getSecureCommand() {
    if ($this->isSecure() && !$this->isInDebugMode()) {
      return WipLogEntryInterface::OUTPUT_SUPPRESSED_MESSAGE;
    } else {
      return $this->getCommand();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function exec($options = '--no-logs') {
    $command = $this->getCommand();
    if (empty($command)) {
      $message = sprintf('Ssh command not set for %s', $this->getDescription());
      $this->getLogger()->log(WipLogLevel::ERROR, $message, $this->getWipId());
      throw new \RuntimeException($message);
    }
    $environment = $this->getEnvironment();
    try {
      $exec_result = $this->invokeWrapperOperation('exec', $command, $options);
      $wrapped_command = $this->getCommandWrapper('exec', $command, $options);

      // Now parse the output of the ssh wrapper to get the result.
      $result = $this->parseResult($exec_result);
      // Make sure the start time has been set.
      try {
        $result->setStartTime($exec_result->getStartTime());
      } catch (\Exception $e) {
        // Ignore.
      }
      // Make sure the end time has been set.
      try {
        $result->setEndTime(time());
      } catch (\Exception $e) {
        // Ignore.
      }
      $result->setSuccessExitCodes($this->getSuccessExitCodes());
      $result->setLogLevel($this->getLogLevel());
      $result->setSecure($this->isSecure());
      $interpreter = $this->getResultInterpreter();
      if (!empty($interpreter)) {
        $result->setResultInterpreter($interpreter);
      }
      $log_level = WipLogLevel::ERROR;
      if ($result->isSuccess()) {
        $log_level = WipLogLevel::TRACE;
      }

      $this->getLogger()
        ->multiLog(
          $this->getWipId(),
          $log_level,
          sprintf('Synchronous ssh - %s completed in %d seconds', $this->getDescription(), $result->getRuntime()),
          $this->getDebugLevel(),
          sprintf(
            ' - exit: %s; stdout: %s; stderr: %s, server: %s || COMMAND: %s [%s]',
            $result->getExitCode(),
            $result->getSecureStdout(),
            $result->getSecureStderr(),
            $environment->getCurrentServer(),
            $this->getCommandWrapper('exec', $this->getSecureCommand(), $options),
            $this->getSecureCommand()
          )
        );
    } catch (\Exception $e) {
      $this->logger->multiLog(
        $this->getWipId(),
        WipLogLevel::ERROR,
        sprintf('Synchronous ssh failed - %s, message: %s', $this->getDescription(), $e->getMessage()),
        $this->getLogLevel(),
        sprintf(
          ' - server: %s, command: %s',
          $environment->getCurrentServer(),
          $this->getCommandWrapper('exec', $this->getSecureCommand(), $options)
        )
      );
      throw $e;
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function execCommand($command, $options = '--no-logs') {
    $this->setCommand($command);
    return $this->exec($options);
  }

  /**
   * {@inheritdoc}
   */
  public function invokeWrapperOperation($operation, $command, $options) {
    $options .= ' ' . self::getGlobalExecOptions();
    $result = NULL;
    $wrapped_command = $this->getCommandWrapper($operation, $command, $options);
    $environment = $this->getEnvironment();
    try {
      $ssh_service = $this->getSshService($environment);
      $result = $ssh_service->exec($wrapped_command);
    } catch (\Exception $e) {
      $message = sprintf('Failed to execute command %s: %s', $command, $e->getMessage());
      $this->logger->log(WipLogLevel::ERROR, $message, $this->getWipId());
      throw $e;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function execAsync($options = '', $data = NULL) {
    $command = $this->getCommand();
    if (empty($command)) {
      $message = sprintf('Ssh command not set for %s', $this->getDescription());
      $this->getLogger()->log(WipLogLevel::ERROR, $message, $this->getWipId());
      throw new \RuntimeException($message);
    }
    $options .= ' ' . self::getGlobalExecOptions();
    $result = NULL;

    // Serialize additional information that the process may use to call back.
    if (!$data) {
      $data = new \stdClass();
      try {
        // Figure out if the certification should not be verified within the
        // ssh_wrapper script. This behavior is required in the vast majority
        // of our test environments because we use self-signed certificates.
        $verify = WipFactory::getBool('$acquia.wip.ssl.verifyCertificate', TRUE);
        if (!$verify) {
          $data->noVerifyCert = TRUE;
        }
      } catch (\Exception $e) {
        // Nothing to do here; the default is to always verify.
      }
      $data->report = TRUE;
    }

    // If a callback is requested, but no explicit callback URL was provided,
    // create one now.
    if (!empty($data->report)) {
      $options .= ' --report ';
      if (empty($data->callbackUrl)) {
        /** @var SignalCallbackHttpTransportInterface $handler */
        $handler = $this->dependencyManager->getDependency('acquia.wip.handler.signal');
        $data->callbackUrl = $handler->getCallbackUrl($this->getWipId());

        // Add authentication if needed.
        try {
          /** @var AuthenticationInterface $authentication */
          $authentication = WipFactory::getObject('acquia.wip.uri.authentication');
          $data->authUser = $authentication->getAccountId();
          $data->authSecret = $authentication->getSecret();
        } catch (\Exception $e) {
          // No authentication.
        }
      }
      // Request a SSH completion signal.
      $data->classId = '$acquia.wip.signal.ssh.complete';
    }

    $data->server = $this->getEnvironment()->getCurrentServer();
    $start_time = time();
    $data->startTime = $start_time;
    $options .= sprintf(" --data='%s'", base64_encode(serialize($data)));

    $environment = $this->getEnvironment();
    $wrapped_command = $this->getAsynchronousCommandWrapper($command, $options);

    try {
      $ssh_service = $this->getSshService($environment);
      $exec_result = $ssh_service->exec($wrapped_command);
      $pid = intval(trim($exec_result->getStdout()));

      // Set the start time at the point the command was actually sent. This
      // will be used in conjunction with the process ID to uniquely identify
      // the process on the remote server.
      $result = new SshProcess($environment, $this->getDescription(), $pid, $start_time, $this->getWipId());
      $result->setSshService($ssh_service);
      $result->setSuccessExitCodes($this->getSuccessExitCodes());
      $result->setSecure($this->isSecure());
      $interpreter = $this->getResultInterpreter();
      if (!empty($interpreter)) {
        $result->setResultInterpreter($interpreter);
      }

      $this->logger->multiLog(
        $this->getWipId(),
        WipLogLevel::TRACE,
        sprintf(
          'Asynchronous ssh - %s - proc: %s; server: %s ',
          $this->getDescription(),
          $pid,
          $environment->getCurrentServer()
        ),
        $this->getDebugLevel(),
        sprintf(
          ' || COMMAND: %s [%s]',
          $this->getCommandWrapper('exec', $this->getSecureCommand(), $options),
          $this->getSecureCommand()
        )
      );
    } catch (\Exception $e) {
      $this->logger->multiLog(
        $this->getWipId(),
        WipLogLevel::ERROR,
        sprintf('Asynchronous ssh failed - %s', $this->getDescription()),
        $this->getLogLevel(),
        sprintf(
          ' - server: %s, message: %s; command: %s',
          $environment->getCurrentServer(),
          $e->getMessage(),
          $this->getCommandWrapper('exec', $this->getSecureCommand(), $options)
        )
      );
      throw $e;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function execAsyncCommand($command, $options = '', $data = NULL) {
    $this->setCommand($command);
    return $this->execAsync($options, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function getSshService(EnvironmentInterface $environment) {
    if (!isset($this->sshService)) {
      $ssh_keys = new SshKeys();
      $this->sshService = $this->dependencyManager->getDependency('acquia.wip.ssh_service');
      $this->sshService->setEnvironment($environment);
      $this->sshService->setKeyPath($ssh_keys->getPrivateKeyPath($environment));
      $this->sshService->setSecure($this->isSecure());
    }
    return $this->sshService;
  }

  /**
   * {@inheritdoc}
   */
  public function setSshService(SshServiceInterface $ssh_service) {
    $this->sshService = $ssh_service;
  }

  /**
   * Returns a properly formed command string that uses the remote wrapper.
   *
   * @param string $operation
   *   The operation to perform on the remote wrapper e.g. "exec", "stdout",
   *   etc.
   * @param string $operation_arg
   *   An optional argument to the remote operation. For example, the argument
   *   for the "exec" operation is a string containing the Unix command to be
   *   executed.
   * @param string $options
   *   Optional. Other command line options to be passed to the remote wrapper.
   * @param bool $async
   *   Optional. If TRUE, the wrapper will be constructed for an asynchronous
   *   call.
   *
   * @return string
   *   The properly formatted command string that uses the remote wrapper.
   */
  public function getCommandWrapper($operation, $operation_arg = '', $options = '', $async = FALSE) {
    // Run as another user if specified.
    if (NULL !== $this->getUser()) {
      $options .= sprintf(' --switch-user %s', $this->getUser());
    }

    if (!empty($operation_arg)) {
      // Always encode the command.
      $operation_arg = base64_encode($operation_arg);
      $options .= ' --encoded';
    }

    // Check the override in case we are not using the standard hosting temp
    // directory.
    $temp_dir = WipFactory::getPath(self::PROPERTY_TEMP_DIR_OVERRIDE, '');
    if (!empty($temp_dir)) {
      $options .= sprintf(' --temp-dir %s', $temp_dir);
    }
    $prefix = '';
    if (!$async) {
      $prefix = 'TERM=dumb';
    }
    $environment = $this->getEnvironment();
    $wrapped_command = sprintf(
      '%s %s --%s %s %s --site "%s" --env "%s"',
      $prefix,
      self::getSshWrapper($environment),
      $operation,
      $operation_arg,
      $options,
      $environment->getSitegroup(),
      $environment->getEnvironmentName()
    );
    return $wrapped_command;
  }

  /**
   * Returns a properly formed command string for an asynchronous command.
   *
   * @param string $command
   *   The Unix command to execute.
   * @param string $options
   *   Optional command line options to be passed to the remote wrapper.
   *
   * @return string
   *   The properly formatted command string that uses the remote wrapper for
   *   asynchronous execution of the specified Unix command.
   */
  private function getAsynchronousCommandWrapper($command, $options = '') {
    $wrapper = $this->getCommandWrapper('exec', $command, '--silent ' . trim($options), TRUE);

    // Optionally redirect the exec output to a file for debugging purposes.
    $redirect_file = WipFactory::getPath(self::PROPERTY_COMMAND_REDIRECT_FILE, '/dev/null');
    $wrapped_command = sprintf('TERM=dumb nohup %s > %s 2>&1 & echo $!', $wrapper, $redirect_file);
    return $wrapped_command;
  }

  /**
   * {@inheritdoc}
   */
  public function parseResult(SshResultInterface $ssh_result) {
    $result = SshResult::fromObject(SshResult::objectFromJson($ssh_result->getStdout()));
    $result->setEnvironment($ssh_result->getEnvironment());
    return $result;
  }

  /**
   * Sets the Ssh wrapper path.
   *
   * This is used during testing.
   *
   * @param string $ssh_wrapper
   *   The full path to the Ssh wrapper script.
   */
  public static function setSshWrapper($ssh_wrapper = 'ssh_wrapper') {
    self::$sshWrapper = $ssh_wrapper;
  }

  /**
   * Gets the Ssh wrapper script path.
   *
   * @param EnvironmentInterface $environment
   *   The environment.
   *
   * @return string
   *   The full path to the Ssh wrapper script.
   */
  public static function getSshWrapper(EnvironmentInterface $environment) {
    $result = self::$sshWrapper;
    if (empty($result)) {
      $default = sprintf('/home/%s/bin/ssh_wrapper', $environment->getSitegroup());
      $result = WipFactory::getString('$acquia.wip.ssh.wrapper', $default);
    }
    return $result;
  }

  /**
   * Sets wrapper options that apply to every wrapper invocation.
   *
   * This is used in unit testing to change the path where log files are
   * written, as the unit tests do not run on a standard hosting environment.
   *
   * @param string $options
   *   Optional. The options. Call with no arguments to clear the global
   *   options.
   */
  public static function setGlobalExecOptions($options = '') {
    self::$globalExecOptions = $options;
  }

  /**
   * Gets wrapper options that apply to every wrapper invocation.
   *
   * @return string
   *   The options.
   */
  public static function getGlobalExecOptions() {
    return self::$globalExecOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function addSuccessExitCode($exit_code) {
    if (!is_int($exit_code) || $exit_code < 0) {
      throw new \InvalidArgumentException('The exit_code must be a positive integer.');
    }
    if (!in_array($exit_code, $this->successCodes)) {
      $this->successCodes[] = $exit_code;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSuccessExitCodes() {
    return $this->successCodes;
  }

  /**
   * {@inheritdoc}
   */
  public function setSuccessExitCodes($exit_codes) {
    $this->successCodes = array();
    foreach ($exit_codes as $code) {
      $this->addSuccessExitCode($code);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getResultInterpreter() {
    return $this->interpreter;
  }

  /**
   * {@inheritdoc}
   */
  public function setResultInterpreter(SshResultInterpreterInterface $interpreter) {
    $this->interpreter = $interpreter;
  }

  /**
   * {@inheritdoc}
   */
  public function setLogLevel($level) {
    $this->logLevel = $level;
  }

  /**
   * Gets the log level set into this Ssh instance.
   *
   * @return int
   *   The log level.
   */
  public function getLogLevel() {
    return $this->logLevel;
  }

  /**
   * Determines the log level for debug log messages.
   *
   * @return int
   *   The log level.
   */
  protected function getDebugLevel() {
    $result = WipLogLevel::DEBUG;
    if ($result < $this->getLogLevel()) {
      $result = $this->getLogLevel();
    }
    return $result;
  }

}
