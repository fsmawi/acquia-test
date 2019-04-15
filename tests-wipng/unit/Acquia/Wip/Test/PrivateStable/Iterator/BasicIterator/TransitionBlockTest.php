<?php

namespace Acquia\Wip\Test\PrivateStable\Iterator\BasicIterator;

use Acquia\Wip\Iterators\BasicIterator\Transition;
use Acquia\Wip\Iterators\BasicIterator\TransitionBlock;

/**
 * Missing summary.
 */
class TransitionBlockTest extends \PHPUnit_Framework_TestCase {

  private $state = 'state';
  private $transition = 'transition';

  /**
   * Missing summary.
   *
   * @var TransitionBlock
   */
  private $transitionBlock = NULL;

  /**
   * Missing summary.
   *
   * @var Transition
   */
  private $asteriskTransition = NULL;

  /**
   * Missing summary.
   *
   * @var Transition
   */
  private $successTransition = NULL;

  /**
   * Missing summary.
   *
   * @var Transition
   */
  private $failTransition = NULL;

  private $successValue = 'success';
  private $successState = 'successState';
  private $failValue = 'fail';
  private $failState = 'failState';

  private $transitionValue = '*';
  private $transitionState = 'start';
  private $wait = 37;
  private $maxCount = 3;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    $this->transitionBlock = new TransitionBlock($this->state, $this->transition);
    $this->asteriskTransition = new Transition(
      $this->transitionValue,
      $this->transitionState,
      $this->wait,
      $this->maxCount
    );
    $this->successTransition = new Transition($this->successValue, $this->successState);
    $this->failTransition = new Transition($this->failValue, $this->failState);
  }

  /**
   * Missing summary.
   */
  public function testConstructor() {
    $this->assertNotEmpty($this->transitionBlock);
    $this->assertEquals('system', $this->transitionBlock->getTimerName());
  }

  /**
   * Missing summary.
   */
  public function testState() {
    $this->assertEquals($this->state, $this->transitionBlock->getState());
  }

  /**
   * Missing summary.
   */
  public function testTransitionMethod() {
    $this->assertEquals($this->transition, $this->transitionBlock->getTransitionMethod());
  }

  /**
   * Missing summary.
   */
  public function testAddTransitionMethod() {
    $this->transitionBlock->addTransition($this->asteriskTransition);
    $transition = $this->transitionBlock->getTransition($this->transitionValue);
    $this->assertEquals($this->transitionValue, $transition->getValue());
  }

  /**
   * Missing summary.
   */
  public function testGetTransition() {
    $this->addTransitions();

    $success_transition = $this->transitionBlock->getTransition($this->successValue);
    $this->assertEquals($this->successTransition, $success_transition);
  }

  /**
   * Missing summary.
   */
  public function testFindNextState() {
    $this->addTransitions();
    $new_transition = $this->transitionBlock->findNextTransition($this->successValue);
    $this->assertEquals($this->successTransition, $new_transition);
  }

  /**
   * Missing summary.
   */
  public function testFindNextStateWildcard() {
    $this->addTransitions();
    $new_transition = $this->transitionBlock->findNextTransition('');
    $this->assertEquals($this->asteriskTransition, $new_transition);
  }

  /**
   * Missing summary.
   */
  public function testFindNextStateMissing() {
    // Create a transitionBlock that has no asterisk transition.
    $this->transitionBlock = new TransitionBlock($this->state, $this->transition);
    $this->transitionBlock->addTransition($this->successTransition);
    $this->transitionBlock->addTransition($this->failTransition);

    $new_transition = $this->transitionBlock->findNextTransition('');
    $this->assertEmpty($new_transition);
  }

  /**
   * Missing summary.
   */
  public function testLineNumber() {
    $line_number = 13;
    $this->transitionBlock->setLineNumber($line_number);
    $result = $this->transitionBlock->getLineNumber();
    $this->assertEquals($line_number, $result);
  }

  /**
   * Missing summary.
   */
  public function testTimerName() {
    $name = 'timer_name';
    $block = new TransitionBlock($this->state, $this->transition, $name);
    $this->addTransitions($block);
    $this->assertEquals($name, $block->getTimerName());
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testTimerNameWrongType() {
    $name = 15;
    $block = new TransitionBlock($this->state, $this->transition, $name);
  }

  /**
   * Missing summary.
   */
  private function addTransitions($transition_block = NULL) {
    if (NULL === $transition_block) {
      $transition_block = $this->transitionBlock;
    }
    $transition_block->addTransition($this->asteriskTransition);
    $transition_block->addTransition($this->successTransition);
    $transition_block->addTransition($this->failTransition);
  }

}
