<?php

namespace Acquia\Wip\Test\PrivateStable\Iterator\BasicIterator;

use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\Iterators\BasicIterator\StateMachine;
use Acquia\Wip\Iterators\BasicIterator\StateMachineValidator;
use Acquia\Wip\Iterators\BasicIterator\StateTableParser;
use Acquia\Wip\Iterators\BasicIterator\ValidationResult;

/**
 * Missing summary.
 */
class TestValidationResult extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @var ValidationResult
   */
  private $validationResult = NULL;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    $this->validationResult = new ValidationResult(new StateMachine());
  }

  /**
   * Missing summary.
   */
  public function testEmptyValidationResult() {
    $this->assertEmpty($this->validationResult->getMissingpaths());
  }

  /**
   * Missing summary.
   */
  public function testUsedValidationObject() {
    $this->assertFalse($this->validationResult->getUsedValidationObject());
    $this->validationResult->setUsedValidationObject(TRUE);
    $this->assertTrue($this->validationResult->getUsedValidationObject());
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testUsedValidationObjectBadValue() {
    $this->validationResult->setUsedValidationObject('hello');
  }

  /**
   * Missing summary.
   */
  public function testSetStateTable() {
    $state_table = <<<EOT
start {
  * finish
}
EOT;

    $this->validationResult->setStateTable($state_table);
    $this->assertEquals($state_table, $this->validationResult->getStateTable());
  }

  /**
   * Missing summary.
   */
  public function testAddMissingBlocks() {
    $missing_block = 'test';
    $this->validationResult->addMissingBlock($missing_block);
    $missing_blocks = $this->validationResult->getMissingBlocks();
    $this->assertEquals(1, count($missing_blocks));
    $this->assertEquals($missing_block, $missing_blocks[0]);
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testBadBlockType() {
    $this->validationResult->addMissingBlock(15);
  }

  /**
   * Missing summary.
   */
  public function testAddMissingBlockMultipleTimes() {
    $missing_block = 'test';
    $this->validationResult->addMissingBlock($missing_block);
    $missing_blocks = $this->validationResult->getMissingBlocks();
    $this->assertEquals(1, count($missing_blocks));
    $this->assertEquals($missing_block, $missing_blocks[0]);

    $this->validationResult->addMissingBlock($missing_block);
    $this->assertEquals(array($missing_block), $this->validationResult->getMissingBlocks());
  }

  /**
   * Missing summary.
   */
  public function testAddMissingPaths() {
    $missing_path = 'test';
    $this->validationResult->addMissingPath($missing_path);
    $missing_paths = $this->validationResult->getMissingPaths();
    $this->assertEquals(1, count($missing_paths));
    $this->assertEquals($missing_path, $missing_paths[0]);
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testBadPathType() {
    $this->validationResult->addMissingPath(15);
  }

  /**
   * Missing summary.
   */
  public function testAddMissingPathMultipleTimes() {
    $missing_path = 'test';
    $this->validationResult->addMissingPath($missing_path);
    $missing_paths = $this->validationResult->getMissingPaths();
    $this->assertEquals(1, count($missing_paths));
    $this->assertEquals($missing_path, $missing_paths[0]);

    $this->validationResult->addMissingPath($missing_path);
    $this->assertEquals(array($missing_path), $this->validationResult->getMissingPaths());
  }

  /**
   * Missing summary.
   */
  public function testAddSpinTransition() {
    $this->validationResult->addSpinTransition('start', '*');
    $this->assertTrue($this->validationResult->hasFailures());
    $this->assertEquals(array('start' => array('*')), $this->validationResult->getSpinTransitions());
  }

  /**
   * Missing summary.
   */
  public function testAddSpinTransitionMultipleTimes() {
    $this->validationResult->addSpinTransition('start', '*');
    $this->assertTrue($this->validationResult->hasFailures());
    $this->assertEquals(array('start' => array('*')), $this->validationResult->getSpinTransitions());
    $this->validationResult->addSpinTransition('start', '*');
    $this->assertEquals(array('start' => array('*')), $this->validationResult->getSpinTransitions());
  }

  /**
   * Missing summary.
   */
  public function testAddMissingStateMethod() {
    $this->validationResult->addMissingStateMethod('start');
    $this->assertTrue($this->validationResult->hasFailures());
    $this->assertEquals(array('start'), $this->validationResult->getMissingStateMethods());
  }

  /**
   * Missing summary.
   */
  public function testAddMissingStateMethodMultipleTimes() {
    $this->validationResult->addMissingStateMethod('start');
    $this->validationResult->addMissingStateMethod('start');
    $this->assertTrue($this->validationResult->hasFailures());
    $this->assertEquals(array('start'), $this->validationResult->getMissingStateMethods());
  }

  /**
   * Missing summary.
   */
  public function testAddMissingTransitionMethod() {
    $this->validationResult->addMissingTransitionMethod('checkValue');
    $this->assertTrue($this->validationResult->hasFailures());
    $this->assertEquals(array('checkValue'), $this->validationResult->getMissingTransitionMethods());
  }

  /**
   * Missing summary.
   */
  public function testAddMissingTransitionMethodMultipleTimes() {
    $this->validationResult->addMissingTransitionMethod('checkValue');
    $this->validationResult->addMissingTransitionMethod('checkValue');
    $this->assertTrue($this->validationResult->hasFailures());
    $this->assertEquals(array('checkValue'), $this->validationResult->getMissingTransitionMethods());
  }

  /**
   * Missing summary.
   */
  public function testUsedTransitionValues() {
    $this->validationResult->addUsedTransition('step1', 'a');
    $this->validationResult->addUsedTransition('step1', 'b');
    $this->assertEquals(array('a', 'b'), $this->validationResult->getUsedTransitionValues('step1'));
  }

  /**
   * Missing summary.
   */
  public function testAvailableTransitionValues() {
    $this->validationResult->addAvailableTransitionValue('step1', 'a');
    $this->validationResult->addAvailableTransitionValue('step1', 'b');
    $this->assertEquals(array('a', 'b'), $this->validationResult->getAvailableTransitionValues('step1'));
  }

  /**
   * Missing summary.
   */
  public function testReportGeneration() {
    $this->validationResult->addMissingTransitionMethod('checkValue');
    $this->validationResult->addMissingTransitionMethod('checkValue');
    $this->assertTrue($this->validationResult->hasFailures());
    $this->assertNotEmpty($this->validationResult->getReport());
  }

  /**
   * Missing summary.
   */
  public function testUsedValidationObjectNegative() {
    $state_table = <<<EOT
start {
  * finish
}
EOT;

    $parser = new StateTableParser($state_table);
    $state_machine = $parser->parse();
    $validator = new StateMachineValidator();
    $validation_result = $validator->validate($state_machine, $state_table);
    $this->assertFalse($validation_result->getUsedValidationObject());
  }

  /**
   * Missing summary.
   */
  public function testUsedValidationObjectPositive() {
    $state_table = <<<EOT
start {
  * finish
}
EOT;

    $parser = new StateTableParser($state_table);
    $state_machine = $parser->parse();
    $validator = new StateMachineValidator();
    $validation_result = $validator->validate($state_machine, $state_table, new BasicWip());
    $this->assertTrue($validation_result->getUsedValidationObject());
  }

}
