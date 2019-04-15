<?php

namespace Acquia\Wip;

use Acquia\Wip\Security\SecureTraitInterface;
use Acquia\Wip\Signal\SignalInterface;

/**
 * Contains methods common to all result implementations.
 */
interface WipResultInterface extends SecureTraitInterface {

  /**
   * Gets the Environment instance associated with this result instance.
   *
   * @return EnvironmentInterface
   *   The environment.
   */
  public function getEnvironment();

  /**
   * Sets the Environment instance associated with this result instance.
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
   * Indicates whether the process was successful.
   *
   * @return bool
   *   TRUE if the call was successful; FALSE otherwise.
   *
   * @throws \RuntimeException
   *   If the exit code has not been set.
   */
  public function isSuccess();

  /**
   * Sets the specified exit codes that represent success.
   *
   * @param int[] $exit_codes
   *   The exit codes to add. If the process exits with any of these codes, the
   *   isSuccess method will return TRUE.
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
   *   code identified as a successful execution, the isSuccess method will
   *   return TRUE.
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
   * Gets the time it took to run the command, measured in seconds.
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
   * Gets the process ID associated with the command.
   *
   * @return int|string
   *   The process ID.
   *
   * @throws \RuntimeException
   *   If the pid has not yet been set.
   */
  public function getPid();

  /**
   * Sets the process ID associated with the command.
   *
   * @param int|string $pid
   *   The process ID.
   *
   * @throws \RuntimeException
   *   If the pid has already been set.
   */
  public function setPid($pid);

  /**
   * Gets the ID of the Wip object associated with this result.
   *
   * @return int
   *   The Wip ID.
   *
   * @throws \RuntimeException
   *   If the Wip ID has not yet been set.
   */
  public function getWipId();

  /**
   * Sets the ID of the Wip object associated with this result.
   *
   * @param int $id
   *   The Wip ID.
   *
   * @throws \RuntimeException
   *   If the Wip ID has already been set.
   * @throws \InvalidArgumentException
   *   If the $id parameter is not an integer.
   */
  public function setWipId($id);

  /**
   * Gets the Unix timestamp corresponding to the process start time.
   *
   * @return int
   *   The process start time.
   *
   * @throws \RuntimeException
   *   If the start time has not been set.
   */
  public function getStartTime();

  /**
   * Sets the Unix timestamp corresponding to the process start time.
   *
   * @param int $start_time
   *   The process start time.
   *
   * @throws \InvalidArgumentException
   *   If the start time is not an integer.
   *
   * @throws \RuntimeException
   *   If the start time has already been set.
   */
  public function setStartTime($start_time);

  /**
   * Gets the Unix timestamp corresponding to the process end time.
   *
   * @return int
   *   The process end time.
   *
   * @throws \RuntimeException
   *   If the end time has not been set.
   *
   * @throws \RuntimeException
   *   If the end time has not been set.
   */
  public function getEndTime();

  /**
   * Sets the Unix timestamp corresponding to the process end time.
   *
   * @param int $end_time
   *   The process end time.
   *
   * @throws \InvalidArgumentException
   *   If the end time is not an integer.
   *
   * @throws \RuntimeException
   *   If the end time has already been set.
   */
  public function setEndTime($end_time);

  /**
   * Converts this object to a JSON string.
   *
   * @param object $object
   *   The object to convert.
   *
   * @return string
   *   The JSON form of this result instance.
   */
  public function toJson($object = NULL);

  /**
   * Converts this object into a stdClass.
   *
   * @param object $object
   *   Optional.  If provided, the result will be built upon the specified
   *   object.
   *
   * @return object
   *   The object.
   */
  public function toObject($object = NULL);

  /**
   * Creates a WipResultInterface instance from the specified JSON string.
   *
   * @param string $json
   *   The result in JSON format.
   *
   * @return object
   *   An object of type \stdClass that contains all of the properties from the
   *   specified JSON document.
   *
   * @throws \InvalidArgumentException
   *   If the JSON document is invalid.
   */
  public static function objectFromJson($json);

  /**
   * Creates a WipResultInterface instance from the specified stdClass instance.
   *
   * The class must have the following structure:
   *   pid - The process ID.
   *   startTime - The Unix timestamp indicating when the process started.
   *   result->endTime - The Unix timestamp indicating when the process completed.
   *   result->exitCode - The process exit code.
   *
   * Other fields can optionally be added.  Any fields relating to the result of
   * the associated process must appear in the result section.  Any fields
   * relating to the process initialization must appear at the top level.
   *
   * @param object $object
   *   The object containing the process exit properties.
   * @param WipResultInterface $wip_result
   *   Optional.  If provided, the object fields will be interpreted and applied
   *   to the specified WipResultInterface instance.  Otherwise a new instance
   *   of WipResult will be created.
   *
   * @return WipResultInterface
   *   The WipResultInterface instance.
   *
   * @throws \InvalidArgumentException
   *   If the pid or exitCode fields are missing from the specified object.
   */
  public static function fromObject($object, WipResultInterface $wip_result = NULL);

  /**
   * Returns an id that uniquely represents this result.
   *
   * This is used to quickly retrieve a particular result from an associative
   * array, for example, when a signal is received this value is used to
   * identify the associated process.
   *
   * @return string
   *   The unique id.
   */
  public function getUniqueId();

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
   * Sets properties into this instance from the specified process.
   *
   * @param WipProcessInterface $process
   *   The process.
   */
  public function populateFromProcess(WipProcessInterface $process);

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
   * Forces this task to fail with the specified reason.
   *
   * @param string $reason
   *   Optional. The reason the associated task was forced into a failed state.
   */
  public function forceFail($reason = NULL);

  /**
   * Gets the signal associated with this result.
   *
   * @return SignalInterface
   *   The signal, or NULL if this result was not populated from a signal.
   */
  public function getSignal();

  /**
   * Sets the signal associated with this WipResult instance.
   *
   * @param SignalInterface $signal
   *   The signal. Note that for storage and security concerns, only the signal
   *   ID will be stored.
   */
  public function setSignal(SignalInterface $signal);

}
