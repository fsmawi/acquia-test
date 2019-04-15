<?php

namespace Acquia\Wip\Test\PrivateStable\Iterator\BasicIterator;

use Acquia\Wip\Exception\WipParseException;
use Acquia\Wip\Iterators\BasicIterator\TestScriptParser;

/**
 * Tests the TestScriptParser class.
 */
class TestScriptParserTest extends \PHPUnit_Framework_TestCase {
  private $validStateTable = <<<EOT
# Simple script derived from valid state table

start {
  * 3
}

step1 {
  * 2
}

step2 {
  * 1
}
EOT;

  private $stateTableMissingBrace = <<<EOT
# Simple state table.

start {
  * 3
}

step1 {
  * 2


step2 {
  * 1
}
EOT;

  private $stateTableMissingLastBrace = <<<EOT
# Simple state table.

start {
  * 3
}

step1 {
  * 2
}

step2 {
  * 1
EOT;

  private $stateTableMissingTransitionBrace = <<<EOT
# Simple state table.

start {
  * 3
}

step1
  * 2
}

step2 {
  * 1
}
EOT;

  private $stateTableBadLineInTransitionBlock = <<<EOT
# Simple state table.

start {
  * 3
}

step1 {
  * 2
  this should cause a problem
}

step2 {
  * 1
}
EOT;

  private $stateTableRepeatNotANumber = <<<EOT
# Simple state table.

start {
  * 3
}

step1 {
  * string
}

step2 {
  * 1
}
EOT;

  private $stateTableTooManyArguments = <<<EOT
# Simple state table.

start {
  * 3
}

step1 {
  * 3 2 1
}

step2 {
  * 1
}
EOT;

  /**
   * Tests the constructor.
   *
   * @group WipVerification
   */
  public function testParserConstructor() {
    new TestScriptParser('test');
  }

  /**
   * Tests empty constructor.
   *
   * @expectedException \InvalidArgumentException
   *
   * @group WipVerification
   */
  public function testEmptyConstructor() {
    new TestScriptParser(NULL);
  }

  /**
   * Tests parsing.
   *
   * @group WipVerification
   */
  public function testParse() {
    $parser = new TestScriptParser($this->validStateTable);
    $wip = $parser->parse();
    $this->assertInstanceOf('Acquia\Wip\Implementation\BasicWip', $wip);
  }

  /**
   * Tests that the correct number and value of transitions are added to states.
   *
   * @group WipVerification
   */
  public function disabledTestTransitionValuesCorrectlyAdded() {
    $parser = new TestScriptParser($this->validStateTable);
    $wip = $parser->parse();
    $this->assertEquals(array('*', '*', '*'), $wip->getTransitionValues()['start']);
    $this->assertEquals(array('*', '*'), $wip->getTransitionValues()['step1']);
    $this->assertEquals(array('*'), $wip->getTransitionValues()['step2']);
  }

  /**
   * Tests parsing with a bad state table.
   *
   * @group WipVerification
   */
  public function testParseWithBadStateTable() {
    $parser = new TestScriptParser('test');
    try {
      $parser->parse();
    } catch (WipParseException $e) {
    }
  }

  /**
   * Tests separate state tables.
   *
   * @group WipVerification
   */
  public function testSeparateStateTable() {
    $result = TestScriptParser::separateStateTable($this->validStateTable);
    $this->assertNotEmpty($result);
  }

  /**
   * Tests separate state tables with no end brace.
   *
   * @expectedException \Acquia\Wip\Exception\WipParseException
   *
   * @group WipVerification
   */
  public function testSeparateStateTableNoEndBrace() {
    TestScriptParser::separateStateTable($this->stateTableMissingBrace);
  }

  /**
   * Tests separate state tables with no final end brace.
   *
   * @expectedException \Acquia\Wip\Exception\WipParseException
   *
   * @group WipVerification
   */
  public function testSeparateStateTableNoFinalEndBrace() {
    TestScriptParser::separateStateTable($this->stateTableMissingLastBrace);
  }

  /**
   * Tests separate state tables with no transition brace.
   *
   * @expectedException \Acquia\Wip\Exception\WipParseException
   *
   * @group WipVerification
   */
  public function testSeparateStateTableNoTransitionBrace() {
    TestScriptParser::separateStateTable($this->stateTableMissingTransitionBrace);
  }

  /**
   * Tests separate state tables with bad lines in a transition block.
   *
   * @expectedException \Acquia\Wip\Exception\WipParseException
   *
   * @group WipVerification
   */
  public function testSeparateStateTableBadLineInTransitionBlock() {
    TestScriptParser::separateStateTable($this->stateTableBadLineInTransitionBlock);
  }

  /**
   * Tests extra arguments.
   *
   * @expectedException \Acquia\Wip\Exception\WipParseException
   *
   * @group WipVerification
   */
  public function testExtraArguments() {
    TestScriptParser::separateStateTable($this->stateTableTooManyArguments);
  }

  /**
   * Tests a repeat that is not a number.
   *
   * @expectedException \Acquia\Wip\Exception\WipParseException
   *
   * @group WipVerification
   */
  public function testNotNumberOfRepeats() {
    TestScriptParser::separateStateTable($this->stateTableRepeatNotANumber);
  }

}
