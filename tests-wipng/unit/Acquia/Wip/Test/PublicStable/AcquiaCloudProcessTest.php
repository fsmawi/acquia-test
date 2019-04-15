<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Cloud\Api\Response\Task;
use Acquia\Wip\AcquiaCloud\AcquiaCloudProcess;
use Acquia\Wip\AcquiaCloud\AcquiaCloudResult;
use Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudTaskInfo;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudTaskResult;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\Test\Integration\PublicStable\AcquiaCloud\AcquiaCloudTestSetup;
use Acquia\Wip\Test\Utility\DataProviderTrait;
use Acquia\Wip\WipResult;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Message\Response;

/**
 * Missing summary.
 */
class AcquiaCloudProcessTest extends \PHPUnit_Framework_TestCase {

  use DataProviderTrait;

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testConstructor() {
    new AcquiaCloudProcess();
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
    $result = new AcquiaCloudProcess();
    $result->setPid($pid);
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetResultWrongType() {
    $result = new WipResult();
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $process->setResult($result);
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testSetError() {
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $error = new \InvalidArgumentException('error');
    $process->setError($error, new WipLog());
    $this->assertEquals($error->getMessage(), $process->getError());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testSetErrorBadResponseException() {
    $status = mt_rand(1, 1000);
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $error = new BadResponseException('error');
    $response = new Response((string) $status);
    $error->setResponse($response);
    $process->setError($error, new WipLog());
    $this->assertEquals($error->getMessage(), $process->getError());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testSetResult() {
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $result = new AcquiaCloudResult();
    $process->setResult($result);
    $this->assertEquals($result, $process->getResult(new WipLog()));
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testGetResultNoResultNoFetch() {
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $this->assertEquals(NULL, $process->getResult(new WipLog()));
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testGetResultNoResultWithFetch() {
    $now = time();
    $started = $now - mt_rand(60, 180);
    $data = array(
      'id' => mt_rand(1, PHP_INT_MAX),
      'started' => $started,
      'queue' => 'dns',
      'state' => 'success',
      'description' => 'domain modify',
      'created' => $started + mt_rand(1, 180),
      'completed' => $now - mt_rand(0, 60),
      'sender' => 'user',
      'result' => 'success',
      'cookie' => '',
      'logs' => 'finished',
    );
    $task = new Task($data);
    $task_info = $this->getMock(
      'Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudTaskInfo',
      array('isRunning'),
      array($task),
      '',
      TRUE,
      TRUE,
      TRUE,
      TRUE,
      FALSE
    );
    $task_info->expects($this->any())
      ->method('isRunning')
      ->willReturn(FALSE);
    $now = time();
    $result = new AcquiaCloudTaskResult();
    $result->setData($task_info);
    $process = $this->getMock(
      'Acquia\Wip\AcquiaCloud\AcquiaCloudProcess',
      array('getTaskInfo'),
      array(),
      '',
      TRUE,
      TRUE,
      TRUE,
      TRUE,
      FALSE
    );
    $process->expects($this->any())
      ->method('getTaskInfo')
      ->willReturn($result);
    $process->setPid(mt_rand());
    $process->setWipId(mt_rand(1000, 10000));
    $this->assertEquals($result, $process->getResult(new WipLog(), TRUE));
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testGetResultNoResultWithFetchEmptyTask() {
    $process = $this->getMock(
      'Acquia\Wip\AcquiaCloud\AcquiaCloudProcess',
      array('getTaskInfo'),
      array(),
      '',
      TRUE,
      TRUE,
      TRUE,
      TRUE,
      FALSE
    );
    $process->expects($this->any())
      ->method('getTaskInfo')
      ->willReturn(NULL);
    $process->setPid(mt_rand());
    $process->setWipId(mt_rand(1000, 10000));
    try {
      $process->getResult(new WipLog(), TRUE);
      $this->fail('Failed to throw exception when unable to get task info from the Cloud API.');
    } catch (\RuntimeException $e) {
      // This is expected behavior.
    }
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testGetResultNoResultWithFetchTaskDataNotSet() {
    $result = new AcquiaCloudTaskResult();
    $process = $this->getMock(
      'Acquia\Wip\AcquiaCloud\AcquiaCloudProcess',
      array('getTaskInfo'),
      array(),
      '',
      TRUE,
      TRUE,
      TRUE,
      TRUE,
      FALSE
    );
    $process->expects($this->any())
      ->method('getTaskInfo')
      ->willReturn($result);
    $process->setPid(mt_rand());
    $process->setWipId(mt_rand(1000, 10000));
    try {
      $process->getResult(new WipLog(), TRUE);
      $this->fail('Failed to throw an exception when the task data was not set.');
    } catch (\RuntimeException $e) {
      // This is expected behavior.
    }
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testGetResultNoResultWithFetchStillRunning() {
    $now = time();
    $started = $now - mt_rand(60, 180);
    $data = array(
      'id' => mt_rand(1, PHP_INT_MAX),
      'started' => $started,
      'queue' => 'dns',
      'state' => 'waiting',
      'description' => 'domain modify',
      'created' => $started + mt_rand(1, 180),
      'completed' => '',
      'sender' => 'user',
      'result' => 'success',
      'cookie' => '',
      'logs' => 'finished',
    );
    $task = new Task($data);
    $task_info = $this->getMock(
      'Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudTaskInfo',
      array('isRunning'),
      array($task),
      '',
      TRUE,
      TRUE,
      TRUE,
      TRUE,
      FALSE
    );
    $task_info->expects($this->any())
      ->method('isRunning')
      ->willReturn(TRUE);
    $result = new AcquiaCloudTaskResult();
    $result->setData($task_info);
    $process = $this->getMock(
      'Acquia\Wip\AcquiaCloud\AcquiaCloudProcess',
      array('getTaskInfo'),
      array(),
      '',
      TRUE,
      TRUE,
      TRUE,
      TRUE,
      FALSE
    );
    $process->expects($this->any())
      ->method('getTaskInfo')
      ->willReturn($result);
    $process->setPid(mt_rand());
    $process->setWipId(mt_rand(1000, 10000));
    try {
      $process->getResult(new WipLog(), TRUE);
      $this->fail('Failed to throw an exception on getResult when the task is still running.');
    } catch (\RuntimeException $e) {
      // This is expected behavior.
    }
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testGetResultNoResultWithFetchTaskFailed() {
    $now = time();
    $started = $now - mt_rand(60, 180);
    $data = array(
      'id' => mt_rand(1, PHP_INT_MAX),
      'started' => $started,
      'queue' => 'dns',
      'state' => 'waiting',
      'description' => 'domain modify',
      'created' => $started + mt_rand(1, 180),
      'completed' => time(),
      'sender' => 'user',
      'result' => 'success',
      'cookie' => '',
      'logs' => 'finished',
    );
    $task = new Task($data);
    $task_info = $this->getMock(
      'Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudTaskInfo',
      array('hasCompleted'),
      array($task),
      '',
      TRUE,
      TRUE,
      TRUE,
      TRUE,
      FALSE
    );
    $task_info->expects($this->any())
      ->method('hasCompleted')
      ->willReturn(TRUE);
    $result = new AcquiaCloudTaskResult();
    $result->setData($task_info);
    $process = $this->getMock(
      'Acquia\Wip\AcquiaCloud\AcquiaCloudProcess',
      array('getTaskInfo'),
      array(),
      '',
      TRUE,
      TRUE,
      TRUE,
      TRUE,
      FALSE
    );
    $process->expects($this->any())
      ->method('getTaskInfo')
      ->willReturn($result);
    $pid = mt_rand();
    $wip_id = mt_rand(1000, 10000);
    $process->setPid($pid);
    $process->setWipId($wip_id);

    // Clone the result for comparison and make it have the same values for exit
    // code, pid, end time, wip ID.
    $result = clone($result);
    $result->setExitCode(AcquiaCloudResult::EXIT_CODE_GENERAL_FAILURE);
    $result->setEndTime($data['completed']);
    $result->setPid($pid);
    $result->setWipId($wip_id);
    $this->assertEquals($result, $process->getResult(new WipLog(), TRUE));
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testHasCompletedWithResult() {
    $process = new AcquiaCloudProcess();
    $process->setPid(mt_rand());
    $result = new AcquiaCloudTaskResult();
    $process->setResult($result);
    $this->assertTrue($process->hasCompleted(new WipLog()));
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testHasCompletedTrueUsingMockTaskStillRunning() {
    $task_info = new AcquiaCloudTaskResult();
    $process = $this->getMockBuilder('Acquia\Wip\AcquiaCloud\AcquiaCloudProcess')
      ->setMethods(array('getTaskInfo'))
      ->getMock();
    $process->expects($this->any())
      ->method('getTaskInfo')
      ->willReturn($task_info);
    $this->assertFalse($process->hasCompleted(new WipLog()));
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testHasCompletedTrueUsingMockTaskNotStillRunning() {
    $task_info = $this->getMock(
      'Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudTaskInfo',
      array('isRunning'),
      array(),
      '',
      TRUE,
      TRUE,
      TRUE,
      TRUE,
      FALSE
    );
    $task_info->expects($this->any())
      ->method('isRunning')
      ->willReturn(FALSE);
    $result = new AcquiaCloudTaskResult();
    $result->setData($task_info);
    $process = $this->getMock(
      'Acquia\Wip\AcquiaCloud\AcquiaCloudProcess',
      array('getTaskInfo'),
      array(),
      '',
      TRUE,
      TRUE,
      TRUE,
      TRUE,
      FALSE
    );
    $process->expects($this->any())
      ->method('getTaskInfo')
      ->willReturn($result);
    $this->assertTrue($process->hasCompleted(new WipLog()));
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testTaskInfoClass() {
    $classname = 'classname';
    $process = new AcquiaCloudProcess();
    $process->setTaskInfoClass($classname);
    $this->assertEquals($classname, $process->getTaskInfoClass());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   *
   * @expectedException \InvalidArgumentException
   */
  public function testTaskInfoClassWrongType() {
    $class_name = 15;
    $process = new AcquiaCloudProcess();
    $process->setTaskInfoClass($class_name);
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   *
   * @expectedException \InvalidArgumentException
   */
  public function testTaskInfoClassEmpty() {
    $class_name = '';
    $process = new AcquiaCloudProcess();
    $process->setTaskInfoClass($class_name);
  }

}
