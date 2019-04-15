<?php

namespace Acquia\Wip;

use Acquia\Wip\Iterators\BasicIterator\WipContext;

/**
 * Retains data for use between a state and its associated transition method.
 *
 * The WipContextInterface is the interface through which a Wip object interacts
 * with its runtime environment and provides a means of sharing data between a
 * state method and a transition method.
 */
interface WipContextInterface {

  /**
   * Magic setter.
   *
   * Used for adding data to this object.
   *
   * @param string $name
   *   The property name.
   * @param mixed $value
   *   The property value.
   */
  public function __set($name, $value);

  /**
   * Magic getter.
   *
   * Used for retrieving data from this object.
   *
   * @param string $name
   *   The property name.
   *
   * @return mixed
   *   The value for the specified property.
   */
  public function __get($name);

  /**
   * Indicates whether the specified property has been set.
   *
   * @param string $name
   *   The property name.
   *
   * @return bool
   *   TRUE if the specified property has been set; FALSE otherwise.
   */
  public function __isset($name);

  /**
   * Unsets the specified property.
   *
   * @param string $name
   *   The property name.
   */
  public function __unset($name);

  /**
   * Gets the Wip object ID associated with this context.
   *
   * @return int
   *   The object ID.
   */
  public function getObjectId();

  /**
   * Sets the exit code, indicating success or failure of the Wip object.
   *
   * @param int $exit_code
   *   The exit code. Must be a value from IteratorResult.
   */
  public function setExitCode($exit_code);

  /**
   * Returns the exit code of the Wip object.
   *
   * @return int
   *   The exit code.
   */
  public function getExitCode();

  /**
   * Sets the final message that will be logged or displayed upon completion.
   *
   * @param string $message
   *   The exit message.
   *
   * @throws \InvalidArgumentException
   *   If the message is not a string.
   */
  public function setExitMessage($message);

  /**
   * Returns the final message that will be logged or displayed upon completion.
   *
   * @return string
   *   The exit message.
   */
  public function getExitMessage();

  /**
   * Processes any signals associated with the associated Wip object.
   *
   * @return int
   *   The number of signals processed.
   */
  public function processSignals();

  /**
   * Sets the iterator into this context.
   *
   * This facilitates interacting with the iterator.
   *
   * @param StateTableIteratorInterface $iterator
   *   The iterator.
   */
  public function setIterator(StateTableIteratorInterface $iterator);

  /**
   * Gets the iterator associated with this WipContext instance.
   *
   * @return StateTableIteratorInterface
   *   The iterator.
   */
  public function getIterator();

  /**
   * Links this context with the context associated with the specified state.
   *
   * @param string $state
   *   The name of the state to link this context to.
   */
  public function linkContext($state);

  /**
   * Gets the context associated with this context.
   *
   * @return WipContext
   *   The context.
   */
  public function getLinkedContext();

  /**
   * Sets whether asynchronous calls should report on no apparent progress.
   *
   * Note: not all asynchronous calls support this option. In cases where it is
   * unsupported, the lack of progress will simply not be detected and no
   * report will occur.
   *
   * @param bool $report_no_progress
   *   If TRUE, asynchronous calls should report if there is no apparent
   *   progress.
   */
  public function setReportOnNoProgress($report_no_progress);

  /**
   * Indicates whether asynchronous calls should report no apparent progress.
   *
   * @return bool
   *   TRUE if asynchronous calls should report if there is no apparent
   *   progress.
   */
  public function getReportOnNoProgress();

}
