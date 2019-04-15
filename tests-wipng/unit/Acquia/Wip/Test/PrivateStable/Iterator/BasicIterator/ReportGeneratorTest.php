<?php

namespace Acquia\Wip\Test\PrivateStable\Iterator\BasicIterator;

use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\Iterators\BasicIterator\ReportGenerator;
use Acquia\Wip\Iterators\BasicIterator\StateMachine;
use Acquia\Wip\Iterators\BasicIterator\StateMachineValidator;
use Acquia\Wip\Iterators\BasicIterator\StateTableParser;
use Acquia\Wip\Iterators\BasicIterator\ValidationResult;

/**
 * Missing summary.
 */
class ReportGeneratorTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   */
  public function testInstantiation() {
    new ReportGenerator(new ValidationResult(new StateMachine()));
  }

  /**
   * Missing summary.
   */
  public function testCleanReport() {
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
    $validation_result = $validator->validate($state_machine, $state_table);
    $this->assertFalse($validation_result->hasFailures());

    $section_name = ReportGenerator::STATE_TABLE_SECTION;
    $report_generator = new ReportGenerator($validation_result);
    $report = $report_generator->generate();
    $this->assertTrue($this->resultContainsSection($report, $section_name));
  }

  /**
   * Missing summary.
   */
  public function testMissingStateTable() {
    $state_table = <<<EOT
start {
  * finish
}

failure {
  * finish
}

terminate {
  * finish
}
EOT;

    $parser = new StateTableParser($state_table);
    $state_machine = $parser->parse();
    $validator = new StateMachineValidator();
    $validation_result = $validator->validate($state_machine);
    $this->assertFalse($validation_result->hasFailures());

    $section_name = ReportGenerator::MISSING_STATE_TABLE_SECTION;
    $report_generator = new ReportGenerator($validation_result);
    $report = $report_generator->generate();
    $this->assertTrue($this->resultContainsSection($report, $section_name));
  }

  /**
   * Missing summary.
   */
  public function testMissingTransitionBlocks() {
    $state_table = <<<EOT
start {
  * state1
  a state2
  b finish
}
EOT;

    $parser = new StateTableParser($state_table);
    $state_machine = $parser->parse();
    $validator = new StateMachineValidator();
    $validation_result = $validator->validate($state_machine, $state_table);
    $this->assertTrue($validation_result->hasFailures());

    $section_name = ReportGenerator::MISSING_TRANSITION_BLOCKS_SECTION;
    $report_generator = new ReportGenerator($validation_result);
    $report = $report_generator->generate();
    $this->assertTrue($this->resultContainsSection($report, $section_name));
  }

  /**
   * Missing summary.
   */
  public function testNoPathToFinish() {
    $state_table = <<<EOT
start {
  * state1
}

state1 {
  * state2
}

state2 {
  a state1
}
EOT;

    $parser = new StateTableParser($state_table);
    $state_machine = $parser->parse();
    $validator = new StateMachineValidator();
    $validation_result = $validator->validate($state_machine, $state_table);
    $this->assertTrue($validation_result->hasFailures());

    $section_name = ReportGenerator::NO_PATH_TO_FINISH_STATE;
    $report_generator = new ReportGenerator($validation_result);
    $report = $report_generator->generate();
    $this->assertTrue($this->resultContainsSection($report, $section_name));
  }

  /**
   * Missing summary.
   */
  public function testMissingStateMethods() {
    $state_table = <<<EOT
start {
  * state1
}

state1 {
  * state2
}

state2 {
  * finish
}
EOT;

    $parser = new StateTableParser($state_table);
    $state_machine = $parser->parse();
    $validator = new StateMachineValidator();
    $validation_result = $validator->validate($state_machine, $state_table, new BasicWip());
    $this->assertTrue($validation_result->hasFailures());

    $section_name = ReportGenerator::MISSING_METHODS;
    $report_generator = new ReportGenerator($validation_result);
    $report = $report_generator->generate();
    $this->assertTrue($this->resultContainsSection($report, $section_name));
  }

  /**
   * Missing summary.
   */
  public function testMissingTransitionMethods() {
    $state_table = <<<EOT
start:transition1 {
  * finish
}
EOT;

    $parser = new StateTableParser($state_table);
    $state_machine = $parser->parse();
    $validator = new StateMachineValidator();
    $validation_result = $validator->validate($state_machine, $state_table, new BasicWip());
    $this->assertTrue($validation_result->hasFailures());

    $section_name = ReportGenerator::MISSING_METHODS;
    $report_generator = new ReportGenerator($validation_result);
    $report = $report_generator->generate();
    $this->assertTrue($this->resultContainsSection($report, $section_name));
  }

  /**
   * Missing summary.
   */
  public function testMissingStateAndTransitionMethods() {
    $state_table = <<<EOT
start {
  * state1
}

state1:transition1 {
  * state2
}

state2:transition2 {
  * finish
}
EOT;

    $parser = new StateTableParser($state_table);
    $state_machine = $parser->parse();
    $validator = new StateMachineValidator();
    $validation_result = $validator->validate($state_machine, $state_table, new BasicWip());
    $this->assertTrue($validation_result->hasFailures());

    $section_name = ReportGenerator::MISSING_METHODS;
    $report_generator = new ReportGenerator($validation_result);
    $report = $report_generator->generate();
    $this->assertTrue($this->resultContainsSection($report, $section_name));
  }

  /**
   * Missing summary.
   */
  public function testUnusedTransitionValues() {
    $state_table = <<<EOT
start {
  * state1
}

state1:transition1 {
  a state2
}

state2:transition2 {
  * finish
}
EOT;

    $parser = new StateTableParser($state_table);
    $state_machine = $parser->parse();
    $validator = new StateMachineValidator();
    $validation_result = $validator->validate($state_machine, $state_table, new TranscriptTestWip());
    $this->assertTrue($validation_result->hasFailures());

    $section_name = ReportGenerator::UNUSED_TRANSITION_VALUES;
    $report_generator = new ReportGenerator($validation_result);
    $report = $report_generator->generate();
    $this->assertTrue($this->resultContainsSection($report, $section_name));
  }

  /**
   * Missing summary.
   */
  public function testSourceCodeWithNoObject() {
    $state_table = <<<EOT
start:transition6 {
  hello state1
}

state1:transition1 {
  a state2
  b start
  c state1 wait=1
}

state2:transition2 {
  a start
  b state1
  c state2 wait=1
  d finish
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
    $validation_result = $validator->validate($state_machine, $state_table);
    $this->assertFalse($validation_result->hasFailures());

    $section_name = ReportGenerator::SOURCE_CODE;
    $report_generator = new ReportGenerator($validation_result);
    $report = $report_generator->generate();
    $this->assertTrue($this->resultContainsSection($report, $section_name));
    $this->assertTrue($this->resultContainsSection($report, ReportGenerator::SUCCESS_BUT_NO_OBJECT));
  }

  /**
   * Missing summary.
   */
  public function testNoSourceCodeForBrokenWipWithNoObject() {
    $state_table = <<<EOT
start:transition6 {
  hello state1
}

state1:transition1 {
  a state2
  b start
  c state1 # This should have a wait value.
}

state2:transition2 {
  a start
  b state1
  c state2 wait=1
  d finish
}
EOT;

    $parser = new StateTableParser($state_table);
    $state_machine = $parser->parse();
    $validator = new StateMachineValidator();
    $validation_result = $validator->validate($state_machine, $state_table);
    $this->assertTrue($validation_result->hasFailures());

    $section_name = ReportGenerator::SOURCE_CODE;
    $report_generator = new ReportGenerator($validation_result);
    $report = $report_generator->generate();
    $this->assertFalse($this->resultContainsSection($report, $section_name));
    $this->assertTrue($this->resultContainsSection($report, ReportGenerator::FAIL));
  }

  /**
   * Missing summary.
   */
  public function testSourceCodeWithBrokenStateTableWithObject() {
    $state_table = <<<EOT
start:transition6 {
  hello state1
}

state1:transition1 {
  a state2
  b start
  c state1 wait=1
}

state2:transition2 {
  a start
  b state1
  c state2 wait=1
  d finish
}
EOT;

    $parser = new StateTableParser($state_table);
    $state_machine = $parser->parse();
    $validator = new StateMachineValidator();

    // The state table above references transitions and states not available
    // in the BasicWip class. Thus the source code for missing methods should be
    // provided.
    $validation_result = $validator->validate($state_machine, $state_table, new BasicWip());
    $this->assertTrue($validation_result->hasFailures());

    $section_name = ReportGenerator::SOURCE_CODE;
    $report_generator = new ReportGenerator($validation_result);
    $report = $report_generator->generate();
    $this->assertTrue($this->resultContainsSection($report, $section_name));
    $this->assertTrue($this->resultContainsSection($report, ReportGenerator::FAIL));
  }

  /**
   * Missing summary.
   */
  public function testSourceCodeWithPerfectStateTableWithObject() {
    $state_table = <<<EOT
start {
  * step1
}

step1:transition1 {
  success step2
  fail start
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
    $validator = new StateMachineValidator();

    // The state table above references transitions and states not available
    // in the BasicWip class. Thus the source code for missing methods should be
    // provided.
    $validation_result = $validator->validate($state_machine, $state_table, new TranscriptTestWip());
    $this->assertFalse($validation_result->hasFailures());

    $section_name = ReportGenerator::SOURCE_CODE;
    $report_generator = new ReportGenerator($validation_result);
    $report = $report_generator->generate();
    $this->assertFalse($this->resultContainsSection($report, $section_name));
    $this->assertTrue($this->resultContainsSection($report, ReportGenerator::SUCCESS));
  }

  /**
   * Missing summary.
   */
  public function testUnrecognizedTransitionValues() {
    $state_table = <<<EOT
start {
  * step1
}

step1:transition1 {
  a step2
  b step2
  c step2
}

step2:transition2 {
  * finish
}
EOT;

    $parser = new StateTableParser($state_table);
    $state_machine = $parser->parse();
    $validator = new StateMachineValidator();
    $validation_result = $validator->validate($state_machine, $state_table, new TranscriptTestWip());
    $this->assertTrue($validation_result->hasFailures());

    $section_name = ReportGenerator::UNRECOGNIZED_TRANSITION_VALUES;
    $report_generator = new ReportGenerator($validation_result);
    $report = $report_generator->generate();
    $this->assertTrue($this->resultContainsSection($report, $section_name));
  }

  /**
   * Missing summary.
   */
  public function testSpinTransitions() {
    $state_table = <<<EOT
start {
  * start
  x step1
}

step1 {
  * step1
  x finish
}
EOT;

    $parser = new StateTableParser($state_table);
    $state_machine = $parser->parse();
    $validator = new StateMachineValidator();
    $validation_result = $validator->validate($state_machine, $state_table, new TranscriptTestWip());
    $this->assertTrue($validation_result->hasFailures());

    $section_name = ReportGenerator::SPIN_TRANSITIONS;
    $report_generator = new ReportGenerator($validation_result);
    $report = $report_generator->generate();
    $this->assertTrue($this->resultContainsSection($report, $section_name));
  }

  /**
   * Missing summary.
   */
  public function testStateTableHeader() {
    $section_name = 'STATE TABLE';
    $section_header = ReportGenerator::generateSectionSeparator($section_name);
    $this->assertTrue($this->verifyHeader($section_name, $section_header));
  }

  /**
   * Missing summary.
   */
  public function testMissingCodeHeader() {
    $section_name = 'MISSING CODE';
    $section_header = ReportGenerator::generateSectionSeparator($section_name);
    $this->assertTrue($this->verifyHeader($section_name, $section_header));
  }

  /**
   * Missing summary.
   */
  public function testAsteriskValuesSection() {
    $state_table = <<<EOT
start {
  * step1
}

step1:transition1 {
  * step2
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
    $validator = new StateMachineValidator();

    // The state table above references transitions and states not available
    // in the BasicWip class. Thus the source code for missing methods should be
    // provided.
    $validation_result = $validator->validate($state_machine, $state_table, new TranscriptTestWip());
    $this->assertFalse($validation_result->hasFailures());

    $report_generator = new ReportGenerator($validation_result);
    $report = $report_generator->generate();
    $this->assertTrue($this->resultContainsSection($report, ReportGenerator::ASTERISK_VALUES));
    $this->assertTrue($this->resultContainsSection($report, ReportGenerator::SUCCESS));
  }

  /**
   * Missing summary.
   */
  private function verifyHeader($section_name, $section_separator) {
    $result = FALSE;
    $char = ReportGenerator::SEPARATOR;
    $matches = array();
    if (1 === preg_match("/^([$char]+) $section_name ([$char]+)$/m", $section_separator, $matches)) {
      if (ReportGenerator::SEPARATOR_LENGTH === strlen($matches[0])) {
        $difference = abs(strlen($matches[1]) - strlen($matches[2]));
        if ($difference === 0 || $difference === 1) {
          $result = TRUE;
        }
      }
    }
    return $result;
  }

  /**
   * Missing summary.
   */
  private function resultContainsSection($report, $section_name) {
    $section_header = trim(ReportGenerator::generateSectionSeparator($section_name));
    return (1 === preg_match("/^$section_header$/m", $report));
  }

}
