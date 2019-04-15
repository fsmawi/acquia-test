<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\Test\Integration\PublicStable\AcquiaCloud\AcquiaCloudTestSetup;
use Acquia\Wip\WipLogLevel;
use Acquia\Wip\WipProcess;
use Acquia\Wip\WipProcessInterface;
use Acquia\Wip\WipResult;

/**
 * Missing summary.the WIP process.
 */
class WipProcessTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testDescription() {
    $description = 'This is the process description.';
    $process = new WipProcess();
    $process->setDescription($description);
    $this->assertEquals($description, $process->getDescription());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \RuntimeException
   */
  public function testDescriptionTwice() {
    $description = 'This is the process description.';
    $process = new WipProcess();
    $process->setDescription($description);
    $process->setDescription($description);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testDescriptionBadType() {
    $description = new \stdClass();
    $process = new WipProcess();
    $process->setDescription($description);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testDescriptionEmpty() {
    $description = '';
    $process = new WipProcess();
    $process->setDescription($description);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testEnvironment() {
    /** @var WipProcessInterface $process */
    $process = new WipProcess();
    $environment = AcquiaCloudTestSetup::getEnvironment();
    $process->setEnvironment($environment);
    $retrieved_environment = $process->getEnvironment();
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
    /** @var WipProcessInterface $process */
    $process = new WipProcess();
    $environment = AcquiaCloudTestSetup::getEnvironment();
    $process->setEnvironment($environment);
    $environment2 = AcquiaCloudTestSetup::getEnvironment();
    $process->setEnvironment($environment2);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testExitCode() {
    $exit_code = 3;
    $process = new WipProcess();
    $process->setExitCode($exit_code);
    $this->assertEquals($exit_code, $process->getExitCode());
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
    $process = new WipProcess();
    $process->setExitCode($exit_code);
    $process->setExitCode($exit_code);
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
    $process = new WipProcess();
    $process->setExitCode($exit_code);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testExitMessage() {
    $message = 'message';
    $process = new WipProcess();
    $process->setExitMessage($message);
    $this->assertEquals($message, $process->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testExitMessageWrongType() {
    $message = 1;
    $process = new WipProcess();
    $process->setExitMessage($message);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testExitMessageNull() {
    $message = NULL;
    $process = new WipProcess();
    $process->setExitMessage($message);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \RuntimeException
   */
  public function testExitMessageAlreadySet() {
    $first_message = 'one';
    $second_message = 'two';
    $process = new WipProcess();
    $process->setExitMessage($first_message);
    $process->setExitMessage($second_message);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testSuccessExitCodes() {
    $exit_codes = array(1, 2, 3);
    $process = new WipProcess();
    $process->setSuccessExitCodes($exit_codes);
    $this->assertEquals($exit_codes, $process->getSuccessExitCodes());
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
    $process = new WipProcess();
    $process->setSuccessExitCodes($exit_codes);
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
    $process = new WipProcess();
    $process->setSuccessExitCodes($exit_codes);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testSuccessExitCodesTwice() {
    $exit_codes_1 = array(1, 2, 3);
    $exit_codes_2 = array(4, 5, 6);
    $process = new WipProcess();
    $process->setSuccessExitCodes($exit_codes_1);
    $this->assertEquals($exit_codes_1, $process->getSuccessExitCodes());

    $process->setSuccessExitCodes($exit_codes_2);
    $this->assertEquals($exit_codes_2, $process->getSuccessExitCodes());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testAddSuccessExitCode() {
    $exit_codes = array(1, 2, 3);

    $process = new WipProcess();
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
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testAddSuccessExitCodeWrongType() {
    $exit_codes = array(1, 2, 3);

    $process = new WipProcess();
    $process->setSuccessExitCodes($exit_codes);
    $this->assertEquals($exit_codes, $process->getSuccessExitCodes());

    $new_exit_code = 'wrongtype';
    $process->addSuccessExitCode($new_exit_code);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testStartTime() {
    $start_time = time();
    $process = new WipProcess();
    $process->setStartTime($start_time);
    $this->assertEquals($start_time, $process->getStartTime());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \RuntimeException
   */
  public function testGetStartTimeBeforeSet() {
    $process = new WipProcess();
    $process->getStartTime();
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
    $process = new WipProcess();
    $process->setStartTime($start_time);
    $process->setStartTime($start_time);
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
    $process = new WipProcess();
    $process->setStartTime($start_time);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetStartTimeZero() {
    $start_time = 0;
    $process = new WipProcess();
    $process->setStartTime($start_time);
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
    $process = new WipProcess();
    $process->setStartTime($start_time);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testEndTime() {
    $end_time = time();
    $process = new WipProcess();
    $process->setEndTime($end_time);
    $this->assertEquals($end_time, $process->getEndTime());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \RuntimeException
   */
  public function testGetEndTimeBeforeSet() {
    $process = new WipProcess();
    $process->getEndTime();
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
    $process = new WipProcess();
    $process->setendTime($end_time);
    $process->setEndTime($end_time);
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
    $process = new WipProcess();
    $process->setEndTime($end_time);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetEndTimeZero() {
    $start_time = 0;
    $process = new WipProcess();
    $process->setEndTime($start_time);
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
    $process = new WipProcess();
    $process->setEndTime($start_time);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testGetRuntime() {
    $start_time = time() - mt_rand(1, 35);
    $end_time = time();
    $process = new WipProcess();
    $process->setStartTime($start_time);
    $process->setEndTime($end_time);
    $this->assertEquals($end_time - $start_time, $process->getRuntime());
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
    $process = new WipProcess();
    $process->setEndTime($end_time);
    $process->getRuntime();
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
    $process = new WipProcess();
    $process->setStartTime($start_time);
    $process->getRuntime();
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
    $process = new WipProcess();
    $process->setPid($pid);
    $process->setPid($pid);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \RuntimeException
   */
  public function testGetPidBeforeSet() {
    $process = new WipProcess();
    $process->getPid();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testSetWipid() {
    $id = mt_rand(1, PHP_INT_MAX);
    $process = new WipProcess();
    $process->setWipId($id);
    $this->assertEquals($id, $process->getWipId());
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
    $process = new WipProcess();
    $process->setWipId($id);
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
    $process = new WipProcess();
    $process->setWipId($id);
    $process->setWipId($id);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \RuntimeException
   */
  public function testGetWipIdBeforeSet() {
    $process = new WipProcess();
    $process->getWipId();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testLogLevel() {
    $log_level = WipLogLevel::ALERT;
    $process = new WipProcess();
    $process->setLogLevel($log_level);
    $this->assertEquals($log_level, $process->getLogLevel());
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
    $process = new WipProcess();
    $process->setLogLevel($log_level);
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
    $process = new WipProcess();
    $process->setLogLevel($log_level);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testGetUniqueId() {
    $pid = 244;
    $process = new WipProcess();
    $process->setPid($pid);
    $this->assertNotEmpty($process->getUniqueId());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testGetUniqueIdIsUnique() {
    $pid_1 = 244;
    $pid_2 = 553;
    $process_1 = new WipProcess();
    $process_1->setPid($pid_1);
    $this->assertNotEmpty($process_1->getUniqueId());

    $process_2 = new WipProcess();
    $process_2->setPid($pid_2);
    $this->assertNotEmpty($process_2->getUniqueId());

    $this->assertNotEquals($process_1->getUniqueId(), $process_2->getUniqueId());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testGetUniqueIdSameForSimilarResultInstances() {
    $pid = 244;
    $process_1 = new WipProcess();
    $process_1->setPid($pid);
    $this->assertNotEmpty($process_1->getUniqueId());

    $process_2 = new WipProcess();
    $process_2->setPid($pid);
    $this->assertNotEmpty($process_2->getUniqueId());

    $this->assertEquals($process_1->getUniqueId(), $process_2->getUniqueId());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testGetResult() {
    $wip_log = new WipLog();
    $process = new WipProcess();
    $result = new WipResult();
    $process->setResult($result);
    $this->assertEquals($result, $process->getResult($wip_log));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \RuntimeException
   */
  public function testSetResultTwice() {
    $process = new WipProcess();
    $process->setResult(new WipResult());
    $process->setResult(new WipResult());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testHasCompletedNoResultSet() {
    $wip_log = new WipLog();
    $process = new WipProcess();
    $this->assertFalse($process->hasCompleted($wip_log));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testHasCompletedWithResultSet() {
    $wip_log = new WipLog();
    $process = new WipProcess();
    $process->setResult(new WipResult());
    $this->assertTRUE($process->hasCompleted($wip_log));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testKillProcessNotCompleted() {
    $wip_log = new WipLog();
    $process = new WipProcess();
    $this->assertFalse($process->kill($wip_log));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testKillProcessCompleted() {
    $wip_log = new WipLog();
    $process = new WipProcess();
    $process->setResult(new WipResult());
    $this->assertTrue($process->kill($wip_log));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testRelease() {
    $process = new WipProcess();
    $process->release(new WipLog());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateEnvironment() {
    $result = new WipResult();
    $process = new WipProcess();
    $environment = AcquiaCloudTestSetup::getEnvironment();
    $result->setEnvironment($environment);
    $process->populateFromResult($result);
    $this->assertEquals($environment, $process->getEnvironment());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateEnvironmentWithMissingEnvironment() {
    $result = new WipResult();
    $process = new WipProcess();
    $process->populateFromResult($result);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateEnvironmentWithEnvironmentAlreadySet() {
    $result = new WipResult();
    $process = new WipProcess();
    $environment1 = AcquiaCloudTestSetup::getEnvironment();
    $environment2 = AcquiaCloudTestSetup::getEnvironment();
    $environment2->setServers(array_merge($environment2->getServers(), array('wakaflocak')));
    $result->setEnvironment($environment1);
    $process->setEnvironment($environment2);
    $process->populateFromResult($result);
    $this->assertNotEquals($environment1, $process->getEnvironment());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateEnvironmentMissingEnvironmentAndAlreadySet() {
    $result = new WipResult();
    $process = new WipProcess();
    $environment = AcquiaCloudTestSetup::getEnvironment();
    $process->setEnvironment($environment);
    $process->populateFromResult($result);
    $this->assertEquals($environment, $process->getEnvironment());
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
    $result->setExitCode($exit_code);
    $process->populateFromResult($result);
    $this->assertEquals($exit_code, $process->getExitCode());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateExitCodeFromProcessMissingExitCode() {
    $result = new WipResult();
    $process = new WipProcess();
    $process->populateFromResult($result);
    $this->assertNull($process->getExitCode());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateExitCodeFromProcessWithExitCodeAlreadySet() {
    $exit_code = 4;
    $result = new WipResult();
    $result->setExitCode($exit_code * 2);
    $process = new WipProcess();
    $process->setExitCode($exit_code);
    $process->populateFromResult($result);
    $this->assertEquals($exit_code, $process->getExitCode());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateExitCodeFromProcessMissingExitCodeAndAlreadySet() {
    $exit_code = 4;
    $result = new WipResult();
    $process = new WipProcess();
    $process->setExitCode($exit_code);
    $process->populateFromResult($result);
    $this->assertEquals($exit_code, $process->getExitCode());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateSuccessCodes() {
    $success_codes = array(4, 8, 12, 16);
    $process = new WipProcess();
    $result = new WipResult();
    $result->setSuccessExitCodes($success_codes);
    $process->populateFromResult($result);
    $this->assertEquals($success_codes, $process->getSuccessExitCodes());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateSuccessCodesMissingCodes() {
    $process = new WipProcess();
    $result = new WipResult();
    $process->populateFromResult($result);
    $this->assertEmpty($process->getSuccessExitCodes());
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
    $process->populateFromResult($result);
    $this->assertEquals($process_success_codes, $process->getSuccessExitCodes());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateSuccessCodesMissingAndAlreadySet() {
    $process_success_codes = array(4, 8, 12, 16);
    $process = new WipProcess();
    $result = new WipResult();
    $process->setSuccessExitCodes($process_success_codes);
    $process->populateFromResult($result);
    $this->assertEquals($process_success_codes, $process->getSuccessExitCodes());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateStartTime() {
    $start_time = time() - mt_rand(1, 45);
    $process = new WipProcess();
    $result = new WipResult();
    $result->setStartTime($start_time);
    $process->populateFromResult($result);
    $this->assertEquals($start_time, $process->getStartTime());
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
    $process->populateFromResult($result);
    $this->assertEquals($process_start_time, $process->getStartTime());
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
    $process->populateFromResult($result);
    $process->getStartTime();
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
    $process->setStartTime($start_time);
    $process->populateFromResult($result);
    $this->assertEquals($start_time, $process->getStartTime());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateEndTime() {
    $end_time = time() - mt_rand(1, 45);
    $process = new WipProcess();
    $result = new WipResult();
    $result->setEndTime($end_time);
    $process->populateFromResult($result);
    $this->assertEquals($end_time, $process->getEndTime());
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
    $process->populateFromResult($result);
    $this->assertEquals($process_end_time, $process->getEndTime());
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
    $process->populateFromResult($result);
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
    $process->setEndTime($end_time);
    $process->populateFromResult($result);
    $this->assertEquals($end_time, $process->getEndTime());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulatePid() {
    $pid = mt_rand(1, 1000);
    $process = new WipProcess();
    $result = new WipResult();
    $result->setPid($pid);
    $process->populateFromResult($result);
    $this->assertEquals($pid, $process->getPid());
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
    $process->populateFromResult($result);
    $this->assertEquals($process_pid, $process->getPid());
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
    $process->populateFromResult($result);
    $process->getPid();
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
    $process->setPid($pid);
    $process->populateFromResult($result);
    $this->assertEquals($pid, $process->getPid());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateWipId() {
    $wip_id = mt_rand(1, PHP_INT_MAX);
    $process = new WipProcess();
    $result = new WipResult();
    $result->setWipId($wip_id);
    $process->populateFromResult($result);
    $this->assertEquals($wip_id, $process->getWipId());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateWipIdAlreadySet() {
    $process_id = mt_rand(1, PHP_INT_MAX);
    // Make sure the result and process IDs are different.
    do {
      $result_id = mt_rand(1, PHP_INT_MAX);
    } while ($result_id == $process_id);
    $process = new WipProcess();
    $process->setWipId($process_id);
    $result = new WipResult();
    $result->setWipId($result_id);
    $process->populateFromResult($result);
    $this->assertEquals($process_id, $process->getWipId());
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
    $process->populateFromResult($result);
    $result->getWipId();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateWipIdMissingAndAlreadySet() {
    $process_id = mt_rand(1, PHP_INT_MAX);
    $process = new WipProcess();
    $process->setWipId($process_id);
    $result = new WipResult();
    $process->populateFromResult($result);
    $this->assertEquals($process_id, $process->getWipId());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateLogLevel() {
    $log_level = WipLogLevel::ALERT;
    $process = new WipProcess();
    $result = new WipResult();
    $result->setLogLevel($log_level);
    $process->populateFromResult($result);
    $this->assertEquals($log_level, $process->getLogLevel());
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
    $process->populateFromResult($result);
    $this->assertEquals($process_log_level, $process->getLogLevel());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateLogLevelMissing() {
    $process = new WipProcess();
    $result = new WipResult();
    $process->populateFromResult($result);
    $this->assertEquals(WipProcess::DEFAULT_LOG_LEVEL, $process->getLogLevel());
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
    $process->setLogLevel($log_level);
    $process->populateFromResult($result);
    $this->assertEquals($log_level, $process->getLogLevel());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateExitMessage() {
    $result_message = 'exit';
    $process = new WipProcess();
    $result = new WipResult();
    $result->setExitMessage($result_message);
    $process->populateFromResult($result);
    $this->assertEquals($result_message, $process->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateExitMessageAlreadySet() {
    $result_message = 'result message';
    $process_message = 'process message';
    $process = new WipProcess();
    $process->setExitMessage($process_message);
    $result = new WipResult();
    $result->setExitMessage($result_message);
    $process->populateFromResult($result);
    $this->assertEquals($process_message, $process->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateExitMessageMissing() {
    $process = new WipProcess();
    $result = new WipResult();
    $process->populateFromResult($result);
    $this->assertNull($process->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testPopulateExitMessageMissingAndAlreadySet() {
    $process_message = 'process message';
    $process = new WipProcess();
    $process->setExitMessage($process_message);
    $result = new WipResult();
    $process->populateFromResult($result);
    $this->assertEquals($process_message, $process->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testForceFail() {
    $message = 'failure';
    $process = new WipProcess();
    $process->setWipId(mt_rand(1, PHP_INT_MAX));
    $process->setPid(mt_rand(1, PHP_INT_MAX));
    $process->forceFail($message, new WipLog());
    $this->assertEquals($message, $process->getExitMessage());
  }

  /**
   * Missing summary.
   */
  public function forceFailInvalidReasonProvider() {
    return array(
      array(-10),
      array(TRUE),
      array(''),
      array(NULL),
    );
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @dataProvider forceFailInvalidReasonProvider
   */
  public function testForceFailReasonNotString($reason) {
    $process = new WipProcess();
    $process->setWipId(mt_rand(1, PHP_INT_MAX));
    $process->setPid(mt_rand(1, PHP_INT_MAX));
    $process->forceFail($reason, new WipLog());
    $this->assertEquals('No reason provided.', $process->getExitMessage());
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
