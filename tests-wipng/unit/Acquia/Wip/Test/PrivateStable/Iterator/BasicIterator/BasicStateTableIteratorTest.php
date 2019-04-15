<?php

namespace Acquia\Wip\Test\PrivateStable\Iterator\BasicIterator;

use Acquia\Wip\AdjustableTimer;
use Acquia\Wip\Environment;
use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\Implementation\SshApi;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\IteratorResultInterface;
use Acquia\Wip\IteratorStatus;
use Acquia\Wip\Iterators\BasicIterator\SimulationScriptInterpreter;
use Acquia\Wip\Iterators\BasicIterator\StateTableIterator;
use Acquia\Wip\Iterators\BasicIterator\StateTableRecording;
use Acquia\Wip\Iterators\BasicIterator\WipContext;
use Acquia\Wip\RecordingInterface;
use Acquia\Wip\Signal\ContainerCompleteSignal;
use Acquia\Wip\Signal\SignalInterface;
use Acquia\Wip\Signal\SignalType;
use Acquia\Wip\Signal\SshCompleteSignal;
use Acquia\Wip\Signal\WipCompleteSignal;
use Acquia\Wip\Ssh\SshProcess;
use Acquia\Wip\Storage\SignalStoreInterface;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipInterface;
use Acquia\Wip\WipLogEntryInterface;

/**
 * Missing summary.
 */
class BasicStateTableIteratorTest extends \PHPUnit_Framework_TestCase {

  private $objectId = 3;

  /**
   * Missing summary.
   *
   * @var StateTableIterator
   */
  private $iterator = NULL;

  private $fullStateTable = <<<EOT
  # Test table
begin {
  * step1
}

step1:checkProcess {
  success step2
  fail start wait=30 max=3
  running step1 wait=30
}

step2 {
  * finish
}

terminate {
  * failure
}
EOT;

  /**
   * A mock SimulationScriptInterpreter object.
   *
   * @var SimulationScriptInterpreter
   */
  private $mockInterpreter = NULL;

  /**
   * Provides invalid arguments to the setSimulationMode method.
   */
  public function invalidSimulationTypeProvider() {
    if ($this->mockInterpreter == NULL) {
      $this->mockInterpreter = $this->getMockBuilder(
        'Acquia\Wip\Iterators\BasicIterator\SimulationScriptInterpreter'
      )->setConstructorArgs(array($this->fullStateTable))
        ->getMock();
    }

    return array(
      array(
        StateTableIterator::SIMULATION_RANDOM,
        $this->mockInterpreter,
      ),
      array(
        StateTableIterator::SIMULATION_SCRIPT,
        NULL,
      ),
      array(-5, NULL),
    );
  }

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    WipFactory::reset();
    $wip_obj = new BasicWip();
    $wip_obj->setId($this->objectId);
    $this->iterator = new StateTableIterator();
    $this->iterator->initialize($wip_obj);
    $this->iterator->compileStateTable();
    $signal_store = $this->iterator->getSignalStore();
    $signals = $signal_store->loadAll($this->objectId);
    foreach ($signals as $signal) {
      $signal_store->delete($signal);
    }

    $this->mockInterpreter = $this->getMockBuilder(
      'Acquia\Wip\Iterators\BasicIterator\SimulationScriptInterpreter'
    )->setConstructorArgs(array($this->fullStateTable))
      ->getMock();
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testGetWip() {
    $wip = $this->iterator->getWip();
    $this->assertTrue($wip instanceof WipInterface);
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testStartState() {
    $wip_obj = new BasicWip();
    $wip_obj->setStateTable($this->fullStateTable);
    $this->iterator = new StateTableIterator();
    $this->iterator->initialize($wip_obj);
    $this->iterator->compileStateTable();
    $state = $this->iterator->getStartState();
    $this->assertEquals('begin', $state);
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testCurrentStateIsEmpty() {
    // Note that the iterator has not executed any states yet, so current
    // state should be empty.
    $state = $this->iterator->getCurrentState();
    $this->assertEmpty($state);
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testMoveToInitialState() {
    $this->iterator->moveToNextState();
    $state = $this->iterator->getCurrentState();
    $this->assertEquals('start', $state);
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testMoveToNextState() {
    $this->assertNull($this->iterator->getCurrentState());

    $start_state = $this->iterator->getStartState();
    $this->iterator->moveToNextState();
    $this->assertEquals($start_state, $this->iterator->getCurrentState());

    $this->iterator->moveToNextState();
    $this->assertEquals('finish', $this->iterator->getCurrentState());
  }

  /**
   * Missing summary.
   *
   * @expectedException \Exception
   *
   * @group StateTable
   * @group Wip
   */
  public function testMovePastFinish() {
    $this->assertNull($this->iterator->getCurrentState());
    $index = 0;
    do {
      $this->iterator->moveToNextState();
      if ($index++ > 20) {
        // Never found the finish state.
        break;
      }
    } while ($this->iterator->getCurrentState() !== 'finish');

    // Now try one more...
    $this->iterator->moveToNextState();
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testSimpleStateTable() {
    $state_table = <<<EOT
start {
  * step1
}

step1 {
  * step2
}

step2 {
  * step3
}

step3 {
  * finish
}
EOT;

    $expected_result = <<<EOT
start => '' => step1
step1 => '' => step2
step2 => '' => step3
step3 => '' => finish
EOT;
    $wip_obj = new TranscriptTestWip();
    $wip_obj->setWipLog($this->iterator->getWipLog());
    $wip_obj->setStateTable($state_table);
    $this->iterator = new StateTableIterator();
    $this->iterator->initialize($wip_obj);
    $this->iterator->compileStateTable();
    while (TRUE) {
      $this->iterator->moveToNextState();
      if ('finish' === $this->iterator->getCurrentState()) {
        break;
      }
    }
    $recordings = $this->iterator->getRecordings();
    /** @var RecordingInterface $recording */
    $recording = reset($recordings);
    $this->assertEquals(trim($expected_result), trim($recording->getTranscript()));
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testStateTableWithTransitions() {
    $state_table = <<<EOT
start {
  * step1
}

step1:transition1 {
  running step1
  success step2
  fail start
}

step2:transition2 {
  success step3
  running step2
}

step3 {
  * finish
}

EOT;

    $expected_result = <<<EOT
start => '' => step1
step1 => 'running' => step1
step1 => 'running' => step1
step1 => 'running' => step1
step1 => 'fail' => start
start => '' => step1
step1 => 'running' => step1
step1 => 'success' => step2
step2 => 'running' => step2
step2 => 'running' => step2
step2 => 'success' => step3
step3 => '' => finish
EOT;

    $wip_obj = new TranscriptTestWip();
    $wip_obj->setWipLog($this->iterator->getWipLog());
    $wip_obj->setStateTable($state_table);
    $wip_obj->setTransitionValues(
      'transition1',
      array(
        'running',
        'running',
        'running',
        'fail',
        'running',
        'success',
      )
    );
    $wip_obj->setTransitionValues(
      'transition2',
      array(
        'running',
        'running',
        'success',
      )
    );
    $this->iterator = new StateTableIterator();
    $this->iterator->initialize($wip_obj);
    $this->iterator->compileStateTable();
    while (TRUE) {
      $this->iterator->moveToNextState();
      if ('finish' === $this->iterator->getCurrentState()) {
        break;
      }
    }
    $recordings = $this->iterator->getRecordings();
    /** @var RecordingInterface $recording */
    $recording = reset($recordings);
    $this->assertEquals(trim($expected_result), trim($recording->getTranscript()));
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testStateTableWithTransitionsAndExec() {
    $state_table = <<<EOT
start {
  * step1
}

step1:transition1 {
  running step1 exec=false
  success step2
  fail start
}

step2:transition2 {
  success step3
  running step2
}

step3 {
  * finish
}

EOT;

    $expected_result = <<<EOT
start => '' => step1
step1 => 'running' => [step1]
step1 => 'running' => [step1]
step1 => 'running' => [step1]
step1 => 'fail' => start
start => '' => step1
step1 => 'running' => [step1]
step1 => 'success' => step2
step2 => 'running' => step2
step2 => 'running' => step2
step2 => 'success' => step3
step3 => '' => finish
EOT;

    $wip_obj = new TranscriptTestWip();
    $wip_obj->setWipLog($this->iterator->getWipLog());
    $wip_obj->setStateTable($state_table);
    $wip_obj->setTransitionValues(
      'transition1',
      array(
        'running',
        'running',
        'running',
        'fail',
        'running',
        'success',
      )
    );
    $wip_obj->setTransitionValues(
      'transition2',
      array(
        'running',
        'running',
        'success',
      )
    );
    $this->iterator = new StateTableIterator();
    $this->iterator->initialize($wip_obj);
    $this->iterator->compileStateTable();
    while (TRUE) {
      $this->iterator->moveToNextState();
      if ('finish' === $this->iterator->getCurrentState()) {
        break;
      }
    }
    $recordings = $this->iterator->getRecordings();
    /** @var RecordingInterface $recording */
    $recording = reset($recordings);
    $this->assertEquals(trim($expected_result), trim($recording->getTranscript()));
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testStateTableWithTransitionsAndExecTrue() {
    $state_table = <<<EOT
start {
  * step1
}

step1:transition1 {
  running step1 exec=true
  success step2 exec=false
  fail start
}

step2:transition2 {
  success step3
  running step2
}

step3 {
  * finish
}

EOT;

    $expected_result = <<<EOT
start => '' => step1
step1 => 'running' => step1
step1 => 'success' => [step2]
step2 => 'running' => step2
step2 => 'running' => step2
step2 => 'success' => step3
step3 => '' => finish
EOT;

    $wip_obj = new TranscriptTestWip();
    $wip_obj->setWipLog($this->iterator->getWipLog());
    $wip_obj->setStateTable($state_table);
    $wip_obj->setTransitionValues(
      'transition1',
      array(
        'running',
        'success',
      )
    );
    $wip_obj->setTransitionValues(
      'transition2',
      array(
        'running',
        'running',
        'success',
      )
    );
    $this->iterator = new StateTableIterator();
    $this->iterator->initialize($wip_obj);
    $this->iterator->compileStateTable();
    while (TRUE) {
      $this->iterator->moveToNextState();
      if ('finish' === $this->iterator->getCurrentState()) {
        break;
      }
    }
    $recordings = $this->iterator->getRecordings();
    /** @var RecordingInterface $recording */
    $recording = reset($recordings);
    $this->assertEquals(trim($expected_result), trim($recording->getTranscript()));
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testStateTableWithMaxTransitions() {
    $state_table = <<<EOT
start {
  * step1
}

step1:transition1 {
  running step1
  success finish
  fail start max=3
}

failure {
  * finish
}
EOT;

    $expected_result = <<<EOT
start => '' => step1
step1 => 'running' => step1
step1 => 'running' => step1
step1 => 'running' => step1
step1 => 'fail' => start
start => '' => step1
step1 => 'running' => step1
step1 => 'fail' => start
start => '' => step1
step1 => 'fail' => start
start => '' => step1
step1 => 'fail' => failure
failure => '' => finish
EOT;

    $wip_obj = new TranscriptTestWip();
    $wip_obj->setStateTable($state_table);
    $wip_obj->setTransitionValues(
      'transition1',
      array(
        'running',
        'running',
        'running',
        'fail',
        'running',
        'fail',
        'fail',
        'fail',
      )
    );
    $this->iterator = new StateTableIterator();
    $this->iterator->initialize($wip_obj);
    $this->iterator->compileStateTable();
    while (TRUE) {
      $this->iterator->moveToNextState();
      if ('finish' === $this->iterator->getCurrentState()) {
        break;
      }
    }
    $recordings = $this->iterator->getRecordings();
    /** @var RecordingInterface $recording */
    $recording = reset($recordings);
    $this->assertEquals(trim($expected_result), trim($recording->getTranscript()));
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testStateTableWithMaxTransitionsMaxFailureTransition() {
    $state_table = <<<EOT
start {
  * step1
}

step1:transition1 {
  running step1
  success finish
  fail start max=3
  ! step1 max=2
}

failure {
  * finish
}
EOT;

    $expected_result = <<<EOT
start => '' => step1
step1 => 'fail' => start
start => '' => step1
step1 => 'running' => step1
step1 => 'fail' => start
start => '' => step1
step1 => 'fail' => start
start => '' => step1
step1 => 'fail' => step1
step1 => 'fail' => step1
step1 => 'fail' => failure
failure => '' => finish
EOT;

    $wip_obj = new TranscriptTestWip();
    $wip_obj->setStateTable($state_table);
    $wip_obj->setTransitionValues(
      'transition1',
      array(
        'fail',
        'running',
        'fail',
        'fail',
        'fail',
        'fail',
        'fail',
      )
    );
    $this->iterator = new StateTableIterator();
    $this->iterator->initialize($wip_obj);
    $this->iterator->compileStateTable();
    do {
      $result = $this->iterator->moveToNextState();
    } while (!$result->isComplete());
    $recordings = $this->iterator->getRecordings();
    /** @var RecordingInterface $recording */
    $recording = reset($recordings);
    $this->assertEquals(trim($expected_result), trim($recording->getTranscript()));
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   *
   * @expectedException \Exception
   */
  public function testStateTableMissingTransition() {
    $state_table = <<<EOT
start {
  * transition1
}

step1:transition1 {
  a step1
}

failure {
  * finish
}
EOT;

    $wip_obj = new TranscriptTestWip();
    $wip_obj->setStateTable($state_table);
    $wip_obj->setTransitionValues('transition1', array('a', 'b'));
    $this->iterator = new StateTableIterator();
    $this->iterator->initialize($wip_obj);
    $this->iterator->compileStateTable();
    while (TRUE) {
      $this->iterator->moveToNextState();
      if ('finish' === $this->iterator->getCurrentState()) {
        break;
      }
    }
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testMoveToNextStateResult() {
    $state_table = <<<EOT
start {
  * step1 wait=10
}

step1 {
  * finish
}

EOT;

    $wip_obj = new TranscriptTestWip();
    $wip_obj->setStateTable($state_table);
    $this->iterator = new StateTableIterator();
    $this->iterator->initialize($wip_obj);
    $this->iterator->compileStateTable();

    $start_result = $this->iterator->moveToNextState();
    $this->assertFalse(($start_result->isComplete()));
    $this->assertEquals(0, $start_result->getWaitTime());

    $step1_result = $this->iterator->moveToNextState();
    $this->assertFalse(($step1_result->isComplete()));
    $this->assertEquals(10, $step1_result->getWaitTime());

    $finish_result = $this->iterator->moveToNextState();
    $this->assertTrue($finish_result->isComplete());
    $this->assertEquals(0, $finish_result->getWaitTime());
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testClearTransitionCount() {
    $state_table = <<<EOT
start {
  * step1
}

step1 {
  * step2 max=2
}

step2:transition2 {
  fail step2 max=3
  success finish
  ! step1
}

failure {
  * finish
}
EOT;

    $expected_result = <<<EOT
start => '' => step1
step1 => '' => step2
step2 => 'fail' => step2
step2 => 'fail' => step2
step2 => 'fail' => step2
step2 => 'fail' => step1
step1 => '' => step2
step2 => 'fail' => step2
step2 => 'fail' => step2
step2 => 'fail' => step2
step2 => 'success' => finish
EOT;

    $wip_obj = new TranscriptTestWip();
    $wip_obj->setStateTable($state_table);
    $wip_obj->setTransitionValues(
      'transition2',
      array(
        'fail',
        'fail',
        'fail',
        'fail',
        'fail',
        'fail',
        'fail',
        'success',
      )
    );
    $this->iterator = new StateTableIterator();
    $this->iterator->initialize($wip_obj);
    $this->iterator->compileStateTable();
    do {
      $iterator_result = $this->iterator->moveToNextState();
      if ($this->iterator->getCurrentState() === 'step1') {
        $context = $this->iterator->getWipContext('start');
        if ($context instanceof WipContext) {
          $context->clearTransitionCount('step2', 'fail');
        }
      }
      if ($this->iterator->getCurrentState() === 'failure') {
        // It should never get here.
        throw new \RuntimeException('Failed to reset transition ["step2", "fail"]');
      }
    } while (!$iterator_result->isComplete());
    $recordings = $this->iterator->getRecordings();
    /** @var RecordingInterface $recording */
    $recording = reset($recordings);
    $this->assertEquals(trim($expected_result), trim($recording->getTranscript()));
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testSetExitCode() {
    $fail_state_table = <<<EOT
start {
  * failure
}

failure {
  * finish
}
EOT;

    $wip_obj = new TranscriptTestWip();
    $wip_obj->setStateTable($fail_state_table);
    $this->iterator = new StateTableIterator();
    $this->iterator->initialize($wip_obj);
    $this->iterator->compileStateTable();
    /** @var IteratorResultInterface $iterator_result */
    $iterator_result = NULL;
    do {
      $iterator_result = $this->iterator->moveToNextState();
    } while (!$iterator_result->isComplete());
    $this->assertEquals(IteratorStatus::ERROR_SYSTEM, $iterator_result->getStatus()->getValue());
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetInvalidExitCode() {
    $iterator = new StateTableIterator();
    $iterator->setExitCode(15);
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testSetExitMessage() {
    $state_table = <<<EOT
start {
  * finish
}
EOT;

    $exit_message = 'Finished successfully';
    $wip_obj = new TranscriptTestWip();
    $wip_obj->setStateTable($state_table);
    $this->iterator = new StateTableIterator();
    $this->iterator->initialize($wip_obj);
    $this->iterator->compileStateTable();
    $context = $this->iterator->getWipContext('finish');
    $context->setExitMessage($exit_message);

    /** @var IteratorResultInterface $iterator_result */
    $iterator_result = NULL;
    do {
      $iterator_result = $this->iterator->moveToNextState();
    } while (!$iterator_result->isComplete());
    $this->assertEquals($exit_message, $iterator_result->getMessage());
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidExitMessage() {
    $iterator = new StateTableIterator();
    $iterator->setExitMessage(15);
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testRestartClearsEverything() {
    $state_table = <<<EOT
start {
  * finish
}
EOT;

    $exit_message = 'Finished successfully';
    $exit_code = IteratorStatus::WARNING;
    $wip_obj = new TranscriptTestWip();
    $wip_obj->setWipLog($this->iterator->getWipLog());
    $wip_obj->setStateTable($state_table);
    $this->iterator = new StateTableIterator();
    $this->iterator->setWipLog(new WipLog());
    $log_store = $this->iterator->getWipLog()->getStore();
    $log_store->delete();

    $this->iterator->initialize($wip_obj);
    $this->iterator->compileStateTable();
    $context = $this->iterator->getWipContext('finish');
    $context->setExitMessage($exit_message);
    $context->setExitCode($exit_code);
    $context->key = 'value';

    /** @var IteratorResultInterface $iterator_result */
    $iterator_result = NULL;
    do {
      $iterator_result = $this->iterator->moveToNextState();
    } while (!$iterator_result->isComplete());
    $this->assertEquals($exit_message, $iterator_result->getMessage());
    $this->assertEquals($exit_code, $iterator_result->getStatus()->getValue());
    $messages = $this->logEntriesToString($log_store->load());
    $this->assertContains('[start] ->{*}-> [finish]', $messages);
    $this->iterator->restart();

    $this->assertNull($this->iterator->getCurrentState());
    $iterator_result = NULL;
    do {
      $iterator_result = $this->iterator->moveToNextState();
    } while (!$iterator_result->isComplete());
    $this->assertNotEquals($exit_message, $iterator_result->getMessage());
    $this->assertNotEquals($exit_code, $iterator_result->getStatus()->getValue());

    $context = $this->iterator->getWipContext('finish');
    $this->assertFalse(isset($context->key));
    $this->assertEquals('', $this->iterator->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testValidateWithGoodStateTable() {
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
    $wip_obj = new TranscriptTestWip();
    $wip_obj->setWipLog($this->iterator->getWipLog());
    $wip_obj->setStateTable($state_table);
    $this->iterator = new StateTableIterator();
    $this->iterator->initialize($wip_obj);
    $this->iterator->compileStateTable();
    $this->assertTrue($this->iterator->validate());
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testValidateWithBadStateTable() {
    $state_table = <<<EOT
start {
  * missingStateMethod
}

missingStateMethod {
  * finish
}
EOT;
    $wip_obj = new TranscriptTestWip();
    $wip_obj->setWipLog($this->iterator->getWipLog());
    $wip_obj->setStateTable($state_table);
    $this->iterator = new StateTableIterator();
    $this->iterator->initialize($wip_obj);
    $this->iterator->compileStateTable();
    $this->assertFalse($this->iterator->validate());
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testFailureToFinishTransition() {
    $state_table = <<<EOT
start {
  * failure
}

failure:transition1 {
  * finish
  wait failure max=3
  ! finish
}
EOT;
    $wip_obj = new TranscriptTestWip();
    $wip_obj->setWipLog($this->iterator->getWipLog());
    $wip_obj->setStateTable($state_table);
    $wip_obj->setTransitionValues(
      'transition1',
      array(
        'wait',
        'wait',
        'wait',
        'wait',
      )
    );
    $this->iterator = new StateTableIterator();
    $log_store = $this->iterator->getWipLog()->getStore();
    $log_store->delete();
    $this->iterator->initialize($wip_obj);
    $this->iterator->compileStateTable();
    $transition_count = 0;
    while (TRUE) {
      $this->iterator->moveToNextState();
      ++$transition_count;
      if ('finish' === $this->iterator->getCurrentState()) {
        break;
      }
    }
    $this->assertEquals(6, $transition_count);
    $this->assertContains('[failure] ->{!}-> [finish]', $this->logEntriesToString($log_store->load()));
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testFailureToFinishTransitionOrderTwo() {
    $state_table = <<<EOT
start {
  * failure
}

failure:transition1 {
  ! finish
  * finish
  wait failure max=3
}
EOT;
    $wip_obj = new TranscriptTestWip();
    $wip_obj->setWipLog($this->iterator->getWipLog());
    $wip_obj->setStateTable($state_table);
    $wip_obj->setTransitionValues(
      'transition1',
      array(
        'wait',
        'wait',
        'wait',
        'wait',
      )
    );
    $this->iterator = new StateTableIterator();
    $log_store = $this->iterator->getWipLog()->getStore();
    $log_store->delete();
    $this->iterator->initialize($wip_obj);
    $this->iterator->compileStateTable();
    $transition_count = 0;
    while (TRUE) {
      $this->iterator->moveToNextState();
      ++$transition_count;
      if ('finish' === $this->iterator->getCurrentState()) {
        break;
      }
    }
    $this->assertEquals(6, $transition_count);
    $this->assertContains('[failure] ->{!}-> [finish]', $this->logEntriesToString($log_store->load()));
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testFailureToFinishTransitionOrderThree() {
    $state_table = <<<EOT
start {
  * failure
}

failure:transition1 {
  * finish
  ! finish
  wait failure max=3
}
EOT;
    $wip_obj = new TranscriptTestWip();
    $wip_obj->setWipLog($this->iterator->getWipLog());
    $wip_obj->setStateTable($state_table);
    $wip_obj->setTransitionValues(
      'transition1',
      array(
        'wait',
        'wait',
        'wait',
        'wait',
      )
    );
    $this->iterator = new StateTableIterator();
    $log_store = $this->iterator->getWipLog()->getStore();
    $log_store->delete();
    $this->iterator->initialize($wip_obj);
    $this->iterator->compileStateTable();
    $transition_count = 0;
    while (TRUE) {
      $this->iterator->moveToNextState();
      ++$transition_count;
      if ('finish' === $this->iterator->getCurrentState()) {
        break;
      }
    }
    $this->assertEquals(6, $transition_count);
    $this->assertContains('[failure] ->{!}-> [finish]', $this->logEntriesToString($log_store->load()));
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testFailureToFinishTransitionOrderWithCleanupState() {
    $state_table = <<<EOT
start {
  * failure
}

failure:transition1 {
  * cleanup
  ! cleanup
  wait failure max=3
}

cleanup:transition2 {
  * finish
  wait cleanup max=3
  ! finish
}
EOT;
    $wip_obj = new TranscriptTestWip();
    $wip_obj->setWipLog($this->iterator->getWipLog());
    $wip_obj->setStateTable($state_table);
    $wip_obj->setTransitionValues(
      'transition1',
      array(
        'wait',
        'wait',
        'wait',
        'wait',
      )
    );
    $wip_obj->setTransitionValues(
      'transition2',
      array(
        'wait',
        'wait',
        'wait',
        'wait',
      )
    );
    $this->iterator = new StateTableIterator();
    $log_store = $this->iterator->getWipLog()->getStore();
    $log_store->delete();
    $this->iterator->initialize($wip_obj);
    $this->iterator->compileStateTable();
    $transition_count = 0;
    while (TRUE) {
      $this->iterator->moveToNextState();
      ++$transition_count;
      if ('finish' === $this->iterator->getCurrentState()) {
        break;
      }
    }
    $this->assertEquals(10, $transition_count);
    $this->assertContains('[failure] ->{!}-> [cleanup]', $this->logEntriesToString($log_store->load()));
    $this->assertContains('[cleanup] ->{!}-> [finish]', $this->logEntriesToString($log_store->load()));
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testStateTableLoggingWithAsteriskTransitions() {
    $state_table = <<<EOT
start {
  * step1
}

step1:transition1 {
  running step1
  * step2
  fail start
}

step2:transition2 {
  success step3
  running step2
}

step3 {
  * finish
}

EOT;

    $expected_result = <<<EOT
start => '' => step1
step1 => 'running' => step1
step1 => 'running' => step1
step1 => 'running' => step1
step1 => 'fail' => start
start => '' => step1
step1 => 'running' => step1
step1 => 'success' => step2
step2 => 'running' => step2
step2 => 'running' => step2
step2 => 'success' => step3
step3 => '' => finish
EOT;

    $wip_obj = new TranscriptTestWip();
    $wip_obj->setWipLog($this->iterator->getWipLog());
    $wip_obj->setStateTable($state_table);
    $wip_obj->setTransitionValues(
      'transition1',
      array(
        'running',
        'running',
        'running',
        'fail',
        'running',
        'success',
      )
    );
    $wip_obj->setTransitionValues(
      'transition2',
      array(
        'running',
        'running',
        'success',
      )
    );
    $this->iterator = new StateTableIterator();
    $log_store = $this->iterator->getWipLog()->getStore();
    $log_store->delete();
    $this->iterator->initialize($wip_obj);
    $this->iterator->compileStateTable();
    while (TRUE) {
      $this->iterator->moveToNextState();
      if ('finish' === $this->iterator->getCurrentState()) {
        break;
      }
    }
    $this->assertContains('[step1] ->{*[success]}-> [step2]', $this->logEntriesToString($log_store->load()));
    $recordings = $this->iterator->getRecordings();
    /** @var RecordingInterface $recording */
    $recording = reset($recordings);
    $this->assertEquals(trim($expected_result), trim($recording->getTranscript()));
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testIteratorIsSerializable() {
    $this->iterator->setWipLog(new WipLog());
    $data = serialize($this->iterator);
    $iterator = unserialize($data);
    $this->assertInstanceOf('Acquia\Wip\StateTableIteratorInterface', $iterator);
  }

  /**
   * Tests all fields are serialized except the set that are not serializable.
   *
   * NOTE: Put "@notSerializable in the documentation block of properties that
   * are not serializable.
   *
   * @group StateTable
   * @group Wip
   */
  public function testVerifySerializableFields() {
    $this->iterator->setWipLog(new WipLog());
    $non_serializable_fields = array();
    $reflection_class = new \ReflectionClass(get_class($this->iterator));
    $all_properties = $reflection_class->getProperties();
    foreach ($all_properties as $property) {
      if (strpos($property->getDocComment(), '@notSerializable') !== FALSE) {
        $non_serializable_fields[] = $property->getName();
        $property->setAccessible(TRUE);
        $this->assertNotEmpty(
          $property->getValue($this->iterator),
          sprintf('Expected object property %s to not be empty.', $property->getName())
        );
      }
    }

    // Serialize, deserialize, then verify the non-serializable fields are empty.
    $new_obj = unserialize(serialize($this->iterator));
    foreach ($all_properties as $property) {
      if (in_array($property->getName(), $non_serializable_fields)) {
        $property->setAccessible(TRUE);
        $this->assertEmpty($property->getValue($new_obj));
      }
    }
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testSetId() {
    $id = 15;
    $this->iterator->setId($id);
    $this->assertEquals($id, $this->iterator->getId());
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testGetIterator() {
    $wip = $this->iterator->getWip();
    $iterator = $wip->getIterator();
    $this->assertEquals($this->iterator, $iterator);
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testGetSignalsEmpty() {
    $result = $this->iterator->getSignals();
    $this->assertTrue(is_array($result));
    $this->assertEmpty($result);
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testSendSshCompleteSignal() {
    // Delete all signals in preparation for this test.
    $signal_store = WipFactory::getObject('acquia.wip.storage.signal');
    foreach ($signal_store->loadAll($this->iterator->getId()) as $signal) {
      $signal_store->delete($signal);
    }
    $signals = $this->iterator->getSignals();
    $this->assertEmpty($signals);

    $this->assertEquals($this->objectId, $this->iterator->getId());

    $signal = new SshCompleteSignal();
    $signal->setObjectId($this->iterator->getId());
    $this->assertEquals($this->objectId, $signal->getObjectId());
    $this->assertEquals($this->objectId, $this->iterator->getId());

    $signal->setType(SignalType::COMPLETE);
    $signal_store->send($signal);
    $this->assertEquals($this->objectId, $signal->getObjectId());
    $this->assertEquals($this->objectId, $this->iterator->getId());

    $signals = $this->iterator->getSignals();
    $this->assertTrue(count($signals) === 1);
    /** @var SignalInterface $signal */
    $signal = $signals[0];
    $this->assertEquals($this->objectId, $signal->getObjectId());
    $this->assertEquals($this->objectId, $this->iterator->getId());

    $this->assertEquals($this->objectId, $signal->getObjectId());
    $this->assertEquals(SignalType::COMPLETE, $signal->getType());
    $signal_store->delete($signal);
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testSendWipCompleteSignal() {
    if ($this->iterator instanceof StateTableIterator) {
      $signal_store = $this->iterator->getSignalStore();
    } else {
      $signal_store = WipFactory::getObject('acquia.wip.storage.signal');
    }
    foreach ($signal_store->loadAll($this->iterator->getId()) as $signal) {
      $signal_store->delete($signal);
    }
    $this->assertEmpty($signal_store->loadAll($this->iterator->getId()));

    $signal = new WipCompleteSignal();
    $signal->setId(24423424);
    $signal->setObjectId($this->iterator->getId());
    $signal->setType(SignalType::COMPLETE);
    $signal_store->send($signal);
    $signals = $this->iterator->getSignals();
    $this->assertTrue(count($signals) === 1);

    /** @var SignalInterface $signal */
    $signal = $signals[0];
    $this->assertEquals($this->objectId, $signal->getObjectId());
    $this->assertEquals(SignalType::COMPLETE, $signal->getType());
    $signal_store->delete($signal);
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testProcessSignalsEmpty() {
    $result = $this->iterator->processSignals(new WipContext());
    $this->assertEmpty(0, $result);
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testProcessSshCompleteSignal() {
    $context = new WipContext();
    $ssh_process = $this->createSshProcess();
    $ssh_api = new SshApi();
    $ssh_api->addSshProcess($ssh_process, $context);
    $this->assertEquals(1, count($ssh_api->getSshProcesses($context)));
    $this->assertEmpty($ssh_api->getSshResults($context));

    $signal = new SshCompleteSignal();
    $signal->setObjectId($this->iterator->getId());
    $signal->setType(SignalType::COMPLETE);
    $signal->setPid($ssh_process->getPid());
    $signal->setEndTime(time());
    $signal->setStartTime($ssh_process->getStartTime());
    if ($this->iterator instanceof StateTableIterator) {
      $signal_store = $this->iterator->getSignalStore();
    } else {
      $signal_store = WipFactory::getObject('acquia.wip.storage.signal');
    }

    $object = new \stdClass();
    $result = new \stdClass();
    $object->result = $result;
    $object->server = $ssh_process->getEnvironment()->getCurrentServer();
    $object->pid = $ssh_process->getPid();
    $object->startTime = $ssh_process->getStartTime();
    $result->endTime = time();
    $result->exitCode = 0;
    $result->stdout = 'ok';
    $result->stderr = 'no errors';
    $object->classId = '$acquia.wip.signal.ssh.complete';
    $signal->setData($object);
    $this->assertEmpty($signal->getId());
    $signal_store->send($signal);
    $this->assertNotEmpty($signal->getId());
    $this->iterator->processSignals($context);

    $this->assertEmpty($ssh_api->getSshProcesses($context));
    $this->assertEquals(1, count($ssh_api->getSshResults($context)));
    $signal_after_processing = $signal_store->load($signal->getId());
    $this->assertTrue($signal_after_processing->getConsumedTime() > 0);
    $signal_store->delete($signal);
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testProcessWipCompleteSignal() {
    $signal = new WipCompleteSignal();
    $signal->setId(24423424);
    $signal->setObjectId($this->iterator->getWip()->getId());
    $signal->setType(SignalType::COMPLETE);
    $signal_store = $this->iterator->getSignalStore();
    $signal_store->send($signal);
    $this->iterator->processSignals(new WipContext());
    $signal_store->delete($signal);
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testProcessContainerCompleteSignal() {
    $signal = new ContainerCompleteSignal();
    $signal->setId(24423424);
    $signal->setObjectId($this->iterator->getWip()->getId());
    $signal->setType(SignalType::COMPLETE);
    $signal_store = $this->iterator->getSignalStore();
    $signal_store->send($signal);
    $this->iterator->processSignals(new WipContext());
    $loaded_signal = $signal_store->load($signal->getId());
    $this->assertGreaterThan(1, $loaded_signal->getConsumedTime());
    $signal_store->delete($signal);
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testGetSignalStore() {
    $result = $this->iterator->getSignalStore();
    $this->assertInstanceof('Acquia\Wip\Storage\SignalStoreInterface', $result);
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testGetSignalStoreMissingConfig() {
    $result = WipFactory::getObject('acquia.wip.storage.signal');
    $this->assertNotEmpty($result);
    WipFactory::removeMapping('acquia.wip.storage.signal');
    $this->iterator->getSignalStore();
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testGetSignalFactory() {
    $result = $this->iterator->getSignalFactory();
    $this->assertInstanceof('Acquia\Wip\Signal\SignalFactoryInterface', $result);
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testGetSignalFactoryMissingConfig() {
    $result = WipFactory::getObject('acquia.wip.signal.signalfactory');
    $this->assertNotEmpty($result);
    WipFactory::removeMapping('acquia.wip.signal.signalfactory');
    $this->iterator->getSignalFactory();
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testConsumeSignal() {
    /** @var SignalStoreInterface $signal_store */
    $signal_store = WipFactory::getObject('acquia.wip.storage.signal');
    $signal = new SshCompleteSignal();
    $signal->setObjectId($this->iterator->getWip()->getId());
    $this->assertEmpty($signal->getId());
    $this->assertEmpty($signal->getConsumedTime());
    $signal_store->send($signal);
    $this->assertNotEmpty($signal->getId());
    $this->assertEmpty($signal->getConsumedTime());

    $loaded_signal = $signal_store->load($signal->getId());
    $this->assertNotEmpty($loaded_signal->getId());
    $this->assertEmpty($loaded_signal->getConsumedTime());

    $this->iterator->consumeSignal($signal);
    $consumed_signal = $signal_store->load($signal->getId());
    $this->assertNotEmpty($consumed_signal->getConsumedTime());
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   *
   * @expectedException \RuntimeException
   */
  public function testConsumeSignalNoSignalId() {
    $signal = new SshCompleteSignal();
    $this->iterator->consumeSignal($signal);
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   *
   * @expectedException \RuntimeException
   */
  public function testConsumeSignalWrongWipId() {
    $signal = new SshCompleteSignal();
    $signal->setId(24423424);
    $incorrect_wip_id = $this->iterator->getWip()->getId() + 1;
    $signal->setObjectId($incorrect_wip_id);
    $this->iterator->consumeSignal($signal);
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testDeleteSignal() {
    /** @var SignalStoreInterface $signal_store */
    $signal_store = WipFactory::getObject('acquia.wip.storage.signal');
    $signal = new SshCompleteSignal();
    $signal->setObjectId($this->iterator->getWip()->getId());
    $signal_store->send($signal);

    $loaded_signal = $signal_store->load($signal->getId());
    $this->assertNotEmpty($loaded_signal);
    $this->assertNotEmpty($loaded_signal->getId());
    $this->assertEmpty($loaded_signal->getConsumedTime());
    $this->assertEquals($this->iterator->getId(), $signal->getObjectId());

    $this->iterator->deleteSignal($signal);
    $deleted_signal = $signal_store->load($signal->getId());
    $this->assertEmpty($deleted_signal);
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   *
   * @expectedException \RuntimeException
   */
  public function testDeleteSignalNoSignalId() {
    $signal = new SshCompleteSignal();
    $this->iterator->deleteSignal($signal);
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   *
   * @expectedException \RuntimeException
   */
  public function testDeleteSignalWrongWipId() {
    $signal = new SshCompleteSignal();
    $signal->setId(24423424);
    $incorrect_wip_id = $this->iterator->getWip()->getId() + 1;
    $signal->setObjectId($incorrect_wip_id);
    $this->iterator->deleteSignal($signal);
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testGetTimer() {
    $timer = $this->iterator->getTimer();
    $this->assertNotEmpty($timer);
    $this->assertInstanceOf('Acquia\Wip\TimerInterface', $timer);
    $names = $timer->getTimerNames();
    $this->assertEmpty($names);
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testBlendTimerData() {
    $system_time = 15;
    $user_time = 4;
    $timer = new AdjustableTimer();
    $timer->start('system');
    $timer->adjustStart(-$system_time);
    $timer->stop();
    $timer->start('user');
    $timer->adjustStart(-$user_time);
    $timer->stop();
    $this->iterator->blendTimerData($timer);
    $iterator_timer = $this->iterator->getTimer();
    $this->assertEquals($system_time, intval($iterator_timer->getTime('system')));
    $this->assertEquals($user_time, intval($iterator_timer->getTime('user')));
    $this->assertEquals(array('system', 'user'), $iterator_timer->getTimerNames());
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testUserTimer() {
    $state_table = <<<EOT
start {
  * step1
}

step1:transition1 [user] {
  running step1
  * step2
  fail start
}

step2:transition2 [user] {
  success step3
  running step2
}

step3 {
  * finish
}

EOT;

    $expected_result = <<<EOT
start => '' => step1
step1 => 'running' => step1
step1 => 'running' => step1
step1 => 'running' => step1
step1 => 'fail' => start
start => '' => step1
step1 => 'running' => step1
step1 => 'success' => step2
step2 => 'running' => step2
step2 => 'running' => step2
step2 => 'success' => step3
step3 => '' => finish
EOT;

    $wip_obj = new TranscriptTestWip();
    $wip_obj->setWipLog($this->iterator->getWipLog());
    $wip_obj->setStateTable($state_table);
    $wip_obj->setTransitionValues(
      'transition1',
      array(
        'running',
        'running',
        'running',
        'fail',
        'running',
        'success',
      )
    );
    $wip_obj->setTransitionValues(
      'transition2',
      array(
        'running',
        'running',
        'success',
      )
    );
    $this->iterator = new StateTableIterator();
    $log_store = $this->iterator->getWipLog()->getStore();
    $log_store->delete();
    $this->iterator->initialize($wip_obj);
    $this->iterator->compileStateTable();
    while (TRUE) {
      $this->iterator->moveToNextState();
      if ('finish' === $this->iterator->getCurrentState()) {
        break;
      }
    }
    $this->assertContains('[step1] ->{*[success]}-> [step2]', $this->logEntriesToString($log_store->load()));
    $recordings = $this->iterator->getRecordings();
    /** @var RecordingInterface $recording */
    $recording = reset($recordings);
    $this->assertEquals(trim($expected_result), trim($recording->getTranscript()));
    $timer = $this->iterator->getTimer();
    $sla_start_delay = $timer->getTime('sla.startDelay');
    $this->assertGreaterThan(0, $sla_start_delay);
    $this->assertEquals(0, round($sla_start_delay));
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testNoneTimer() {
    $state_table = <<<EOT
start {
  * step1
}

step1:transition1 [none] {
  running step1
  * step2
  fail start
}

step2:transition2 [none] {
  success step3
  running step2
}

step3 {
  * finish
}

EOT;

    $expected_result = <<<EOT
start => '' => step1
step1 => 'running' => step1
step1 => 'running' => step1
step1 => 'running' => step1
step1 => 'fail' => start
start => '' => step1
step1 => 'running' => step1
step1 => 'success' => step2
step2 => 'running' => step2
step2 => 'running' => step2
step2 => 'success' => step3
step3 => '' => finish
EOT;

    $wip_obj = new TranscriptTestWip();
    $wip_obj->setWipLog($this->iterator->getWipLog());
    $wip_obj->setStateTable($state_table);
    $wip_obj->setTransitionValues(
      'transition1',
      array(
        'running',
        'running',
        'running',
        'fail',
        'running',
        'success',
      )
    );
    $wip_obj->setTransitionValues(
      'transition2',
      array(
        'running',
        'running',
        'success',
      )
    );
    $this->iterator = new StateTableIterator();
    $log_store = $this->iterator->getWipLog()->getStore();
    $log_store->delete();
    $this->iterator->initialize($wip_obj);
    $this->iterator->compileStateTable();
    while (TRUE) {
      $this->iterator->moveToNextState();
      if ('finish' === $this->iterator->getCurrentState()) {
        break;
      }
    }
    $this->assertContains('[step1] ->{*[success]}-> [step2]', $this->logEntriesToString($log_store->load()));
    $recordings = $this->iterator->getRecordings();
    /** @var RecordingInterface $recording */
    $recording = reset($recordings);
    $this->assertEquals(trim($expected_result), trim($recording->getTranscript()));
    $timer = $this->iterator->getTimer();
    $none_time = $timer->getTime('none');
    $this->assertGreaterThan(0, $none_time);
  }

  /**
   * Missing summary.
   *
   * @param WipLogEntryInterface[] $entries
   *   An array of log entries.
   *
   * @return string
   *   A string representation of the log.
   */
  private function logEntriesToString($entries) {
    $result = '';
    foreach ($entries as $entry) {
      $result .= $entry->getMessage() . "\n";
    }
    return $result;
  }

  /**
   * Missing summary.
   *
   * @param string $description
   *   The description.
   * @param int $pid
   *   The pid.
   * @param mixed $start_time
   *   The start time.
   * @param int $id
   *   The id.
   *
   * @return SshProcess
   *   The ssh process.
   */
  private function createSshProcess($description = 'testing', $pid = 57, $start_time = NULL, $id = 37) {
    if (empty($start_time)) {
      $start_time = time() - mt_rand(0, 15);
    }
    $environment = new Environment();
    $environment->setServers(array('localhost'));
    $environment->setCurrentServer('localhost');
    $process = new SshProcess($environment, $description, $pid, $start_time, $id);
    return $process;
  }

  /**
   * Missing summary.
   *
   * @group StateTable
   * @group Wip
   */
  public function testAddAndGetRecordings() {
    $name = 'recording';
    $original_recording_count = count($this->iterator->getRecordings());
    $this->iterator->addRecording($name, new StateTableRecording());

    $this->assertCount($original_recording_count + 1, $this->iterator->getRecordings());
  }

  /**
   * Tests setting the simulation mode.
   *
   * @param string $mode
   *   The simulation mode.
   * @param SimulationScriptInterpreter|null $interpreter
   *   The interpreter to use.
   *
   * @dataProvider invalidSimulationTypeProvider
   *
   * @group StateTable
   * @group Wip
   */
  public function testSetSimulationMode($mode, $interpreter) {
    // By default, the mode should be SIMULATIOIN_DISABLED.
    $this->assertEquals(
      $this->iterator->getSimulationMode(),
      StateTableIterator::SIMULATION_DISABLED
    );

    // RANDOM mode without interpreter.
    $this->iterator->setSimulationMode(StateTableIterator::SIMULATION_RANDOM);
    $this->assertEquals(
      $this->iterator->getSimulationMode(),
      StateTableIterator::SIMULATION_RANDOM
    );

    // SCRIPT mode with interpreter.
    $this->iterator->setSimulationMode(
      StateTableIterator::SIMULATION_SCRIPT,
      $this->mockInterpreter
    );
    $this->assertEquals(
      $this->iterator->getSimulationMode(),
      StateTableIterator::SIMULATION_SCRIPT
    );

    // All calls from the provider should throw InvalidArgumentException. RANDOM
    // mode will throw an exception if an interpreter is provided, while
    // SCRIPT mode will throw an exception if an interpreter is not provided.
    $this->setExpectedException('\InvalidArgumentException');
    $this->iterator->setSimulationMode($mode, $interpreter);
  }

}
