<?php

namespace Acquia\Wip\Test\PublicStable\Signal;

use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\Signal\ContainerCompleteSignal;
use Acquia\Wip\Signal\ProcessSignal;
use Acquia\Wip\Signal\Signal;
use Acquia\Wip\Signal\SignalType;
use Acquia\Wip\Signal\SshCompleteSignal;
use Acquia\Wip\Signal\WipCompleteSignal;
use Acquia\Wip\Ssh\SshProcess;
use Acquia\Wip\Ssh\SshResult;
use Acquia\Wip\Ssh\SshResultInterface;
use Acquia\Wip\Test\Integration\PublicStable\AcquiaCloud\AcquiaCloudTestSetup;

/**
 * Missing summary.
 */
class SignalTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testSignalId() {
    $id = 45;
    $signal = new Signal();
    $signal->setId($id);
    $this->assertEquals($id, $signal->getId());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSignalNotInt() {
    $id = 'hello';
    $signal = new Signal();
    $signal->setId($id);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSignalNegative() {
    $id = -1;
    $signal = new Signal();
    $signal->setId($id);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testObjectId() {
    $id = 45;
    $signal = new Signal();
    $signal->setObjectId($id);
    $this->assertEquals($id, $signal->getObjectId());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @expectedException \InvalidArgumentException
   */
  public function testObjectIdNotInt() {
    $id = 'hello';
    $signal = new Signal();
    $signal->setObjectId($id);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @expectedException \InvalidArgumentException
   */
  public function testObjectIdNegative() {
    $id = -1;
    $signal = new Signal();
    $signal->setObjectId($id);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testSentTime() {
    $time = 45;
    $signal = new Signal();
    $signal->setSentTime($time);
    $this->assertEquals($time, $signal->getSentTime());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSentTimeNotInt() {
    $time = 'hello';
    $signal = new Signal();
    $signal->setSentTime($time);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSentTimeNegative() {
    $time = -1;
    $signal = new Signal();
    $signal->setSentTime($time);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testConsumedTime() {
    $time = 45;
    $signal = new Signal();
    $signal->setConsumedTime($time);
    $this->assertEquals($time, $signal->getConsumedTime());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @expectedException \InvalidArgumentException
   */
  public function testConsumedTimeNotInt() {
    $time = 'hello';
    $signal = new Signal();
    $signal->setConsumedTime($time);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @expectedException \InvalidArgumentException
   */
  public function testConsumedTimeNegative() {
    $time = -1;
    $signal = new Signal();
    $signal->setConsumedTime($time);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testType() {
    $type = SignalType::DATA;
    $signal = new Signal();
    $signal->setType($type);
    $this->assertEquals($type, $signal->getType());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidType() {
    $type = 'hello';
    $signal = new Signal();
    $signal->setType($type);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testData() {
    $data = new \stdClass();
    $data->time = time();
    $signal = new Signal();
    $signal->setData($data);
    $this->assertEquals($data, $signal->getData());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testSignalDataConversion() {
    $exit_code = 3;
    $stdout = 'hello there';
    $stderr = 'wakaflocka';
    $start_time = time() - mt_rand(1, 15);
    $end_time = time();
    $pid = mt_rand(1, PHP_INT_MAX);
    $wip_id = 0;
    $exit_message = 'whatever';

    $input = new \stdClass();
    $input_result = new \stdClass();
    $input->result = $input_result;
    $input_result->exitCode = $exit_code;
    $input_result->exitMessage = $exit_message;
    $input_result->stdout = $stdout;
    $input_result->stderr = $stderr;
    $input_result->endTime = $end_time;
    $input->startTime = $start_time;
    $input->pid = $pid;

    $signal = new SshCompleteSignal();
    $signal->setData($input);

    $process = new SshProcess(AcquiaCloudTestSetup::getEnvironment(), 'hello', $pid, $start_time, $wip_id);
    $result = $process->getResultFromSignal($signal, new WipLog());
    $this->assertEquals($exit_code, $result->getExitCode());
    $this->assertEquals($start_time, $result->getStartTime());
    $this->assertEquals($end_time, $result->getEndTime());
    $this->assertEquals($pid, $result->getPid());
    $this->assertEquals($stdout, $result->getStdout());
    $this->assertEquals($stderr, $result->getStderr());
    $this->assertEquals($wip_id, $result->getWipId());
    $this->assertEquals($exit_message, $result->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @expectedException \InvalidArgumentException
   */
  public function testDataInvalidType() {
    $data = 'hello';
    $signal = new Signal();
    $signal->setData($data);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testInitializeFromSignal() {
    $id = 4;
    $signal = new Signal();
    $signal->setId($id++);
    $signal->setObjectId($id++);
    $signal->setSentTime(time());
    $signal->setConsumedTime(time());
    $signal->setType(SignalType::DATA);
    $data = new \stdClass();
    $data->time = time();
    $signal->setData($data);

    $test_signal = new Signal();
    $this->assertNotEquals($signal, $test_signal);
    $test_signal->initializeFromSignal($signal);
    $this->assertEquals($signal, $test_signal);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testProcessSignalConvertToObject() {
    $signal = new ProcessSignal();
    $signal->setId(1);
    $signal->setObjectId(1);
    $signal->setStartTime(time());

    $data = new \stdClass();
    $data->endTime = time();
    $data->startTime = $data->endTime - 9;
    $signal->setData($data);

    $new_signal = new ProcessSignal();
    $new_signal->initializeFromSignal($signal);

    $signal_obj = $signal->convertToObject();
    $this->assertNotEmpty($signal_obj);
    $this->assertEquals($signal_obj, $new_signal->convertToObject());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testProcessInitializeFromObject() {
    $data = new \stdClass();
    $data->endTime = time();
    $data->startTime = $data->endTime - 9;
    $data->completedWipId = 1;
    $signal = new WipCompleteSignal();
    $signal->initializeFromSignalData($data);
    $this->assertEquals($data->endTime, $signal->getEndTime());
    $this->assertEquals($data->startTime, $signal->getStartTime());
  }

}
