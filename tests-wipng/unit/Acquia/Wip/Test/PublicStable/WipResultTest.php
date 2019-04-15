<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\Test\Integration\PublicStable\AcquiaCloud\AcquiaCloudTestSetup;
use Acquia\Wip\WipLogLevel;
use Acquia\Wip\WipProcess;
use Acquia\Wip\WipResult;
use Acquia\Wip\WipResultInterface;

/**
 * Missing summary.WIP results.
 */
class WipResultTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testEnvironment() {
    /** @var WipResultInterface $result */
    $result = new WipResult();
    $environment = AcquiaCloudTestSetup::getEnvironment();
    $result->setEnvironment($environment);
    $retrieved_environment = $result->getEnvironment();
    $this->assertEquals($environment, $retrieved_environment);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \RuntimeException
   */
  public function testSetEnvironmentSetTwice() {
    /** @var WipResultInterface $result */
    $result = new WipResult();
    $environment = AcquiaCloudTestSetup::getEnvironment();
    $result->setEnvironment($environment);
    $environment2 = AcquiaCloudTestSetup::getEnvironment();
    $result->setEnvironment($environment2);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testExitCode() {
    $exit_code = 3;
    $result = new WipResult();
    $result->setExitCode($exit_code);
    $this->assertEquals($exit_code, $result->getExitCode());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \RuntimeException
   */
  public function testSetExitCodeTwice() {
    $exit_code = 3;
    $result = new WipResult();
    $result->setExitCode($exit_code);
    $result->setExitCode($exit_code);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetExitCodeWrongType() {
    $exit_code = 'wrongtype';
    $result = new WipResult();
    $result->setExitCode($exit_code);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testExitMessage() {
    $exit_message = 'exit';
    $result = new WipResult();
    $result->setExitMessage($exit_message);
    $this->assertEquals($exit_message, $result->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testExitMessageWrongType() {
    $exit_message = 1;
    $result = new WipResult();
    $result->setExitMessage($exit_message);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \RuntimeException
   */
  public function testExitMessageAlreadySet() {
    $exit_message_1 = 'exit1';
    $exit_message_2 = 'exit2';
    $result = new WipResult();
    $result->setExitMessage($exit_message_1);
    $result->setExitMessage($exit_message_2);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testSuccessExitCodes() {
    $exit_codes = array(1, 2, 3);
    $result = new WipResult();
    $result->setSuccessExitCodes($exit_codes);
    $this->assertEquals($exit_codes, $result->getSuccessExitCodes());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSuccessExitCodesWrongType() {
    $exit_codes = 'wrongtype';
    $result = new WipResult();
    $result->setSuccessExitCodes($exit_codes);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSuccessExitCodesArrayIncludingWrongType() {
    $exit_codes = array(1, 2, 'wrongtype', 4);
    $result = new WipResult();
    $result->setSuccessExitCodes($exit_codes);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testSuccessExitCodesTwice() {
    $exit_codes_1 = array(1, 2, 3);
    $exit_codes_2 = array(4, 5, 6);
    $result = new WipResult();
    $result->setSuccessExitCodes($exit_codes_1);
    $this->assertEquals($exit_codes_1, $result->getSuccessExitCodes());

    $result->setSuccessExitCodes($exit_codes_2);
    $this->assertEquals($exit_codes_2, $result->getSuccessExitCodes());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testAddSuccessExitCode() {
    $exit_codes = array(1, 2, 3);

    $result = new WipResult();
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
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testAddSuccessExitCodeWrongType() {
    $exit_codes = array(1, 2, 3);

    $result = new WipResult();
    $result->setSuccessExitCodes($exit_codes);
    $this->assertEquals($exit_codes, $result->getSuccessExitCodes());

    $new_exit_code = 'wrongtype';
    $result->addSuccessExitCode($new_exit_code);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testIsSuccessWhenSuccessful() {
    $exit_codes = array(1, 2, 3);
    $result = new WipResult();
    $result->setSuccessExitCodes($exit_codes);
    $result->setExitCode($exit_codes[1]);
    $this->assertTrue($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testIsSuccessWhenNotSuccessful() {
    $exit_codes = array(1, 2, 3);
    $result = new WipResult();
    $result->setSuccessExitCodes($exit_codes);
    $result->setExitCode(52);
    $this->assertFalse($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \RuntimeException
   */
  public function testIsSuccessBeforeExitCodeIsSet() {
    $exit_codes = array(1, 2, 3);
    $result = new WipResult();
    $result->setSuccessExitCodes($exit_codes);
    $result->isSuccess();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testStartTime() {
    $start_time = time();
    $result = new WipResult();
    $result->setStartTime($start_time);
    $this->assertEquals($start_time, $result->getStartTime());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \RuntimeException
   */
  public function testGetStartTimeBeforeSet() {
    $result = new WipResult();
    $result->getStartTime();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \RuntimeException
   */
  public function testSetStartTimeTwice() {
    $start_time = time();
    $result = new WipResult();
    $result->setStartTime($start_time);
    $result->setStartTime($start_time);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetStartTimeWrongType() {
    $start_time = 'wrongtype';
    $result = new WipResult();
    $result->setStartTime($start_time);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetStartTimeEqualsZero() {
    $start_time = 0;
    $result = new WipResult();
    $result->setStartTime($start_time);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetStartTimeNegative() {
    $start_time = -1;
    $result = new WipResult();
    $result->setStartTime($start_time);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testEndTime() {
    $end_time = time();
    $result = new WipResult();
    $result->setEndTime($end_time);
    $this->assertEquals($end_time, $result->getEndTime());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \RuntimeException
   */
  public function testGetEndTimeBeforeSet() {
    $result = new WipResult();
    $result->getEndTime();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \RuntimeException
   */
  public function testSetEndTimeTwice() {
    $end_time = time();
    $result = new WipResult();
    $result->setendTime($end_time);
    $result->setEndTime($end_time);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetEndTimeWrongType() {
    $end_time = 'wrongtype';
    $result = new WipResult();
    $result->setEndTime($end_time);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetEndTimeEqualsZero() {
    $start_time = 0;
    $result = new WipResult();
    $result->setEndTime($start_time);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetEndTimeNegative() {
    $start_time = -1;
    $result = new WipResult();
    $result->setEndTime($start_time);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testGetRuntime() {
    $start_time = time() - mt_rand(1, 35);
    $end_time = time();
    $result = new WipResult();
    $result->setStartTime($start_time);
    $result->setEndTime($end_time);
    $this->assertEquals($end_time - $start_time, $result->getRuntime());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \RuntimeException
   */
  public function testGetRuntimeBeforeStartTimeIsSet() {
    $end_time = time();
    $result = new WipResult();
    $result->setEndTime($end_time);
    $result->getRuntime();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \RuntimeException
   */
  public function testGetRuntimeBeforeEndTimeIsSet() {
    $start_time = time();
    $result = new WipResult();
    $result->setStartTime($start_time);
    $result->getRuntime();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @dataProvider pidDataProvider
   */
  public function testPid($pid) {
    $process = new WipProcess();
    $process->setPid($pid);
    $this->assertEquals($pid, $process->getPid());
  }

  /**
   * Missing summary.
   */
  public function pidDataProvider() {
    return array(
      array(-10),
      array(0),
      array(10),
      array('string'),
    );
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \RuntimeException
   */
  public function testSetPidTwice() {
    $pid = 15;
    $result = new WipResult();
    $result->setPid($pid);
    $result->setPid($pid);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \RuntimeException
   */
  public function testGetPidBeforeSet() {
    $result = new WipResult();
    $result->getPid();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testWipId() {
    $id = 15;
    $result = new WipResult();
    $result->setWipId($id);
    $this->assertEquals($id, $result->getWipId());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetWipIdWrongType() {
    $id = 'wrongtype';
    $result = new WipResult();
    $result->setWipId($id);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \RuntimeException
   */
  public function testSetWipIdTwice() {
    $id = 15;
    $result = new WipResult();
    $result->setWipId($id);
    $result->setWipId($id);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \RuntimeException
   */
  public function testGetWipIdBeforeSet() {
    $result = new WipResult();
    $result->getWipId();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testLogLevel() {
    $log_level = WipLogLevel::ALERT;
    $result = new WipResult();
    $result->setLogLevel($log_level);
    $this->assertEquals($log_level, $result->getLogLevel());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testLogLevelInvalidValue() {
    $log_level = 45;
    $result = new WipResult();
    $result->setLogLevel($log_level);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testLogLevelWrongType() {
    $log_level = 'wrongtype';
    $result = new WipResult();
    $result->setLogLevel($log_level);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testGetUniqueId() {
    $pid = 244;
    $result = new WipResult();
    $result->setPid($pid);
    $this->assertNotEmpty($result->getUniqueId());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testGetUniqueIdIsUnique() {
    $pid_1 = 244;
    $pid_2 = 553;
    $result_1 = new WipResult();
    $result_1->setPid($pid_1);
    $this->assertNotEmpty($result_1->getUniqueId());

    $result_2 = new WipResult();
    $result_2->setPid($pid_2);
    $this->assertNotEmpty($result_2->getUniqueId());

    $this->assertNotEquals($result_1->getUniqueId(), $result_2->getUniqueId());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testGetUniqueIdSameForSimilarResultInstances() {
    $pid = 244;
    $result_1 = new WipResult();
    $result_1->setPid($pid);
    $this->assertNotEmpty($result_1->getUniqueId());

    $result_2 = new WipResult();
    $result_2->setPid($pid);
    $this->assertNotEmpty($result_2->getUniqueId());

    $this->assertEquals($result_1->getUniqueId(), $result_2->getUniqueId());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testFromObject() {
    $object = $this->createObject();
    $result = WipResult::fromObject($object);
    $this->assertEquals($object->pid, $result->getPid());
    $this->assertEquals($object->wipId, $result->getWipId());
    $this->assertEquals($object->exitCode, $result->getExitCode());
    $this->assertEquals($object->startTime, $result->getStartTime());
    $this->assertEquals($object->endTime, $result->getEndTime());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testFromObjectMissingPid() {
    $object = $this->createObject();
    unset($object->pid);
    WipResult::fromObject($object);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testFromObjectMissingPidWithResultParameter() {
    $result = new WipResult();
    $object = $this->createObject();
    unset($object->pid);
    WipResult::fromObject($object, $result);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testFromObjectWithResultParameterWithPidAlreadySet() {
    $result = new WipResult();
    $object = $this->createObject();
    do {
      $pid = mt_rand(1, PHP_INT_MAX);
    } while ($pid == $object->pid);
    $result->setPid($pid);
    $result = WipResult::fromObject($object, $result);
    $this->assertEquals($pid, $result->getPid());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testFromObjectMissingWipId() {
    $object = $this->createObject();
    unset($object->wipId);
    WipResult::fromObject($object);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testFromObjectMissingWipIdWithResultParameter() {
    $result = new WipResult();
    $object = $this->createObject();
    unset($object->wipId);
    WipResult::fromObject($object, $result);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testFromObjectWithResultParameterWithWipIdAlreadySet() {
    $result = new WipResult();
    $object = $this->createObject();
    do {
      $wip_id = mt_rand(1, PHP_INT_MAX);
    } while ($wip_id == $object->wipId);
    $result->setWipId($wip_id);
    WipResult::fromObject($object, $result);
    $this->assertEquals($wip_id, $result->getWipId());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testFromObjectMissingExitCode() {
    $object = $this->createObject();
    unset($object->exitCode);
    WipResult::fromObject($object);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testFromObjectMissingExitCodeWithResultParameter() {
    $object = $this->createObject();
    $result = new WipResult();
    unset($object->exitCode);
    WipResult::fromObject($object, $result);

    // If we try to get the exit code and it hasn't been set, an exception will
    // be thrown.  If we try to set the exit code and it has already been set,
    // an exception will be thrown.  If we set the exit code and it hasn't been
    // set, no exception will be thrown.
    // Here we set the exit code and expect no exception because the exit code
    // should *not* have been set already.
    $result->setExitCode(0);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testFromObjectWithResultParameterWithExitCodeAlreadySet() {
    $exit_code = mt_rand(1, 1000);
    $result = new WipResult();
    $object = $this->createObject();
    $result->setExitCode($exit_code);
    WipResult::fromObject($object, $result);
    $this->assertEquals($exit_code, $result->getExitCode());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testFromObjectWithExitMessage() {
    $message = 'Here is the exit message.';
    $result = new WipResult();
    $object = $this->createObject();
    $object->exitMessage = $message;
    WipResult::fromObject($object, $result);
    $this->assertEquals($message, $result->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testFromObjectMissingExitMessageWithResultParameter() {
    $result = new WipResult();
    $object = $this->createObject();
    WipResult::fromObject($object, $result);
    $this->assertNull($result->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testFromObjectWithResultParameterWithExitMessageAlreadySet() {
    $message_1 = 'message 1';
    $message_2 = 'message 2';
    $result = new WipResult();
    $object = $this->createObject();
    $object->exitMessage = $message_1;
    $result->setExitMessage($message_2);
    WipResult::fromObject($object, $result);
    $this->assertEquals($message_2, $result->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testToJson() {
    $object = $this->createObject();
    $result = WipResult::fromObject($object);
    $json = $result->toJson();
    $json_result = WipResult::fromObject(WipResult::objectFromJson($json));
    $this->assertEquals($result, $json_result);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testFromJsonNullDocument() {
    WipResult::objectFromJson(NULL);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testFromJsonInvalidDocument() {
    WipResult::objectFromJson('invalidjson');
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateEnvironmentFromProcess() {
    $result = new WipResult();
    $process = new WipProcess();
    $environment = AcquiaCloudTestSetup::getEnvironment();
    $process->setEnvironment($environment);
    $result->populateFromProcess($process);
    $this->assertEquals($environment, $result->getEnvironment());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateEnvironmentFromProcessWithMissingEnvironment() {
    $result = new WipResult();
    $process = new WipProcess();
    $result->populateFromProcess($process);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateEnvironmentFromProcessWithEnvironmentAlreadySet() {
    $result = new WipResult();
    $process = new WipProcess();
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
   * @group Wip
   */
  public function testPopulateEnvironmentFromProcessMissingEnvironmentAndAlreadySet() {
    $result = new WipResult();
    $process = new WipProcess();
    $environment = AcquiaCloudTestSetup::getEnvironment();
    $result->setEnvironment($environment);
    $result->populateFromProcess($process);
    $this->assertEquals($environment, $result->getEnvironment());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateExitCodeFromProcess() {
    $exit_code = 4;
    $result = new WipResult();
    $process = new WipProcess();
    $process->setExitCode($exit_code);
    $result->populateFromProcess($process);
    $this->assertEquals($exit_code, $result->getExitCode());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateExitCodeFromProcessMissingExitCode() {
    $result = new WipResult();
    $process = new WipProcess();
    $result->populateFromProcess($process);
    $this->assertNull($result->getExitCode());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateExitCodeFromProcessWithExitCodeAlreadySet() {
    $exit_code = 4;
    $result = new WipResult();
    $result->setExitCode($exit_code);
    $process = new WipProcess();
    $process->setExitCode($exit_code * 2);
    $result->populateFromProcess($process);
    $this->assertEquals($exit_code, $result->getExitCode());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateExitCodeFromProcessMissingExitCodeAndAlreadySet() {
    $exit_code = 4;
    $result = new WipResult();
    $result->setExitCode($exit_code);
    $process = new WipProcess();
    $result->populateFromProcess($process);
    $this->assertEquals($exit_code, $result->getExitCode());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateSuccessCodes() {
    $success_codes = array(4, 8, 12, 16);
    $process = new WipProcess();
    $process->setSuccessExitCodes($success_codes);
    $result = new WipResult();
    $result->populateFromProcess($process);
    $this->assertEquals($success_codes, $result->getSuccessExitCodes());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateSuccessCodesMissingCodes() {
    $process = new WipProcess();
    $result = new WipResult();
    $result->populateFromProcess($process);
    $this->assertEmpty($result->getSuccessExitCodes());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateSuccessCodesAlreadySet() {
    $process_success_codes = array(1, 2, 3, 4);
    $result_success_codes = array(4, 8, 12, 16);
    $process = new WipProcess();
    $process->setSuccessExitCodes($process_success_codes);
    $result = new WipResult();
    $result->setSuccessExitCodes($result_success_codes);
    $result->populateFromProcess($process);
    $this->assertEquals($process_success_codes, $result->getSuccessExitCodes());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateSuccessCodesMissingAndAlreadySet() {
    $result_success_codes = array(4, 8, 12, 16);
    $process = new WipProcess();
    $result = new WipResult();
    $result->setSuccessExitCodes($result_success_codes);
    $result->populateFromProcess($process);
    $this->assertEquals($result_success_codes, $result->getSuccessExitCodes());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateStartTime() {
    $start_time = time() - mt_rand(1, 45);
    $process = new WipProcess();
    $process->setStartTime($start_time);
    $result = new WipResult();
    $result->populateFromProcess($process);
    $this->assertEquals($start_time, $result->getStartTime());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateStartTimeAlreadySet() {
    $process_start_time = time() - mt_rand(1, 45);
    $result_start_time = time() - mt_rand(46, 100);
    $this->assertNotEquals($process_start_time, $result_start_time);
    $process = new WipProcess();
    $process->setStartTime($process_start_time);
    $result = new WipResult();
    $result->setStartTime($result_start_time);
    $result->populateFromProcess($process);
    $this->assertEquals($result_start_time, $result->getStartTime());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \RuntimeException
   */
  public function testPopulateStartTimeMissing() {
    $process = new WipProcess();
    $result = new WipResult();
    $result->populateFromProcess($process);
    $result->getStartTime();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateStartTimeMissingAndAlreadySet() {
    $start_time = time() - mt_rand(1, 45);
    $process = new WipProcess();
    $result = new WipResult();
    $result->setStartTime($start_time);
    $result->populateFromProcess($process);
    $this->assertEquals($start_time, $result->getStartTime());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateEndTime() {
    $end_time = time() - mt_rand(1, 45);
    $process = new WipProcess();
    $process->setEndTime($end_time);
    $result = new WipResult();
    $result->populateFromProcess($process);
    $this->assertEquals($end_time, $result->getEndTime());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateEndTimeAlreadySet() {
    $process_end_time = time() - mt_rand(1, 45);
    $result_end_time = time() - mt_rand(46, 100);
    $this->assertNotEquals($process_end_time, $result_end_time);
    $process = new WipProcess();
    $process->setEndTime($process_end_time);
    $result = new WipResult();
    $result->setEndTime($result_end_time);
    $result->populateFromProcess($process);
    $this->assertEquals($result_end_time, $result->getEndTime());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \RuntimeException
   */
  public function testPopulateEndTimeMissing() {
    $process = new WipProcess();
    $result = new WipResult();
    $result->populateFromProcess($process);
    $result->getEndTime();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateEndTimeMissingAndAlreadySet() {
    $end_time = time() - mt_rand(1, 45);
    $process = new WipProcess();
    $result = new WipResult();
    $result->setEndTime($end_time);
    $result->populateFromProcess($process);
    $this->assertEquals($end_time, $result->getEndTime());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulatePid() {
    $pid = mt_rand(1, 1000);
    $process = new WipProcess();
    $process->setPid($pid);
    $result = new WipResult();
    $result->populateFromProcess($process);
    $this->assertEquals($pid, $result->getPid());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulatePidAlreadySet() {
    $process_pid = mt_rand(1, 1000);
    $result_pid = mt_rand(1001, 2000);
    $this->assertNotEquals($process_pid, $result_pid);
    $process = new WipProcess();
    $process->setPid($process_pid);
    $result = new WipResult();
    $result->setPid($result_pid);
    $result->populateFromProcess($process);
    $this->assertEquals($result_pid, $result->getPid());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \RuntimeException
   */
  public function testPopulatePidMissing() {
    $process = new WipProcess();
    $result = new WipResult();
    $result->populateFromProcess($process);
    $result->getPid();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulatePidMissingAndAlreadySet() {
    $pid = mt_rand(1, 1000);
    $process = new WipProcess();
    $result = new WipResult();
    $result->setPid($pid);
    $result->populateFromProcess($process);
    $this->assertEquals($pid, $result->getPid());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateLogLevel() {
    $log_level = WipLogLevel::ALERT;
    $process = new WipProcess();
    $process->setLogLevel($log_level);
    $result = new WipResult();
    $result->populateFromProcess($process);
    $this->assertEquals($log_level, $result->getLogLevel());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateLogLevelAlreadySet() {
    $process_log_level = WipLogLevel::ALERT;
    $result_log_level = WipLogLevel::FATAL;
    $this->assertNotEquals($process_log_level, $result_log_level);
    $process = new WipProcess();
    $process->setLogLevel($process_log_level);
    $result = new WipResult();
    $result->setLogLevel($result_log_level);
    $result->populateFromProcess($process);
    $this->assertEquals($result_log_level, $result->getLogLevel());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateLogLevelMissing() {
    $process = new WipProcess();
    $result = new WipResult();
    $result->populateFromProcess($process);
    $this->assertEquals(WipResult::DEFAULT_LOG_LEVEL, $result->getLogLevel());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateLogLevelMissingAndAlreadySet() {
    $log_level = WipLogLevel::ERROR;
    $process = new WipProcess();
    $result = new WipResult();
    $result->setLogLevel($log_level);
    $result->populateFromProcess($process);
    $this->assertEquals($log_level, $result->getLogLevel());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateExitMessage() {
    $exit_message = 'exit message';
    $process = new WipProcess();
    $process->setExitMessage($exit_message);
    $result = new WipResult();
    $result->populateFromProcess($process);
    $this->assertEquals($exit_message, $result->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateExitMessageAlreadySet() {
    $process_message = 'process message';
    $result_message = 'result message';
    $process = new WipProcess();
    $process->setExitMessage($process_message);
    $result = new WipResult();
    $result->setExitMessage($result_message);
    $result->populateFromProcess($process);
    $this->assertEquals($result_message, $result->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateExitMessageMissing() {
    $process = new WipProcess();
    $result = new WipResult();
    $result->populateFromProcess($process);
    $result->getExitMessage();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateExitMessageMissingAndAlreadySet() {
    $result_message = 'result message';
    $process = new WipProcess();
    $result = new WipResult();
    $result->setExitMessage($result_message);
    $result->populateFromProcess($process);
    $this->assertEquals($result_message, $result->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateWipId() {
    $wip_id = mt_rand(1, PHP_INT_MAX);
    $process = new WipProcess();
    $process->setWipId($wip_id);
    $result = new WipResult();
    $result->populateFromProcess($process);
    $this->assertEquals($wip_id, $result->getWipId());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateWipIdAlreadySet() {
    $process_id = mt_rand(1, PHP_INT_MAX);
    do {
      $result_id = mt_rand(1, PHP_INT_MAX);
    } while ($result_id == $process_id);
    $this->assertNotEquals($process_id, $result_id);
    $process = new WipProcess();
    $process->setWipId($process_id);
    $result = new WipResult();
    $result->setWipId($result_id);
    $result->populateFromProcess($process);
    $this->assertEquals($result_id, $result->getWipId());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \RuntimeException
   */
  public function testPopulateWipIdMissing() {
    $process = new WipProcess();
    $result = new WipResult();
    $result->populateFromProcess($process);
    $result->getWipId();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateWipIdMissingAndAlreadySet() {
    $result_id = mt_rand(1, PHP_INT_MAX);
    $process = new WipProcess();
    $result = new WipResult();
    $result->setWipId($result_id);
    $result->populateFromProcess($process);
    $this->assertEquals($result_id, $result->getWipId());
  }

  /**
   * Missing summary.
   */
  private function createObject() {
    $result = new \stdClass();
    $result->pid = mt_rand(1, PHP_INT_MAX);
    $result->wipId = mt_rand(1, PHP_INT_MAX);
    $result->startTime = time() - mt_rand(1, 45);
    $result->endTime = time();
    $result->exitCode = 0;
    return $result;
  }

  /**
   * Ensure that we can set this object as secure.
   *
   * @group Wip
   * @group WipProcess
   */
  public function testSetSecure() {
    $process = new WipProcess();
    $process->setSecure(TRUE);
    $this->assertTrue($process->isSecure());
  }

}
