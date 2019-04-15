<?php

namespace Acquia\Wip\Signal;

use Acquia\Wip\Timer;
use Acquia\Wip\TimerInterface;

/**
 * The ProcessSignalInterface is associated with asynchronous processes.
 */
interface ProcessSignalInterface extends SignalInterface {

  /**
   * Returns the Unix timestamp representing the start time of the process.
   *
   * @return int
   *   The Unix timestamp.
   */
  public function getStartTime();

  /**
   * Returns the Unix timestamp representing the end time of the process.
   *
   * @return int
   *   The end time, or NULL if the process has not completed.
   */
  public function getEndTime();

  /**
   * Gets the process ID.
   *
   * @return string
   *   The process ID.
   */
  public function getPid();

  /**
   * Sets the process ID.
   *
   * @param string $pid
   *   The process ID.
   */
  public function setPid($pid);

  /**
   * Gets the exit code of the completed Wip object.
   *
   * @return int
   *   The Wip task exit code.
   */
  public function getExitCode();

  /**
   * Sets the exit code for the completed Wip object.
   *
   * @param int $exit_code
   *   The Wip task exit code.
   *
   * @throws \InvalidArgumentException
   *   If the specified exit code is not a valid task status.
   */
  public function setExitCode($exit_code);

  /**
   * Sets the exit message of the completed Wip object.
   *
   * @param string $exit_message
   *   The exit message.
   */
  public function setExitMessage($exit_message);

  /**
   * Gets the exit message of the completed Wip object.
   *
   * @return string
   *   The exit message.
   */
  public function getExitMessage();

  /**
   * Gets the timer data associated with the process.
   *
   * @return Timer
   *   The Timer instance for this process.
   */
  public function getTimer();

  /**
   * Sets timer data.
   *
   * @param TimerInterface|string $timer
   *   The timer.
   *
   * @throws \InvalidArgumentException
   *   If the timer is not a string or TimerInterface.
   */
  public function setTimer($timer);

}
