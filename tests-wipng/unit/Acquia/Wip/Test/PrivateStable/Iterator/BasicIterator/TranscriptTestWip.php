<?php

namespace Acquia\Wip\Test\PrivateStable\Iterator\BasicIterator;

use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\WipContextInterface;

/**
 * Missing summary.
 */
class TranscriptTestWip extends BasicWip {
  const TYPE_STATE = 1;
  const TYPE_TRANSITION = 2;

  /**
   * Provides a transcript of actions.
   *
   * @var array
   */
  private $recording = '';
  private $transitions = array();

  /**
   * Missing summary.
   */
  public function start(WipContextInterface $wip_context) {
    $this->addStateEntry(__FUNCTION__);
  }

  /**
   * Missing summary.
   */
  public function finish(WipContextInterface $wip_context) {
    $this->addStateEntry(__FUNCTION__);
  }

  /**
   * Missing summary.
   */
  public function failure(WipContextInterface $wip_context, \Exception $exception = NULL) {
    $this->addStateEntry(__FUNCTION__);
    parent::failure($wip_context, $exception);
  }

  /**
   * Missing summary.
   */
  public function step1() {
    $this->addStateEntry(__FUNCTION__);
  }

  /**
   * Missing summary.
   */
  public function step2() {
    $this->addStateEntry(__FUNCTION__);
  }

  /**
   * Missing summary.
   */
  public function step3() {
    $this->addStateEntry(__FUNCTION__);
  }

  /**
   * Missing summary.
   */
  public function step4() {
    $this->addStateEntry(__FUNCTION__);
  }

  /**
   * Missing summary.
   */
  public function step5() {
    $this->addStateEntry(__FUNCTION__);
  }

  /**
   * Missing summary.
   */
  public function step6() {
    $this->addStateEntry(__FUNCTION__);
  }

  /**
   * Missing summary.
   *
   * @return string
   *   'success' - on success.
   *   'fail'    - on failure.
   */
  public function transition1() {
    return $this->runTransition(__FUNCTION__);
  }

  /**
   * Missing summary.
   */
  public function transition2() {
    return $this->runTransition(__FUNCTION__);
  }

  /**
   * Missing summary.
   */
  public function transition3() {
    return $this->runTransition(__FUNCTION__);
  }

  /**
   * Missing summary.
   */
  public function transition4() {
    return $this->runTransition(__FUNCTION__);
  }

  /**
   * Missing summary.
   */
  public function transition5() {
    return $this->runTransition(__FUNCTION__);
  }

  /**
   * Missing summary.
   */
  public function transition6() {
    return $this->runTransition(__FUNCTION__);
  }

  /**
   * Missing summary.
   */
  public function emptyTransition(WipContextInterface $wip_context) {
    return '';
  }

  /**
   * Missing summary.
   */
  public function addStateEntry($method) {
    $this->recording .= "s $method\n";
  }

  /**
   * Missing summary.
   */
  public function addTransitionEntry($method, $value) {
    $this->recording .= "t $method '$value'\n";
  }

  /**
   * Missing summary.
   */
  public function getTranscript() {
    return $this->recording;
  }

  /**
   * Missing summary.
   */
  public function setTransitionValues($method, $values) {
    $this->transitions[$method] = $values;
  }

  /**
   * Missing summary.
   */
  public function runTransition($method) {
    if ($method == 'emptyTransition') {
      return '';
    }
    if (!isset($this->transitions[$method])) {
      throw new \RuntimeException(sprintf('No values set for transition method %s', $method));
    }
    if (count($this->transitions[$method]) === 0) {
      throw new \RuntimeException(sprintf('No remaining values for transition method %s', $method));
    }
    $value = array_shift($this->transitions[$method]);
    return $value;
  }

}
