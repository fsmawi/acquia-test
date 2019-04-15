<?php

namespace Acquia\Wip\Test\PrivateStable\Iterator\BasicIterator;

use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\Iterators\BasicIterator\StateMachineValidator;
use Acquia\Wip\Iterators\BasicIterator\StateTableParser;

/**
 * Tests the StateMachineValidator class.
 */
class StateMachineValidatorTest extends \PHPUnit_Framework_TestCase {

  /**
   * Tests instantiation.
   */
  public function testInstantiation() {
    $validator = new StateMachineValidator();
    $this->assertNotNull($validator);
  }

  /**
   * Tests that a valid state machine will be validated.
   */
  public function testValidateWithGoodStateMachine() {
    $state_table = <<<EOT
start {
  * finish
}

failure {
  * finish
}

terminate {
  * failure
}
EOT;

    $parser = new StateTableParser($state_table);
    $state_machine = $parser->parse();
    $validator = new StateMachineValidator();
    $validation_result = $validator->validate($state_machine);
    $this->assertFalse($validation_result->hasFailures());
    $this->assertEmpty($validation_result->getMissingBlocks());
    $this->assertEmpty($validation_result->getMissingPaths());
  }

  /**
   * Tests state machines without a path to finish.
   */
  public function testValidateWithNoPathToFinish() {
    $state_table = <<<EOT
start {
  * state1
}

state1 {
  * start
  fail failure
}

failure {
  * start
}
EOT;

    $parser = new StateTableParser($state_table);
    $state_machine = $parser->parse();
    $validator = new StateMachineValidator();
    $validation_result = $validator->validate($state_machine);
    $this->assertTrue($validation_result->hasFailures());
    $this->assertEquals(array('start', 'state1', 'failure'), $validation_result->getMissingPaths());
    $this->assertEquals(array(), $validation_result->getMissingBlocks());
  }

  /**
   * Tests state machines containing cycles.
   */
  public function testValidateWithPathToFinishContainingCycles() {
    $state_table = <<<EOT
start {
  wait start wait=1
  * state1
}

state1 {
  wait state1 wait=1
  start start
  fail failure
}

failure {
  * finish
}

terminate {
  * failure
}
EOT;

    $parser = new StateTableParser($state_table);
    $state_machine = $parser->parse();
    $validator = new StateMachineValidator();
    $validation_result = $validator->validate($state_machine);
    $this->assertFalse($validation_result->hasFailures());
  }

  /**
   * Tests state machines with cycles and missing blocks.
   */
  public function testValidateWithMissingBlockContainingCycles() {
    $state_table = <<<EOT
start {
  wait start
  * state1
}

state1 {
  wait state1
  start start
  fail failure
  ? missingState
}

failure {
  * finish
}
EOT;

    $parser = new StateTableParser($state_table);
    $state_machine = $parser->parse();
    $validator = new StateMachineValidator();
    $validation_result = $validator->validate($state_machine);
    $this->assertTrue($validation_result->hasFailures());
    $this->assertEquals(array('missingState'), $validation_result->getMissingBlocks());
    $this->assertEquals(array(), $validation_result->getMissingPaths());
  }

  /**
   * Tests state machines with waits.
   */
  public function testValidateWithSpin() {
    $state_table = <<<EOT
start {
  wait start
  * finish
}

failure {
  * finish
}
EOT;

    $parser = new StateTableParser($state_table);
    $state_machine = $parser->parse();
    $validator = new StateMachineValidator();
    $validation_result = $validator->validate($state_machine);
    $this->assertTrue($validation_result->hasFailures());
    $this->assertEmpty($validation_result->getMissingBlocks());
    $this->assertEmpty($validation_result->getMissingPaths());
    $this->assertEquals(array('start' => array('wait')), $validation_result->getSpinTransitions());
  }

  /**
   * Tests state methods in state machines.
   */
  public function testStateMethods() {
    $state_table = <<<EOT
start {
  * finish
}

failure {
  * finish
}

terminate {
  * failure
}
EOT;

    $parser = new StateTableParser($state_table);
    $state_machine = $parser->parse();
    $validator = new StateMachineValidator();
    $wip_obj = new BasicWip();
    $wip_obj->setStateTable($state_table);
    $validation_result = $validator->validate($state_machine, $wip_obj);
    $this->assertFalse($validation_result->hasFailures());
    $this->assertEmpty($validation_result->getMissingStateMethods());
  }

  /**
   * Tests state machines with missing state methods.
   */
  public function testMissingStateMethods() {
    $state_table = <<<EOT
start {
  * state1
}

state1 {
  * finish
}

failure {
  * finish
}

terminate {
  * failure
}
EOT;

    $parser = new StateTableParser($state_table);
    $state_machine = $parser->parse();
    $validator = new StateMachineValidator();
    $wip_obj = new BasicWip();
    $wip_obj->setStateTable($state_table);
    $validation_result = $validator->validate($state_machine, $state_table, $wip_obj);
    $this->assertTrue($validation_result->hasFailures());
    $this->assertEquals(array('state1'), $validation_result->getMissingStateMethods());
  }

  /**
   * Tests transition methods in state machines.
   */
  public function testTransitionMethods() {
    $state_table = <<<EOT
start:emptyTransition {
  * finish
}

failure {
  * finish
}

terminate {
  * failure
}
EOT;

    $parser = new StateTableParser($state_table);
    $state_machine = $parser->parse();
    $validator = new StateMachineValidator();
    $wip_obj = new BasicWip();
    $wip_obj->setStateTable($state_table);
    $validation_result = $validator->validate($state_machine, $wip_obj);
    $this->assertFalse($validation_result->hasFailures());
    $this->assertEmpty($validation_result->getMissingTransitionMethods());
  }

  /**
   * Tests state machines with missing transition methods.
   */
  public function testMissingTransitionMethods() {
    $state_table = <<<EOT
start:transition1 {
  * finish
}

failure {
  * finish
}

terminate {
  * failure
}
EOT;

    $parser = new StateTableParser($state_table);
    $state_machine = $parser->parse();
    $validator = new StateMachineValidator();
    $wip_obj = new BasicWip();
    $wip_obj->setStateTable($state_table);
    $validation_result = $validator->validate($state_machine, $state_table, $wip_obj);
    $this->assertTrue($validation_result->hasFailures());
    $this->assertEquals(array('transition1'), $validation_result->getMissingTransitionMethods());
  }

  /**
   * Tests getting used transition values from state machines.
   */
  public function testGetUsedTransitionValues() {
    $state_table = <<<EOT
start {
  * state1
}

state1:transition1 {
  a state2
  b state2
  c state2
  d state2
  e finish
}

state2 {
  * finish
}

failure {
  * finish
}

terminate {
  * failure
}
EOT;

    $parser = new StateTableParser($state_table);
    $state_machine = $parser->parse();
    $validator = new StateMachineValidator();
    $validation_result = $validator->validate($state_machine);
    $this->assertFalse($validation_result->hasFailures());

    $this->assertEquals(array('a', 'b', 'c', 'd', 'e'), $validation_result->getUsedTransitionValues('state1'));
  }

  /**
   * Tests getting available transition values from state machines.
   */
  public function testGetAvailableTransitionValues() {
    $state_table = <<<EOT
start {
  * step1
}

step1:transition1 {
  success step2
  fail step2
}

step2 {
  * finish
}

failure {
  * finish
}

terminate {
  * failure
}
EOT;

    $parser = new StateTableParser($state_table);
    $state_machine = $parser->parse();
    $wip_obj = new TranscriptTestWip();
    $validator = new StateMachineValidator();
    $validation_result = $validator->validate($state_machine, $state_table, $wip_obj);
    $this->assertFalse($validation_result->hasFailures());
    $this->assertEquals(array('success', 'fail'), $validation_result->getAvailableTransitionValues('step1'));
  }

  /**
   * Tests state machines with missing transition values.
   */
  public function testMissingTransitionValues() {
    $state_table = <<<EOT
start {
  * step1
}

step1:transition1 {
  success step2
}

step2 {
  * finish
}

failure {
  * finish
}

terminate {
  * failure
}
EOT;

    $parser = new StateTableParser($state_table);
    $state_machine = $parser->parse();
    $wip_obj = new TranscriptTestWip();
    $validator = new StateMachineValidator();
    $validation_result = $validator->validate($state_machine, $state_table, $wip_obj);
    $this->assertEquals(array('success', 'fail'), $validation_result->getAvailableTransitionValues('step1'));
    $this->assertTrue($validation_result->hasFailures());
  }

  /**
   * Tests state machines with unknown transition values.
   */
  public function testUnknownTransitionValues() {
    $state_table = <<<EOT
start {
  * step1
}

step1:transition1 {
  success step2
  fail step2
  missing step2
}

step2 {
  * finish
}

failure {
  * finish
}

terminate {
  * failure
}
EOT;

    $parser = new StateTableParser($state_table);
    $state_machine = $parser->parse();
    $wip_obj = new TranscriptTestWip();
    $validator = new StateMachineValidator();
    $validation_result = $validator->validate($state_machine, $state_table, $wip_obj);
    $this->assertEquals(
      array_values(array('missing')),
      array_values($validation_result->getUnknownTransitionValues('step1'))
    );
    $this->assertTrue($validation_result->hasFailures());
  }

  /**
   * Tests state machines without a failure to finish transition.
   */
  public function testMissingFailureToFinishTransition() {
    $state_table = <<<EOT
start {
  * finish
}
EOT;

    $parser = new StateTableParser($state_table);
    $state_machine = $parser->parse();
    $validator = new StateMachineValidator();
    $validation_result = $validator->validate($state_machine);

    $this->assertTrue($validation_result->hasFailures());
    $this->assertEquals(
      array('failure', 'terminate'),
      $validation_result->getMissingStateMethods()
    );
    $this->assertEquals(array(), $validation_result->getMissingBlocks());
  }

}
