<?php

namespace Acquia\Wip\Runtime;

/**
 * Compares ExecutionTranscriptElements.
 */
class ExecutionTranscriptElement {

  /**
   * Indicates the parent phase.
   */
  const PROCESS_PARENT = 'parent';

  /**
   * Indicates the child phase.
   */
  const PROCESS_CHILD = 'child';

  /**
   * Indicates task phase, when the task is being processed.
   */
  const PROCESS_TASK = 'task';

  /**
   * Indicates the cleanup phase.
   */
  const PROCESS_CLEANUP = 'cleanup';

  /**
   * Indicates thread cleanup phase.
   */
  const PROCESS_THREAD_CLEANUP = 'thread_cleanup';

  /**
   * Indicates the start of a phase.
   */
  const START = 'STARTED';

  /**
   * Indicates the completion of a phase.
   */
  const COMPLETE = 'COMPLETED';

  /**
   * The execution phase.
   *
   * @var string
   */
  private $phase = NULL;

  /**
   * The action.
   *
   * @var string
   */
  private $action = NULL;

  /**
   * The timestamp indicating when the action occurred.
   *
   * @var double
   */
  private $timestamp = NULL;

  /**
   * Constructs an ExecutionTranscriptElement object with class values.
   *
   * @param string $phase
   *   The name of the phase.
   * @param string $action
   *   The name of the action.
   * @param float $timestamp
   *   The start time of the execution phase.
   */
  public function __construct($phase = NULL, $action = NULL, $timestamp = NULL) {
    if ($phase !== NULL) {
      $this->setPhase($phase);
    }
    if ($action !== NULL) {
      $this->setAction($action);
    }
    if ($timestamp !== NULL) {
      $this->setTimestamp($timestamp);
    }
  }

  /**
   * Sets the phase name.
   *
   * @param string $phase
   *   The name of the phase.
   */
  public function setPhase($phase) {
    if (!is_string($phase)) {
      throw new \InvalidArgumentException('The "phase" parameter must be a string.');
    }
    $this->phase = $phase;
  }

  /**
   * Retrieves the name of the phase.
   *
   * @return string
   *   The name of the phase.
   */
  public function getPhase() {
    return $this->phase;
  }

  /**
   * Sets the name of the action.
   *
   * @param string $action
   *   The name of the action.
   */
  public function setAction($action) {
    if (!is_string($action)) {
      throw new \InvalidArgumentException('The "action" parameter must be a string.');
    }
    $this->action = $action;
  }

  /**
   * Retrieves the name of the action.
   *
   * @return string
   *   The name of the action.
   */
  public function getAction() {
    return $this->action;
  }

  /**
   * Sets the timestamp.
   *
   * @param float $timestamp
   *   The start time of the execution phase.
   */
  public function setTimestamp($timestamp) {
    if (!is_float($timestamp)) {
      throw new \InvalidArgumentException('The "timestamp" parameter must be a double.');
    }
    $this->timestamp = $timestamp;
  }

  /**
   * Retrieves the timestamp.
   *
   * @return float
   *   The start time of the execution phase.
   */
  public function getTimestamp() {
    return $this->timestamp;
  }

  /**
   * Compares two ExecutionTranscriptElements.
   *
   * @param ExecutionTranscriptElement $a
   *   First element to compare.
   * @param ExecutionTranscriptElement $b
   *   Second element to compare.
   *
   * @return int
   *   0 if equal, -1 if the first element is younger, 1 if the first element is older.
   */
  public static function sortByTime(ExecutionTranscriptElement $a, ExecutionTranscriptElement $b) {
    $result = $a->getTimestamp() - $b->getTimestamp();
    if ($result == 0) {
      $result = 0;
    } elseif ($result < 0) {
      $result = -1;
    } elseif ($result > 0) {
      $result = 1;
    }
    return $result;
  }

}
