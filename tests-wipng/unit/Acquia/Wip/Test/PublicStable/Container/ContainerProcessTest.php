<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\Container\ContainerProcess;
use Acquia\Wip\Container\ContainerProcessInterface;
use Acquia\Wip\Container\ContainerResult;
use Acquia\Wip\Container\NullContainer;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\Test\Integration\PublicStable\AcquiaCloud\AcquiaCloudTestSetup;
use Acquia\Wip\Test\Utility\DataProviderTrait;
use Acquia\Wip\WipLogLevel;
use Acquia\Wip\WipResult;

/**
 * Missing summary.
 */
class ContainerProcessTest extends \PHPUnit_Framework_TestCase {

  use DataProviderTrait;

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testDescription() {
    $description = 'This is the process description.';
    $process = new ContainerProcess();
    $process->setDescription($description);
    $this->assertEquals($description, $process->getDescription());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \RuntimeException
   */
  public function testDescriptionTwice() {
    $description = 'This is the process description.';
    $process = new ContainerProcess();
    $process->setDescription($description);
    $process->setDescription($description);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   */
  public function testDescriptionBadType() {
    $description = new \stdClass();
    $process = new ContainerProcess();
    $process->setDescription($description);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   */
  public function testDescriptionEmpty() {
    $description = '';
    $process = new ContainerProcess();
    $process->setDescription($description);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testEnvironment() {
    $process = new ContainerProcess();
    $environment = AcquiaCloudTestSetup::getEnvironment();
    $process->setEnvironment($environment);
    $retrieved_environment = $process->getEnvironment();
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
    $process = new ContainerProcess();
    $environment = AcquiaCloudTestSetup::getEnvironment();
    $process->setEnvironment($environment);
    $environment2 = AcquiaCloudTestSetup::getEnvironment();
    $process->setEnvironment($environment2);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testExitCode() {
    $exit_code = 3;
    $process = new ContainerProcess();
    $process->setExitCode($exit_code);
    $this->assertEquals($exit_code, $process->getExitCode());
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
    $process = new ContainerProcess();
    $process->setExitCode($exit_code);
    $process->setExitCode($exit_code);
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
    $process = new ContainerProcess();
    $process->setExitCode($exit_code);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testExitMessage() {
    $message = 'message';
    $process = new ContainerProcess();
    $process->setExitMessage($message);
    $this->assertEquals($message, $process->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   */
  public function testExitMessageWrongType() {
    $message = 1;
    $process = new ContainerProcess();
    $process->setExitMessage($message);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   */
  public function testExitMessageNull() {
    $message = NULL;
    $process = new ContainerProcess();
    $process->setExitMessage($message);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \RuntimeException
   */
  public function testExitMessageAlreadySet() {
    $first_message = 'one';
    $second_message = 'two';
    $process = new ContainerProcess();
    $process->setExitMessage($first_message);
    $process->setExitMessage($second_message);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testSuccessExitCodes() {
    $exit_codes = array(1, 2, 3);
    $process = new ContainerProcess();
    $process->setSuccessExitCodes($exit_codes);
    $this->assertEquals($exit_codes, $process->getSuccessExitCodes());
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
    $process = new ContainerProcess();
    $process->setSuccessExitCodes($exit_codes);
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
    $process = new ContainerProcess();
    $process->setSuccessExitCodes($exit_codes);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testSuccessExitCodesTwice() {
    $exit_codes_1 = array(1, 2, 3);
    $exit_codes_2 = array(4, 5, 6);
    $process = new ContainerProcess();
    $process->setSuccessExitCodes($exit_codes_1);
    $this->assertEquals($exit_codes_1, $process->getSuccessExitCodes());

    $process->setSuccessExitCodes($exit_codes_2);
    $this->assertEquals($exit_codes_2, $process->getSuccessExitCodes());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testAddSuccessExitCode() {
    $exit_codes = array(1, 2, 3);

    $process = new ContainerProcess();
    $process->setSuccessExitCodes($exit_codes);
    $this->assertEquals($exit_codes, $process->getSuccessExitCodes());

    $new_exit_code = 15;
    $exit_codes[] = $new_exit_code;
    $process->addSuccessExitCode($new_exit_code);
    $this->assertEquals($exit_codes, $process->getSuccessExitCodes());
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

    $process = new ContainerProcess();
    $process->setSuccessExitCodes($exit_codes);
    $this->assertEquals($exit_codes, $process->getSuccessExitCodes());

    $new_exit_code = 'wrongtype';
    $process->addSuccessExitCode($new_exit_code);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testStartTime() {
    $start_time = time();
    $process = new ContainerProcess();
    $process->setStartTime($start_time);
    $this->assertEquals($start_time, $process->getStartTime());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \RuntimeException
   */
  public function testGetStartTimeBeforeSet() {
    $process = new ContainerProcess();
    $process->getStartTime();
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
    $process = new ContainerProcess();
    $process->setStartTime($start_time);
    $process->setStartTime($start_time);
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
    $process = new ContainerProcess();
    $process->setStartTime($start_time);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetStartTimeZero() {
    $start_time = 0;
    $process = new ContainerProcess();
    $process->setStartTime($start_time);
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
    $process = new ContainerProcess();
    $process->setStartTime($start_time);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testEndTime() {
    $end_time = time();
    $process = new ContainerProcess();
    $process->setEndTime($end_time);
    $this->assertEquals($end_time, $process->getEndTime());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \RuntimeException
   */
  public function testGetEndTimeBeforeSet() {
    $process = new ContainerProcess();
    $process->getEndTime();
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
    $process = new ContainerProcess();
    $process->setendTime($end_time);
    $process->setEndTime($end_time);
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
    $process = new ContainerProcess();
    $process->setEndTime($end_time);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetEndTimeZero() {
    $end_time = 0;
    $process = new ContainerProcess();
    $process->setEndTime($end_time);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetEndTimeNegative() {
    $end_time = -1;
    $process = new ContainerProcess();
    $process->setEndTime($end_time);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testGetRuntime() {
    $start_time = time() - mt_rand(1, 35);
    $end_time = time();
    $process = new ContainerProcess();
    $process->setStartTime($start_time);
    $process->setEndTime($end_time);
    $this->assertEquals($end_time - $start_time, $process->getRuntime());
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
    $process = new ContainerProcess();
    $process->setEndTime($end_time);
    $process->getRuntime();
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
    $process = new ContainerProcess();
    $process->setStartTime($start_time);
    $process->getRuntime();
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPid() {
    $pid = 'pid';
    $process = new ContainerProcess();
    $process->setPid($pid);
    $this->assertEquals($pid, $process->getPid());
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
    $process = new ContainerProcess();
    $process->setPid($pid);
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
    $process = new ContainerProcess();
    $process->setPid($pid);
    $process->setPid($pid);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \RuntimeException
   */
  public function testGetPidBeforeSet() {
    $process = new ContainerProcess();
    $process->getPid();
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testSetWipid() {
    $id = mt_rand(1, PHP_INT_MAX);
    $process = new ContainerProcess();
    $process->setWipId($id);
    $this->assertEquals($id, $process->getWipId());
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
    $process = new ContainerProcess();
    $process->setWipId($id);
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
    $process = new ContainerProcess();
    $process->setWipId($id);
    $process->setWipId($id);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \RuntimeException
   */
  public function testGetWipIdBeforeSet() {
    $process = new ContainerProcess();
    $process->getWipId();
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testLogLevel() {
    $log_level = WipLogLevel::ALERT;
    $process = new ContainerProcess();
    $process->setLogLevel($log_level);
    $this->assertEquals($log_level, $process->getLogLevel());
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
    $process = new ContainerProcess();
    $process->setLogLevel($log_level);
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
    $process = new ContainerProcess();
    $process->setLogLevel($log_level);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testGetUniqueId() {
    $pid = 'pid';
    $start_time = time();
    $process = new ContainerProcess();
    $process->setPid($pid);
    $process->setStartTime($start_time);
    $this->assertSame(ContainerResult::createUniqueId($pid, $start_time), $process->getUniqueId());
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
    $process_1 = new ContainerProcess();
    $process_1->setPid($pid_1);
    $process_1->setStartTime($start_time);
    $this->assertNotEmpty($process_1->getUniqueId());

    $process_2 = new ContainerProcess();
    $process_2->setPid($pid_2);
    $process_2->setStartTime($start_time);
    $this->assertNotEmpty($process_2->getUniqueId());

    $this->assertNotEquals($process_1->getUniqueId(), $process_2->getUniqueId());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testGetUniqueIdSameForSimilarResultInstances() {
    $pid = 'pid';
    $process_1 = new ContainerProcess();
    $process_1->setPid($pid);
    $process_1->setStartTime(time());
    $this->assertNotEmpty($process_1->getUniqueId());

    $process_2 = new ContainerProcess();
    $process_2->setPid($pid);
    $process_2->setStartTime(time());
    $this->assertNotEmpty($process_2->getUniqueId());

    $this->assertEquals($process_1->getUniqueId(), $process_2->getUniqueId());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testGetResult() {
    $wip_log = new WipLog();
    $process = new ContainerProcess();
    $result = new ContainerResult();
    $process->setResult($result);
    $this->assertEquals($result, $process->getResult($wip_log));
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \RuntimeException
   */
  public function testSetResultTwice() {
    $process = new ContainerProcess();
    $process->setResult(new ContainerResult());
    $process->setResult(new ContainerResult());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetResultWrongType() {
    $process = new ContainerProcess();
    $process->setResult(new WipResult());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testHasCompletedNoResultSet() {
    $wip_log = new WipLog();
    $process = new ContainerProcess();
    $this->assertFalse($process->hasCompleted($wip_log));
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testHasCompletedWithResultSet() {
    $wip_log = new WipLog();
    $process = new ContainerProcess();
    $process->setResult(new ContainerResult());
    $this->assertTrue($process->hasCompleted($wip_log));
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testKillProcessNotCompleted() {
    $wip_log = new WipLog();
    $process = new ContainerProcess();
    $process->setContainer(new NullContainer());
    $this->assertFalse($process->kill($wip_log));
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testKillProcessCompleted() {
    $wip_log = new WipLog();
    $process = new ContainerProcess();
    $process->setContainer(new NullContainer());
    $process->setResult(new ContainerResult());
    $this->assertTrue($process->kill($wip_log));
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testRelease() {
    $process = new ContainerProcess();
    $process->release(new WipLog());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateEnvironment() {
    $result = new ContainerResult();
    $process = new ContainerProcess();
    $environment = AcquiaCloudTestSetup::getEnvironment();
    $result->setEnvironment($environment);
    $process->populateFromResult($result);
    $this->assertEquals($environment, $process->getEnvironment());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateEnvironmentWithMissingEnvironment() {
    $result = new ContainerResult();
    $process = new ContainerProcess();
    $process->populateFromResult($result);
    $this->assertSame(NULL, $process->getEnvironment());
    $this->assertSame(NULL, $process->getExitCode());
    $this->assertEmpty($process->getSuccessExitCodes());
    $this->assertSame(ContainerProcess::DEFAULT_LOG_LEVEL, $process->getLogLevel());
    $this->assertSame(NULL, $process->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateEnvironmentWithEnvironmentAlreadySet() {
    $result = new ContainerResult();
    $process = new ContainerProcess();
    $environment1 = AcquiaCloudTestSetup::getEnvironment();
    $environment2 = AcquiaCloudTestSetup::getEnvironment();
    $environment2->setServers(array_merge($environment2->getServers(), array('wakaflocak')));
    $result->setEnvironment($environment1);
    $process->setEnvironment($environment2);
    $process->populateFromResult($result);
    $this->assertNotEquals($environment1, $process->getEnvironment());
    $this->assertEquals($environment2, $process->getEnvironment());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateEnvironmentMissingEnvironmentAndAlreadySet() {
    $result = new ContainerResult();
    $process = new ContainerProcess();
    $environment = AcquiaCloudTestSetup::getEnvironment();
    $process->setEnvironment($environment);
    $process->populateFromResult($result);
    $this->assertEquals($environment, $process->getEnvironment());
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
    $result->setExitCode($exit_code);
    $process->populateFromResult($result);
    $this->assertEquals($exit_code, $process->getExitCode());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateExitCodeFromProcessMissingExitCode() {
    $result = new ContainerResult();
    $process = new ContainerProcess();
    $process->populateFromResult($result);
    $this->assertNull($process->getExitCode());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateExitCodeFromProcessWithExitCodeAlreadySet() {
    $exit_code = 4;
    $result = new ContainerResult();
    $result->setExitCode($exit_code * 2);
    $process = new ContainerProcess();
    $process->setExitCode($exit_code);
    $process->populateFromResult($result);
    $this->assertEquals($exit_code, $process->getExitCode());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateExitCodeFromProcessMissingExitCodeAndAlreadySet() {
    $exit_code = 4;
    $result = new ContainerResult();
    $process = new ContainerProcess();
    $process->setExitCode($exit_code);
    $process->populateFromResult($result);
    $this->assertEquals($exit_code, $process->getExitCode());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateSuccessCodes() {
    $success_codes = array(4, 8, 12, 16);
    $process = new ContainerProcess();
    $result = new ContainerResult();
    $result->setSuccessExitCodes($success_codes);
    $process->populateFromResult($result);
    $this->assertEquals($success_codes, $process->getSuccessExitCodes());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateSuccessCodesMissingCodes() {
    $process = new ContainerProcess();
    $result = new ContainerResult();
    $process->populateFromResult($result);
    $this->assertEmpty($process->getSuccessExitCodes());
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
    $process->populateFromResult($result);
    $this->assertEquals($process_success_codes, $process->getSuccessExitCodes());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateSuccessCodesMissingAndAlreadySet() {
    $process_success_codes = array(4, 8, 12, 16);
    $process = new ContainerProcess();
    $result = new ContainerResult();
    $process->setSuccessExitCodes($process_success_codes);
    $process->populateFromResult($result);
    $this->assertEquals($process_success_codes, $process->getSuccessExitCodes());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateStartTime() {
    $start_time = time() - mt_rand(1, 45);
    $process = new ContainerProcess();
    $result = new ContainerResult();
    $result->setStartTime($start_time);
    $process->populateFromResult($result);
    $this->assertEquals($start_time, $process->getStartTime());
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
    $process->populateFromResult($result);
    $this->assertEquals($process_start_time, $process->getStartTime());
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
    $process->populateFromResult($result);
    $process->getStartTime();
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
    $process->setStartTime($start_time);
    $process->populateFromResult($result);
    $this->assertEquals($start_time, $process->getStartTime());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateEndTime() {
    $end_time = time() - mt_rand(1, 45);
    $process = new ContainerProcess();
    $result = new ContainerResult();
    $result->setEndTime($end_time);
    $process->populateFromResult($result);
    $this->assertEquals($end_time, $process->getEndTime());
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
    $process->populateFromResult($result);
    $this->assertEquals($process_end_time, $process->getEndTime());
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
    $process->populateFromResult($result);
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
    $process->setEndTime($end_time);
    $process->populateFromResult($result);
    $this->assertEquals($end_time, $process->getEndTime());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulatePid() {
    $pid = 'pid';
    $process = new ContainerProcess();
    $result = new ContainerResult();
    $result->setPid($pid);
    $process->populateFromResult($result);
    $this->assertEquals($pid, $process->getPid());
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
    $process->populateFromResult($result);
    $this->assertEquals($process_pid, $process->getPid());
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
    $process->populateFromResult($result);
    $process->getPid();
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
    $process->setPid($pid);
    $process->populateFromResult($result);
    $this->assertEquals($pid, $process->getPid());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateWipId() {
    $wip_id = mt_rand(1, PHP_INT_MAX);
    $process = new ContainerProcess();
    $result = new ContainerResult();
    $result->setWipId($wip_id);
    $process->populateFromResult($result);
    $this->assertEquals($wip_id, $process->getWipId());
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
    $process->populateFromResult($result);
    $this->assertEquals($process_id, $process->getWipId());
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
    $process->populateFromResult($result);
    $result->getWipId();
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateWipIdMissingAndAlreadySet() {
    $process_id = mt_rand(1, PHP_INT_MAX);
    $process = new ContainerProcess();
    $process->setWipId($process_id);
    $result = new ContainerResult();
    $process->populateFromResult($result);
    $this->assertEquals($process_id, $process->getWipId());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateLogLevel() {
    $log_level = WipLogLevel::ALERT;
    $process = new ContainerProcess();
    $result = new ContainerResult();
    $result->setLogLevel($log_level);
    $process->populateFromResult($result);
    $this->assertEquals($log_level, $process->getLogLevel());
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
    $process->populateFromResult($result);
    $this->assertEquals($process_log_level, $process->getLogLevel());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateLogLevelMissing() {
    $process = new ContainerProcess();
    $result = new ContainerResult();
    $process->populateFromResult($result);
    $this->assertEquals(ContainerProcess::DEFAULT_LOG_LEVEL, $process->getLogLevel());
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
    $process->setLogLevel($log_level);
    $process->populateFromResult($result);
    $this->assertEquals($log_level, $process->getLogLevel());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateExitMessage() {
    $result_message = 'exit';
    $process = new ContainerProcess();
    $result = new ContainerResult();
    $result->setExitMessage($result_message);
    $process->populateFromResult($result);
    $this->assertEquals($result_message, $process->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateExitMessageAlreadySet() {
    $result_message = 'result message';
    $process_message = 'process message';
    $process = new ContainerProcess();
    $process->setExitMessage($process_message);
    $result = new ContainerResult();
    $result->setExitMessage($result_message);
    $process->populateFromResult($result);
    $this->assertEquals($process_message, $process->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateExitMessageMissing() {
    $process = new ContainerProcess();
    $result = new ContainerResult();
    $process->populateFromResult($result);
    $this->assertNull($process->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testPopulateExitMessageMissingAndAlreadySet() {
    $process_message = 'process message';
    $process = new ContainerProcess();
    $process->setExitMessage($process_message);
    $result = new ContainerResult();
    $process->populateFromResult($result);
    $this->assertEquals($process_message, $process->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testForceFail() {
    $message = 'failure';
    $process = new ContainerProcess();
    $process->setContainer(new NullContainer());
    $process->setWipId(mt_rand());
    $process->setPid('pid');
    $process->forceFail($message, new WipLog());
    $this->assertEquals($message, $process->getExitMessage());
    $this->assertEquals(ContainerProcess::FORCE_FAIL_EXIT_CODE, $process->getExitCode());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   */
  public function testForceFailReasonNotString() {
    $message = 15;
    $process = new ContainerProcess();
    $process->setWipId(mt_rand(1, PHP_INT_MAX));
    $process->setPid(mt_rand(1, PHP_INT_MAX));
    $process->forceFail($message, new WipLog());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   */
  public function testForceFailEmptyReason() {
    $message = '';
    $process = new ContainerProcess();
    $process->setWipId(mt_rand(1, PHP_INT_MAX));
    $process->setPid(mt_rand(1, PHP_INT_MAX));
    $process->forceFail($message, new WipLog());
  }

}
