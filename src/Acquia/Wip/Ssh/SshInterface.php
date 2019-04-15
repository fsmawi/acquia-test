<?php

namespace Acquia\Wip\Ssh;

use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\Security\SecureTraitInterface;
use Acquia\Wip\WipLogInterface;

/**
 * The SshInterface describes the interface for invoking SSH commands.
 */
interface SshInterface extends SecureTraitInterface {

  /**
   * Returns the Environment instance associated with this object.
   *
   * @return EnvironmentInterface
   *   The Environment instance.
   */
  public function getEnvironment();

  /**
   * Returns the description that identifies the purpose of this object.
   *
   * @return string
   *   The description.
   */
  public function getDescription();

  /**
   * Returns the logger associated with this object.
   *
   * @return WipLogInterface
   *   The logger associated with this object.
   */
  public function getLogger();

  /**
   * Returns the ID this instance will log against.
   *
   * @return int
   *   The ID.
   */
  public function getWipId();

  /**
   * Sets the command that will be executed.
   *
   * This setting is used in conjunction with the exec() and execAsync() methods
   * and is ignore in execCommand() and execAsyncCommand().
   *
   * @param string $command
   *   The command to execute.
   */
  public function setCommand($command);

  /**
   * Returns the command that will be executed.
   *
   * This setting is used in conjunction with the exec() and execAsync() methods
   * and is ignored in execCommand() and execAsyncCommand().
   *
   * @return string
   *   The command to execute.
   */
  public function getCommand();

  /**
   * Returns the command string or a suppressed message if secure.
   *
   * @return string
   *   The command to execute or a suppressed message.
   */
  public function getSecureCommand();

  /**
   * Execute the command as another user.
   *
   * @param string $user
   *   The user who should execute the command.
   *
   * @return SshInterface
   *   The Ssh object.
   */
  public function switchUser($user);

  /**
   * Returns the user who should execute the command.
   *
   * @return string
   *   The user.
   */
  public function getUser();

  /**
   * Executes the command synchronously.
   *
   * Example:
   * ``` php
   * $ssh = new Ssh($environment, 'List root directory', $wip_log, $id);
   * $ssh->setCommand('ls -l /');
   * $ssh_result = $ssh_process = $ssh->exec();
   * if ($ssh_result->isSuccess()) {
   *   print($ssh_result->getStdout() . "\n");
   * }
   * else {
   *   print($ssh_result->getStderr() . "\n");
   * }
   * ```
   *
   * @return SshResult
   *   The result.
   */
  public function exec();

  /**
   * Executes the specified command.
   *
   * Example:
   * ``` php
   * $ssh = new Ssh($environment, 'List root directory', $wip_log, $id);
   * $ssh_result = $ssh_process = $ssh->execCommand('ls -l /');
   * if ($ssh_result->isSuccess()) {
   *   print($ssh_result->getStdout() . "\n");
   * }
   * else {
   *   print($ssh_result->getStderr() . "\n");
   * }
   * ```
   *
   * @param string $command
   *   The command to execute.
   *
   * @return SshResult
   *   The result.
   */
  public function execCommand($command);

  /**
   * Executes the command asynchronously.
   *
   * This object requires an Environment instance that contains the hosting
   * sitegroup, the hosting environment, and a current server. This example
   * shows how you might create that:
   * ``` php
   * $environment = new Environment();
   * $environment->setSitegroup('my_hosting_sitegroup');
   * $environment->setEnvironmentName('my_hosting_environment');
   * $environment->setServers(array('web-8.my_stage.hosting.acquia.com'));
   * $environment->selectNextServer();
   * ```
   *
   * Once you have an Environment instance properly set up, using the Ssh class
   * is straightforward:
   * ``` php
   * $ssh = new Ssh($environment, 'Sleep for two minutes.', $wip_log, $id);
   * $ssh->setCommand('sleep 2m');
   * $ssh_process = $ssh->execAsync();
   * ```
   *
   * From there you can use the resulting SshProcess instance to manage the
   * asynchronous process and get the result.
   *
   * @param string $options
   *   Optional.  Additional options for the ssh wrapper.
   * @param object $data
   *   Optional.  Data that can be passed back to the caller via the callback
   *   mechanism.
   *
   * @return SshProcess
   *   The SshProcess instance that represents the asynchronous process.
   */
  public function execAsync($options = '', $data = NULL);

  /**
   * Executes the specified command asynchronously.
   *
   * Example:
   * ``` php
   * $ssh = new Ssh($environment, 'Sleep for two minutes.', $wip_log, $id);
   * $ssh_process = $ssh->execAsyncCommand('sleep 2m');
   * ```
   *
   * From there you can use the resulting SshProcess instance to manage the
   * asynchronous process and get the result.
   *
   * @param string $command
   *   The command to execute.
   *
   * @return SshProcess
   *   The SshProcess instance that represents the asynchronous process.
   */
  public function execAsyncCommand($command);

  /**
   * Adds an exit code to the set of exit codes representing success.
   *
   * @param int $exit_code
   *   The exit code to add. If the process exits with this code or any other
   *   code identified as a successful execution, the isSuccess method will
   *   return TRUE.
   */
  public function addSuccessExitCode($exit_code);

  /**
   * Gets all exit codes that are considered successful.
   *
   * @return int[]
   *   The exit codes that represent a successful execution.
   */
  public function getSuccessExitCodes();

  /**
   * Sets the specified exit codes that represent success.
   *
   * @param int[] $exit_codes
   *   The exit codes to add. If the process exits with any of these codes, the
   *   isSuccess method will return TRUE.
   */
  public function setSuccessExitCodes($exit_codes);

  /**
   * Returns the result interpreter for this ssh command, if provided.
   *
   * @return SshResultInterpreterInterface
   *   The interpreter.
   */
  public function getResultInterpreter();

  /**
   * Sets the result interpreter for this ssh command.
   *
   * @param SshResultInterpreterInterface $interpreter
   *   The interpreter.
   */
  public function setResultInterpreter(SshResultInterpreterInterface $interpreter);

  /**
   * Initializes the Ssh instance ready for use.
   *
   * @param EnvironmentInterface $environment
   *   The Environment instance that provides the hosting sitegroup,
   *   environment, and server list.
   * @param string $description
   *   The description of the purpose of the Ssh command being executed.
   * @param WipLogInterface $logger
   *   The logger to use.
   * @param int $id
   *   The Wip ID to log against.
   *
   * @return SshInterface
   *   The Ssh object.
   */
  public function initialize(EnvironmentInterface $environment, $description, WipLogInterface $logger, $id);

  /**
   * Gets an SshService instance for the specified Environment.
   *
   * @param EnvironmentInterface $environment
   *   The environment.
   *
   * @return SshServiceInterface
   *   The service.
   */
  public function getSshService(EnvironmentInterface $environment);

  /**
   * Sets the SshService instance.
   *
   * This is used for unit testing.
   *
   * @param SshServiceInterface $ssh_service
   *   The SshService to use, or NULL to use the default.
   */
  public function setSshService(SshServiceInterface $ssh_service);

  /**
   * Sets the default log level associated with this SshInterface instance.
   *
   * This log level will be used for all logging except for entries that
   * represent warnings or errors.
   *
   * @param int $level
   *   The log level.
   *
   * @throws \InvalidArgumentException
   *   If the specified log level is not a legal value.
   */
  public function setLogLevel($level);

  /**
   * Executes the specified operation using the ssh_wrapper.
   *
   * @param string $operation
   *   The operation to perform on the remote wrapper e.g. "exec", "stdout",
   *   etc.
   * @param string $command
   *   An optional argument to the remote operation. For example, the argument
   *   for the "exec" operation is a string containing the Unix command to be
   *   executed.
   * @param string $options
   *   Optional. Other command line options to be passed to the remote wrapper.
   *
   * @return SshResult
   *   The stdout and stderr from the command will be returned in the SshResult
   *   object, along with the exit code of the process.
   *
   * @throws \Exception
   *   If the command could not be executed.
   */
  public function invokeWrapperOperation($operation, $command, $options);

  /**
   * Parses the result of the ssh wrapper.
   *
   * @param SshResultInterface $ssh_result
   *   The raw result from the Ssh call.
   *
   * @return SshResultInterface
   *   The decoded result object.
   */
  public function parseResult(SshResultInterface $ssh_result);

}
