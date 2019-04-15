<?php

namespace Acquia\Wip\Runtime;

use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\DependencyManager;
use Acquia\Wip\Metrics\MetricsRelayInterface;
use Acquia\Wip\ThreadPoolDetailInterface;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipLogLevel;

/**
 * A class to keep track of ThreadPool statistics.
 */
class ThreadPoolProcessDetail implements DependencyManagedInterface, ThreadPoolDetailInterface {

  /**
   * The dependency manager.
   *
   * @var DependencyManager
   */
  public $dependencyManager;

  /**
   * The logger.
   *
   * @var WipLogInterface
   */
  private $logger;

  /**
   * The message prefix for this instance.
   *
   * @var string
   */
  private $messagePrefix = '';

  /**
   * The list of ThreadPoolIterationDetail objects.
   *
   * @var ThreadPoolIterationDetail[]
   */
  private $iterationDetails = [];

  /**
   * The current iteration of useThreads being called.
   *
   * @var int
   */
  private $iteration = 0;

  /**
   * The pid of the process.
   *
   * @var int
   */
  private $pid;

  /**
   * The total sleep time.
   *
   * @var float
   */
  private $totalSleepTime = 0.0;

  /**
   * The total amount of time that this process took.
   *
   * @var float
   */
  private $totalRunTime = 0.0;

  /**
   * The interface to send the timing metrics to.
   *
   * @var MetricsRelayInterface
   */
  private $relay;

  /**
   * The name pattern for metrics.
   */
  const METRICS_PATTERN = 'wip.system.threadpool.%s';

  /**
   * Creates a new ThreadPoolProcessDetail instance.
   *
   * @param int $pid
   *   The pid of the process..
   */
  public function __construct($pid) {
    $this->dependencyManager = new DependencyManager();
    $this->dependencyManager->addDependencies($this->getDependencies());
    $this->logger = $this->dependencyManager->getDependency('acquia.wip.wiplog');
    $this->pid = $pid;
    $this->relay = $this->dependencyManager->getDependency('acquia.wip.metrics.relay');

    $this->messagePrefix = sprintf('ThreadPool pid %s:', $this->pid);
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.wiplog' => 'Acquia\Wip\WipLogInterface',
      'acquia.wip.metrics.relay' => 'Acquia\Wip\Metrics\MetricsRelayInterface',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function recordSleep($length) {
    $this->totalSleepTime += $length;
  }

  /**
   * Gets the total length of time spent in sleep.
   *
   * This does NOT include any sleep time of its ThreadPoolIterationDetail objects.
   *
   * @return float
   *   The total sleep time.
   */
  public function getTotalSleepTime() {
    return round($this->totalSleepTime, 2);
  }

  /**
   * Adds a new ThreadPoolIterationDetail to this object.
   */
  public function addNewIterationDetails() {
    $this->iterationDetails[++$this->iteration] = new ThreadPoolIterationDetail($this->getIteration());
  }

  /**
   * Gets the number of iterations.
   *
   * @return int
   *   The number of iterations.
   */
  public function getIteration() {
    return $this->iteration;
  }

  /**
   * Gets the current ThreadPoolIterationDetail.
   *
   * @return ThreadPoolIterationDetail | NULL
   *   The current ThreadDetail, or NULL if one does not exist.
   */
  public function getCurrentIterationDetail() {
    if (empty($this->iterationDetails)) {
      return NULL;
    }
    return end($this->iterationDetails);
  }

  /**
   * Sets the total run time.
   *
   * @param float $time
   *   The float time.
   */
  public function setTotalRunTime($time) {
    $this->totalRunTime = $time;
  }

  /**
   * Returns the total run time.
   *
   * @return float
   *   The total run time.
   */
  public function getTotalRunTime() {
    return round($this->totalRunTime, 2);
  }

  /**
   * Reports all the data gathered for this ThreadPoolProcess.
   *
   * This will include data gathered from each iteration of the useThreads
   * method, as recorded in ThreadPoolIterationDetail objects.
   */
  public function report() {
    // Always calculate these so that timing information can be sent to the
    // metric relay.
    $total_sleep_time = $this->getTotalSleepTime();
    $total_run_time = $this->getTotalRunTime();
    $total_threads_returned = 0;
    $total_tasks_returned = 0;
    $total_successful_matches = 0;
    $total_failed_matches = 0;
    $total_iterations = $this->getIteration();
    $failed_matches_details = [];

    foreach ($this->iterationDetails as $iterationDetail) {
      $total_sleep_time += $iterationDetail->getTotalSleepTime();
      $total_threads_returned += $iterationDetail->getTotalThreads();
      $total_tasks_returned += $iterationDetail->getTotalTasks();
      $total_successful_matches += $iterationDetail->getTotalSuccessfulMatches();
      $total_failed_matches += $iterationDetail->getTotalFailedMatches();
      $failed_matches_details = array_merge($failed_matches_details, $iterationDetail->getFailedMatchesByReason());
    }

    $threads_per_iteration = $total_iterations === 0 ?
      0 :
      round($total_threads_returned / $total_iterations, 2);

    $total_execution_time = round($total_run_time - $total_sleep_time, 2);
    $percent_time_in_execution = $total_run_time === 0 ?
      0 :
      round($total_execution_time / $total_run_time * 100, 2);

    // Relay the correct times.
    $execution_metric_name = sprintf(self::METRICS_PATTERN, 'percent_time_in_execution');
    $total_execution_metric_name = sprintf(self::METRICS_PATTERN, 'total_execution');
    $threads_per_iteration_metric_name = sprintf(self::METRICS_PATTERN, 'threads_per_iteration');

    $this->relay->count($execution_metric_name, $percent_time_in_execution);
    $this->relay->count($total_execution_metric_name, $total_execution_time);
    $this->relay->count($threads_per_iteration_metric_name, $threads_per_iteration);

    // If logging is not turned on, skip everything else and return.
    if (!$this->isLogging()) {
      return;
    }

    // Convert the specific failures into a readable format.
    $failed_matches_details_string = '';
    $failed_matches_details = array_count_values($failed_matches_details);
    foreach ($failed_matches_details as $key => $value) {
      $failed_matches_details_string .= sprintf("%s: %s\n     ", $key, $value);
    }
    $failed_matches_details_string = rtrim($failed_matches_details_string);

    $message = <<<EOT

Processing tasks on pid $this->pid:
  Total run time: $total_run_time seconds
  Total sleep time: $total_sleep_time seconds
  Total execution time: $total_execution_time seconds
  Total tasks found: $total_tasks_returned
  Total tasks matched and executed: $total_successful_matches
  Total failed task/thread matches: $total_failed_matches
     $failed_matches_details_string
  Total iterations of useThreads: $total_iterations
  Average threads found per iteration: $threads_per_iteration
  Percentage of time spent in execution: $percent_time_in_execution%
EOT;

    // Verbose is FALSE by default. If TRUE, in addition to the cumulative
    // statistics, each iteration of useThreads will also print out its own
    // statistics.
    if ($this->isVerbose()) {
      foreach ($this->iterationDetails as $iterationDetail) {
        $total_execution_time = $iterationDetail->getTotalExecutionTime();
        $sleep_time = $iterationDetail->getTotalSleepTime();
        $threads_returned = $iterationDetail->getTotalThreads();
        $tasks_returned = $iterationDetail->getTotalTasks();
        $successful_matches = $iterationDetail->getTotalSuccessfulMatches();
        $failed_matches = $iterationDetail->getTotalFailedMatches();
        $failed_matches_details_string = '';
        foreach (array_count_values($iterationDetail->getFailedMatchesByReason()) as $key => $value) {
          $failed_matches_details_string .= sprintf("%s: %s\n        ", $key, $value);
        }
        $failed_matches_details_string = rtrim($failed_matches_details_string);
        $iteration = $iterationDetail->getIteration();

        $additional_message = <<<EOT

  useThreads iteration $iteration
     Execution time: $total_execution_time seconds
     Sleep time: $sleep_time seconds
     Threads found: $threads_returned
     Tasks found: $tasks_returned
     Tasks matched and executed: $successful_matches
     Failed task/thread matches: $failed_matches
        $failed_matches_details_string
EOT;

        $message .= $additional_message;
      }
    }

    $this->logger->log(WipLogLevel::DEBUG, rtrim($message));
  }

  /**
   * Sets the log instance.
   *
   * @param WipLogInterface $logger
   *   The log instance.
   */
  public function setLogger(WipLogInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * Checks whether logging is turned on.
   *
   * @return bool
   *   Whether logging is turned on.
   */
  private function isLogging() {
    return WipFactory::getBool('$acquia.wip.process.details.logging', FALSE);
  }

  /**
   * Checks whether verbose logging is turned on.
   *
   * @return bool
   *   Whether the verbose is turned on.
   */
  private function isVerbose() {
    return WipFactory::getBool('$acquia.wip.process.details.verbose', FALSE);
  }

}
