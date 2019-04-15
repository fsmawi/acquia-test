<?php

namespace Acquia\Wip;

use Acquia\WipIntegrations\DoctrineORM\SignalStore;
use Acquia\Wip\Security\SecureTrait;
use Acquia\Wip\Signal\SignalInterface;

/**
 * A class that contains methods common to all result implementations.
 */
class WipResult implements WipResultInterface {

  use SecureTrait;

  /**
   * The log level used if not explicitly set.
   */
  const DEFAULT_LOG_LEVEL = WipLogLevel::INFO;

  /**
   * The exit code associated with a process or task being force-failed.
   */
  const FORCE_FAIL_EXIT_CODE = 7777777;

  /**
   * The Environment instance associated with this result.
   *
   * @var EnvironmentInterface
   */
  private $environment = NULL;

  /**
   * The exit code associated with this result.
   *
   * @var int
   */
  private $exitCode = NULL;

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
   * The human-readable message associated with this result.
   *
   * Note that not all results will use this.
   *
   * @var string
   */
  private $exitMessage = NULL;

  /**
   * The log level.
   *
   * @var int
   */
  private $logLevel = self::DEFAULT_LOG_LEVEL;

  /**
   * The ID of the signal this result was populated from, if applicable.
   *
   * @var int
   */
  private $signalId = NULL;

  /**
   * Initializes a new instance.
   */
  public function __construct() {
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
  public function isSuccess() {
    $exit_code = $this->getExitCode();
    if (!is_int($exit_code)) {
      throw new \RuntimeException('The isSuccess method can only be called after the exit code has been set.');
    }
    return in_array($this->getExitCode(), $this->getSuccessExitCodes());
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
The exit_codes parameter must be an array of integers; element %d is not an integer (value "%s", type "%s".
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
    $this->successExitCodes = array_unique(array_merge($this->successExitCodes, array($exit_code)));
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
      throw new \RuntimeException('The Wip ID can only be set once.');
    }
    if (!is_int($id)) {
      throw new \InvalidArgumentException('The id parameter must be an integer.');
    }
    $this->wipId = $id;
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
  public function toJson($object = NULL) {
    return json_encode($this->toObject($object));
  }

  /**
   * {@inheritdoc}
   */
  public function toObject($object = NULL) {
    $result = $object;
    if ($result === NULL) {
      $result = new \stdClass();
    }
    if (!isset($result->result)) {
      $result->result = new \stdClass();
    }
    $result_field = $result->result;
    try {
      $result_field->exitCode = $this->getExitCode();
    } catch (\RuntimeException $e) {
      // Ignore.
    }
    try {
      $result->pid = $this->getPid();
    } catch (\RuntimeException $e) {
      // Ignore.
    }
    try {
      $result->wipId = $this->getWipId();
    } catch (\RuntimeException $e) {
      // Ignore.
    }
    try {
      $result->startTime = $this->getStartTime();
    } catch (\RuntimeException $e) {
      // Ignore.
    }
    try {
      $result_field->endTime = $this->getEndTime();
    } catch (\RuntimeException $e) {
      // Ignore.
    }
    try {
      $result_field->exitMessage = $this->getExitMessage();
    } catch (\RuntimeException $e) {
      // Ignore.
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public static function objectFromJson($json) {
    $object = json_decode($json);
    if (NULL === $object) {
      throw new \InvalidArgumentException('The json document cannot be decoded.');
    }
    return $object;
  }

  /**
   * {@inheritdoc}
   */
  public static function fromObject($object, WipResultInterface $wip_result = NULL) {
    $result = $wip_result;
    if ($wip_result === NULL) {
      $result = new WipResult();
    }

    $result_field = $object;
    if (isset($object->result)) {
      $result_field = $object->result;
    }

    // The exitCode is a required field, but if a WipResult instance is being
    // passed in the caller may set the exitCode separately.
    if (!isset($result_field->exitCode)) {
      if (empty($wip_result)) {
        throw new \InvalidArgumentException('The object parameter must have an exitCode field.');
      }
    } else {
      try {
        $result->setExitCode(intval($result_field->exitCode));
      } catch (\RuntimeException $e) {
        // Ignore; the caller may have set the pid already.
      }
    }

    // The pid is a required field, but if a WipResult instance is being
    // passed in the caller may set the pid separately.
    if (!isset($object->pid)) {
      if (empty($wip_result)) {
        throw new \InvalidArgumentException('The object parameter must have a pid field.');
      }
    } else {
      try {
        // @todo Needs type coercion from implementations.
        $result->setPid($object->pid);
      } catch (\RuntimeException $e) {
        // Ignore; the caller may have set the pid already.
      }
    }

    // The wipId is a required field, but if a WipResult instance is being
    // passed in the caller may set the wipId separately.
    if (!isset($object->wipId)) {
      if (empty($wip_result)) {
        throw new \InvalidArgumentException('The object parameter must have a wipId field.');
      }
    } else {
      try {
        $result->setWipId(intval($object->wipId));
      } catch (\RuntimeException $e) {
        // Ignore; the caller may have set the pid already.
      }
    }

    // These are optional parameters.
    if (isset($object->startTime)) {
      try {
        $result->setStartTime(intval($object->startTime));
      } catch (\RuntimeException $e) {
        // Ignore; the caller may have set the start time already.
      }
    }
    if (isset($result_field->endTime)) {
      try {
        $result->setEndTime(intval($result_field->endTime));
      } catch (\RuntimeException $e) {
        // Ignore; the caller may have set the end time already.
      }
    }
    if (isset($result_field->exitMessage)) {
      try {
        $result->setExitMessage($result_field->exitMessage);
      } catch (\RuntimeException $e) {
        // Ignore; the caller may have set the exit message already.
      }
    }
    return $result;
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
  public function populateFromProcess(WipProcessInterface $process) {
    if (!empty($process->getEnvironment())) {
      try {
        $this->setEnvironment($process->getEnvironment());
      } catch (\Exception $e) {
        // Ignore.
      }
    }
    try {
      $this->setExitCode($process->getExitCode());
    } catch (\Exception $e) {
      // Ignore.
    }
    $process_exit_codes = $process->getSuccessExitCodes();
    if (!empty($process_exit_codes)) {
      $this->setSuccessExitCodes($process_exit_codes);
    }
    try {
      $this->setStartTime($process->getStartTime());
    } catch (\Exception $e) {
      // Ignore.
    }
    try {
      $this->setEndTime($process->getEndTime());
    } catch (\Exception $e) {
      // Ignore.
    }
    try {
      $this->setPid($process->getPid());
    } catch (\Exception $e) {
      // Ignore.
    }
    try {
      $this->setWipId($process->getWipId());
    } catch (\Exception $e) {
      // Ignore.
    }
    $log_level = $this->getLogLevel();
    if ($log_level === self::DEFAULT_LOG_LEVEL) {
      $this->setLogLevel($process->getLogLevel());
    }
    $exit_message = $this->getExitMessage();
    if (empty($exit_message)) {
      $exit_message = $process->getExitMessage();
      if (!empty($exit_message)) {
        $this->setExitMessage($exit_message);
      }
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
  public function forceFail($reason = NULL) {
    if (!is_string($reason) || trim($reason) == FALSE) {
      $reason = 'No reason provided.';
    }

    // This method overrides previously set fields if needed, so don't use the
    // setter methods here.
    $this->exitMessage = trim($reason);
    $this->exitCode = self::FORCE_FAIL_EXIT_CODE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSignal() {
    $result = NULL;
    $signal_id = $this->signalId;
    if (NULL !== $signal_id) {
      $result = SignalStore::getSignalStore()->load($signal_id);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function setSignal(SignalInterface $signal) {
    $this->signalId = $signal->getId();
  }

}
