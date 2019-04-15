<?php

namespace Acquia\Wip;

/**
 * Declares the functions needed for a Timer instance.
 */
interface TimerInterface {

  /**
   * Returns the name of the current timer.
   *
   * @return string
   *   The name of the timer that is currently accumulating seconds.
   */
  public function getCurrentTimerName();

  /**
   * Returns the names of all timers.
   *
   * @return string[]
   *   A numerically indexed array of all timer names.
   */
  public function getTimerNames();

  /**
   * Returns the cumulative elapsed time for the given timer name in seconds.
   *
   * @param string $name
   *   The name of a timer.
   *
   * @return float
   *   The number of elapsed seconds and fractions of a second down to
   *   microsecond accuracy for the given timer.
   *
   * @throws \InvalidArgumentException
   *   If the given timer name does not exist.
   */
  public function getTime($name);

  /**
   * Returns the elapsed time for the given timer name in milliseconds.
   *
   * Graphite expects values in whole milliseconds.
   *
   * @param string $name
   *   The name of a timer.
   *
   * @return int
   *   The number of elapsed milliseconds for the given timer.
   *
   * @throws \InvalidArgumentException
   *   If the given timer name does not exist.
   */
  public function getTimeMs($name);

  /**
   * Stops the current timer and starts the timer with the given name.
   *
   * @param string $name
   *   The name of the timer to start.
   *
   * @throws \InvalidArgumentException
   *   If the current timer name is empty or not a string.
   */
  public function start($name);

  /**
   * Adds elapsed time to the current timer and stops keeping time.
   *
   * @throws \InvalidArgumentException
   *   If the current timer name is empty or not a string.
   */
  public function stop();

  /**
   * Combines the times from the given Timer to this Timer.
   *
   * @param TimerInterface $timer
   *   Timer with names and times to add to this Timer.
   */
  public function blend(TimerInterface $timer);

  /**
   * Encodes and returns this Timer into a JSON string.
   *
   * @return string
   *   The JSON representation of this Timer's names and times.
   */
  public function toJson();

  /**
   * Returns a Timer with the names and times from the given JSON string.
   *
   * @param string $json
   *   The string representation of timer names and their times.
   *
   * @return Timer
   *   A Timer object populated with the given names and times.
   *
   * @throws \InvalidArgumentException
   *   If any timer name in the given JSON is empty or not a string or if the
   *   json string does not contain an array.
   */
  public static function fromJson($json);

  /**
   * Returns a human-readable summary of this Timer's names and times.
   *
   * @return string
   *   Human-readable summary of names and elapsed times.
   */
  public function report();

}
