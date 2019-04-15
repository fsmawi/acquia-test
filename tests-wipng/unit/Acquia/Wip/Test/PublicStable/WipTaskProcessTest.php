<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\Task;
use Acquia\Wip\TaskExitStatus;
use Acquia\Wip\TaskStatus;
use Acquia\Wip\Test\Integration\PublicStable\AcquiaCloud\AcquiaCloudTestSetup;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogLevel;
use Acquia\Wip\WipProcessInterface;
use Acquia\Wip\WipResult;
use Acquia\Wip\WipTaskProcess;
use Acquia\Wip\WipTaskResult;

/**
 * Tests the Wip task processing.
 */
class WipTaskProcessTest extends \PHPUnit_Framework_TestCase {

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testDescription() {
    $description = 'This is the process description.';
    $process = new WipTaskProcess($this->createTask());
    $process->setDescription($description);
    $this->assertEquals($description, $process->getDescription());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \RuntimeException
   */
  public function testDescriptionTwice() {
    $description = 'This is the process description.';
    $process = new WipTaskProcess($this->createTask());
    $process->setDescription($description);
    $process->setDescription($description);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \InvalidArgumentException
   */
  public function testDescriptionBadType() {
    $description = new \stdClass();
    $process = new WipTaskProcess($this->createTask());
    $process->setDescription($description);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testEnvironment() {
    // @var WipProcessInterface $process.
    $process = new WipTaskProcess($this->createTask());
    $environment = AcquiaCloudTestSetup::getEnvironment();
    $process->setEnvironment($environment);
    $retrieved_environment = $process->getEnvironment();
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
    // @var WipProcessInterface $process.
    $process = new WipTaskProcess($this->createTask());
    $environment = AcquiaCloudTestSetup::getEnvironment();
    $process->setEnvironment($environment);
    $environment2 = AcquiaCloudTestSetup::getEnvironment();
    $process->setEnvironment($environment2);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testExitCode() {
    $exit_code = 3;
    $process = new WipTaskProcess($this->createTask());
    $process->setExitCode($exit_code);
    $this->assertEquals($exit_code, $process->getExitCode());
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
    $process = new WipTaskProcess($this->createTask());
    $process->setExitCode($exit_code);
    $process->setExitCode($exit_code);
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
    $process = new WipTaskProcess($this->createTask());
    $process->setExitCode($exit_code);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testSuccessExitCodes() {
    $exit_codes = array(1, 2, 3);
    $process = new WipTaskProcess($this->createTask());
    $process->setSuccessExitCodes($exit_codes);
    $this->assertEquals($exit_codes, $process->getSuccessExitCodes());
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
    $process = new WipTaskProcess($this->createTask());
    $process->setSuccessExitCodes($exit_codes);
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
    $process = new WipTaskProcess($this->createTask());
    $process->setSuccessExitCodes($exit_codes);
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
    $process = new WipTaskProcess($this->createTask());
    $process->setSuccessExitCodes($exit_codes_1);
    $this->assertEquals($exit_codes_1, $process->getSuccessExitCodes());

    $process->setSuccessExitCodes($exit_codes_2);
    $this->assertEquals($exit_codes_2, $process->getSuccessExitCodes());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testAddSuccessExitCode() {
    $exit_codes = array(1, 2, 3);

    $process = new WipTaskProcess($this->createTask());
    $process->setSuccessExitCodes($exit_codes);
    $this->assertEquals($exit_codes, $process->getSuccessExitCodes());

    $new_exit_code = 15;
    $exit_codes[] = $new_exit_code;
    $process->addSuccessExitCode($new_exit_code);
    $this->assertEquals($exit_codes, $process->getSuccessExitCodes());
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

    try {
      $process = new WipTaskProcess($this->createTask());
      $process->setSuccessExitCodes($exit_codes);
      $this->assertEquals($exit_codes, $process->getSuccessExitCodes());
    } catch (\Exception $e) {
      $this->fail('Failed to set up test.');
    }
    $new_exit_code = 'wrongtype';
    $process->addSuccessExitCode($new_exit_code);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testStartTime() {
    $start_time = time();
    $process = new WipTaskProcess($this->createTask());
    $process->setStartTime($start_time);
    $this->assertEquals($start_time, $process->getStartTime());
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
    $process = new WipTaskProcess($this->createTask());
    $process->getStartTime();
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
    $process = new WipTaskProcess($this->createTask());
    $process->setStartTime($start_time);
    $process->setStartTime($start_time);
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
    $process = new WipTaskProcess($this->createTask());
    $process->setStartTime($start_time);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testEndTime() {
    $end_time = time();
    $process = new WipTaskProcess($this->createTask());
    $process->setEndTime($end_time);
    $this->assertEquals($end_time, $process->getEndTime());
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
    $process = new WipTaskProcess($this->createTask());
    $process->getEndTime();
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
    $process = new WipTaskProcess($this->createTask());
    $process->setendTime($end_time);
    $process->setEndTime($end_time);
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
    $process = new WipTaskProcess($this->createTask());
    $process->setEndTime($end_time);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testGetRuntime() {
    $now = time();
    $start_time = $now - mt_rand(1, 35);
    $end_time = $now;
    $process = new WipTaskProcess($this->createTask());
    $process->setStartTime($start_time);
    $process->setEndTime($end_time);
    $this->assertEquals($end_time - $start_time, $process->getRuntime());
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
    $process = new WipTaskProcess($this->createTask());
    $process->setEndTime($end_time);
    $process->getRuntime();
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
    $process = new WipTaskProcess($this->createTask());
    $process->setStartTime($start_time);
    $process->getRuntime();
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testLogLevel() {
    $log_level = WipLogLevel::ALERT;
    $process = new WipTaskProcess($this->createTask());
    $process->setLogLevel($log_level);
    $this->assertEquals($log_level, $process->getLogLevel());
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
    $process = new WipTaskProcess($this->createTask());
    $process->setLogLevel($log_level);
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
    $process = new WipTaskProcess($this->createTask());
    $process->setLogLevel($log_level);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testGetUniqueId() {
    $process = new WipTaskProcess($this->createTask());
    $this->assertNotEmpty($process->getUniqueId());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testGetUniqueIdIsUnique() {
    $task_1 = $this->createTask();
    $process_1 = new WipTaskProcess($task_1);
    $this->assertNotEmpty($process_1->getUniqueId());

    // Make sure we have different IDs.
    do {
      $task_2 = $this->createTask();
    } while ($task_1->getId() == $task_2->getId());

    $process_2 = new WipTaskProcess($task_2);
    $this->assertNotEmpty($process_2->getUniqueId());

    $this->assertNotEquals($process_1->getUniqueId(), $process_2->getUniqueId());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testGetResult() {
    $wip_log = new WipLog();
    $process = new WipTaskProcess($this->createTask());
    $result = new WipTaskResult();
    $process->setResult($result);
    $this->assertEquals($result, $process->getResult($wip_log));
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \RuntimeException
   */
  public function testSetResultTwice() {
    $process = new WipTaskProcess($this->createTask());
    $process->setResult(new WipTaskResult());
    $process->setResult(new WipTaskResult());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetResultWrongType() {
    $process = new WipTaskProcess($this->createTask());
    $process->setResult(new WipResult());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testGetResultNotSetFetchIsFalseTaskCompleted() {
    $wip_log = new WipLog();
    $task = $this->createTask();
    $process = new WipTaskProcess($task);
    $task->setStatus(TaskStatus::COMPLETE);
    $task->setExitStatus(TaskExitStatus::COMPLETED);
    $now = time();
    $task->setStartTimestamp($now - mt_rand(1, 1000));
    $task->setCompletedTimestamp($now);
    $wip_pool_store = WipFactory::getObject('acquia.wip.storage.wippool');
    $wip_pool_store->save($task);
    $result = $process->getResult($wip_log);
    $this->assertEmpty($result);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testGetResultNotSetFetchIsTrueTaskCompleted() {
    $wip_log = new WipLog();
    $task = $this->createTask();
    $process = new WipTaskProcess($task);
    $task->setStatus(TaskStatus::COMPLETE);
    $task->setExitStatus(TaskExitStatus::COMPLETED);
    $now = time();
    $task->setStartTimestamp($now - mt_rand(1, 1000));
    $task->setCompletedTimestamp($now);
    $wip_pool_store = WipFactory::getObject('acquia.wip.storage.wippool');
    $wip_pool_store->save($task);
    $result = $process->getResult($wip_log, TRUE);
    $this->assertNotEmpty($result);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testGetResultNotSetFetchIsTrueTaskCompletedNoStartTimeSet() {
    $wip_log = new WipLog();
    $task = $this->createTask();
    $process = new WipTaskProcess($task);
    $task->setStatus(TaskStatus::COMPLETE);
    $task->setExitStatus(TaskExitStatus::COMPLETED);
    $task->setCompletedTimestamp(time());
    $wip_pool_store = WipFactory::getObject('acquia.wip.storage.wippool');
    $wip_pool_store->save($task);
    $result = $process->getResult($wip_log, TRUE);
    $this->assertNotEmpty($result);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testGetResultNotSetFetchIsTrueTaskCompletedNoEndTimeSet() {
    $wip_log = new WipLog();
    $task = $this->createTask();
    $process = new WipTaskProcess($task);
    $task->setStatus(TaskStatus::COMPLETE);
    $task->setExitStatus(TaskExitStatus::COMPLETED);
    $task->setStartTimestamp(time() - mt_rand(1, 1000));
    $wip_pool_store = WipFactory::getObject('acquia.wip.storage.wippool');
    $wip_pool_store->save($task);
    $result = $process->getResult($wip_log, TRUE);
    $this->assertNotEmpty($result);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testGetResultNotSetFetchIsTrueTaskCompletedNoStartOrEndTimeSet() {
    $wip_log = new WipLog();
    $task = $this->createTask();
    $process = new WipTaskProcess($task);
    $task->setStatus(TaskStatus::COMPLETE);
    $task->setExitStatus(TaskExitStatus::COMPLETED);
    $wip_pool_store = WipFactory::getObject('acquia.wip.storage.wippool');
    $wip_pool_store->save($task);
    $result = $process->getResult($wip_log, TRUE);
    $this->assertNotEmpty($result);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testHasCompletedNoResultSet() {
    $wip_log = new WipLog();
    $task = $this->createTask();
    $wip_pool_store = WipFactory::getObject('acquia.wip.storage.wippool');
    $wip_pool_store->save($task);
    $process = new WipTaskProcess($task);
    $this->assertFalse($process->hasCompleted($wip_log));
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testHasCompletedWithResultSet() {
    $wip_log = new WipLog();
    $process = new WipTaskProcess($this->createTask());
    $process->setResult(new WipTaskResult());
    $this->assertTrue($process->hasCompleted($wip_log));
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testKillProcessNotCompleted() {
    $wip_log = new WipLog();
    $task = $this->createTask();
    $wip_pool_store = WipFactory::getObject('acquia.wip.storage.wippool');
    $wip_pool_store->save($task);
    $process = new WipTaskProcess($task);
    $this->assertTrue($process->kill($wip_log));
    $this->assertTrue($process->hasCompleted($wip_log));
    $result = $process->getResult($wip_log);
    $this->assertNotEmpty($result);
    $this->assertFalse($result->isSuccess());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testKillProcessCompleted() {
    $wip_log = new WipLog();
    $process = new WipTaskProcess($this->createTask());
    $process->setResult(new WipTaskResult());
    $this->assertTrue($process->kill($wip_log));
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testRelease() {
    $process = new WipTaskProcess($this->createTask());
    $process->release(new WipLog());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testPopulateEnvironment() {
    $result = new WipTaskResult();
    $process = new WipTaskProcess($this->createTask());
    $environment = AcquiaCloudTestSetup::getEnvironment();
    $result->setEnvironment($environment);
    $process->populateFromResult($result);
    $this->assertEquals($environment, $process->getEnvironment());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testPopulateEnvironmentWithMissingEnvironment() {
    $result = new WipTaskResult();
    $process = new WipTaskProcess($this->createTask());
    $process->populateFromResult($result);
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testPopulateEnvironmentWithEnvironmentAlreadySet() {
    $result = new WipTaskResult();
    $process = new WipTaskProcess($this->createTask());
    $environment1 = AcquiaCloudTestSetup::getEnvironment();
    $environment2 = AcquiaCloudTestSetup::getEnvironment();
    $environment2->setServers(array_merge($environment2->getServers(), array('wakaflocak')));
    $result->setEnvironment($environment1);
    $process->setEnvironment($environment2);
    $process->populateFromResult($result);
    $this->assertNotEquals($environment1, $process->getEnvironment());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testPopulateEnvironmentMissingEnvironmentAndAlreadySet() {
    $result = new WipTaskResult();
    $process = new WipTaskProcess($this->createTask());
    $environment = AcquiaCloudTestSetup::getEnvironment();
    $process->setEnvironment($environment);
    $process->populateFromResult($result);
    $this->assertEquals($environment, $process->getEnvironment());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testPopulateExitCodeFromProcess() {
    $exit_code = 4;
    $result = new WipTaskResult();
    $process = new WipTaskProcess($this->createTask());
    $result->setExitCode($exit_code);
    $process->populateFromResult($result);
    $this->assertEquals($exit_code, $process->getExitCode());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testPopulateExitCodeFromProcessMissingExitCode() {
    $result = new WipTaskResult();
    $process = new WipTaskProcess($this->createTask());
    $process->populateFromResult($result);
    $this->assertNull($process->getExitCode());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testPopulateExitCodeFromProcessWithExitCodeAlreadySet() {
    $exit_code = TaskExitStatus::COMPLETED;
    $result = new WipTaskResult();
    $result->setExitCode(TaskExitStatus::WARNING);
    $process = new WipTaskProcess($this->createTask());
    $process->setExitCode($exit_code);
    $process->populateFromResult($result);
    $this->assertEquals($exit_code, $process->getExitCode());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testPopulateExitCodeFromProcessMissingExitCodeAndAlreadySet() {
    $exit_code = 4;
    $result = new WipTaskResult();
    $process = new WipTaskProcess($this->createTask());
    $process->setExitCode($exit_code);
    $process->populateFromResult($result);
    $this->assertEquals($exit_code, $process->getExitCode());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testPopulateSuccessCodes() {
    $success_codes = array(4, 8, 12, 16);
    $process = new WipTaskProcess($this->createTask());
    $result = new WipTaskResult();
    $result->setSuccessExitCodes($success_codes);
    $process->populateFromResult($result);
    $this->assertEquals($success_codes, $process->getSuccessExitCodes());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testPopulateSuccessCodesAlreadySet() {
    $process_success_codes = array(1, 2, 3, 4);
    $result_success_codes = array(4, 8, 12, 16);
    $process = new WipTaskProcess($this->createTask());
    $process->setSuccessExitCodes($process_success_codes);
    $result = new WipTaskResult();
    $result->setSuccessExitCodes($result_success_codes);
    $process->populateFromResult($result);
    $this->assertEquals($process_success_codes, $process->getSuccessExitCodes());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testPopulateSuccessCodesMissingAndAlreadySet() {
    $process_success_codes = array(4, 8, 12, 16);
    $process = new WipTaskProcess($this->createTask());
    $result = new WipTaskResult();
    $process->setSuccessExitCodes($process_success_codes);
    $process->populateFromResult($result);
    $this->assertEquals($process_success_codes, $process->getSuccessExitCodes());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testPopulateStartTime() {
    $start_time = time() - mt_rand(1, 45);
    $process = new WipTaskProcess($this->createTask());
    $result = new WipTaskResult();
    $result->setStartTime($start_time);
    $process->populateFromResult($result);
    $this->assertEquals($start_time, $process->getStartTime());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testPopulateStartTimeAlreadySet() {
    $now = time();
    $process_start_time = $now - mt_rand(1, 45);
    $result_start_time = $now - mt_rand(46, 100);
    $this->assertNotEquals($process_start_time, $result_start_time);
    $process = new WipTaskProcess($this->createTask());
    $process->setStartTime($process_start_time);
    $result = new WipTaskResult();
    $result->setStartTime($result_start_time);
    $process->populateFromResult($result);
    $this->assertEquals($process_start_time, $process->getStartTime());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \RuntimeException
   */
  public function testPopulateStartTimeMissing() {
    $process = new WipTaskProcess($this->createTask());
    $result = new WipTaskResult();
    $process->populateFromResult($result);
    $process->getStartTime();
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testPopulateStartTimeMissingAndAlreadySet() {
    $start_time = time() - mt_rand(1, 45);
    $process = new WipTaskProcess($this->createTask());
    $result = new WipTaskResult();
    $process->setStartTime($start_time);
    $process->populateFromResult($result);
    $this->assertEquals($start_time, $process->getStartTime());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testPopulateEndTime() {
    $end_time = time() - mt_rand(1, 45);
    $process = new WipTaskProcess($this->createTask());
    $result = new WipTaskResult();
    $result->setEndTime($end_time);
    $process->populateFromResult($result);
    $this->assertEquals($end_time, $process->getEndTime());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testPopulateEndTimeAlreadySet() {
    $now = time();
    $process_end_time = $now - mt_rand(1, 45);
    $result_end_time = $now - mt_rand(46, 100);
    $this->assertNotEquals($process_end_time, $result_end_time);
    $process = new WipTaskProcess($this->createTask());
    $process->setEndTime($process_end_time);
    $result = new WipTaskResult();
    $result->setEndTime($result_end_time);
    $process->populateFromResult($result);
    $this->assertEquals($process_end_time, $process->getEndTime());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   *
   * @expectedException \RuntimeException
   */
  public function testPopulateEndTimeMissing() {
    $process = new WipTaskProcess($this->createTask());
    $result = new WipTaskResult();
    $process->populateFromResult($result);
    $result->getEndTime();
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testPopulateEndTimeMissingAndAlreadySet() {
    $end_time = time() - mt_rand(1, 45);
    $process = new WipTaskProcess($this->createTask());
    $result = new WipTaskResult();
    $process->setEndTime($end_time);
    $process->populateFromResult($result);
    $this->assertEquals($end_time, $process->getEndTime());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testPopulateLogLevel() {
    $log_level = WipLogLevel::ALERT;
    $process = new WipTaskProcess($this->createTask());
    $result = new WipTaskResult();
    $result->setLogLevel($log_level);
    $process->populateFromResult($result);
    $this->assertEquals($log_level, $process->getLogLevel());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testPopulateLogLevelAlreadySet() {
    $process_log_level = WipLogLevel::ALERT;
    $result_log_level = WipLogLevel::FATAL;
    $this->assertNotEquals($process_log_level, $result_log_level);
    $process = new WipTaskProcess($this->createTask());
    $process->setLogLevel($process_log_level);
    $result = new WipTaskResult();
    $result->setLogLevel($result_log_level);
    $process->populateFromResult($result);
    $this->assertEquals($process_log_level, $process->getLogLevel());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testPopulateLogLevelMissing() {
    $process = new WipTaskProcess($this->createTask());
    $result = new WipTaskResult();
    $process->populateFromResult($result);
    $this->assertEquals(WipTaskProcess::DEFAULT_LOG_LEVEL, $process->getLogLevel());
  }

  /**
   * Test.
   *
   * @group Wip
   * @group WipTask
   */
  public function testPopulateLogLevelMissingAndAlreadySet() {
    $log_level = WipLogLevel::ERROR;
    $process = new WipTaskProcess($this->createTask());
    $result = new WipTaskResult();
    $process->setLogLevel($log_level);
    $process->populateFromResult($result);
    $this->assertEquals($log_level, $process->getLogLevel());
  }

  /**
   * Test.
   */
  private function createTask() {
    $result = new Task();
    $result->setId(mt_rand(1, PHP_INT_MAX));
    return $result;
  }

}
