<?php

namespace Acquia\Wip;

/**
 * Keeps track of time elapsed in seconds for any number of timers.
 *
 * The instance variables in this class are protected so as to be used by the
 * AdjustableTimer class, which allows for unit testing this class without
 * calling sleep and wasting time.
 */
class Timer implements TimerInterface {

  /**
   * The associative array of timer names and their cumulative seconds elapsed.
   *
   * @var array
   */
  protected $timers = array();

  /**
   * The time at which the current timer was started.
   *
   * @var float
   */
  protected $start = 0;

  /**
   * The name of the timer that is currently accumulating seconds.
   *
   * @var string
   */
  protected $currentTimerName = NULL;

  /**
   * {@inheritdoc}
   */
  public function getCurrentTimerName() {
    return $this->currentTimerName;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimerNames() {
    return array_keys($this->timers);
  }

  /**
   * {@inheritdoc}
   */
  public function getTime($name) {
    if (!array_key_exists($name, $this->timers)) {
      throw new \InvalidArgumentException(sprintf('Timer "%s" does not exist', $name));
    }
    return $this->timers[$name];
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeMs($name) {
    if (!array_key_exists($name, $this->timers)) {
      throw new \InvalidArgumentException(sprintf('Timer "%s" does not exist', $name));
    }
    return (int) ($this->timers[$name] * 1000);
  }

  /**
   * Validates timer name and adds time to the given timer name.
   *
   * @param string $name
   *   The name of a new or existing timer.
   * @param int $time
   *   The number of seconds to add to the given timer.
   *
   * @throws \InvalidArgumentException
   *   If the given timer name is empty or not a string.
   */
  private function addTime($name, $time) {
    if (!array_key_exists($name, $this->timers)) {
      if (!is_string($name) || empty($name)) {
        throw new \InvalidArgumentException(sprintf('"%s" is an invalid timer name', $name));
      }
      $this->timers[$name] = 0;
    }
    $this->timers[$name] += $time;
  }

  /**
   * {@inheritdoc}
   */
  public function start($name) {
    if ($name === $this->currentTimerName) {
      // Continue keeping time.
      return;
    }
    $this->stop();
    // Start given timer.
    if ($this->start === 0) {
      $this->start = microtime(TRUE);
    }
    $this->currentTimerName = $name;
  }

  /**
   * {@inheritdoc}
   */
  public function stop() {
    if (is_string($this->currentTimerName)) {
      $now = microtime(TRUE);
      $this->addTime($this->currentTimerName, round($now - $this->start, 6));
      $this->start = $now;
    }
    $this->currentTimerName = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function blend(TimerInterface $timer) {
    foreach ($timer->getTimerNames() as $name) {
      $this->addTime($name, $timer->getTime($name));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function toJson() {
    return json_encode($this->timers);
  }

  /**
   * {@inheritdoc}
   */
  public static function fromJson($json) {
    $timer_obj = json_decode($json);
    if (empty($timer_obj) || !is_object($timer_obj)) {
      throw new \InvalidArgumentException('The json parameter must be a string representation of an object.');
    }
    $timers = get_object_vars($timer_obj);
    $timer = new Timer();
    foreach ($timers as $name => $time) {
      $timer->addTime($name, $time);
    }
    return $timer;
  }

  /**
   * {@inheritdoc}
   */
  public function report() {
    $report = "";
    foreach ($this->timers as $name => $elapsed) {
      $sec = (int) $elapsed;
      $milliseconds = round($elapsed - $sec, 2) * 100;
      $hours = floor($sec / (60 * 60));
      $minutes = floor($sec / 60) % 60;
      $seconds = $sec % 60;
      $report .= sprintf(
        "The %s timer took %'.02d:%'.02d:%'.02d.%'.02d\n",
        $name,
        $hours,
        $minutes,
        $seconds,
        $milliseconds
      );
    }
    return $report;
  }

}
