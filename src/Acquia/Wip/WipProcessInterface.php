<?php

namespace Acquia\Wip;

use Acquia\Wip\Security\SecureTraitInterface;

/**
 * Contains methods common to all process implementations.
 */
interface WipProcessInterface extends SecureTraitInterface {

  /**
   * Gets the description of the associated process.
   *
   * @return string
   *   The description.
   */
  public function getDescription();

  /**
   * Sets the description of the associated process.
   *
   * The description should be a human-readable piece of text that describes the
   * purpose of the process.
   *
   * @param string $description
   *   The description.
   *
   * @throws \RuntimeException
   *   If the description has already been set.
   *
   * @throws \InvalidArgumentException
   *   If the description is not a string.
   */
  public function setDescription($description);

  /**
   * Gets the Environment instance associated with this process instance.
   *
   * @return EnvironmentInterface
   *   The environment.
   */
  public function getEnvironment();

  /**
   * Sets the Environment instance associated with this process instance.
   *
   * Note: This can only be called once.
   *
   * @param EnvironmentInterface $environment
   *   The environment.
   *
   * @throws \RuntimeException
   *   If the environment has already been set.
   */
  public function setEnvironment(EnvironmentInterface $environment);

  /**
   * Returns the process exit code.
   *
   * @return int
   *   The exit code of the process.
   */
  public function getExitCode();

  /**
   * Sets the process exit code.
   *
   * @param int $exit_code
   *   The exit code of the process.
   *
   * @throws \RuntimeException
   *   If the exit code has already been set.
   *
   * @throws \InvalidArgumentException
   *   If the specified exit code is not an integer.
   */
  public function setExitCode($exit_code);

  /**
   * Sets the specified exit codes that represent success.
   *
   * @param int[] $exit_codes
   *   The exit codes to add. If the process exits with any of these codes, the
   *   WipResultInterface isSuccess method will return TRUE.
   *
   * @throws \InvalidArgumentException
   *   If the exit_codes parameter is not an array or if any element in the
   *   exit_codes array is not an integer.
   */
  public function setSuccessExitCodes($exit_codes);

  /**
   * Adds an exit code to the set of exit codes representing success.
   *
   * @param int $exit_code
   *   The exit code to add. If the process exits with this code or any other
   *   code identified as a successful execution, the WipResultInterface
   *   isSuccess method will return TRUE.
   *
   * @throws \InvalidArgumentException
   *   If the exit code is not an integer.
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
   * Gets the time it took complete the process, measured in seconds.
   *
   * This value is calculated from the start and end times.
   *
   * @return int
   *   The number of seconds.
   *
   * @throws \RuntimeException
   *   If the start time or end time has not been set.
   */
  public function getRuntime();

  /**
   * Gets the Unix timestamp indicating the process start time.
   *
   * @return int
   *   The Unix timestamp representing the start time.
   *
   * @throws \RuntimeException
   *   If the start time has not been set.
   */
  public function getStartTime();

  /**
   * Sets the Unix timestamp indicating the process start time.
   *
   * @param int $start_time
   *   The Unix timestamp representing the start time.
   *
   * @throws \RuntimeException
   *   If the start time has already been set.
   *
   * @throws \InvalidArgumentException
   *   If the start time is not an integer.
   */
  public function setStartTime($start_time);

  /**
   * Gets the Unix timestamp indicating the process end time.
   *
   * @return int
   *   The Unix timestamp representing the end time.
   */
  public function getEndTime();

  /**
   * Sets the Unix timestamp indicating the process end time.
   *
   * @param int $end_time
   *   The Unix timestamp representing the end time.
   */
  public function setEndTime($end_time);

  /**
   * Gets the process ID associated with this process.
   *
   * @return int|string
   *   The process ID.
   */
  public function getPid();

  /**
   * Sets the process ID associated with this process.
   *
   * @param int|string $pid
   *   The process ID.
   */
  public function setPid($pid);

  /**
   * Gets the ID of the Wip object associated with this process.
   *
   * @return int
   *   The Wip ID.
   */
  public function getWipId();

  /**
   * Sets the ID of the Wip object associated with this process.
   *
   * @param int $id
   *   The Wip ID.
   */
  public function setWipId($id);

  /**
   * Gets the result of this process.
   *
   * @param WipLogInterface $wip_log
   *   The logger.
   * @param bool $fetch
   *   Optional.  If TRUE and the result has not yet been set, this method will
   *   attempt to fetch the result.
   *
   * @return WipResultInterface
   *   The result.
   */
  public function getResult(WipLogInterface $wip_log, $fetch = FALSE);

  /**
   * Sets the result of this process.
   *
   * @param WipResultInterface $result
   *   The result.
   *
   * @throws \RuntimeException
   *   If the result has already been set.
   */
  public function setResult(WipResultInterface $result);

  /**
   * Determines if the process has completed.
   *
   * @param WipLogInterface $logger
   *   The logger.
   *
   * @return bool
   *   TRUE if the process has completed; FALSE if it is still running; NULL if
   *   we can't tell.
   */
  public function hasCompleted(WipLogInterface $logger);

  /**
   * Kills the process.
   *
   * @param WipLogInterface $logger
   *   The logger.
   *
   * @return bool
   *   TRUE if the process is still running; FALSE if it is not running; NULL if
   *   we can't tell.
   */
  public function kill(WipLogInterface $logger);

  /**
   * Returns an id that uniquely represents this process.
   *
   * This is used to quickly retrieve a particular process from an associative
   * array, for example, when a signal is received this value is used to
   * identify the associated process.
   *
   * @return string
   *   The unique id.
   */
  public function getUniqueId();

  /**
   * Releases any resources associated with this running process.
   *
   * @param WipLogInterface $logger
   *   The Wip log.
   */
  public function release(WipLogInterface $logger);

  /**
   * Sets the default log level associated with this instance.
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
   * Gets the log level set into this instance.
   *
   * @return int
   *   The log level.
   */
  public function getLogLevel();

  /**
   * Populates properties in this instance with values from the result.
   *
   * @param WipResultInterface $result
   *   The result.
   */
  public function populateFromResult(WipResultInterface $result);

  /**
   * Gets the human-readable exit message.
   *
   * @return string
   *   The final message of the process.
   */
  public function getExitMessage();

  /**
   * Sets the human-readable exit message.
   *
   * @param string $exit_message
   *   The human-readable exit message.
   *
   * @throws \InvalidArgumentException
   *   If the exit message is not a string.
   *
   * @throws \RuntimeException
   *   If the exit message has already been set.
   */
  public function setExitMessage($exit_message);

  /**
   * Forces this process to fail with the specified reason.
   *
   * @param string $reason
   *   The reason the associated task was forced into a failed state.
   * @param WipLogInterface $logger
   *   The WipLog instance.
   */
  public function forceFail($reason, WipLogInterface $logger);

}
