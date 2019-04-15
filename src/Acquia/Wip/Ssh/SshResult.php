<?php

namespace Acquia\Wip\Ssh;

use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\WipLogEntryInterface;
use Acquia\Wip\WipResult;
use Acquia\Wip\WipResultInterface;

/**
 * The SshResult class encapsulates the result of an Ssh call.
 */
class SshResult extends WipResult implements SshResultInterface {

  /**
   * The stdout associated with the Ssh call.
   *
   * @var string
   */
  private $stdout;

  /**
   * The stderr associated with the Ssh call.
   *
   * @var string
   */
  private $stderr;

  /**
   * The result interpreter.
   *
   * @var SshResultInterpreterInterface
   */
  private $interpreter = NULL;

  /**
   * Constructs a new instance.
   *
   * @param int $exit_code
   *   The process exit code.
   * @param string $stdout
   *   The stdout from the process.
   * @param string $stderr
   *   The stderr from the process.
   *
   * @throws \InvalidArgumentException
   *   If the exit_code argument is not a positive integer or if the stdout
   *   or stderr are not a string.
   */
  public function __construct($exit_code = NULL, $stdout = NULL, $stderr = NULL) {
    $this->setSuccessExitCodes(array(0));
    $this->initialize($exit_code, $stdout, $stderr);
  }

  /**
   * Missing summary.
   */
  public function initialize($exit_code = NULL, $stdout = NULL, $stderr = NULL) {
    if ($exit_code !== NULL) {
      $this->setExitCode($exit_code);
    }
    if ($stdout !== NULL) {
      $this->setStdout($stdout);
    }
    if ($stderr !== NULL) {
      $this->setStderr($stderr);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setExitCode($exit_code) {
    if ($exit_code < 0) {
      throw new \InvalidArgumentException('The exit_code argument cannot be negative.');
    }
    parent::setExitCode($exit_code);
  }

  /**
   * {@inheritdoc}
   */
  public function setEnvironment(EnvironmentInterface $environment) {
    // Do not allow a change to a new server to break this SshResult instance.
    $environment_clone = clone $environment;
    $current_server = $environment->getCurrentServer();
    $environment_clone->setServers(array($current_server));
    $environment_clone->setCurrentServer($current_server);
    parent::setEnvironment($environment_clone);
  }

  /**
   * {@inheritdoc}
   */
  public function getStdout() {
    return $this->stdout;
  }

  /**
   * {@inheritdoc}
   */
  public function getSecureStdout() {
    if ($this->isSecure() && !$this->isInDebugMode()) {
      return WipLogEntryInterface::OUTPUT_SUPPRESSED_MESSAGE;
    } else {
      return $this->getStdout();
    }
  }

  /**
   * Sets the stdout associated with the Ssh call.
   *
   * @param string $stdout
   *   The stdout.
   *
   * @throws \InvalidArgumentException
   *   If the stdout argument is not a string.
   */
  public function setStdout($stdout) {
    if (!is_string($stdout)) {
      throw new \InvalidArgumentException('The stdout argument must be a string.');
    }
    $this->stdout = $stdout;
  }

  /**
   * {@inheritdoc}
   */
  public function getStderr() {
    return $this->stderr;
  }

  /**
   * {@inheritdoc}
   */
  public function getSecureStderr() {
    if ($this->isSecure() && !$this->isInDebugMode()) {
      return WipLogEntryInterface::OUTPUT_SUPPRESSED_MESSAGE;
    } else {
      return $this->getStderr();
    }
  }

  /**
   * Sets the stderr associated with the Ssh call.
   *
   * @param string $stderr
   *   The stderr.
   *
   * @throws \InvalidArgumentException
   *   If the stderr argument is not a string.
   */
  public function setStderr($stderr) {
    if (!is_string($stderr)) {
      throw new \InvalidArgumentException('The stderr argument must be a string.');
    }
    $this->stderr = $stderr;
  }

  /**
   * {@inheritdoc}
   */
  public function toObject($object = NULL) {
    $result = parent::toObject();
    $result->result->stdout = $this->getStdout();
    $result->result->stderr = $this->getSecureStderr();
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public static function fromObject($object, WipResultInterface $wip_result = NULL) {
    $result = $wip_result;
    if (empty($result)) {
      $result = new SshResult();
    } elseif (!$result instanceof SshResultInterface) {
      throw new \InvalidArgumentException('The wip_result parameter must be an instance of SshResultInterface.');
    }
    $result_object = $object;
    if (!isset($result_object->exitCode) && isset($result_object->result) && isset($result_object->result->exitCode)) {
      $result_object = $object->result;
    }
    parent::fromObject($object, $result);

    if (isset($result_object->stdout)) {
      $result->setStdout($result_object->stdout);
    }
    if (isset($result_object->stderr)) {
      $result->setStderr($result_object->stderr);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getUniqueId() {
    $environment = $this->getEnvironment();
    return self::createUniqueId($environment->getCurrentServer(), $this->getPid(), $this->getStartTime());
  }

  /**
   * {@inheritdoc}
   */
  public static function createUniqueId($server, $pid, $start_time) {
    return sprintf('%s:%d@%d', $server, $pid, $start_time);
  }

  /**
   * {@inheritdoc}
   */
  public function getResultInterpreter() {
    if (!empty($this->interpreter)) {
      $this->interpreter->setSshResult($this);
    }
    return $this->interpreter;
  }

  /**
   * {@inheritdoc}
   */
  public function setResultInterpreter(SshResultInterpreterInterface $interpreter) {
    $this->interpreter = $interpreter;
  }

}
