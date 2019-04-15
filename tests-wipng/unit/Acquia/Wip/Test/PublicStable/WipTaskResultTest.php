<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\Task;
use Acquia\Wip\TaskExitStatus;
use Acquia\Wip\Test\Integration\PublicStable\AcquiaCloud\AcquiaCloudTestSetup;
use Acquia\Wip\WipLogLevel;
use Acquia\Wip\WipTaskResult;

/**
 * Test the WIP task results.
 */
class WipTaskResultTest extends \PHPUnit_Framework_TestCase {

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testEnvironment() {
    // @var WipTaskResultInterface $result.
    $result = new WipTaskResult();
    $environment = AcquiaCloudTestSetup::getEnvironment();
    $result->setEnvironment($environment);
    $retrieved_environment = $result->getEnvironment();
    $this->assertEquals($environment, $retrieved_environment);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \RuntimeException
   */
  public function testSetEnvironmentSetTwice() {
    // @var WipTaskResultInterface $result.
    $result = new WipTaskResult();
    $environment = AcquiaCloudTestSetup::getEnvironment();
    $result->setEnvironment($environment);
    $environment2 = AcquiaCloudTestSetup::getEnvironment();
    $result->setEnvironment($environment2);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testExitCode() {
    $exit_code = 3;
    $result = new WipTaskResult();
    $result->setExitCode($exit_code);
    $this->assertEquals($exit_code, $result->getExitCode());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \RuntimeException
   */
  public function testSetExitCodeTwice() {
    $exit_code = 3;
    $result = new WipTaskResult();
    $result->setExitCode($exit_code);
    $result->setExitCode($exit_code);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetExitCodeWrongType() {
    $exit_code = 'wrongtype';
    $result = new WipTaskResult();
    $result->setExitCode($exit_code);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testSuccessExitCodes() {
    $exit_codes = array(1, 2, 3);
    $result = new WipTaskResult();
    $result->setSuccessExitCodes($exit_codes);
    $this->assertEquals($exit_codes, $result->getSuccessExitCodes());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSuccessExitCodesWrongType() {
    $exit_codes = 'wrongtype';
    $result = new WipTaskResult();
    $result->setSuccessExitCodes($exit_codes);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSuccessExitCodesArrayIncludingWrongType() {
    $exit_codes = array(1, 2, 'wrongtype', 4);
    $result = new WipTaskResult();
    $result->setSuccessExitCodes($exit_codes);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testSuccessExitCodesTwice() {
    $exit_codes_1 = array(1, 2, 3);
    $exit_codes_2 = array(4, 5, 6);
    $result = new WipTaskResult();
    $result->setSuccessExitCodes($exit_codes_1);
    $this->assertEquals($exit_codes_1, $result->getSuccessExitCodes());

    $result->setSuccessExitCodes($exit_codes_2);
    $this->assertEquals($exit_codes_2, $result->getSuccessExitCodes());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testAddSuccessExitCode() {
    $exit_codes = array(1, 2, 3);

    $result = new WipTaskResult();
    $result->setSuccessExitCodes($exit_codes);
    $this->assertEquals($exit_codes, $result->getSuccessExitCodes());

    $new_exit_code = 15;
    $exit_codes[] = $new_exit_code;
    $result->addSuccessExitCode($new_exit_code);
    $this->assertEquals($exit_codes, $result->getSuccessExitCodes());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \InvalidArgumentException
   */
  public function testAddSuccessExitCodeWrongType() {
    $exit_codes = array(1, 2, 3);

    $result = new WipTaskResult();
    $result->setSuccessExitCodes($exit_codes);
    $this->assertEquals($exit_codes, $result->getSuccessExitCodes());

    $new_exit_code = 'wrongtype';
    $result->addSuccessExitCode($new_exit_code);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testIsSuccessWhenSuccessful() {
    $exit_codes = array(1, 2, 3);
    $result = new WipTaskResult();
    $result->setSuccessExitCodes($exit_codes);
    $result->setExitCode($exit_codes[1]);
    $this->assertTrue($result->isSuccess());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testIsSuccessWhenNotSuccessful() {
    $exit_codes = array(1, 2, 3);
    $result = new WipTaskResult();
    $result->setSuccessExitCodes($exit_codes);
    $result->setExitCode(TaskExitStatus::COMPLETED);
    $this->assertFalse($result->isSuccess());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \RuntimeException
   */
  public function testIsSuccessBeforeExitCodeIsSet() {
    $exit_codes = array(1, 2, 3);
    $result = new WipTaskResult();
    $result->setSuccessExitCodes($exit_codes);
    $result->isSuccess();
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testStartTime() {
    $start_time = time();
    $result = new WipTaskResult();
    $result->setStartTime($start_time);
    $this->assertEquals($start_time, $result->getStartTime());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \RuntimeException
   */
  public function testGetStartTimeBeforeSet() {
    $result = new WipTaskResult();
    $result->getStartTime();
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \RuntimeException
   */
  public function testSetStartTimeTwice() {
    $start_time = time();
    $result = new WipTaskResult();
    $result->setStartTime($start_time);
    $result->setStartTime($start_time);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetStartTimeWrongType() {
    $start_time = 'wrongtype';
    $result = new WipTaskResult();
    $result->setStartTime($start_time);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testEndTime() {
    $end_time = time();
    $result = new WipTaskResult();
    $result->setEndTime($end_time);
    $this->assertEquals($end_time, $result->getEndTime());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \RuntimeException
   */
  public function testGetEndTimeBeforeSet() {
    $result = new WipTaskResult();
    $result->getEndTime();
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \RuntimeException
   */
  public function testSetEndTimeTwice() {
    $end_time = time();
    $result = new WipTaskResult();
    $result->setendTime($end_time);
    $result->setEndTime($end_time);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetEndTimeWrongType() {
    $end_time = 'wrongtype';
    $result = new WipTaskResult();
    $result->setEndTime($end_time);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testGetRuntime() {
    $now = time();
    $start_time = $now - mt_rand(1, $now);
    $end_time = $now;
    $result = new WipTaskResult();
    $result->setStartTime($start_time);
    $result->setEndTime($end_time);
    $this->assertEquals($end_time - $start_time, $result->getRuntime());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \RuntimeException
   */
  public function testGetRuntimeBeforeStartTimeIsSet() {
    $end_time = time();
    $result = new WipTaskResult();
    $result->setEndTime($end_time);
    $result->getRuntime();
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \RuntimeException
   */
  public function testGetRuntimeBeforeEndTimeIsSet() {
    $start_time = time();
    $result = new WipTaskResult();
    $result->setStartTime($start_time);
    $result->getRuntime();
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testPid() {
    $pid = 15;
    $result = new WipTaskResult();
    $result->setPid($pid);
    $this->assertEquals($pid, $result->getPid());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetPidWrongType() {
    $pid = 'wrongtype';
    $result = new WipTaskResult();
    $result->setPid($pid);
    $result->getPid();
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \RuntimeException
   */
  public function testSetPidTwice() {
    $pid = 15;
    $result = new WipTaskResult();
    $result->setPid($pid);
    $result->setPid($pid);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \RuntimeException
   */
  public function testGetPidBeforeSet() {
    $result = new WipTaskResult();
    $result->getPid();
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testLogLevel() {
    $log_level = WipLogLevel::ALERT;
    $result = new WipTaskResult();
    $result->setLogLevel($log_level);
    $this->assertEquals($log_level, $result->getLogLevel());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \InvalidArgumentException
   */
  public function testLogLevelInvalidValue() {
    $log_level = 45;
    $result = new WipTaskResult();
    $result->setLogLevel($log_level);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \InvalidArgumentException
   */
  public function testLogLevelWrongType() {
    $log_level = 'wrongtype';
    $result = new WipTaskResult();
    $result->setLogLevel($log_level);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testGetUniqueId() {
    $pid = 244;
    $result = new WipTaskResult();
    $result->setPid($pid);
    $this->assertNotEmpty($result->getUniqueId());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testGetUniqueIdIsUnique() {
    $pid_1 = 244;
    $pid_2 = 553;
    $result_1 = new WipTaskResult();
    $result_1->setPid($pid_1);
    $this->assertNotEmpty($result_1->getUniqueId());

    $result_2 = new WipTaskResult();
    $result_2->setPid($pid_2);
    $this->assertNotEmpty($result_2->getUniqueId());

    $this->assertNotEquals($result_1->getUniqueId(), $result_2->getUniqueId());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testGetUniqueIdSameForSimilarResultInstances() {
    $pid = 244;
    $result_1 = new WipTaskResult();
    $result_1->setPid($pid);
    $this->assertNotEmpty($result_1->getUniqueId());

    $result_2 = new WipTaskResult();
    $result_2->setPid($pid);
    $this->assertNotEmpty($result_2->getUniqueId());

    $this->assertEquals($result_1->getUniqueId(), $result_2->getUniqueId());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testFromObject() {
    $object = $this->createObject();
    $result = WipTaskResult::fromObject($object);
    $this->assertEquals($object->pid, $result->getPid());
    $this->assertEquals($object->exitCode, $result->getExitCode());
    $this->assertEquals($object->startTime, $result->getStartTime());
    $this->assertEquals($object->endTime, $result->getEndTime());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testFromObjectMissingPidWithResultParameter() {
    $result = new WipTaskResult();
    $object = $this->createObject();
    unset($object->pid);
    WipTaskResult::fromObject($object, $result);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testFromObjectWithResultParameterWithPidAlreadySet() {
    $result = new WipTaskResult();
    $object = $this->createObject();
    do {
      $pid = mt_rand(1, PHP_INT_MAX);
    } while ($pid == $object->pid);
    $result->setPid($pid);
    WipTaskResult::fromObject($object, $result);
    $this->assertEquals($pid, $result->getPid());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testFromObjectMissingExitCodeWithResultParameter() {
    $result = new WipTaskResult();
    $object = $this->createObject();
    unset($object->exitCode);
    WipTaskResult::fromObject($object, $result);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testFromObjectWithResultParameterWithExitCodeAlreadySet() {
    $result = new WipTaskResult();
    $object = $this->createObject();
    $result->setExitCode($object->exitCode);
    WipTaskResult::fromObject($object, $result);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testToJson() {
    $task_result = new WipTaskResult();
    $task_result->setStartTime(time());
    $task_result->setPid(15);
    $task_result->setEndTime(time() + 15);
    $task_result->setWipId(15);

    $object = $task_result->toObject();
    $result = WipTaskResult::fromObject($object);
    $json = $result->toJson();
    $json_result = WipTaskResult::fromObject(WipTaskResult::objectFromJson($json));
    $this->assertEquals($result, $json_result);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \InvalidArgumentException
   */
  public function testFromJsonNullDocument() {
    WipTaskResult::objectFromJson(NULL);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \InvalidArgumentException
   */
  public function testFromJsonInvalidDocument() {
    WipTaskResult::objectFromJson('invalidjson');
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testSetMessage() {
    $error_message = 'something bad happened.';
    $result = new WipTaskResult();
    $result->setExitMessage($error_message);
    $this->assertEquals($error_message, $result->getExitMessage());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetMessageBadType() {
    $error_message = new \stdClass();
    $result = new WipTaskResult();
    $result->setExitMessage($error_message);
    $this->assertEquals($error_message, $result->getExitMessage());
  }

  /**
   * Tesst setMessage twice.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \RuntimeException
   */
  public function testSetMessageTwice() {
    $error_message = 'error message';
    $result = new WipTaskResult();
    $result->setExitMessage($error_message);
    $result->setExitMessage($error_message);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testFromTask() {
    $id = mt_rand(1, PHP_INT_MAX);
    $exit_code = TaskExitStatus::COMPLETED;
    $exit_message = 'exit';
    $now = time();
    $start_time = $now - mt_rand(1, $now);
    $end_time = $now;
    $task = new Task();
    $task->setId($id);
    $task->setExitStatus($exit_code);
    $task->setExitMessage($exit_message);
    $task->setStartTimestamp($start_time);
    $task->setCompletedTimestamp($end_time);

    $result = WipTaskResult::fromTask($task);
    $this->assertEquals($id, $result->getPid());
    $this->assertEquals($exit_code, $result->getExitCode());
    $this->assertEquals($exit_message, $result->getExitMessage());
    $this->assertEquals($start_time, $result->getStartTime());
    $this->assertEquals($end_time, $result->getEndTime());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testFromTaskMissingPid() {
    $exit_code = TaskExitStatus::COMPLETED;
    $exit_message = 'exit';
    $now = time();
    $start_time = $now - mt_rand(1, 45);
    $end_time = $now;
    $task = new Task();
    $task->setExitStatus($exit_code);
    $task->setExitMessage($exit_message);
    $task->setStartTimestamp($start_time);
    $task->setCompletedTimestamp($end_time);

    $result = WipTaskResult::fromTask($task);
    $this->assertEquals($exit_code, $result->getExitCode());
    $this->assertEquals($exit_message, $result->getExitMessage());
    $this->assertEquals($start_time, $result->getStartTime());
    $this->assertEquals($end_time, $result->getEndTime());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testFromTaskMissingExitCode() {
    $id = mt_rand(1, PHP_INT_MAX);
    $exit_message = 'exit';
    $now = time();
    $start_time = $now - mt_rand(1, 45);
    $end_time = $now;
    $task = new Task();
    $task->setId($id);
    $task->setExitMessage($exit_message);
    $task->setStartTimestamp($start_time);
    $task->setCompletedTimestamp($end_time);

    $result = WipTaskResult::fromTask($task);
    $this->assertEquals($id, $result->getPid());
    $this->assertEquals($exit_message, $result->getExitMessage());
    $this->assertEquals($start_time, $result->getStartTime());
    $this->assertEquals($end_time, $result->getEndTime());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testFromTaskMissingExitMessage() {
    $id = mt_rand(1, PHP_INT_MAX);
    $exit_code = TaskExitStatus::COMPLETED;
    $now = time();
    $start_time = $now - mt_rand(1, 45);
    $end_time = $now;
    $task = new Task();
    $task->setId($id);
    $task->setExitStatus($exit_code);
    $task->setStartTimestamp($start_time);
    $task->setCompletedTimestamp($end_time);

    $result = WipTaskResult::fromTask($task);
    $this->assertEquals($id, $result->getPid());
    $this->assertEquals($exit_code, $result->getExitCode());
    $this->assertEquals($start_time, $result->getStartTime());
    $this->assertEquals($end_time, $result->getEndTime());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testFromTaskMissingStartTime() {
    $id = mt_rand(1, PHP_INT_MAX);
    $exit_code = TaskExitStatus::COMPLETED;
    $exit_message = 'exit';
    $end_time = time();
    $task = new Task();
    $task->setId($id);
    $task->setExitStatus($exit_code);
    $task->setExitMessage($exit_message);
    $task->setCompletedTimestamp($end_time);

    $result = WipTaskResult::fromTask($task);
    $this->assertEquals($id, $result->getPid());
    $this->assertEquals($exit_code, $result->getExitCode());
    $this->assertEquals($exit_message, $result->getExitMessage());
    $this->assertEquals($end_time, $result->getEndTime());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testFromTaskMissingEndTime() {
    $id = mt_rand(1, PHP_INT_MAX);
    $exit_code = TaskExitStatus::COMPLETED;
    $exit_message = 'exit';
    $now = time();
    $start_time = $now - mt_rand(1, 45);
    $task = new Task();
    $task->setId($id);
    $task->setExitStatus($exit_code);
    $task->setExitMessage($exit_message);
    $task->setStartTimestamp($start_time);

    $result = WipTaskResult::fromTask($task);
    $this->assertEquals($id, $result->getPid());
    $this->assertEquals($exit_code, $result->getExitCode());
    $this->assertEquals($exit_message, $result->getExitMessage());
    $this->assertEquals($start_time, $result->getStartTime());
  }

  /**
   * Creates an object.
   *
   * @return object
   *   A test object.
   */
  private function createObject() {
    $result = new \stdClass();
    $result->pid = mt_rand(1, PHP_INT_MAX);
    $result->wipId = $result->pid;
    $now = time();
    $result->startTime = $now - mt_rand(1, $now);
    $result->endTime = $now;
    $result->exitCode = 0;
    return $result;
  }

}
