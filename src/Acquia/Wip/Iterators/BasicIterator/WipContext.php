<?php

namespace Acquia\Wip\Iterators\BasicIterator;

use Acquia\Wip\IteratorStatus;
use Acquia\Wip\StateTableIteratorInterface;
use Acquia\Wip\WipContextInterface;

/**
 * A simple implementation of the WipContextInterface.
 */
class WipContext extends \stdClass implements WipContextInterface {

  /**
   * The properties and values are stored here.
   *
   * @var array
   */
  private $data = array();

  /**
   * The iterator.
   *
   * @var StateTableIterator
   */
  private $iterator = NULL;

  /**
   * The state this context has been linked to.
   *
   * If this context has not been linked to any state, this will remain NULL.
   *
   * @var string
   */
  private $linkedState = NULL;

  /**
   * Indicates whether no progress detected should be reported.
   *
   * @var bool
   */
  private $reportNoProgress = FALSE;

  /**
   * Sets the iterator into this context.
   *
   * Setting the iterator is not part of the public API, but it does facilitate
   * this WipContext implementation interacting with the iterator.  Note that
   * it is not possible within this interface to get the iterator, which keeps
   * Wip implementations from interfering with the normal flow of execution.
   *
   * @param StateTableIteratorInterface $iterator
   *   The iterator.
   */
  public function setIterator(StateTableIteratorInterface $iterator) {
    $this->iterator = $iterator;
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return $this->iterator;
  }

  /**
   * Clears the transition count from a particular transition.
   *
   * This facilitates the execution of inner and outer loops in which both have
   * max execution values defined. As each outer loop restarts, it can reset
   * the execution count of the inner loop transitions so each loop starts fresh
   * and gets the same error behavior each time.
   *
   * @param string $state
   *   The name of the state that contains the transition.
   * @param string $transition_value
   *   The transition value.
   *
   * @return bool
   *   TRUE if the transition count was reset; FALSE if it could not be reset.
   */
  public function clearTransitionCount($state, $transition_value) {
    $result = FALSE;
    if ($this->iterator instanceof StateTableIterator) {
      $result = $this->iterator->clearTransitionCount($state, $transition_value);
    }
    return $result;
  }

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
  public function __set($name, $value) {
    $this->data[$name] = $value;
  }

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
  public function &__get($name) {
    return $this->data[$name];
  }

  /**
   * Indicates whether the specified property has been set.
   *
   * @param string $name
   *   The property name.
   *
   * @return bool
   *   TRUE if the specified property has been set; FALSE otherwise.
   */
  public function __isset($name) {
    return isset($this->data[$name]);
  }

  /**
   * Unsets the specified property.
   *
   * @param string $name
   *   The property name.
   */
  public function __unset($name) {
    unset($this->data[$name]);
  }

  /**
   * {@inheritdoc}
   */
  public function getObjectId() {
    $result = 0;
    $iterator = $this->getIterator();
    if (!empty($iterator)) {
      $result = $iterator->getId();
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function setExitCode($exit_code) {
    if (!empty($this->iterator)) {
      $this->iterator->setExitCode($exit_code);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getExitCode() {
    if (!empty($this->iterator)) {
      return $this->iterator->getExitCode();
    } else {
      return IteratorStatus::OK;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setExitMessage($message) {
    if (!is_string($message)) {
      throw new \InvalidArgumentException(sprintf('The "message" argument must be a string.'));
    }
    if (!empty($this->iterator)) {
      $this->iterator->setExitMessage($message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getExitMessage() {
    if (!empty($this->iterator)) {
      $result = $this->iterator->getExitMessage();
    } else {
      $result = 'No iterator has been configured; cannot retrieve the exit message.';
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function processSignals() {
    $result = 0;
    if (!empty($this->iterator)) {
      $result = $this->iterator->processSignals($this);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function linkContext($state) {
    if (!is_string($state)) {
      throw new \InvalidArgumentException('The state argument must be a string.');
    }
    if (empty($state)) {
      throw new \InvalidArgumentException('The state argument cannot be empty.');
    }
    $this->linkedState = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkedContext() {
    $result = $this;
    if (!empty($this->linkedState)) {
      $result = $this->getIterator()->getWipContext($this->linkedState, FALSE);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function setReportOnNoProgress($report_no_progress) {
    if (!is_bool($report_no_progress)) {
      throw new \InvalidArgumentException('The "report_no_progress" parameter must be a boolean value.');
    }
    $this->reportNoProgress = $report_no_progress;
  }

  /**
   * {@inheritdoc}
   */
  public function getReportOnNoProgress() {
    return $this->reportNoProgress;
  }

}
