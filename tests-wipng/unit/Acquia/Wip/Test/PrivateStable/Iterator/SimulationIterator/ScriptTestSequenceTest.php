<?php

namespace Acquia\Wip\Test\PrivateStable\Iterator\SimulationIterator;

use Acquia\Wip\Iterators\SimulationIterator\ScriptTestSequence;

/**
 * Tests the ScriptTestSequence class.
 */
class ScriptTestSequenceTest extends \PHPUnit_Framework_TestCase {

  /**
   * Provides non-string and non-array values for testing.
   */
  public function nonStringOrArrayDataProvider() {
    return array(
      array(NULL),
      array(TRUE),
      array(8),
      array(new \stdClass()),
    );
  }

  /**
   * Sequences to test.
   *
   * @var array
   */
  private $sequence = array('status1', 'status2', 'status2');

  /**
   * Tests adding an array of values.
   *
   * @group SimulationIterator
   */
  public function testAddArrayValue() {
    $sequence_object = new ScriptTestSequence();
    $sequence_object->addValue($this->sequence);
    $this->assertEquals($this->sequence, $sequence_object->getSequence());
  }

  /**
   * Tests adding a single string value.
   *
   * @group SimulationIterator
   */
  public function testAddStringValue() {
    $sequence_object = new ScriptTestSequence();
    $sequence_object->addValue('status1');
    $this->assertEquals(array('status1'), $sequence_object->getSequence());
  }

  /**
   * Tests adding invalid data.
   *
   * @param mixed $value
   *   The value provided by the data provider.
   *
   * @expectedException \InvalidArgumentException
   *
   * @dataProvider nonStringOrArrayDataProvider
   */
  public function testAddInvalidValue($value) {
    $sequence_object = new ScriptTestSequence();
    $sequence_object->addValue($value);
  }

  /**
   * Tests getting a sequence.
   *
   * @group SimulationIterator
   */
  public function testGetSequence() {
    $sequence_object = new ScriptTestSequence();
    $this->assertEquals(array(), $sequence_object->getSequence());
  }

  /**
   * Tests resetting.
   *
   * @group SimulationIterator
   */
  public function testReset() {
    $sequence_object = new ScriptTestSequence();
    $sequence_object->addValue($this->sequence);
    $this->assertEquals($this->sequence, $sequence_object->getSequence());
    $sequence_object->reset();
    $this->assertEquals(array(), $sequence_object->getSequence());
  }

  /**
   * Tests peek.
   *
   * @group SimulationIterator
   */
  public function testPeek() {
    $sequence_object = new ScriptTestSequence();
    $this->assertNull($sequence_object->peek());
    $sequence_object->addValue($this->sequence);
    $this->assertEquals('status1', $sequence_object->peek());
    // Make sure the value is still on the underlying array.
    $this->assertEquals('status1', $sequence_object->peek());
  }

  /**
   * Tests pop.
   *
   * @group SimulationIterator
   */
  public function testPop() {
    $sequence_object = new ScriptTestSequence();
    $this->assertNull($sequence_object->pop());
    $sequence_object->addValue($this->sequence);
    $this->assertEquals('status1', $sequence_object->pop());
    // Make sure the value has been removed from the underlying array.
    $this->assertEquals('status2', $sequence_object->pop());
  }

}
