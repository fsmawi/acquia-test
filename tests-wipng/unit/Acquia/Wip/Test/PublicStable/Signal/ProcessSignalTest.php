<?php

namespace Acquia\Wip\Test\PublicStable\Signal;

use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\Signal\ProcessSignal;
use Acquia\Wip\Signal\SignalType;
use Acquia\Wip\Signal\SshCompleteSignal;
use Acquia\Wip\Signal\WipCompleteSignal;
use Acquia\Wip\Ssh\SshProcess;
use Acquia\Wip\Test\Integration\PublicStable\AcquiaCloud\AcquiaCloudTestSetup;
use Acquia\Wip\Timer;

/**
 * Missing summary.
 */
class ProcessSignalTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testSetExitMessage() {
    $message = 'This is the message.';
    $signal = new ProcessSignal();
    $signal->setExitMessage($message);
    $this->assertEquals($message, $signal->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetExitMessageInvalidType() {
    $message = 15;
    $signal = new ProcessSignal();
    $signal->setExitMessage($message);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testSetTimer() {
    $signal = new ProcessSignal();
    $timer = new Timer();
    $signal->setTimer($timer);
    $this->assertEquals($timer, $signal->getTimer());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testSetTimerJson() {
    $system_time = 1.03;
    $user_time = 10.17;
    $signal = new ProcessSignal();
    $timer_json = <<<EOT
{"system":$system_time,"user":$user_time}
EOT;
    $signal->setTimer($timer_json);
    $timer = $signal->getTimer();
    $this->assertEquals($system_time, $timer->getTime('system'));
    $this->assertEquals($user_time, $timer->getTime('user'));
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetTimerWrongType() {
    $timer = new \stdClass();
    $signal = new ProcessSignal();
    $signal->setTimer($timer);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetTimerBadJsonString() {
    $timer = 'Bad json';
    $signal = new ProcessSignal();
    $signal->setTimer($timer);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetTimerEmpty() {
    $timer = NULL;
    $signal = new ProcessSignal();
    $signal->setTimer($timer);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testInitializeFromSignalDataExitMessage() {
    $exit_message = 'Goodbye.';
    $signal_data = new \stdClass();
    $signal_data->exitMessage = $exit_message;
    $signal = new ProcessSignal();
    $signal->initializeFromSignalData($signal_data);
    $this->assertEquals($exit_message, $signal->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testInitializeFromSignalDataTimer() {
    $system_time = 17.1;
    $user_time = 7.8;
    $timer_data = <<<EOT
{"system":$system_time,"user":$user_time}
EOT;

    $timer = Timer::fromJson($timer_data);
    $signal_data = new \stdClass();
    $signal_data->timer = $timer;
    $signal = new ProcessSignal();
    $signal->initializeFromSignalData($signal_data);
    $this->assertEquals($timer, $signal->getTimer());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testConvertFieldsToObject() {
    $exit_code = 17;
    $exit_message = 'Goodbye.';
    $system_time = 17.1;
    $user_time = 7.8;
    $timer_data = <<<EOT
{"system":$system_time,"user":$user_time}
EOT;
    $timer = Timer::fromJson($timer_data);
    $signal = new ProcessSignal();
    $signal->setExitCode($exit_code);
    $signal->setExitMessage($exit_message);
    $signal->setTimer($timer);

    $obj = $signal->convertFieldsToObject();
    $this->assertEquals($exit_code, $obj->exitCode);
    $this->assertEquals($exit_message, $obj->exitMessage);
    $this->assertEquals($timer_data, $obj->timer);
  }

}
