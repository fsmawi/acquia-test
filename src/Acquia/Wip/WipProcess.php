<?php

namespace Acquia\Wip;

use Acquia\Wip\Security\SecureTrait;

/**
 * A class that contains methods common to all process implementations.
 */
class WipProcess implements WipProcessInterface {

  use SecureTrait;

  /**
   * The exit code associated with a process or task being force-failed.
   */
  const FORCE_FAIL_EXIT_CODE = 7777777;

  /**
   * The Environment instance associated with this process.
   *
   * @var EnvironmentInterface
   */
  private $environment = NULL;

  /**
   * The exit code associated with this process.
   *
   * @var int
   */
  private $exitCode = NULL;

  /**
   * The human-readable description of this process.
   *
   * @var string
   */
  private $description = NULL;

  /**
   * The set of exit codes that will be interpreted as success.
   *
   * @var int[]
   */
  private $successExitCodes = array();

  /**
   * The Unix timestamp indicating when the process started.
   *
   * @var int
   */
  private $startTime = NULL;

  /**
   * The Unix timestamp indicating when the process ended.
   *
   * @var int
   */
  private $endTime = NULL;

  /**
   * The process ID.
   *
   * @var string
   */
  private $pid = NULL;

  /**
   * The ID of the associated Wip object.
   *
   * @var int
   */
  private $wipId = NULL;

  /**
   * The log level used if not explicitly set.
   */
  const DEFAULT_LOG_LEVEL = WipLogLevel::INFO;

  /**
   * The log level.
   *
   * @var int
   */
  private $logLevel = self::DEFAULT_LOG_LEVEL;

  /**
   * The result associated with this process.
   *
   * @var WipResultInterface
   */
  private $result = NULL;

  /**
   * The human-readable message associated with this process.
   *
   * Note that not all processes will use this.
   *
   * @var string
   */
  private $exitMessage = NULL;

  /**
   * Initializes this instance.
   */
  public function __construct() {
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    if ($this->description !== NULL) {
      throw new \RuntimeException('The description has already been set.');
    }
    if (!is_string($description)) {
      throw new \InvalidArgumentException('The description parameter must be a string.');
    }
    if (empty($description)) {
      throw new \InvalidArgumentException('The description parameter must not be empty.');
    }
    $this->description = $description;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnvironment() {
    return $this->environment;
  }

  /**
   * {@inheritdoc}
   */
  public function setEnvironment(EnvironmentInterface $environment) {
    if ($this->environment !== NULL) {
      throw new \RuntimeException('The environment property can only be set once.');
    }
    $this->environment = $environment;
  }

  /**
   * {@inheritdoc}
   */
  public function getExitCode() {
    return $this->exitCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setExitCode($exit_code) {
    if ($this->exitCode !== NULL) {
      throw new \RuntimeException('The exit code property can only be set once.');
    }
    if (!is_int($exit_code)) {
      throw new \InvalidArgumentException('The exit code must be an integer.');
    }
    $this->exitCode = $exit_code;
  }

  /**
   * {@inheritdoc}
   */
  public function setSuccessExitCodes($exit_codes) {
    if (!is_array($exit_codes)) {
      throw new \InvalidArgumentException('The exit_codes parameter must be an array of integers.');
    }
    $code_count = count($exit_codes);
    for ($i = 0; $i < $code_count; $i++) {
      $exit_code = $exit_codes[$i];
      if (!is_int($exit_code)) {
        $message = <<<EOT
The exit_codes parameter must be an array of integers; element %d is not an integer (value "%s", type "%s").
EOT;

        throw new \InvalidArgumentException(sprintf($message, $i, strval($exit_code), gettype($exit_code)));
      }
    }
    $this->successExitCodes = $exit_codes;
  }

  /**
   * {@inheritdoc}
   */
  public function addSuccessExitCode($exit_code) {
    if (!is_int($exit_code)) {
      throw new \InvalidArgumentException('The exit_code parameter must be an integer.');
    }
    $this->successExitCodes[] = $exit_code;
  }

  /**
   * {@inheritdoc}
   */
  public function getSuccessExitCodes() {
    return $this->successExitCodes;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntime() {
    return $this->getEndTime() - $this->getStartTime();
  }

  /**
   * {@inheritdoc}
   */
  public function getStartTime() {
    if (NULL === $this->startTime) {
      throw new \RuntimeException('The start time has not been set.');
    }
    return $this->startTime;
  }

  /**
   * {@inheritdoc}
   */
  public function setStartTime($start_time) {
    if (NULL !== $this->startTime) {
      throw new \RuntimeException('The start time can only be set once.');
    }
    if (!is_int($start_time)) {
      throw new \InvalidArgumentException('The start_time parameter must be an integer.');
    }
    if ($start_time <= 0) {
      throw new \InvalidArgumentException('The start_time parameter must be a positive integer.');
    }
    $this->startTime = $start_time;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndTime() {
    if (NULL === $this->endTime) {
      throw new \RuntimeException('The end time has not been set.');
    }
    return $this->endTime;
  }

  /**
   * {@inheritdoc}
   */
  public function setEndTime($end_time) {
    if (NULL !== $this->endTime) {
      throw new \RuntimeException('The end time can only be set once.');
    }
    if (!is_int($end_time)) {
      throw new \InvalidArgumentException('The end_time parameter must be an integer.');
    }
    if ($end_time <= 0) {
      throw new \InvalidArgumentException('The end_time parameter must be a positive integer.');
    }
    $this->endTime = $end_time;
  }

  /**
   * {@inheritdoc}
   */
  public function getPid() {
    if ($this->pid === NULL) {
      throw new \RuntimeException('The pid has not been set.');
    }
    return $this->pid;
  }

  /**
   * {@inheritdoc}
   */
  public function setPid($pid) {
    if ($this->pid !== NULL) {
      throw new \RuntimeException('The pid can only be set once.');
    }
    $this->pid = $pid;
  }

  /**
   * {@inheritdoc}
   */
  public function getWipId() {
    if ($this->wipId === NULL) {
      throw new \RuntimeException('The Wip ID has not been set.');
    }
    return $this->wipId;
  }

  /**
   * {@inheritdoc}
   */
  public function setWipId($id) {
    if ($this->wipId !== NULL) {
      throw new \RuntimeException('The Wip ID has already been set.');
    }
    if (!is_int($id)) {
      throw new \InvalidArgumentException('The id parameter must be an integer.');
    }
    if ($id < 0) {
      throw new \InvalidArgumentException('The id parameter must be a positive integer.');
    }
    $this->wipId = $id;
  }

  /**
   * {@inheritdoc}
   */
  public function getResult(WipLogInterface $wip_log, $fetch = FALSE) {
    return $this->result;
  }

  /**
   * {@inheritdoc}
   */
  public function setResult(WipResultInterface $result) {
    if ($this->result !== NULL) {
      throw new \RuntimeException('The result can only be set once.');
    }
    $this->populateFromResult($result);
    $result->populateFromProcess($this);
    $this->result = $result;
  }

  /**
   * {@inheritdoc}
   */
  public function hasCompleted(WipLogInterface $logger) {
    // For the default implementation assume that the process is still running
    // until the result has been set.
    $result = FALSE;
    if ($this->result !== NULL) {
      $result = TRUE;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function kill(WipLogInterface $logger) {
    // There is no reasonable default implementation of kill.
    return $this->hasCompleted($logger);
  }

  /**
   * {@inheritdoc}
   */
  public function getUniqueId() {
    return $this->getPid();
  }

  /**
   * {@inheritdoc}
   */
  public function release(WipLogInterface $logger) {
    // There are no resources to free in the default implementation.
  }

  /**
   * {@inheritdoc}
   */
  public function setLogLevel($level) {
    if (!WipLogLevel::isValid($level)) {
      throw new \InvalidArgumentException('The level parameter must be a valid WipLogLevel value.');
    }
    $this->logLevel = $level;
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
  public function populateFromResult(WipResultInterface $result) {
    if (!empty($result->getEnvironment())) {
      try {
        $this->setEnvironment($result->getEnvironment());
      } catch (\Exception $e) {
        // Ignore.
      }
    }
    try {
      $this->setExitCode($result->getExitCode());
    } catch (\Exception $e) {
      // Ignore.
    }
    $exit_codes = $this->getSuccessExitCodes();
    if (empty($exit_codes)) {
      $this->setSuccessExitCodes($result->getSuccessExitCodes());
    }
    try {
      $this->setStartTime($result->getStartTime());
    } catch (\Exception $e) {
      // Ignore.
    }
    try {
      $this->setEndTime($result->getEndTime());
    } catch (\Exception $e) {
      // Ignore.
    }
    try {
      $this->setPid($result->getPid());
    } catch (\Exception $e) {
      // Ignore.
    }
    try {
      $this->setWipId($result->getWipId());
    } catch (\Exception $e) {
      // Ignore.
    }
    $log_level = $this->getLogLevel();
    if ($log_level === self::DEFAULT_LOG_LEVEL) {
      $this->setLogLevel($result->getLogLevel());
    }
    try {
      $exit_message = $result->getExitMessage();
      if (!empty($exit_message)) {
        $this->setExitMessage($exit_message);
      }
    } catch (\Exception $e) {
      // Ignore.
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
  public function setExitMessage($exit_message) {
    if ($this->exitMessage !== NULL) {
      throw new \RuntimeException('The exit message has already been set.');
    }
    if (!is_string($exit_message)) {
      throw new \InvalidArgumentException('The exit_message argument must be a string.');
    }
    $this->exitMessage = $exit_message;
  }

  /**
   * {@inheritdoc}
   */
  public function forceFail($reason, WipLogInterface $logger) {
    if (empty($reason) || !is_string($reason)) {
      $reason = 'No reason provided.';
    }

    if (empty($logger)) {
      $logger = WipFactory::getObject('acquia.wip.wiplog');
    }

    if (!$this->hasCompleted($logger)) {
      // Often the force fail is used if a process is obviously running
      // significantly longer than expected.  In that case, try to kill the
      // process.  Not all processes can be killed however.
      $logger->log(
        WipLogLevel::ALERT,
        sprintf('Process %d is being forced to fail because "%s"', $this->getPid(), $reason),
        $this->getWipId()
      );
      try {
        $this->kill($logger);
      } catch (\Exception $e) {
        $logger->log(WipLogLevel::ERROR, sprintf('Failed to kill process %d: %s', $this->getPid(), $e->getMessage()));
      }
      // This method overrides previously set fields if needed, so don't use the
      // setter methods here.
      $this->exitMessage = $reason;
      $this->exitCode = self::FORCE_FAIL_EXIT_CODE;
    }
  }

}
