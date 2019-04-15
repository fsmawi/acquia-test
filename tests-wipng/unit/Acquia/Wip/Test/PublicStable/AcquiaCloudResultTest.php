<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\AcquiaCloud\AcquiaCloudProcess;
use Acquia\Wip\AcquiaCloud\AcquiaCloudResult;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\Test\Integration\PublicStable\AcquiaCloud\AcquiaCloudTestSetup;
use Acquia\Wip\Test\Utility\DataProviderTrait;
use Acquia\Wip\WipLogLevel;
use Acquia\Wip\WipProcess;
use Acquia\Wip\WipResult;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Message\Response;

/**
 * Missing summary.
 */
class AcquiaCloudResultTest extends \PHPUnit_Framework_TestCase {

  use DataProviderTrait;

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testConstructor() {
    $result = new AcquiaCloudResult();
    $this->assertNotEmpty($result->getSuccessExitCodes());
    try {
      $pid = $result->getPid();
      $this->fail("The process ID should not be set: $pid");
    } catch (\RuntimeException $e) {
      // This is correct behavior.
    }
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testConstructorWithDummyPid() {
    $result = new AcquiaCloudResult(TRUE);
    $this->assertNotEmpty($result->getSuccessExitCodes());
    $this->assertNotEmpty($result->getPid());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   *
   * @expectedException \InvalidArgumentException
   *
   * @dataProvider nonIntegerDataProvider
   */
  public function testSetPidInvalid($pid) {
    $result = new AcquiaCloudResult();
    $result->setPid($pid);
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testSetData() {
    $message = 'hello';
    $data = new \stdClass();
    $data->message = $message;
    $result = new AcquiaCloudResult();
    $result->setData($data);
    $this->assertEquals($data, $result->getData());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testToJson() {
    $object = $this->createObject();
    $result = AcquiaCloudResult::fromObject($object);
    $json = $result->toJson();
    $json_result = AcquiaCloudResult::fromObject(AcquiaCloudResult::objectFromJson($json));
    $this->assertEquals($result, $json_result);
    $this->assertEquals($object->pid, $result->getPid());
    $this->assertEquals($object->wipId, $result->getWipId());
    $this->assertEquals($object->startTime, $result->getStartTime());
    $this->assertEquals($object->result->endTime, $result->getEndTime());
    $this->assertEquals($object->result->exitCode, $result->getExitCode());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   *
   * @expectedException \InvalidArgumentException
   */
  public function testFromJsonNullDocument() {
    AcquiaCloudResult::objectFromJson(NULL);
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   *
   * @expectedException \InvalidArgumentException
   */
  public function testFromJsonInvalidDocument() {
    AcquiaCloudResult::objectFromJson('invalid json');
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testFromJsonWithData() {
    $object = $this->createObject();
    $message = 'hello';
    $data = new \stdClass();
    $data->message = $message;
    $object->result = new \stdClass();
    $object->result->data = $data;
    $result = new AcquiaCloudResult();
    $result->setData($data);
    $this->assertEquals($data, $result->getData());
    $result = AcquiaCloudResult::fromObject($object);
    $json = $result->toJson();
    $json_result = AcquiaCloudResult::fromObject(AcquiaCloudResult::objectFromJson($json));
    $this->assertEquals($result, $json_result);
    $this->assertEquals($data, $result->getData());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testFromJsonWithError() {
    $object = $this->createObject();
    $error = new \Exception('error');
    $object->result->error = $error->getMessage();
    $result = AcquiaCloudResult::fromObject($object);
    $this->assertEquals($error->getMessage(), $result->getError());
    $json = $result->toJson();
    $json_result = AcquiaCloudResult::fromObject(AcquiaCloudResult::objectFromJson($json));
    $this->assertEquals($result, $json_result);
    $this->assertEquals($error->getMessage(), $result->getError());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   *
   * @expectedException \InvalidArgumentException
   */
  public function testFromObjectIncorrectType() {
    $object = $this->createObject();
    $wip_result = new WipResult();
    AcquiaCloudResult::fromObject($object, $wip_result);
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testSetError() {
    $result = new AcquiaCloudResult();
    $error = new \InvalidArgumentException('error');
    $result->setError($error);
    $this->assertEquals($error->getMessage(), $result->getError());
    $this->assertEquals($error->getMessage(), $result->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testSetErrorWithBadResponseException() {
    $result = new AcquiaCloudResult();
    $error = new BadResponseException('error');
    $error->setResponse(new Response('500'));
    $result->setError($error);
    $this->assertEquals($error->getMessage(), $result->getError());
    $this->assertEquals($error->getResponse()->getReasonPhrase(), $result->getExitMessage());
    $this->assertEquals($error->getResponse()->getStatusCode(), $result->getExitCode());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testCreateUniqueId() {
    $pid = mt_rand();
    $this->assertEquals($pid, AcquiaCloudResult::createUniqueId($pid));
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testPopulatePidFromProcess() {
    $pid = mt_rand();
    $process = new AcquiaCloudProcess();
    $process->setPid($pid);
    $result = new AcquiaCloudResult();
    $result->populateFromProcess($process);
    $this->assertEquals($pid, $result->getPid());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testPopulateWipIdFromProcess() {
    $wip_id = mt_rand(1, PHP_INT_MAX);
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $process->setWipId($wip_id);
    $result = new AcquiaCloudResult();
    $result->populateFromProcess($process);
    $this->assertEquals($wip_id, $result->getWipId());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testPopulateEnvironmentFromProcess() {
    $environment = AcquiaCloudTestSetup::getEnvironment();
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $process->setEnvironment($environment);
    $result = new AcquiaCloudResult();
    $result->populateFromProcess($process);
    $this->assertEquals($environment, $result->getEnvironment());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testPopulateExitCodeFromProcess() {
    $exit_code = mt_rand(0, 200);
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $process->setExitCode($exit_code);
    $result = new AcquiaCloudResult();
    $result->populateFromProcess($process);
    $this->assertEquals($exit_code, $result->getExitCode());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testPopulateSuccessExitCodesFromProcess() {
    $success_exit_codes = array(mt_rand(0, 200));
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $process->setSuccessExitCodes($success_exit_codes);
    $result = new AcquiaCloudResult();
    $result->populateFromProcess($process);
    $this->assertEquals($success_exit_codes, $result->getSuccessExitCodes());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testPopulateStartTimeFromProcess() {
    $start_time = time() - mt_rand(20, 45);
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $process->setStartTime($start_time);
    $result = new AcquiaCloudResult();
    $result->populateFromProcess($process);
    $this->assertEquals($start_time, $result->getStartTime());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testPopulateEndTimeFromProcess() {
    $end_time = time() - mt_rand(20, 45);
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $process->setEndTime($end_time);
    $result = new AcquiaCloudResult();
    $result->populateFromProcess($process);
    $this->assertEquals($end_time, $result->getEndTime());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testPopulateLogLevelFromProcess() {
    $log_level = WipLogLevel::FATAL;
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $process->setLogLevel($log_level);
    $result = new AcquiaCloudResult();
    $result->populateFromProcess($process);
    $this->assertEquals($log_level, $result->getLogLevel());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testPopulateExitMessageFromProcess() {
    $exit_message = 'exit message';
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $process->setExitMessage($exit_message);
    $result = new AcquiaCloudResult();
    $result->populateFromProcess($process);
    $this->assertEquals($exit_message, $result->getExitMessage());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testPopulateErrorFromProcess() {
    $error = new \InvalidArgumentException('error');
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $process->setError($error, new Wiplog());
    $result = new AcquiaCloudResult();
    $result->populateFromProcess($process);
    $this->assertEquals($error->getMessage(), $result->getError());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   *
   * @expectedException \InvalidArgumentException
   */
  public function testPopulateFromProcessBadType() {
    $process = new WipProcess();
    $process->setPid(mt_rand());
    $result = new AcquiaCloudResult();
    $result->populateFromProcess($process);
  }

  /**
   * Missing summary.
   */
  private function createObject() {
    $result = new \stdClass();
    $result->pid = mt_rand();
    $result->wipId = mt_rand(1, PHP_INT_MAX);
    $result->startTime = time() - mt_rand(1, 45);
    $result->result = new \stdClass();
    $result->result->endTime = time();
    $result->result->exitCode = 0;
    return $result;
  }

}
