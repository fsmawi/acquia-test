<?php

namespace Acquia\Wip\Test\PrivateStable\Iterator\SimulationIterator;

use Acquia\Wip\Iterators\SimulationIterator\ScriptTestSequence;
use Acquia\Wip\Iterators\SimulationIterator\StateTableScript;
use Acquia\Wip\Test\Utility\DataProviderTrait;

/**
 * Tests the StateTableScript class.
 */
class StateTableScriptTest extends \PHPUnit_Framework_TestCase {

  use DataProviderTrait;

  private $sequence = array('status1', 'status2', 'status2');

  /**
   * Tests adding state sequence.
   *
   * @group SimulationIterator
   */
  public function testAddStateSequence() {
    $sequence_object = new ScriptTestSequence();
    $sequence_object->addValue($this->sequence);

    $script = new StateTableScript();
    $script->addStateSequence('state1', $sequence_object);
    $this->assertEquals($sequence_object, $script->getStateSequence('state1'));
  }

  /**
   * Tests adding sequences to empty state names.
   *
   * @param mixed $value
   *   The value from the provider.
   *
   * @dataProvider emptyProvider
   *
   * @expectedException \InvalidArgumentException
   *
   * @group SimulationIterator
   */
  public function testAddEmptyStateSequence($value) {

    $sequence_object = new ScriptTestSequence();
    $sequence_object->addValue($this->sequence);

    $script = new StateTableScript();
    $script->addStateSequence($value, $sequence_object);
  }

  /**
   * Tests adding state value arrays.
   *
   * @group SimulationIterator
   */
  public function testAddStateValueArray() {
    $sequence_object = new ScriptTestSequence();
    $sequence_object->addValue($this->sequence);

    $script = new StateTableScript();
    $script->addStateValue('state1', $this->sequence);
    $this->assertEquals($sequence_object, $script->getStateSequence('state1'));
  }

  /**
   * Tests adding state value strings.
   *
   * @group SimulationIterator
   */
  public function testAddStateValueString() {
    $sequence_object = new ScriptTestSequence();
    $sequence_object->addValue('value');

    $script = new StateTableScript();
    $script->addStateValue('state1', 'value');
    $this->assertEquals($sequence_object, $script->getStateSequence('state1'));
  }

  /**
   * Tests adding state value strings to empty state names.
   *
   * @param mixed $value
   *   The value from the provider.
   *
   * @dataProvider emptyProvider
   *
   * @expectedException \InvalidArgumentException
   *
   * @group SimulationIterator
   */
  public function testAddEmptyStateValue($value) {
    $script = new StateTableScript();
    $script->addStateValue($value, 'Some value');
  }

  /**
   * Tests getting the values of empty state names.
   *
   * @param mixed $value
   *   The value from the provider.
   *
   * @dataProvider emptyProvider
   *
   * @expectedException \InvalidArgumentException
   *
   * @group SimulationIterator
   */
  public function testGetInvalidStateSequence($value) {
    $script = new StateTableScript();
    $script->getStateSequence($value);
  }

  /**
   * Tests getting the next transition value.
   *
   * @group SimulationIterator
   */
  public function testGetNextTransitionValue() {
    $script = new StateTableScript();
    $script->addStateValue('state1', $this->sequence);
    $this->assertEquals('status1', $script->getNextTransitionValue('state1'));
    $this->assertEquals('status2', $script->getNextTransitionValue('state1'));
  }

  /**
   * Tests getting the next transition value of empty state names.
   *
   * @expectedException \InvalidArgumentException
   *
   * @group SimulationIterator
   */
  public function testGetNextTransitionValueFromEmptyState() {
    $script = new StateTableScript();
    $script->getNextTransitionValue('');
  }

  /**
   * Tests getting the next transition value of states that do not exist.
   *
   * @expectedException \InvalidArgumentException
   *
   * @group SimulationIterator
   */
  public function testGetNextTransitionValueFromNonexistentState() {
    $script = new StateTableScript();
    $script->addStateValue('state1', $this->sequence);
    $script->getNextTransitionValue('Nonexistent State');
  }

}
