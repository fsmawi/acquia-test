<?php

namespace Acquia\Wip\Test\PrivateStable\Iterator\BasicIterator;

use Acquia\Wip\Implementation\TransitionSequence;

/**
 * Tests teh TransitionSequence class.
 */
class TransitionSequenceTest extends \PHPUnit_Framework_TestCase {

  /**
   * The sequence.
   *
   * @var TransitionSequence
   */
  private $sequence;

  /**
   * {@inheritdoc}
   *
   * @group WipVerification
   */
  public function setUp() {
    $this->sequence = new TransitionSequence('test');
  }

  /**
   * Tests getting the state name.
   *
   * @group WipVerification
   */
  public function testGetStateName() {
    $this->assertEquals('test', $this->sequence->getStateName());
  }

  /**
   * Tests setting the state name.
   *
   * @group WipVerification
   */
  public function testSetStateName() {
    $this->sequence->setStateName('new name');
    $this->assertEquals($this->sequence->getStateName(), 'new name');
  }

  /**
   * Tests getting transitions.
   *
   * @group WipVerification
   */
  public function testGetTransitions() {
    $this->assertEmpty($this->sequence->getTransitions());

    $this->sequence->addTransition('transition0');
    $this->sequence->addTransition('transition1');

    $transitions = $this->sequence->getTransitions();
    $this->assertCount(2, $transitions);
    $this->assertEquals('transition0', $transitions[0]);
    $this->assertEquals('transition1', $transitions[1]);
  }

  /**
   * Tests getting line numbers.
   *
   * @group WipVerification
   */
  public function testGetLineNumber() {
    $this->assertNull($this->sequence->getLineNumber());
  }

  /**
   * Tests setting the line number.
   *
   * @group WipVerification
   */
  public function testSetLineNumber() {
    $this->assertNull($this->sequence->getLineNumber());

    $this->sequence->setLineNumber(5);
    $this->assertEquals(5, $this->sequence->getLineNumber());
  }

}
