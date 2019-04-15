<?php

namespace Acquia\Wip\Test\PrivateStable\Iterator\BasicIterator;

use Acquia\Wip\Iterators\BasicIterator\SimulationScriptInterpreter;

/**
 * Tests the SimulationScriptInterpreter class.
 */
class SimulationScriptInterpreterTest extends \PHPUnit_Framework_TestCase {

  /**
   * Tests the constructor.
   *
   * @group StateTable
   */
  public function testConstructor() {
    $script = <<<EOT
    state {
      'success'
    }
EOT;
    $parser = new SimulationScriptInterpreter($script);
  }

  /**
   * Tests interpreting scripts with comments.
   *
   * @group StateTable
   */
  public function testScriptWithComments() {
    $script = <<<EOT
# This is a comment.
# Here is another
state {
  'transition'
}
EOT;

    $parser = new SimulationScriptInterpreter($script);
  }

  /**
   * Tests interpreting scripts with no instructions.
   *
   * @expectedException \InvalidArgumentException
   *
   * @group StateTable
   */
  public function testScriptWithNoInstructions() {
    $parser = new SimulationScriptInterpreter('');
  }

  /**
   * Tests interpreting valid scripts.
   *
   * @group StateTable
   */
  public function testValidScript() {
    $script = <<<EOT
    state {
      'value1'
      'value2'
    }
EOT;

    $parser = new SimulationScriptInterpreter($script);
  }

  /**
   * Tests interpreting invalid scripts.
   *
   * @expectedException \InvalidArgumentException
   *
   * @group StateTable
   */
  public function testInvalidScript() {
    $script = <<<EOT
    state transition_value what
    state transition_value test
EOT;

    $parser = new SimulationScriptInterpreter($script);
  }

  /**
   * Tests the interpreter.
   *
   * @group StateTable
   */
  public function testInterpreter() {
    $script = <<<EOT
    state1 {
      'success'
    }

    state2 {
      'success'
    }

    state3 {
      'failure'
    }

EOT;

    $parser = new SimulationScriptInterpreter($script);
    $value = $parser->getNextTransitionValue('state1');
    $this->assertEquals('success', $value);
    $value = $parser->getNextTransitionValue('state2');
    $this->assertEquals('success', $value);
    $value = $parser->getNextTransitionValue('state3');
    $this->assertEquals('failure', $value);
  }

  /**
   * Tests reset.
   *
   * @group StateTable
   */
  public function testReset() {
    $script = <<<EOT
    state1 {
      'success'
    }

    state2 {
      'success'
    }

    state3 {
      'failure'
    }
EOT;

    $parser = new SimulationScriptInterpreter($script);
    for ($index = 0; $index < 3; $index++) {
      $parser->getNextTransitionValue(sprintf('state%d', $index + 1));
    }

    // This should have exhausted the script.
    $parser->reset();
    $value = $parser->getNextTransitionValue('state1');
    $this->assertEquals('success', $value);
    $value = $parser->getNextTransitionValue('state2');
    $this->assertEquals('success', $value);
    $value = $parser->getNextTransitionValue('state3');
    $this->assertEquals('failure', $value);
  }

  /**
   * Tests interpreting incorrect states.
   *
   * @expectedException \DomainException
   *
   * @group StateTable
   */
  public function testIncorrectState() {
    $script = <<<EOT
    state1 {
      'success'
    }

    state2 {
      'success'
    }

    state3 {
      'failure'
    }
EOT;

    $parser = new SimulationScriptInterpreter($script);
    $parser->getNextTransitionValue('wrongstate');
  }

  /**
   * Tests interpreting simulations with no values.
   *
   * @group StateTable
   */
  public function testSimulationWithNoValue() {
    $script = <<<EOT
    state1 {
      ''
    }

    state2 {
      'success'
    }

    state3 {
      'failure'
    }
EOT;

    $parser = new SimulationScriptInterpreter($script);
    $this->assertEquals('', $parser->getNextTransitionValue('state1'));
  }

}
