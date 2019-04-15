<?php

namespace Acquia\Wip\Test\PrivateStable\Iterator\BasicIterator;

use Acquia\Wip\Iterators\BasicIterator\Transition;

/**
 * Missing summary.
 */
class TransitionTest extends \PHPUnit_Framework_TestCase {
  private $value = '*';
  private $state = 'state';
  private $wait = 15;
  private $maxCount = 3;

  /**
   * Missing summary.
   *
   * @var Transition
   */
  private $transition = NULL;

  /**
   * Missing summary.
   */
  public function setUp() {
    $this->transition = new Transition($this->value, $this->state, $this->wait, $this->maxCount);
  }

  /**
   * Missing summary.
   */
  public function testValue() {
    $value = $this->transition->getValue();
    $this->assertEquals($this->value, $value);
  }

  /**
   * Missing summary.
   */
  public function testState() {
    $state = $this->transition->getState();
    $this->assertEquals($this->state, $state);
  }

  /**
   * Missing summary.
   */
  public function testWait() {
    $wait = $this->transition->getWait();
    $this->assertEquals($this->wait, $wait);
  }

  /**
   * Missing summary.
   */
  public function testMaxCount() {
    $max_count = $this->transition->getMaxCount();
    $this->assertEquals($this->maxCount, $max_count);
  }

  /**
   * Missing summary.
   */
  public function testLineNumber() {
    $line_number = 13;
    $this->transition->setLineNumber($line_number);
    $result = $this->transition->getLineNumber();
    $this->assertEquals($line_number, $result);
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testLineNumberType() {
    $this->transition->setLineNumber('whatever');
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testNegativeLineNumber() {
    $this->transition->setLineNumber(-99);
  }

}
