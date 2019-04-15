<?php

namespace Acquia\Wip\Ssh;

use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\Security\SecureTraitInterface;

/**
 * This Interface is responsible for the ssh calls.
 */
interface SshServiceInterface extends SecureTraitInterface {

  /**
   * Creates a new instance of SshServiceInterface.
   *
   * @param EnvironmentInterface $environment
   *   The Environment instance that provides the hosting sitegroup,
   *   environment, and server list.
   * @param string $key_path
   *   The file path to the Ssh key.
   */
  public function __construct(EnvironmentInterface $environment, $key_path);

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
  public function setEnvironment(EnvironmentInterface $environment);

  /**
   * Returns the Environment instance associated with this object.
   *
   * @return EnvironmentInterface
   *   The Environment instance.
   */
  public function getEnvironment();

  /**
   * Runs a command via Ssh on the remote webnode, synchronously.
   *
   * Note that generally you should use the Ssh class instead. The Ssh class
   * supports asynchronous operations with a callback upon completion and can
   * manage the asynchronous command. This class is used by the Ssh class and
   * generally should be for internal use only.
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
   * Once you have an Environment instance properly set up, using the SshService
   * is straightforward:
   * ``` php
   * $ssh_keys = new SshKeys();
   * $ssh_service = new SshService($environment, $ssh_keys->getPrivateKeyPath($environment));
   * $response = $ssh_service->exec('ls -l /');
   * if ($response->getExitCode() === 0) {
   *   print($response->getStdout() . "\n");
   * }
   * else {
   *   print($response->getStderr() . "\n");
   * }
   * ```
   *
   * @param string $command
   *   The command to be run on the webnode.
   * @param object $data
   *   Arbitrary data that is being passed in the command. Note: it is not
   *   usually the responsibility of SshService to do anything with this data
   *   (like, say, json_encode()ing it and adding to the command). Rather, the
   *   caller will have already set this data encoded in the command string, and
   *   is providing it to the SshService in un-encoded form in case SshService
   *   needs to alter its behaviour accordingly.
   *
   * @return SshResult
   *   The stdout and stderr from the command will be returned in the SshResult
   *   object, along with the exit code of the process.
   *
   * @throws \RuntimeException
   *   If the Ssh login failed.
   */
  public function exec($command, $data = NULL);

  /**
   * Sets the path to the Ssh key that will be used in the ssh call.
   *
   * @param string $path
   *   The Ssh key path.
   */
  public function setKeyPath($path);

  /**
   * Gets the path to the Ssh key that will be used in the Ssh call.
   *
   * @return string
   *   The Ssh key path.
   */
  public function getKeyPath();

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

}
