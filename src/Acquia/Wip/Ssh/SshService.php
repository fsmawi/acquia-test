<?php

namespace Acquia\Wip\Ssh;

use Acquia\Wip\EnvironmentInterface;
use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;

/**
 * This class is responsible for the actual ssh calls and process management.
 *
 * This class uses raw Ssh; no wrapper script or interpretation occurs.
 */
class SshService implements SshServiceInterface {

  use \Acquia\Wip\Security\SecureTrait;

  /**
   * The environment.
   *
   * @var EnvironmentInterface
   */
  private $environment;

  /**
   * The path to the Ssh key.
   *
   * @var string
   */
  private $keyPath;

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
   * A specific username to use for SSH login.
   *
   * This can be used to provide a specific username for a single SshService
   * instance.
   *
   * @var string
   */
  private $username = NULL;

  /**
   * A username override to use for *all* SSH logins (mainly for testing).
   *
   * If this is set via SshService::setTestUsername, it will be used for all
   * instances of SshService and hence for all logins.
   *
   * @var string
   */
  private static $testUsername = NULL;

  /**
   * Creates a new instance of SshService for execution on a remote server.
   *
   * @param EnvironmentInterface $environment
   *   The Environment instance that provides the hosting sitegroup,
   *   environment, and server list.
   * @param string $keyPath
   *   The file path to the Ssh key.
   */
  public function __construct(EnvironmentInterface $environment = NULL, $keyPath = NULL) {
    if (!empty($environment)) {
      $this->setEnvironment($environment);
    }
    if (!empty($keyPath)) {
      $this->setKeyPath($keyPath);
    }
  }

  /**
   * Returns the username to use for SSH login.
   *
   * @return string
   *   The SSH username.
   */
  public function getUsername() {
    if (isset(self::$testUsername)) {
      return self::$testUsername;
    } elseif (isset($this->username)) {
      return $this->username;
    }
    // If the environment has a user associated with it, use that.
    $result = $this->getEnvironment()->getUser();
    if (empty($result)) {
      // Otherwise construct the hosting user from the sitegroup and
      // environment.
      $result = $this->environment->getSitegroup() . '.' . $this->environment->getEnvironmentName();
    }
    return $result;
  }

  /**
   * Set the SSH username.
   *
   * @param string $username
   *   The username override to use.
   */
  public function setUsername($username) {
    $this->username = $username;
  }

  /**
   * Set the SSH username for testing.
   *
   * @param string $username
   *   The username override to use.
   */
  public static function setTestUsername($username) {
    self::$testUsername = $username;
  }

  /**
   * Gets the SSH username for testing.
   *
   * @return null|string
   *   The test username which was configured using the setTestUsername method,
   *   or NULL if the test username has not been configured.
   */
  public static function getTestUsername() {
    return self::$testUsername;
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
  public function setEnvironment(EnvironmentInterface $environment) {
    $sitegroup = $environment->getSitegroup();
    if (empty($sitegroup)) {
      throw new \InvalidArgumentException('The environment argument must include the hosting sitegroup.');
    }
    $envName = $environment->getEnvironmentName();
    if (empty($envName)) {
      throw new \InvalidArgumentException('The environment argument must include the environment name.');
    }
    $server = $environment->getCurrentServer();
    if (empty($server)) {
      throw new \InvalidArgumentException('The environment argument must include a selected server.');
    }
    $this->environment = $environment;
  }

  /**
   * Returns the Environment instance associated with this object.
   *
   * @return EnvironmentInterface
   *   The Environment instance.
   */
  public function getEnvironment() {
    return $this->environment;
  }

  /**
   * {@inheritdoc}
   */
  public function setKeyPath($path) {
    $this->keyPath = $path;
  }

  /**
   * {@inheritdoc}
   */
  public function getKeyPath() {
    return $this->keyPath;
  }

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
  public function exec($command, $data = NULL) {
    /** @var \SSH2 $ssh */
    $ssh = NULL;
    $result = NULL;
    $environment = $this->getEnvironment();
    // If no explicit username is set, we use the hosting sitegroup.environment
    // as the username - this allows the SSH login shell to get the same
    // environment variables (eg AH_SITE_GROUP, AH_SITE_ENVIRONMENT and
    // configured PHP version) as the hosting site user.  This can be overridden
    // by setting an explicit username with SshService::setUsername().
    $username = $this->getUsername();
    $password = $this->getPassword();
    $server = $environment->getCurrentServer();
    $port = $environment->getPort();
    $ssh = new SSH2($server, $port);
    $ssh->enableQuietMode();
    $ssh->setCryptoEngine(\phpseclib\Crypt\Base::ENGINE_OPENSSL);
    if (!$ssh->login($username, $password)) {
      $format = 'SSH login failed trying to connect to server %s with user %s (command "%s").';
      $message = sprintf($format, $server, $username, $command);
      throw new \RuntimeException($message);
    }

    $startTime = !empty($data->startTime) ? $data->startTime : time();
    $sshOutput = $ssh->exec($command);
    $result = new SshResult($ssh->getExitStatus(), $sshOutput, $ssh->getStdError());
    $result->setEndTime(time());
    $result->setSuccessExitCodes($this->getSuccessExitCodes());
    $result->setStartTime($startTime);
    $result->setEnvironment($environment);
    $interpreter = $this->getResultInterpreter();
    if (!empty($interpreter)) {
      $result->setResultInterpreter($interpreter);
    }
    $result->setSecure($this->isSecure());
    return $result;
  }

  /**
   * Retrieves the password.
   *
   * The password can be literally a password or a private SSH key.
   *
   * @return RSA
   *   The password.
   *
   * @throws \Exception
   *   If a key path is being used but the key file is not found.
   */
  private function getPassword() {
    $environment = $this->getEnvironment();
    $result = new RSA();
    try {
      $keyPath = $environment->getSshKeyPath();
      $result->loadKey(file_get_contents($keyPath));
    } catch (\DomainException $e) {
      $keyPath = $this->getKeyPath();
      if (!empty($keyPath)) {
        if (file_exists($keyPath)) {
          $result->loadKey(file_get_contents($keyPath));
        } else {
          throw new \Exception(sprintf('Key path %s does not exist.', $keyPath));
        }
      } else {
        // The password is not a private key filename.
        $password = $environment->getPassword();
        $result->setPassword($password);
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function addSuccessExitCode($exitCode) {
    if (!is_int($exitCode) || $exitCode < 0) {
      throw new \InvalidArgumentException('The exitCode must be a positive integer.');
    }
    if (!in_array($exitCode, $this->successCodes)) {
      $this->successCodes[] = $exitCode;
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
  public function setSuccessExitCodes($exitCodes) {
    $this->successCodes = array();
    foreach ($exitCodes as $code) {
      $this->addSuccessExitCode($code);
    }
  }

  /**
   * Returns the result interpreter for this ssh command, if provided.
   *
   * @return SshResultInterpreterInterface
   *   The interpreter.
   */
  public function getResultInterpreter() {
    return $this->interpreter;
  }

  /**
   * Sets the result interpreter for this ssh command.
   *
   * @param SshResultInterpreterInterface $interpreter
   *   The interpreter.
   */
  public function setResultInterpreter(SshResultInterpreterInterface $interpreter) {
    $this->interpreter = $interpreter;
  }

}
