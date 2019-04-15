<?php

namespace Acquia\Wip\Test\PrivateStable\Iterator\BasicIterator;

use Acquia\Wip\Exception\WipParseException;
use Acquia\Wip\Iterators\BasicIterator\StateMachine;
use Acquia\Wip\Iterators\BasicIterator\StateTableParser;

/**
 * Missing summary.
 */
class ParserInterfaceTest extends \PHPUnit_Framework_TestCase {

  private $validStateTable = <<<EOT
# Simple state table.
parser = \Acquia\Wip\Interpreters\BST

start {
  * step1
}

iterator = whatever

step1 {
  * step2
}

step2 {
  * finish
}
EOT;

  private $stateTableMissingBrace = <<<EOT
# Simple state table.
parser = \Acquia\Wip\Interpreters\BST

start {
  * step1
}

iterator = whatever

step1 {
  * step2


step2 {
  * finish
}
EOT;

  private $stateTableMissingLastBrace = <<<EOT
# Simple state table.
parser = \Acquia\Wip\Interpreters\BST

start {
  * step1
}

iterator = whatever

step1 {
  * step2
}

step2 {
  * finish
EOT;

  private $stateTableMissingTransitionBrace = <<<EOT
# Simple state table.
parser = \Acquia\Wip\Interpreters\BST

start {
  * step1
}

iterator = whatever

step1
  * step2
}

step2 {
  * finish
}
EOT;

  private $stateTableBadLineInTransitionBlock = <<<EOT
# Simple state table.
parser = \Acquia\Wip\Interpreters\BST

start {
  * step1
}

iterator = whatever

step1 {
  * step2
  this should cause a problem
}

step2 {
  * finish
}
EOT;

  /**
   * Missing summary.
   *
   * @group StateTable
   */
  public function testParserConstructor() {
    new StateTableParser('test');
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   *
   * @group StateTable
   */
  public function testEmptyConstructor() {
    new StateTableParser(NULL);
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   */
  public function testParse() {
    $parser = new StateTableParser($this->validStateTable);
    $fsm = $parser->parse();
    $this->assertInstanceOf('Acquia\Wip\Iterators\BasicIterator\StateMachine', $fsm);
    $this->assertNotEmpty($fsm->getStartState());
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   */
  public function testParseWithBadStateTable() {
    $parser = new StateTableParser('test');
    try {
      $parser->parse();
    } catch (WipParseException $e) {
    }
  }

  /**
   * Missing summary.
   *
   * @expectedException \Acquia\Wip\Exception\WipParseException
   *
   * @group StateTable
   */
  public function testSeparateStateTableNoEndBrace() {
    $state_machine = new StateMachine();
    StateTableParser::separateStateTable($state_machine, $this->stateTableMissingBrace);
  }

  /**
   * Missing summary.
   *
   * @expectedException \Acquia\Wip\Exception\WipParseException
   *
   * @group StateTable
   */
  public function testSeparateStateTableNoFinalEndBrace() {
    $state_machine = new StateMachine();
    StateTableParser::separateStateTable($state_machine, $this->stateTableMissingLastBrace);
  }

  /**
   * Missing summary.
   *
   * @expectedException \Acquia\Wip\Exception\WipParseException
   *
   * @group StateTable
   */
  public function testSeparateStateTableNoTransitionBrace() {
    $state_machine = new StateMachine();
    StateTableParser::separateStateTable($state_machine, $this->stateTableMissingTransitionBrace);
  }

  /**
   * Missing summary.
   *
   * @expectedException \Acquia\Wip\Exception\WipParseException
   *
   * @group StateTable
   */
  public function testSeparateStateTableBadLineInTransitionBlock() {
    $state_machine = new StateMachine();
    StateTableParser::separateStateTable($state_machine, $this->stateTableBadLineInTransitionBlock);
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   */
  public function testSeparateStateTableWithComments() {
    $state_table = <<<EOT
# Simple state table.
      parser = \Acquia\Wip\Interpreters\BasicIterator\StateTableIterator

#Comment out the start state entirely.
#start {
#      * step1
#}

iterator = whatever # We should not see this comment in the result.

step1 {
      * step2 # Not in the result.
#}
}

step2 {
      * finish
}
EOT;
    $state_machine = new StateMachine();
    StateTableParser::separateStateTable($state_machine, $state_table);
    $this->assertEquals('step1', $state_machine->getStartState());
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   */
  public function testTransitionDetection() {
    $state_table = <<<EOT
# Simple state table.
      parser = \Acquia\Wip\Interpreters\BasicIterator\StateTableIterator

start:testTransition {
      * step1
}

iterator = whatever # We should not see this comment in the result.

step1 {
      * step2 # Not in the result.
}

step2 {
      * finish
}
EOT;
    $state_machine = new StateMachine();
    StateTableParser::separateStateTable($state_machine, $state_table);
    $transition = $state_machine->getTransitionBlock($state_machine->getStartState())->getTransitionMethod();
    $this->assertEquals('testTransition', $transition);
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   */
  public function testWaitValue() {
    $state_table = <<<EOT
    start:testTransition {
      * step1 wait=5
      x step3 wait=2
    }

    state1:whatever {
      * step2 wait=3
    }
EOT;
    $state_machine = new StateMachine();
    StateTableParser::separateStateTable($state_machine, $state_table);
    $transition_block1 = $state_machine->getTransitionBlock($state_machine->getStartState());
    $transition1 = $transition_block1->getTransition('*');
    $this->assertEquals(5, $transition1->getWait());

    $transition_block2 = $state_machine->getTransitionBlock('state1');
    $transition2 = $transition_block2->getTransition('*');
    $this->assertEquals(3, $transition2->getWait());
  }

  /**
   * Missing summary.
   *
   * @expectedException \Acquia\Wip\Exception\WipParseException
   *
   * @group StateTable
   */
  public function testBadWaitValue() {
    $state_table = <<<EOT
    start:testTransition {
      * step1 wait=help
    }
EOT;
    $state_machine = new StateMachine();
    StateTableParser::separateStateTable($state_machine, $state_table);
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   */
  public function testMaxValue() {
    $state_table = <<<EOT
    start:testTransition {
      * step1 max=5
      x step3 max=2
    }

    state1:whatever {
      * step2 max=3
    }
EOT;
    $state_machine = new StateMachine();
    StateTableParser::separateStateTable($state_machine, $state_table);
    $transition_block1 = $state_machine->getTransitionBlock($state_machine->getStartState());
    $transition1 = $transition_block1->getTransition('*');
    $this->assertEquals(5, $transition1->getMaxCount());

    $transition_block2 = $state_machine->getTransitionBlock('state1');
    $transition2 = $transition_block2->getTransition('*');
    $this->assertEquals(3, $transition2->getMaxCount());
  }

  /**
   * Missing summary.
   *
   * @expectedException \Acquia\Wip\Exception\WipParseException
   *
   * @group StateTable
   */
  public function testBadMaxValue() {
    $state_table = <<<EOT
    start:testTransition {
      * step1 max=help
    }
EOT;
    $state_machine = new StateMachine();
    StateTableParser::separateStateTable($state_machine, $state_table);
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   */
  public function testWaitAndMax() {
    $state_table = <<<EOT
    start:testTransition {
      * step1 wait=10 max=5
      x step3 wait=7 max=2
    }

    state1:whatever {
      * step2 wait=1 max=3
    }
EOT;
    $state_machine = new StateMachine();
    StateTableParser::separateStateTable($state_machine, $state_table);
    $transition_block1 = $state_machine->getTransitionBlock($state_machine->getStartState());
    $transition1 = $transition_block1->getTransition('*');
    $this->assertEquals(10, $transition1->getWait());
    $this->assertEquals(5, $transition1->getMaxCount());

    $transition_block2 = $state_machine->getTransitionBlock('state1');
    $transition2 = $transition_block2->getTransition('*');
    $this->assertEquals(1, $transition2->getWait());
    $this->assertEquals(3, $transition2->getMaxCount());
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   */
  public function testMaxAndWait() {
    $state_table = <<<EOT
    start:testTransition {
      * step1 max=10 wait=5
      x step3 max=7 wait=2
    }

    state1:whatever {
      * step2 wait=1 max=3
    }
EOT;
    $state_machine = new StateMachine();
    StateTableParser::separateStateTable($state_machine, $state_table);
    $transition_block1 = $state_machine->getTransitionBlock($state_machine->getStartState());
    $transition1 = $transition_block1->getTransition('*');
    $this->assertEquals(5, $transition1->getWait());
    $this->assertEquals(10, $transition1->getMaxCount());

    $transition_block2 = $state_machine->getTransitionBlock('state1');
    $transition2 = $transition_block2->getTransition('*');
    $this->assertEquals(1, $transition2->getWait());
    $this->assertEquals(3, $transition2->getMaxCount());
  }

  /**
   * Missing summary.
   *
   * @expectedException \Acquia\Wip\Exception\WipParseException
   *
   * @group StateTable
   */
  public function testBadOptions() {
    $state_table = <<<EOT
    start:testTransition {
      * step1 max=10 wait=5
      x step3 max=7 wait=2 this_is_an_error
    }

    state1:whatever {
      * step2 wait=1 max=3
    }
EOT;
    $state_machine = new StateMachine();
    StateTableParser::separateStateTable($state_machine, $state_table);
    $transition_block1 = $state_machine->getTransitionBlock($state_machine->getStartState());
    $transition1 = $transition_block1->getTransition('*');
    $this->assertEquals(5, $transition1->getWait());
    $this->assertEquals(10, $transition1->getMaxCount());

    $transition_block2 = $state_machine->getTransitionBlock('state1');
    $transition2 = $transition_block2->getTransition('*');
    $this->assertEquals(1, $transition2->getWait());
    $this->assertEquals(3, $transition2->getMaxCount());
  }

  /**
   * Missing summary.
   *
   * @expectedException \Acquia\Wip\Exception\WipParseException
   *
   * @group StateTable
   */
  public function testUnrecognizedOption() {
    $state_table = <<<EOT
    start:testTransition {
      * step1 max=10 wait=5
      x step3 max=7 wait=2
    }

    state1:whatever {
      * step2 wait=1 max=3
      success wait=7 hangTime=0 max=3
    }
EOT;
    $state_machine = new StateMachine();
    StateTableParser::separateStateTable($state_machine, $state_table);
    $transition_block1 = $state_machine->getTransitionBlock($state_machine->getStartState());
    $transition1 = $transition_block1->getTransition('*');
    $this->assertEquals(5, $transition1->getWait());
    $this->assertEquals(10, $transition1->getMaxCount());

    $transition_block2 = $state_machine->getTransitionBlock('state1');
    $transition2 = $transition_block2->getTransition('*');
    $this->assertEquals(1, $transition2->getWait());
    $this->assertEquals(3, $transition2->getMaxCount());
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   */
  public function testExecOption() {
    $state_table = <<<EOT
    start:testTransition {
      * step1 max=10 wait=5
      x step3 max=7 wait=2
      running start wait=10 exec=false
      success state1 exec=true
    }

    state1:whatever {
      * step2 wait=1 max=3
    }
EOT;
    $state_machine = new StateMachine();
    StateTableParser::separateStateTable($state_machine, $state_table);
    $transition_block1 = $state_machine->getTransitionBlock($state_machine->getStartState());
    $transition1 = $transition_block1->getTransition('running');
    $this->assertFalse($transition1->getExec());

    $transition2 = $transition_block1->getTransition('*');
    $this->assertTrue($transition2->getExec());

    $transition3 = $transition_block1->getTransition('success');
    $this->assertTrue($transition3->getExec());
  }

  /**
   * Missing summary.
   *
   * @expectedException \Acquia\Wip\Exception\WipParseException
   *
   * @group StateTable
   */
  public function testBadExecOption() {
    $state_table = <<<EOT
    start:testTransition {
      x step3 max=7 wait=2
      running start wait=10 exec=whatever
    }

    state1:whatever {
      * step2 wait=1 max=3
    }
EOT;
    $state_machine = new StateMachine();
    StateTableParser::separateStateTable($state_machine, $state_table);
    $state_machine->getTransitionBlock($state_machine->getStartState());
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   */
  public function testSeparateStateTableWithTimers() {
    $state_table = <<<EOT
start {
      * step1
}

step1 [user] {
      * step2
}

step2 [system] {
      * finish
}
EOT;
    $state_machine = new StateMachine();
    StateTableParser::separateStateTable($state_machine, $state_table);
    $this->assertEquals('start', $state_machine->getStartState());

    $this->assertEquals('system', $state_machine->getTransitionBlock('start')->getTimerName());
    $this->assertEquals('user', $state_machine->getTransitionBlock('step1')->getTimerName());
    $this->assertEquals('system', $state_machine->getTransitionBlock('step2')->getTimerName());
  }

  /**
   * Tests getting all available transition values from the parser.
   */
  public function testGetAvailableTransitionValues() {
    $test_wip = new TranscriptTestWip();
    $state_machine = new StateMachine();
    StateTableParser::separateStateTable(
      $state_machine,
      $test_wip->getStateTable()
    );

    $available_transitions = StateTableParser::getAvailableTransitionValues(
      $test_wip,
      'transition1'
    );
    // Transition1 has two possible values: 'success' and 'fail'.
    $this->assertCount(2, $available_transitions);

    $available_transitions = StateTableParser::getAvailableTransitionValues(
      $test_wip,
      'transition2'
    );
    // Transition2 has no possible values.
    $this->assertCount(0, $available_transitions);
  }

}
