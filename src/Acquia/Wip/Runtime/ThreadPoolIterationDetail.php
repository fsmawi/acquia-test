<?php

namespace Acquia\Wip\Runtime;

use Acquia\Wip\ThreadPoolDetailInterface;

/**
 * A class to gather data about a signle iteration of useThreads.
 */
class ThreadPoolIterationDetail implements ThreadPoolDetailInterface {

  /**
   * The total sleep time.
   *
   * @var float
   */
  private $totalSleepTime = 0.0;

  /**
   * The total number of successful matches between threads and tasks.
   *
   * @var int
   */
  private $totalSuccessfulMatches = 0;

  /**
   * The total number of failed matches between threads and tasks.
   *
   * @var int
   */
  private $totalFailedMatches = 0;

  /**
   * An array of failure reasons and their frequency.
   *
   * @var array
   */
  private $failedMatchesByReason = [];

  /**
   * The total number of threads that were tried for matching.
   *
   * @var int
   */
  private $totalThreads = 0;

  /**
   * The total number of tasks that were tried for matching.
   *
   * @var int
   */
  private $totalTasks = 0;

  /**
   * The iteration number of this call to useThreads.
   *
   * @var int
   */
  private $iteration = 0;

  /**
   * The total execution time that this iteration took.
   *
   * @var float
   */
  private $totalExecutionTime = 0.0;

  /**
   * Creates a new ThreadPool instance.
   *
   * @param int $iteration
   *   The iteration number.
   */
  public function __construct($iteration) {
    $this->iteration = $iteration;
  }

  /**
   * Records when a list of threads has been found.
   *
   * @param int $thread_count
   *   The number of threads that were found.
   */
  public function receivedThreadsToMatch($thread_count) {
    $this->totalThreads += $thread_count;
  }

  /**
   * Records when a list of tasks has been found.
   *
   * @param int $task_count
   *   The number of tasks that were found.
   */
  public function receivedTasksToMatch($task_count) {
    $this->totalTasks += $task_count;
  }

  /**
   * Records failed matches between a task and a thread.
   *
   * @param \Exception $e
   *   The exception message of the failure.
   */
  public function recordFailedMatch(\Exception $e) {
    $this->totalFailedMatches++;
    $this->failedMatchesByReason[] = get_class($e);
  }

  /**
   * Records a match between a task and a thread.
   */
  public function recordSuccessfulMatch() {
    $this->totalSuccessfulMatches++;
  }

  /**
   * {@inheritdoc}
   */
  public function recordSleep($length) {
    $this->totalSleepTime += $length;
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalSleepTime() {
    return round($this->totalSleepTime, 2);
  }

  /**
   * Returns the number of successful matches.
   *
   * @return int
   *   The number of successful matches.
   */
  public function getTotalSuccessfulMatches() {
    return $this->totalSuccessfulMatches;
  }

  /**
   * Returns the number of failed matches.
   *
   * @return int
   *   The number of failed matches.
   */
  public function getTotalFailedMatches() {
    return $this->totalFailedMatches;
  }

  /**
   * Gets an array of failure reasons.
   *
   * This array may contain duplicates, as each failure is logged as a separate
   * entry in the array.
   *
   * @return array
   *   The array of failure reasons (Exception classes).
   */
  public function getFailedMatchesByReason() {
    return $this->failedMatchesByReason;
  }

  /**
   * Returns the number of threads tried for matches.
   *
   * @return int
   *   The number of threads.
   */
  public function getTotalThreads() {
    return $this->totalThreads;
  }

  /**
   * Returns the number of tasks tried for matches.
   *
   * @return int
   *   The number of tasks.
   */
  public function getTotalTasks() {
    return $this->totalTasks;
  }

  /**
   * Returns the iteration number of this call to useThreads.
   *
   * @return int
   *   The iteration number.
   */
  public function getIteration() {
    return $this->iteration;
  }

  /**
   * Sets the total execution time.
   *
   * @param float $time
   *   The run time.
   */
  public function setTotalExecutionTime($time) {
    $this->totalExecutionTime = $time;
  }

  /**
   * Returns the total execution time.
   *
   * @return float
   *   The total execution time.
   */
  public function getTotalExecutionTime() {
    return round($this->totalExecutionTime, 2);
  }

}
