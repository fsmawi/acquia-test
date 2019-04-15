<?php

namespace Acquia\Wip\Test\Integration\PublicStable\AcquiaCloud;

use Acquia\Cloud\Api\Response\Task;
use Acquia\Wip\AcquiaCloud\AcquiaCloud;
use Acquia\Wip\AcquiaCloud\AcquiaCloudProcess;
use Acquia\Wip\AcquiaCloud\AcquiaCloudResult;
use Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudTaskInfo;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudEnvironmentResult;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudTaskResult;
use Acquia\Wip\Environment;
use Acquia\Wip\Test\Utility\DataProviderTrait;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipLogLevel;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Message\Response;

/**
 * Missing summary.
 */
class AcquiaCloudProcessIntegrationTest extends \PHPUnit_Framework_TestCase {

  use DataProviderTrait;

  /**
   * Missing summary.
   *
   * @var AcquiaCloud
   */
  private $cloud = NULL;

  /**
   * Missing summary.
   *
   * @var WipLogInterface
   */
  private $logger = NULL;

  /**
   * Missing summary.
   *
   * @var Environment
   */
  private $environment = NULL;

  private static $taskId = 0;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    $max_attempts = 4;
    $this->logger = AcquiaCloudTestSetup::createWipLog();
    $this->environment = AcquiaCloudTestSetup::getEnvironment();
    $wip_id = mt_rand(1, PHP_INT_MAX);
    $this->cloud = new AcquiaCloud($this->environment, $this->logger, $wip_id);
    // @TODO The Acquia Hosting 1.90 release broke our ability to list tasks.
    // if (empty(self::$taskId)) {
    // do {
    // $task_result = $this->cloud->getTasks();
    // if (!$task_result->isSuccess()) {
    // printf("Failed to get cloud tasks.\n");
    // sleep(1);
    // }
    // } while (!$task_result->isSuccess() && $max_attempts-- > 0);
    // if (count($task_result->getData()) > 0) {
    // self::$taskId = $task_result->getData()[0]->getId();
    // }
    // }
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testSetVcsPathBadCredentials() {
    /** @var AcquiaCloudEnvironmentResult $env_info */
    $env_info = $this->cloud->getEnvironmentInfo($this->environment->getEnvironmentName());
    if (!$env_info->isSuccess()) {
      $this->fail(sprintf('Failed to get environment info: %s', $env_info->getExitMessage()));
    }
    $this->assertNotEmpty($env_info->getData());
    $vcs_path = $env_info->getData()->getVcsPath();
    $cloud = $this->getBadCredentialsCloud();
    $process = $cloud->deployCodePath($vcs_path);
    $error = $process->getError();
    $this->assertNotEmpty($error);
    $this->assertTrue($process->hasCompleted($this->logger));
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   *
   * Disabled for now - it takes way too long to run a hosting task to put it
   * in a unit test.
   */
  public function testGetTaskResult() {
    $id = self::$taskId;
    if ($id == 0) {
      $id = 97160;
    }
    $env = AcquiaCloudTestSetup::getEnvironment();
    $task_data = array(
      'id' => $id,
      'state' => 'done',
      'started' => time() - 45,
      'completed' => time() - 3,
      'created' => time() - 46,
      'queue' => 'code-push',
      'sender' => $env->getFullyQualifiedSitegroup(),
      'description' => 'Deploy code to prod',
      'logs' => '',
      'result' => '',
      'cookie' => '',
    );

    $task = new Task($task_data);

    $task_info = new AcquiaCloudTaskInfo($task);
    $task_result = new AcquiaCloudTaskResult();
    $task_result->setData($task_info);
    $process = $this->getMock(
      'Acquia\Wip\AcquiaCloud\AcquiaCloudProcess',
      array('hasCompleted', 'getTaskInfo'),
      array(),
      '',
      FALSE
    );
    $process->expects($this->any())->method('hasCompleted')->willReturn(TRUE);
    $process->expects($this->any())->method('getTaskInfo')->willReturn($task_result);

    /** @var AcquiaCloudProcess $process */
    $process->setPid($id);
    $process->setWipId(mt_rand(1, PHP_INT_MAX));
    $process->setEnvironment($this->environment);
    $error = $process->getError();
    $this->assertEmpty($error);
    $this->assertTrue($process->hasCompleted($this->logger));
    $process->setExitCode(AcquiaCloudResult::EXIT_CODE_SUCCESS);
    $result = $process->getResult($this->logger, TRUE);
    $this->assertTrue($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testGetTaskInfo() {
    $environment_response = $this->cloud->getEnvironmentInfo($this->environment->getEnvironmentName());
    if (!$environment_response->isSuccess()) {
      $this->fail(sprintf('Failed to get environment info: %s', $environment_response->getExitMessage()));
    }
    $vcs_path = $environment_response->getData()->getVcsPath();
    $process = $this->cloud->deployCodePath($vcs_path);
    $logger = AcquiaCloudTestSetup::createWipLog();
    $this->assertFalse($process->hasCompleted($logger));
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testStartTime() {
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $process->setEnvironment(AcquiaCloudTestSetup::getEnvironment());
    $start_time = time();
    $process->setStartTime($start_time);
    $this->assertEquals($start_time, $process->getStartTime());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   *
   * @expectedException \RuntimeException
   */
  public function testStartTimeNotSet() {
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $process->setEnvironment(AcquiaCloudTestSetup::getEnvironment());
    $process->getStartTime();
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   *
   * @expectedException \RuntimeException
   */
  public function testStartTimeNotInt() {
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $process->setEnvironment(AcquiaCloudTestSetup::getEnvironment());
    $this->assertEquals(0, $process->getStartTime());
    $start_time = 'hello';
    $process->setStartTime($start_time);
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testSetResult() {
    $task_result = new AcquiaCloudTaskResult();
    $task_result->setEnvironment(AcquiaCloudTestSetup::getEnvironment());
    $task_data = new AcquiaCloudTaskInfo();
    $task_result->setData($task_data);
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $process->setEnvironment(AcquiaCloudTestSetup::getEnvironment());
    $process->setResult($task_result);
    $result = $process->getResult($this->logger);
    $this->assertNotEmpty($result);
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
    $process = new AcquiaCloudProcess();
    $process->setPid($pid);
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testUniqueId() {
    $id = 15;
    $process = new AcquiaCloudProcess();
    $process->setPid($id);
    $this->assertEquals($id, $process->getUniqueId());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testGetEnvironment() {
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $process->setEnvironment(AcquiaCloudTestSetup::getEnvironment());
    $this->assertNotEmpty($process->getEnvironment());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testWipId() {
    $id = 15;
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $process->setEnvironment(AcquiaCloudTestSetup::getEnvironment());
    $process->setWipId($id);
    $this->assertEquals($id, $process->getWipId());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   *
   * @expectedException \InvalidArgumentException
   */
  public function testWipIdNotInt() {
    $id = 'hello';
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $process->setEnvironment(AcquiaCloudTestSetup::getEnvironment());
    $process->setWipId($id);
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testSetSuccessExitCodes() {
    $exit_code = 3;
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $process->setSuccessExitCodes(array($exit_code));
    $this->assertEquals(array($exit_code), $process->getSuccessExitCodes());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testAddSuccessExitCodes() {
    $exit_code = 3;
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $process->addSuccessExitCode($exit_code);
    $this->assertEquals(array(200, $exit_code), $process->getSuccessExitCodes());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   *
   * @expectedException \InvalidArgumentException
   */
  public function testAddSuccessExitCodesNotInt() {
    $exit_code = 'hello';
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $process->addSuccessExitCode($exit_code);
    $this->assertEquals(array(0, $exit_code), $process->getSuccessExitCodes());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testLogLevel() {
    $log_level = WipLogLevel::ALERT;
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $process->setLogLevel($log_level);
    $this->assertEquals($log_level, $process->getLogLevel());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   *
   * @expectedException \InvalidArgumentException
   */
  public function testLogLevelBadValue() {
    $log_level = 'hello';
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $process->setLogLevel($log_level);
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testSetError() {
    // The exception is stored properly.
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $process->setEnvironment($this->environment);
    $test_exception = new \Exception('test exception: ' . mt_rand());
    $process->setError($test_exception, $this->logger);
    $verify_exception = $process->getError();
    $this->assertEquals($test_exception->getMessage(), $verify_exception);
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testSetErrorResultIsAlsoSet() {
    // When setting an error the result is also getting set.
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $process->setEnvironment($this->environment);
    $process->setWipId(mt_rand(1, PHP_INT_MAX));
    // Running getResult() on an empty process will throw an exception, this is
    // how we check that the result is not yet set.
    try {
      $result = $process->getResult($this->logger);
    } catch (\RuntimeException $e) {
      $result = NULL;
    }
    $this->assertNull($result);
    $test_exception = new \Exception('test exception: ' . mt_rand());
    $process->setError($test_exception, $this->logger);
    $result = $process->getResult($this->logger);
    $this->assertInstanceOf('Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudTaskResult', $result);
    $this->assertEquals($this->environment, $result->getEnvironment());
    $this->assertEquals($test_exception->getMessage(), $result->getError());
    $this->assertEquals($process->getSuccessExitCodes(), $result->getSuccessExitCodes());
    $this->assertEquals($process->getLogLevel(), $result->getLogLevel());
    $this->assertEquals(AcquiaCloudTaskResult::EXIT_CODE_GENERAL_FAILURE, $result->getExitCode());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testSetErrorDoesNotOverwriteExistingResult() {
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $process->setEnvironment($this->environment);
    $test_result = new AcquiaCloudTaskResult();
    $test_result->setPid(mt_rand());
    $process->setResult($test_result);
    $process->setError(new \Exception('test exception: ' . mt_rand()), $this->logger);
    $this->assertEquals($test_result, $process->getResult($this->logger));
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testErrorBadResponseExceptionErrorCodeIsSet() {
    // Setting a BadResponseException as an error gets the response's error
    // stored in the exit code and the result.
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $process->setEnvironment($this->environment);
    $bad_response_exception = new BadResponseException('Message');
    $response_status_code = mt_rand();
    $bad_response_exception->setResponse(new Response($response_status_code));
    $process->setError($bad_response_exception, $this->logger);
    $result = $process->getResult($this->logger);
    $this->assertEquals($response_status_code, $result->getExitCode());
    $this->assertEquals($response_status_code, $process->getExitCode());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testSetErrorBadResponseExceptionWithNoExitCode() {
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $process->setEnvironment($this->environment);
    $bad_response_exception = new BadResponseException('Message');
    $process->setError($bad_response_exception, $this->logger);
    $this->assertEquals(AcquiaCloudResult::EXIT_CODE_GENERAL_FAILURE, $process->getExitCode());
  }

  /**
   * Missing summary.
   */
  private function getBadCredentialsCloud() {
    $wip_id = mt_rand(1, PHP_INT_MAX);
    $cloud = new AcquiaCloud(AcquiaCloudTestSetup::getBadCredsEnvironment(), $this->logger, $wip_id);
    return $cloud;
  }

}
