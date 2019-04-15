<?php

namespace Acquia\Wip\Test\PrivateStable\Iterator\BasicIterator;

use Acquia\Wip\Iterators\BasicIterator\StateTableRecording;
use Acquia\Wip\Test\Utility\DataProviderTrait;

/**
 * Tests the StateTableRecording class.
 */
class StateTableRecordingTest extends \PHPUnit_Framework_TestCase {

  use DataProviderTrait;

  /**
   * Tests the constructor.
   *
   * @group Transcript
   */
  public function testConstructor() {
    $t = new StateTableRecording();
    $this->assertEmpty($t->getTranscript());
    $this->assertEmpty($t->getSimulationScript());
  }

  /**
   * Tests adding transitions.
   *
   * @group Transcript
   */
  public function testAddTransition() {
    $t = new StateTableRecording();
    $transition_method = 'transition';
    $transition_value = 'success';
    $t->addTransition($transition_method, $transition_value);

    // This is a transition with no state.  The transcript cannot be generated.
    $expected_result = '';
    $this->assertEquals($expected_result, $t->getTranscript());
    $this->assertEquals('', $t->getSimulationScript());
  }

  /**
   * Tests adding states.
   *
   * @group Transcript
   */
  public function testAddState() {
    $t = new StateTableRecording();
    $state = 'start';
    $t->addState($state);

    // This is a single state with no transitions.  The transcript cannot be
    // generated.
    $expected_transcript = '';
    $expected_test_script = '';
    $this->assertEquals($expected_transcript, $t->getTranscript());
    $this->assertEquals($expected_test_script, $t->getSimulationScript());
  }

  /**
   * Tests adding states and transitions.
   *
   * @group Transcript
   */
  public function testStatesAndTransitions() {
    $expected_transcript = "start => '' => finish";
    $expected_test_script = <<<EOT
start {
  ''
}

EOT;


    $t = new StateTableRecording();
    $t->addState('start');
    $t->addTransition('emptyTransition', '');
    $t->addState('finish');
    $this->assertEquals($expected_transcript, $t->getTranscript());
    $this->assertEquals($expected_test_script, $t->getSimulationScript());
  }

  /**
   * Tests getting scripts interleaved with transitions.
   *
   * @group Transcript
   */
  public function testGetTestScriptInterleavedTransitions() {
    $expected_transcript = <<<EOT
start => '' => state2
state2 => '' => state3
state3 => 'success' => start
start => 'fail' => failure
failure => '' => finish
EOT;
    $expected_test_script = <<<EOT
start {
  ''
  'fail'
}

state2 {
  ''
}

state3 {
  'success'
}

failure {
  ''
}

EOT;

    $t = new StateTableRecording();
    $t->addState('start');
    $t->addTransition('emptyTransition', '');
    $t->addState('state2');
    $t->addTransition('emptyTransition', '');
    $t->addState('state3');
    $t->addTransition('successTransition', 'success');
    $t->addState('start');
    $t->addTransition('failureTransition', 'fail');
    $t->addState('failure');
    $t->addTransition('emptyTransition', '');
    $t->addState('finish');
    $this->assertEquals($expected_transcript, $t->getTranscript());
    $this->assertEquals($expected_test_script, $t->getSimulationScript());
  }

  /**
   * Tests setting add time.
   *
   * @group Transcript
   */
  public function testAddTime() {
    $time = time();
    $t = new StateTableRecording();
    $t->setAddTime($time);
    $this->assertEquals($time, $t->getAddTime());
  }

  /**
   * Tests getting add time before initialization.
   *
   * @group Transcript
   */
  public function testGetAddTimeNotInitialized() {
    $t = new StateTableRecording();
    $this->assertNull($t->getAddTime());
  }

  /**
   * Tests setting invalid add time.
   *
   * @expectedException \InvalidArgumentException
   *
   * @dataProvider nonPositiveIntegerDataProvider
   *
   * @group Transcript
   */
  public function testSetInvalidAddTime($value) {
    $t = new StateTableRecording();
    $t->setAddTime($value);
  }

  /**
   * Tests setting start time.
   *
   * @group Transcript
   */
  public function testStartTime() {
    $time = time();
    $t = new StateTableRecording();
    $t->setStartTime($time);
    $this->assertEquals($time, $t->getStartTime());
  }

  /**
   * Tests getting start time before initialization.
   *
   * @group Transcript
   */
  public function testGetStartTimeNotInitialized() {
    $t = new StateTableRecording();
    $this->assertNull($t->getStartTime());
  }

  /**
   * Tests setting invalid start time.
   *
   * @expectedException \InvalidArgumentException
   *
   * @dataProvider nonPositiveIntegerDataProvider
   *
   * @group Transcript
   */
  public function testSetInvalidStartTime($value) {
    $t = new StateTableRecording();
    $t->setStartTime($value);
  }

  /**
   * Tests setting the end time.
   *
   * @group Transcript
   */
  public function testEndTime() {
    $time = time();
    $t = new StateTableRecording();
    $t->setEndTime($time);
    $this->assertEquals($time, $t->getEndTime());
  }

  /**
   * Tests getting the end time before intialization.
   *
   * @group Transcript
   */
  public function testGetEndTimeNotInitialized() {
    $t = new StateTableRecording();
    $this->assertNull($t->getEndTime());
  }

  /**
   * Tests setting invalid end time.
   *
   * @expectedException \InvalidArgumentException
   *
   * @dataProvider nonPositiveIntegerDataProvider
   *
   * @group Transcript
   */
  public function testSetInvalidEndTime($value) {
    $t = new StateTableRecording();
    $t->setEndTime($value);
  }

  /**
   * Tests ignoring timestamps.
   *
   * @group Transcript
   */
  public function testTimestamps() {
    $expected_transcript = <<<EOT
00:00:15  start => '' => state2
00:00:15  state2 => '' => state3
00:00:15  state3 => 'success' => start
00:00:15  start => 'fail' => failure
00:00:15  failure => '' => finish
EOT;
    $expected_test_script = <<<EOT
start {
  ''
  'fail'
}

state2 {
  ''
}

state3 {
  'success'
}

failure {
  ''
}

EOT;

    $t = new StateTableRecording();
    $t->setStartTime(time() - 15);
    $t->addState('start');
    $t->addTransition('emptyTransition', '');
    $t->addState('state2');
    $t->addTransition('emptyTransition', '');
    $t->addState('state3');
    $t->addTransition('successTransition', 'success');
    $t->addState('start');
    $t->addTransition('failureTransition', 'fail');
    $t->addState('failure');
    $t->addTransition('emptyTransition', '');
    $t->addState('finish');

    $transcript = $t->getTranscript();
    $transcript_minus_first_line = preg_replace('/^.+\n/', '', $transcript);
    $this->assertEquals($expected_transcript, $transcript_minus_first_line);
    $this->assertEquals($expected_test_script, $t->getSimulationScript());
  }

  /**
   * Tests timestamps greater than 100 hours.
   *
   * @group Transcript
   */
  public function testTimestampsGreaterThan100Hours() {
    $expected_transcript = <<<EOT
100:01:01  start => '' => state2
100:01:01  state2 => '' => state3
100:01:01  state3 => 'success' => start
100:01:01  start => 'fail' => failure
100:01:01  failure => '' => finish
EOT;
    $expected_test_script = <<<EOT
start {
  ''
  'fail'
}

state2 {
  ''
}

state3 {
  'success'
}

failure {
  ''
}

EOT;

    $t = new StateTableRecording();
    $t->setStartTime(time() - (100 * 60 * 60) - 61);
    $t->addState('start');
    $t->addTransition('emptyTransition', '');
    $t->addState('state2');
    $t->addTransition('emptyTransition', '');
    $t->addState('state3');
    $t->addTransition('successTransition', 'success');
    $t->addState('start');
    $t->addTransition('failureTransition', 'fail');
    $t->addState('failure');
    $t->addTransition('emptyTransition', '');
    $t->addState('finish');

    $transcript = $t->getTranscript();
    $transcript_minus_first_line = preg_replace('/^.+\n/', '', $transcript);
    $this->assertEquals($expected_transcript, $transcript_minus_first_line);
    $this->assertEquals($expected_test_script, $t->getSimulationScript());
  }

  /**
   * Tests getting a diff.
   *
   * @group Transcript
   */
  public function testDiff() {
    $expected_transcript = <<<EOT
start => '' => state2
state2 => '' => state3
state3 => 'success' => start
start => 'fail' => failure
failure => '' => finish
EOT;

    $t = new StateTableRecording();
    $t->setStartTime(time() - 60);
    $t->setAddTime(time() - 61);
    $t->setEndTime(time() - 15);
    $t->addState('start');
    $t->addTransition('emptyTransition', '');
    $t->addState('state2');
    $t->addTransition('emptyTransition', '');
    $t->addState('state3');
    $t->addTransition('successTransition', 'success');
    $t->addState('start');
    $t->addTransition('failureTransition', 'fail');
    $t->addState('failure');
    $t->addTransition('emptyTransition', '');
    $t->addState('finish');
    $transcript1 = $t->getTranscript();

    $t = new StateTableRecording();
    $t->setStartTime(time() - 45);
    $t->addState('start');
    $t->addTransition('emptyTransition', '');
    $t->addState('state2');
    $t->addTransition('emptyTransition', '');
    $t->addState('state3');
    $t->addTransition('successTransition', 'success');
    $t->addState('start');
    $t->addTransition('failureTransition', 'fail');
    $t->addState('failure');
    $t->addTransition('emptyTransition', '');
    $t->addState('finish');
    $transcript2 = $t->getTranscript();

    // Note: The times in the two transcripts will differ.
    $this->assertNotEquals($transcript1, $transcript2);

    $this->assertEmpty($t->diff($transcript2));
    $this->assertEquals('', $t->diff($transcript1));

    $this->assertEquals($expected_transcript, $t->removeTimeData($t->getTranscript()));
    $this->assertEquals($expected_transcript, $t->removeTimeData($expected_transcript));
  }

}
