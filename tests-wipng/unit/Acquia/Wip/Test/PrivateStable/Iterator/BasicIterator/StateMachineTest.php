<?php

namespace Acquia\Wip\Test\PrivateStable\Iterator\BasicIterator;

use Acquia\Wip\Iterators\BasicIterator\StateMachine;
use Acquia\Wip\Iterators\BasicIterator\TransitionBlock;

/**
 * Missing summary.
 */
class StateMachineTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @var StateMachine
   */
  private $stateMachine = NULL;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    $this->stateMachine = new StateMachine();
  }

  /**
   * Missing summary.
   */
  public function testConstructor() {
    $this->assertInstanceOf('Acquia\Wip\Iterators\BasicIterator\StateMachine', $this->stateMachine);
  }

  /**
   * Missing summary.
   */
  public function testAddTransitionBlock() {
    $transition_block = new TransitionBlock('start', 'emptyTransition');
    $this->stateMachine->addTransitionBlock($transition_block);
    $result = $this->stateMachine->getTransitionBlock('start');
    $this->assertEquals('start', $result->getState());
    $this->assertEquals('emptyTransition', $result->getTransitionMethod());
  }

  /**
   * Missing summary.
   */
  public function testAddTransitionBlocks() {
    $block1 = new TransitionBlock('start', 'emptyTransition');
    $block2 = new TransitionBlock('finish', 'emptyTransition');
    $this->stateMachine->addTransitionBlock($block1);
    $this->stateMachine->addTransitionBlock($block2);

    $result1 = $this->stateMachine->getTransitionBlock('start');
    $this->assertEquals('start', $result1->getState());
    $result2 = $this->stateMachine->getTransitionBlock('finish');
    $this->assertEquals('finish', $result2->getState());
  }

  /**
   * Missing summary.
   *
   * @expectedException Acquia\Wip\Exception\MissingTransitionBlockException
   */
  public function testBadTransitionBlockRequest() {
    $this->stateMachine->getTransitionBlock('start');
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testEmptyTransitionBlockRequest() {
    $this->stateMachine->getTransitionBlock(NULL);
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testTransitionBlockRequestWrongType() {
    $this->stateMachine->getTransitionBlock(55);
  }

  /**
   * Missing summary.
   */
  public function testGetStartState() {
    $block1 = new TransitionBlock('start', 'emptyTransition');
    $block2 = new TransitionBlock('finish', 'emptyTransition');
    $this->stateMachine->addTransitionBlock($block1);
    $this->stateMachine->addTransitionBlock($block2);
    $start_state = $this->stateMachine->getStartState();
    $this->assertEquals('start', $start_state);
  }

  /**
   * Missing summary.
   *
   * @expectedException \RuntimeException
   */
  public function testGetStartStateTooSoon() {
    $this->stateMachine->getStartState();
  }

  /**
   * Missing summary.
   */
  public function testGetAllStates() {
    $block1 = new TransitionBlock('start', 'emptyTransition');
    $block2 = new TransitionBlock('finish', 'emptyTransition');
    $this->stateMachine->addTransitionBlock($block1);
    $this->stateMachine->addTransitionBlock($block2);
    $this->assertEquals(array('start', 'finish'), $this->stateMachine->getAllStates());
  }

  /**
   * Missing summary.
   */
  public function testGetAllTransitions() {
    $block1 = new TransitionBlock('start', 'emptyTransition');
    $block2 = new TransitionBlock('finish', 'emptyTransition');
    $this->stateMachine->addTransitionBlock($block1);
    $this->stateMachine->addTransitionBlock($block2);
    $this->assertEquals(array('emptyTransition'), $this->stateMachine->getAllTransitions());
  }

}
