<?php

namespace Acquia\Wip\Test\PublicStable\Ssh;

use Acquia\Wip\Container\ContainerProcess;
use Acquia\Wip\Container\ContainerResult;
use Acquia\Wip\Container\ContainerResultInterface;
use Acquia\Wip\Test\Integration\PublicStable\AcquiaCloud\AcquiaCloudTestSetup;
use Acquia\Wip\Test\Utility\DataProviderTrait;
use Acquia\Wip\WipLogLevel;

/**
 * Missing summary.
 */
class ContainerResultTest extends \PHPUnit_Framework_TestCase {

  use DataProviderTrait;

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testEnvironment() {
    $result = new ContainerResult();
    $environment = AcquiaCloudTestSetup::getEnvironment();
    $result->setEnvironment($environment);
    $retrieved_environment = $result->getEnvironment();
    $this->assertEquals($environment, $retrieved_environment);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \RuntimeException
   */
  public function testSetEnvironmentSetTwice() {
    $result = new ContainerResult();
    $environment = AcquiaCloudTestSetup::getEnvironment();
    $result->setEnvironment($environment);
    $environment2 = AcquiaCloudTestSetup::getEnvironment();
    $result->setEnvironment($environment2);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testExitCode() {
    $exit_code = 3;
    $result = new ContainerResult();
    $result->setExitCode($exit_code);
    $this->assertEquals($exit_code, $result->getExitCode());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \RuntimeException
   */
  public function testSetExitCodeTwice() {
    $exit_code = 3;
    $result = new ContainerResult();
    $result->setExitCode($exit_code);
    $result->setExitCode($exit_code);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetExitCodeWrongType() {
    $exit_code = 'wrongtype';
    $result = new ContainerResult();
    $result->setExitCode($exit_code);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testExitMessage() {
    $exit_message = 'exit';
    $result = new ContainerResult();
    $result->setExitMessage($exit_message);
    $this->assertEquals($exit_message, $result->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   */
  public function testExitMessageWrongType() {
    $exit_message = 1;
    $result = new ContainerResult();
    $result->setExitMessage($exit_message);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \RuntimeException
   */
  public function testExitMessageAlreadySet() {
    $exit_message_1 = 'exit1';
    $exit_message_2 = 'exit2';
    $result = new ContainerResult();
    $result->setExitMessage($exit_message_1);
    $result->setExitMessage($exit_message_2);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testSuccessExitCodes() {
    $exit_codes = array(1, 2, 3);
    $result = new ContainerResult();
    $result->setSuccessExitCodes($exit_codes);
    $this->assertEquals($exit_codes, $result->getSuccessExitCodes());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSuccessExitCodesWrongType() {
    $exit_codes = 'wrongtype';
    $result = new ContainerResult();
    $result->setSuccessExitCodes($exit_codes);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSuccessExitCodesArrayIncludingWrongType() {
    $exit_codes = array(1, 2, 'wrongtype', 4);
    $result = new ContainerResult();
    $result->setSuccessExitCodes($exit_codes);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testSuccessExitCodesTwice() {
    $exit_codes_1 = array(1, 2, 3);
    $exit_codes_2 = array(4, 5, 6);
    $result = new ContainerResult();
    $result->setSuccessExitCodes($exit_codes_1);
    $this->assertEquals($exit_codes_1, $result->getSuccessExitCodes());

    $result->setSuccessExitCodes($exit_codes_2);
    $this->assertEquals($exit_codes_2, $result->getSuccessExitCodes());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testAddSuccessExitCode() {
    $exit_codes = array(1, 2, 3);

    $result = new ContainerResult();
    $result->setSuccessExitCodes($exit_codes);
    $this->assertEquals($exit_codes, $result->getSuccessExitCodes());

    $new_exit_code = 15;
    $exit_codes[] = $new_exit_code;
    $result->addSuccessExitCode($new_exit_code);
    $this->assertEquals($exit_codes, $result->getSuccessExitCodes());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   */
  public function testAddSuccessExitCodeWrongType() {
    $exit_codes = array(1, 2, 3);

    $result = new ContainerResult();
    $result->setSuccessExitCodes($exit_codes);
    $this->assertEquals($exit_codes, $result->getSuccessExitCodes());

    $new_exit_code = 'wrongtype';
    $result->addSuccessExitCode($new_exit_code);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testIsSuccessWhenSuccessful() {
    $exit_codes = array(1, 2, 3);
    $result = new ContainerResult();
    $result->setSuccessExitCodes($exit_codes);
    $result->setExitCode($exit_codes[1]);
    $this->assertTrue($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testIsSuccessWhenNotSuccessful() {
    $exit_codes = array(1, 2, 3);
    $result = new ContainerResult();
    $result->setSuccessExitCodes($exit_codes);
    $result->setExitCode(52);
    $this->assertFalse($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \RuntimeException
   */
  public function testIsSuccessBeforeExitCodeIsSet() {
    $exit_codes = array(1, 2, 3);
    $result = new ContainerResult();
    $result->setSuccessExitCodes($exit_codes);
    $result->isSuccess();
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testStartTime() {
    $start_time = time();
    $result = new ContainerResult();
    $result->setStartTime($start_time);
    $this->assertEquals($start_time, $result->getStartTime());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \RuntimeException
   */
  public function testGetStartTimeBeforeSet() {
    $result = new ContainerResult();
    $result->getStartTime();
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \RuntimeException
   */
  public function testSetStartTimeTwice() {
    $start_time = time();
    $result = new ContainerResult();
    $result->setStartTime($start_time);
    $result->setStartTime($start_time);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetStartTimeWrongType() {
    $start_time = 'wrongtype';
    $result = new ContainerResult();
    $result->setStartTime($start_time);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetStartTimeEqualsZero() {
    $start_time = 0;
    $result = new ContainerResult();
    $result->setStartTime($start_time);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetStartTimeNegative() {
    $start_time = -1;
    $result = new ContainerResult();
    $result->setStartTime($start_time);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testEndTime() {
    $end_time = time();
    $result = new ContainerResult();
    $result->setEndTime($end_time);
    $this->assertEquals($end_time, $result->getEndTime());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \RuntimeException
   */
  public function testGetEndTimeBeforeSet() {
    $result = new ContainerResult();
    $result->getEndTime();
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \RuntimeException
   */
  public function testSetEndTimeTwice() {
    $end_time = time();
    $result = new ContainerResult();
    $result->setendTime($end_time);
    $result->setEndTime($end_time);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetEndTimeWrongType() {
    $end_time = 'wrongtype';
    $result = new ContainerResult();
    $result->setEndTime($end_time);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetEndTimeEqualsZero() {
    $start_time = 0;
    $result = new ContainerResult();
    $result->setEndTime($start_time);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetEndTimeNegative() {
    $start_time = -1;
    $result = new ContainerResult();
    $result->setEndTime($start_time);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testGetRuntime() {
    $start_time = time() - mt_rand(1, 35);
    $end_time = time();
    $result = new ContainerResult();
    $result->setStartTime($start_time);
    $result->setEndTime($end_time);
    $this->assertEquals($end_time - $start_time, $result->getRuntime());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \RuntimeException
   */
  public function testGetRuntimeBeforeStartTimeIsSet() {
    $end_time = time();
    $result = new ContainerResult();
    $result->setEndTime($end_time);
    $result->getRuntime();
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \RuntimeException
   */
  public function testGetRuntimeBeforeEndTimeIsSet() {
    $start_time = time();
    $result = new ContainerResult();
    $result->setStartTime($start_time);
    $result->getRuntime();
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPid() {
    $pid = 'pid';
    $result = new ContainerResult();
    $result->setPid($pid);
    $this->assertEquals($pid, $result->getPid());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   *
   * @dataProvider nonStringDataProvider
   */
  public function testSetPidWrongTypes($pid) {
    $result = new ContainerResult();
    $result->setPid($pid);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \RuntimeException
   */
  public function testSetPidTwice() {
    $pid = 'pid';
    $result = new ContainerResult();
    $result->setPid($pid);
    $result->setPid($pid);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \RuntimeException
   */
  public function testGetPidBeforeSet() {
    $result = new ContainerResult();
    $result->getPid();
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testWipId() {
    $id = 15;
    $result = new ContainerResult();
    $result->setWipId($id);
    $this->assertEquals($id, $result->getWipId());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetWipIdWrongType() {
    $id = 'wrongtype';
    $result = new ContainerResult();
    $result->setWipId($id);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \RuntimeException
   */
  public function testSetWipIdTwice() {
    $id = 15;
    $result = new ContainerResult();
    $result->setWipId($id);
    $result->setWipId($id);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \RuntimeException
   */
  public function testGetWipIdBeforeSet() {
    $result = new ContainerResult();
    $result->getWipId();
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testLogLevel() {
    $log_level = WipLogLevel::ALERT;
    $result = new ContainerResult();
    $result->setLogLevel($log_level);
    $this->assertEquals($log_level, $result->getLogLevel());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   */
  public function testLogLevelInvalidValue() {
    $log_level = 45;
    $result = new ContainerResult();
    $result->setLogLevel($log_level);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   */
  public function testLogLevelWrongType() {
    $log_level = 'wrongtype';
    $result = new ContainerResult();
    $result->setLogLevel($log_level);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testGetUniqueId() {
    $pid = 'pid';
    $start_time = time();
    $result = new ContainerResult();
    $result->setPid($pid);
    $result->setStartTime($start_time);
    $this->assertSame(ContainerResult::createUniqueId($pid, $start_time), $result->getUniqueId());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testGetUniqueIdIsUnique() {
    $pid_1 = 'pid1';
    $pid_2 = 'pid2';
    $start_time = time();

    $result_1 = new ContainerResult();
    $result_1->setPid($pid_1);
    $result_1->setStartTime($start_time);
    $this->assertNotEmpty($result_1->getUniqueId());

    $result_2 = new ContainerResult();
    $result_2->setPid($pid_2);
    $result_2->setStartTime($start_time);
    $this->assertNotEmpty($result_2->getUniqueId());

    $this->assertNotEquals($result_1->getUniqueId(), $result_2->getUniqueId());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testGetUniqueIdSameForSimilarResultInstances() {
    $pid = 'pid';
    $start_time = time();

    $result_1 = new ContainerResult();
    $result_1->setPid($pid);
    $result_1->setStartTime($start_time);
    $this->assertNotEmpty($result_1->getUniqueId());

    $result_2 = new ContainerResult();
    $result_2->setPid($pid);
    $result_2->setStartTime($start_time);
    $this->assertNotEmpty($result_2->getUniqueId());

    $this->assertEquals($result_1->getUniqueId(), $result_2->getUniqueId());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testFromObject() {
    $object = $this->createObject();
    $result = ContainerResult::fromObject($object);
    $this->assertEquals($object->pid, $result->getPid());
    $this->assertEquals($object->wipId, $result->getWipId());
    $this->assertEquals($object->exitCode, $result->getExitCode());
    $this->assertEquals($object->startTime, $result->getStartTime());
    $this->assertEquals($object->endTime, $result->getEndTime());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   */
  public function testFromObjectMissingPid() {
    $object = $this->createObject();
    unset($object->pid);
    ContainerResult::fromObject($object);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testFromObjectMissingPidWithResultParameter() {
    $result = new ContainerResult();
    $object = $this->createObject();
    unset($object->pid);
    ContainerResult::fromObject($object, $result);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testFromObjectWithResultParameterWithPidAlreadySet() {
    $result = new ContainerResult();
    $object = $this->createObject();
    $result->setPid($object->pid);
    ContainerResult::fromObject($object, $result);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   */
  public function testFromObjectMissingWipId() {
    $object = $this->createObject();
    unset($object->wipId);
    ContainerResult::fromObject($object);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testFromObjectMissingWipIdWithResultParameter() {
    $result = new ContainerResult();
    $object = $this->createObject();
    unset($object->wipId);
    ContainerResult::fromObject($object, $result);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testFromObjectWithResultParameterWithWipIdAlreadySet() {
    $result = new ContainerResult();
    $object = $this->createObject();
    $result->setWipId($object->wipId);
    ContainerResult::fromObject($object, $result);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   */
  public function testFromObjectMissingExitCode() {
    $object = $this->createObject();
    unset($object->exitCode);
    ContainerResult::fromObject($object);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testFromObjectMissingExitCodeWithResultParameter() {
    $result = new ContainerResult();
    $object = $this->createObject();
    unset($object->exitCode);
    ContainerResult::fromObject($object, $result);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testFromObjectWithResultParameterWithExitCodeAlreadySet() {
    $exit_code = mt_rand(1, 1000);
    $result = new ContainerResult();
    $object = $this->createObject();
    $result->setExitCode($exit_code);
    ContainerResult::fromObject($object, $result);
    $this->assertEquals($exit_code, $result->getExitCode());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testFromObjectWithExitMessage() {
    $message = 'Here is the exit message.';
    $result = new ContainerResult();
    $object = $this->createObject();
    $object->exitMessage = $message;
    ContainerResult::fromObject($object, $result);
    $this->assertEquals($message, $result->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testFromObjectMissingExitMessageWithResultParameter() {
    $result = new ContainerResult();
    $object = $this->createObject();
    ContainerResult::fromObject($object, $result);
    $this->assertNull($result->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testFromObjectWithResultParameterWithExitMessageAlreadySet() {
    $message_1 = 'message 1';
    $message_2 = 'message 2';
    $result = new ContainerResult();
    $object = $this->createObject();
    $object->exitMessage = $message_1;
    $result->setExitMessage($message_2);
    ContainerResult::fromObject($object, $result);
    $this->assertEquals($message_2, $result->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testToJson() {
    $object = $this->createObject();
    $result = ContainerResult::fromObject($object);
    $json = $result->toJson();
    $json_result = ContainerResult::fromObject(ContainerResult::objectFromJson($json));
    $this->assertEquals($result, $json_result);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   */
  public function testFromJsonNullDocument() {
    ContainerResult::objectFromJson(NULL);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   */
  public function testFromJsonInvalidDocument() {
    ContainerResult::objectFromJson('invalidjson');
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateEnvironmentFromProcess() {
    $result = new ContainerResult();
    $process = new ContainerProcess();
    $environment = AcquiaCloudTestSetup::getEnvironment();
    $process->setEnvironment($environment);
    $result->populateFromProcess($process);
    $this->assertEquals($environment, $result->getEnvironment());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateEnvironmentFromProcessWithMissingEnvironment() {
    $result = new ContainerResult();
    $process = new ContainerProcess();
    $result->populateFromProcess($process);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateEnvironmentFromProcessWithEnvironmentAlreadySet() {
    $result = new ContainerResult();
    $process = new ContainerProcess();
    $environment1 = AcquiaCloudTestSetup::getEnvironment();
    $environment2 = AcquiaCloudTestSetup::getEnvironment();
    $environment2->setServers(array_merge($environment2->getServers(), array('wakaflocak')));
    $process->setEnvironment($environment1);
    $result->setEnvironment($environment2);
    $result->populateFromProcess($process);
    $this->assertNotEquals($environment1, $result->getEnvironment());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateEnvironmentFromProcessMissingEnvironmentAndAlreadySet() {
    $result = new ContainerResult();
    $process = new ContainerProcess();
    $environment = AcquiaCloudTestSetup::getEnvironment();
    $result->setEnvironment($environment);
    $result->populateFromProcess($process);
    $this->assertEquals($environment, $result->getEnvironment());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateExitCodeFromProcess() {
    $exit_code = 4;
    $result = new ContainerResult();
    $process = new ContainerProcess();
    $process->setExitCode($exit_code);
    $result->populateFromProcess($process);
    $this->assertEquals($exit_code, $result->getExitCode());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateExitCodeFromProcessMissingExitCode() {
    $result = new ContainerResult();
    $process = new ContainerProcess();
    $result->populateFromProcess($process);
    $this->assertNull($result->getExitCode());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateExitCodeFromProcessWithExitCodeAlreadySet() {
    $exit_code = 4;
    $result = new ContainerResult();
    $result->setExitCode($exit_code);
    $process = new ContainerProcess();
    $process->setExitCode($exit_code * 2);
    $result->populateFromProcess($process);
    $this->assertEquals($exit_code, $result->getExitCode());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateExitCodeFromProcessMissingExitCodeAndAlreadySet() {
    $exit_code = 4;
    $result = new ContainerResult();
    $result->setExitCode($exit_code);
    $process = new ContainerProcess();
    $result->populateFromProcess($process);
    $this->assertEquals($exit_code, $result->getExitCode());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateSuccessCodes() {
    $success_codes = array(4, 8, 12, 16);
    $process = new ContainerProcess();
    $process->setSuccessExitCodes($success_codes);
    $result = new ContainerResult();
    $result->populateFromProcess($process);
    $this->assertEquals($success_codes, $result->getSuccessExitCodes());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateSuccessCodesMissingCodes() {
    $process = new ContainerProcess();
    $result = new ContainerResult();
    $result->populateFromProcess($process);
    $this->assertEmpty($result->getSuccessExitCodes());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateSuccessCodesAlreadySet() {
    $process_success_codes = array(1, 2, 3, 4);
    $result_success_codes = array(4, 8, 12, 16);
    $process = new ContainerProcess();
    $process->setSuccessExitCodes($process_success_codes);
    $result = new ContainerResult();
    $result->setSuccessExitCodes($result_success_codes);
    $result->populateFromProcess($process);
    $this->assertEquals($process_success_codes, $result->getSuccessExitCodes());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateSuccessCodesMissingAndAlreadySet() {
    $result_success_codes = array(4, 8, 12, 16);
    $process = new ContainerProcess();
    $result = new ContainerResult();
    $result->setSuccessExitCodes($result_success_codes);
    $result->populateFromProcess($process);
    $this->assertEquals($result_success_codes, $result->getSuccessExitCodes());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateStartTime() {
    $start_time = time() - mt_rand(1, 45);
    $process = new ContainerProcess();
    $process->setStartTime($start_time);
    $result = new ContainerResult();
    $result->populateFromProcess($process);
    $this->assertEquals($start_time, $result->getStartTime());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateStartTimeAlreadySet() {
    $process_start_time = time() - mt_rand(1, 45);
    $result_start_time = time() - mt_rand(46, 100);
    $this->assertNotEquals($process_start_time, $result_start_time);
    $process = new ContainerProcess();
    $process->setStartTime($process_start_time);
    $result = new ContainerResult();
    $result->setStartTime($result_start_time);
    $result->populateFromProcess($process);
    $this->assertEquals($result_start_time, $result->getStartTime());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \RuntimeException
   */
  public function testPopulateStartTimeMissing() {
    $process = new ContainerProcess();
    $result = new ContainerResult();
    $result->populateFromProcess($process);
    $result->getStartTime();
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateStartTimeMissingAndAlreadySet() {
    $start_time = time() - mt_rand(1, 45);
    $process = new ContainerProcess();
    $result = new ContainerResult();
    $result->setStartTime($start_time);
    $result->populateFromProcess($process);
    $this->assertEquals($start_time, $result->getStartTime());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateEndTime() {
    $end_time = time() - mt_rand(1, 45);
    $process = new ContainerProcess();
    $process->setEndTime($end_time);
    $result = new ContainerResult();
    $result->populateFromProcess($process);
    $this->assertEquals($end_time, $result->getEndTime());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateEndTimeAlreadySet() {
    $process_end_time = time() - mt_rand(1, 45);
    $result_end_time = time() - mt_rand(46, 100);
    $this->assertNotEquals($process_end_time, $result_end_time);
    $process = new ContainerProcess();
    $process->setEndTime($process_end_time);
    $result = new ContainerResult();
    $result->setEndTime($result_end_time);
    $result->populateFromProcess($process);
    $this->assertEquals($result_end_time, $result->getEndTime());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \RuntimeException
   */
  public function testPopulateEndTimeMissing() {
    $process = new ContainerProcess();
    $result = new ContainerResult();
    $result->populateFromProcess($process);
    $result->getEndTime();
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateEndTimeMissingAndAlreadySet() {
    $end_time = time() - mt_rand(1, 45);
    $process = new ContainerProcess();
    $result = new ContainerResult();
    $result->setEndTime($end_time);
    $result->populateFromProcess($process);
    $this->assertEquals($end_time, $result->getEndTime());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulatePid() {
    $pid = 'pid';
    $process = new ContainerProcess();
    $process->setPid($pid);
    $result = new ContainerResult();
    $result->populateFromProcess($process);
    $this->assertEquals($pid, $result->getPid());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulatePidAlreadySet() {
    $process_pid = 'pid1';
    $result_pid = 'pid2';
    $this->assertNotEquals($process_pid, $result_pid);
    $process = new ContainerProcess();
    $process->setPid($process_pid);
    $result = new ContainerResult();
    $result->setPid($result_pid);
    $result->populateFromProcess($process);
    $this->assertEquals($result_pid, $result->getPid());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \RuntimeException
   */
  public function testPopulatePidMissing() {
    $process = new ContainerProcess();
    $result = new ContainerResult();
    $result->populateFromProcess($process);
    $result->getPid();
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulatePidMissingAndAlreadySet() {
    $pid = 'pid';
    $process = new ContainerProcess();
    $result = new ContainerResult();
    $result->setPid($pid);
    $result->populateFromProcess($process);
    $this->assertEquals($pid, $result->getPid());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateLogLevel() {
    $log_level = WipLogLevel::ALERT;
    $process = new ContainerProcess();
    $process->setLogLevel($log_level);
    $result = new ContainerResult();
    $result->populateFromProcess($process);
    $this->assertEquals($log_level, $result->getLogLevel());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateLogLevelAlreadySet() {
    $process_log_level = WipLogLevel::ALERT;
    $result_log_level = WipLogLevel::FATAL;
    $this->assertNotEquals($process_log_level, $result_log_level);
    $process = new ContainerProcess();
    $process->setLogLevel($process_log_level);
    $result = new ContainerResult();
    $result->setLogLevel($result_log_level);
    $result->populateFromProcess($process);
    $this->assertEquals($result_log_level, $result->getLogLevel());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateLogLevelMissing() {
    $process = new ContainerProcess();
    $result = new ContainerResult();
    $result->populateFromProcess($process);
    $this->assertEquals(ContainerResult::DEFAULT_LOG_LEVEL, $result->getLogLevel());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateLogLevelMissingAndAlreadySet() {
    $log_level = WipLogLevel::ERROR;
    $process = new ContainerProcess();
    $result = new ContainerResult();
    $result->setLogLevel($log_level);
    $result->populateFromProcess($process);
    $this->assertEquals($log_level, $result->getLogLevel());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateExitMessage() {
    $exit_message = 'exit message';
    $process = new ContainerProcess();
    $process->setExitMessage($exit_message);
    $result = new ContainerResult();
    $result->populateFromProcess($process);
    $this->assertEquals($exit_message, $result->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateExitMessageAlreadySet() {
    $process_message = 'process message';
    $result_message = 'result message';
    $process = new ContainerProcess();
    $process->setExitMessage($process_message);
    $result = new ContainerResult();
    $result->setExitMessage($result_message);
    $result->populateFromProcess($process);
    $this->assertEquals($result_message, $result->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateExitMessageMissing() {
    $process = new ContainerProcess();
    $result = new ContainerResult();
    $result->populateFromProcess($process);
    $result->getExitMessage();
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateExitMessageMissingAndAlreadySet() {
    $result_message = 'result message';
    $process = new ContainerProcess();
    $result = new ContainerResult();
    $result->setExitMessage($result_message);
    $result->populateFromProcess($process);
    $this->assertEquals($result_message, $result->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateWipId() {
    $wip_id = mt_rand(1, PHP_INT_MAX);
    $process = new ContainerProcess();
    $process->setWipId($wip_id);
    $result = new ContainerResult();
    $result->populateFromProcess($process);
    $this->assertEquals($wip_id, $result->getWipId());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateWipIdAlreadySet() {
    $process_id = mt_rand(1, PHP_INT_MAX);
    $result_id = mt_rand(1, PHP_INT_MAX);
    $this->assertNotEquals($process_id, $result_id);
    $process = new ContainerProcess();
    $process->setWipId($process_id);
    $result = new ContainerResult();
    $result->setWipId($result_id);
    $result->populateFromProcess($process);
    $this->assertEquals($result_id, $result->getWipId());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \RuntimeException
   */
  public function testPopulateWipIdMissing() {
    $process = new ContainerProcess();
    $result = new ContainerResult();
    $result->populateFromProcess($process);
    $result->getWipId();
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateWipIdMissingAndAlreadySet() {
    $result_id = mt_rand(1, PHP_INT_MAX);
    $process = new ContainerProcess();
    $result = new ContainerResult();
    $result->setWipId($result_id);
    $result->populateFromProcess($process);
    $this->assertEquals($result_id, $result->getWipId());
  }

  /**
   * Missing summary.
   */
  private function createObject() {
    $result = new \stdClass();
    $result->pid = sprintf('pid%d', mt_rand());
    $result->wipId = mt_rand(1, PHP_INT_MAX);
    $result->startTime = time() - mt_rand(1, 45);
    $result->endTime = time();
    $result->exitCode = 0;
    return $result;
  }

}
