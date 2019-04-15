<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\Implementation\SqliteWipLogStore;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\IteratorStatus;
use Acquia\Wip\Iterators\BasicIterator\StateTableIterator;
use Acquia\Wip\Runtime\WipPool;
use Acquia\Wip\Runtime\WipWorker;
use Acquia\Wip\Storage\BasicServerStore;
use Acquia\Wip\Storage\BasicThreadStore;
use Acquia\Wip\Storage\BasicWipPoolStore;
use Acquia\Wip\Storage\BasicWipStore;
use Acquia\Wip\TaskInterface;
use Acquia\Wip\TaskStatus;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipInterface;
use Acquia\Wip\WipLogLevel;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

/**
 * Tests the Wip lifecycle.
 */
class WipLifecycleTest extends \PHPUnit_Framework_TestCase {
  /**
   * The WipWorker instance.
   *
   * @var WipWorker
   */
  private $wipWorker = NULL;

  /**
   * The Task instance.
   *
   * @var TaskInterface
   */
  private $task = NULL;

  /**
   * The Wip instance.
   *
   * @var WipInterface
   */
  private $wip = NULL;

  /**
   * Missing summary.
   *
   * @var BasicThreadStore
   */
  private $threadStorage;

  /**
   * Missing summary.
   *
   * @var WipPool
   */
  private $pool;

  /**
   * Missing summary.
   *
   * @var BasicWipPoolStore
   */
  private $poolStorage;

  /**
   * Missing summary.
   *
   * @var BasicWipStore
   */
  private $objectStorage;

  /**
   * Missing summary.
   *
   * @var BasicServerStore
   */
  private $serverStorage;

  /**
   * A simple state table.
   *
   * @var string
   */
  private $stateTable = <<<EOT
start {
  * finish
}

failure {
  * finish
}
EOT;

  /**
   * A simple state table with a wait value.
   *
   * @var string
   */
  private $stateTableWithWait = <<<EOT
start {
  * second wait=1
}

second {
  * finish
}

failure {
  * finish
}
EOT;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $wip = $this->getMock(
      'Acquia\Wip\Implementation\BasicWip',
      array(
        'onStart',
        'onFinish',
        'onRestart',
        'onProcess',
        'onFail',
        'onTerminate',
        'onWait',
        'second',
      )
    );
    $wip->method('getTitle')->willReturn('Mock Wip title');
    $wip->method('getGroup')->willReturn('MockWipGroup');
    $wip->method('getStateTable')->willReturn($this->stateTable);
    $wip->method('getPid')->willReturn(15);
    $wip->method('getLogLevel')->willReturn(WipLogLevel::TRACE);
    $wip->method('addDependencies')->willReturn(array(
      'acquia.wip.api' => 'Acquia\Wip\WipTaskInterface',
    ));
    $wip->method('getWorkId')->willReturn('mock-work-id');
    $this->wip = $wip;
    $this->wip->setUuid((string) \Ramsey\Uuid\Uuid::uuid4());
    $this->wipWorker = $this->getMock(
      'Acquia\Wip\Runtime\WipWorker',
      array('getTask')
    );
    $this->threadStorage = new BasicThreadStore();
    $this->pool = new WipPool();
    // Ensure that storage implementations use all the same instances.
    $this->poolStorage = new BasicWipPoolStore();
    $this->poolStorage->initialize();
    $this->objectStorage = new BasicWipStore();
    $this->serverStorage = new BasicServerStore();
    $this->pool->dependencyManager->swapDependency('acquia.wip.storage.wippool', $this->poolStorage);
    $this->pool->dependencyManager->swapDependency('acquia.wip.storage.wip', $this->objectStorage);
    // Make load task do a NULL op and leave the current iterator in place.
    $this->wipWorker->getDependencyManager()->swapDependency('acquia.wip.storage.wippool', $this->poolStorage);
    $this->wipWorker->getDependencyManager()->swapDependency('acquia.wip.storage.thread', $this->threadStorage);
    $this->wipWorker->getDependencyManager()->swapDependency('acquia.wip.pool', $this->pool);
    $this->wip->setWipLog(new WipLog(new SqliteWipLogStore()));
    $this->task = $this->pool->addTask($this->wip);
    $this->wipWorker->method('getTask')->willReturn($this->task);
    $this->task->dependencyManager->swapDependency('acquia.wip.storage.wippool', $this->poolStorage);
    $this->task->dependencyManager->swapDependency('acquia.wip.storage.wip', $this->objectStorage);
    $this->poolStorage->save($this->task);
    $this->wipWorker->setTaskId($this->task->getId());
  }

  /**
   * Call protected/private method of a class.
   *
   * @param object &$object
   *   Instantiated object that we will run method on.
   * @param string $methodName
   *   Method name to call.
   * @param array $parameters
   *   Array of parameters to pass into method.
   *
   * @return mixed
   *   Method return.
   */
  public function invokeMethod(&$object, $methodName, array $parameters = array()) {
    $reflection = new \ReflectionClass(get_class($object));
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(TRUE);

    return $method->invokeArgs($object, $parameters);
  }

  /**
   * Provides a list of types of error exit status for testing.
   *
   * @return array
   *   The array of values provided.
   */
  public function errorTypeProvider() {
    return array(
      array(IteratorStatus::ERROR_SYSTEM),
      array(IteratorStatus::ERROR_USER),
    );
  }

  /**
   * Verifies the Wip mock object is of the correct type.
   */
  public function testVerifyMockObject() {
    $this->assertInstanceOf('Acquia\Wip\WipInterface', $this->wip);
  }

  /**
   * Tests that the onStart lifecycle method is called.
   */
  public function testOnStart() {
    $this->wip->expects($this->once())->method('onStart');
    $this->go();
  }

  /**
   * Tests that job_started metric is sent on start job.
   */
  public function testSendMetricOnStartJob() {
    $client = $this->getMock('GuzzleHttp\Client');
    $metrics = $this->getMockBuilder('Acquia\Wip\Utility\MetricsUtility')
      ->setConstructorArgs([$client])
      ->setMethods(['sendMetric', 'sendMtdSystemFailure', 'endTiming'])
      ->getMock();

    $this->wip = new BasicWip();
    $this->wip->setMetricsUtility($metrics);
    $this->wip->setUuid((string) \Ramsey\Uuid\Uuid::uuid4());
    $this->wipWorker = $this->getMock(
      'Acquia\Wip\Runtime\WipWorker',
      array('getTask')
    );

    $this->pool = new WipPool();
    $this->wip->setWipLog(new WipLog(new SqliteWipLogStore()));
    $this->task = $this->pool->addTask($this->wip);
    $this->wipWorker->method('getTask')->willReturn($this->task);
    $this->wipWorker->setTaskId($this->task->getId());

    $metrics->expects($this->once())
      ->method('endTiming')
      ->with('wip.system.job_time.task_start');
    $metrics->expects($this->once())
      ->method('sendMetric')
      ->with('count', 'wip.system.job_status.job_started', 1);
    $metrics->expects($this->once())
      ->method('sendMtdSystemFailure')
      ->with(FALSE);
    $this->go();
  }

  /**
   * Tests that MtdSystemFailure metric is sent on finish.
   */
  public function testSendMetricOnFinish() {
    $client = $this->getMock('GuzzleHttp\Client');
    $metrics = $this->getMockBuilder('Acquia\Wip\Utility\MetricsUtility')
      ->setConstructorArgs([$client])
      ->setMethods(['sendMetric', 'sendMtdSystemFailure', 'endTiming'])
      ->getMock();

    $this->wip = new BasicWip();
    $this->wip->setMetricsUtility($metrics);
    $this->wip->setUuid((string) \Ramsey\Uuid\Uuid::uuid4());
    $this->wipWorker = $this->getMock(
      'Acquia\Wip\Runtime\WipWorker',
      array('getTask')
    );

    $this->pool = new WipPool();
    $this->wip->setWipLog(new WipLog(new SqliteWipLogStore()));
    $this->task = $this->pool->addTask($this->wip);
    $this->wipWorker->method('getTask')->willReturn($this->task);
    $this->wipWorker->setTaskId($this->task->getId());
    $this->wipWorker->getTask()->getWipIterator()->setExitCode(IteratorStatus::OK);

    $metrics->expects($this->once())
      ->method('endTiming')
      ->with('wip.system.job_time.task_start');
    $metrics->expects($this->once())
      ->method('sendMetric')
      ->with('count', 'wip.system.job_status.job_started', 1);
    $metrics->expects($this->once())
      ->method('sendMtdSystemFailure')
      ->with(FALSE);
    $this->go();
  }

  /**
   * Tests that MtdSystemFailure metric is sent on terminate.
   */
  public function testSendMetricOnTerminate() {
    $client = $this->getMock('GuzzleHttp\Client');
    $metrics = $this->getMockBuilder('Acquia\Wip\Utility\MetricsUtility')
      ->setConstructorArgs([$client])
      ->setMethods(['sendMetric', 'sendMtdSystemFailure', 'endTiming'])
      ->getMock();

    $this->wip = new BasicWip();
    $this->wip->setMetricsUtility($metrics);
    $this->wip->setUuid((string) \Ramsey\Uuid\Uuid::uuid4());
    $this->wipWorker = $this->getMock(
      'Acquia\Wip\Runtime\WipWorker',
      array('getTask')
    );

    $this->pool = new WipPool();
    $this->wip->setWipLog(new WipLog(new SqliteWipLogStore()));
    $this->task = $this->pool->addTask($this->wip);
    $this->wipWorker->method('getTask')->willReturn($this->task);
    $this->wipWorker->setTaskId($this->task->getId());
    $this->wipWorker->getTask()->getWipIterator()->setExitCode(IteratorStatus::TERMINATED);

    $metrics->expects($this->once())
      ->method('endTiming')
      ->with('wip.system.job_time.task_start');
    $metrics->expects($this->once())
      ->method('sendMetric')
      ->with('count', 'wip.system.job_status.job_started', 1);
    $metrics->expects($this->once())
      ->method('sendMtdSystemFailure')
      ->with(FALSE);
    $this->go();
  }

  /**
   * Tests that user_error metric is sent on user error.
   */
  public function testSendUserErrorMetric() {
    $client = $this->getMock('GuzzleHttp\Client');
    $metrics = $this->getMockBuilder('Acquia\Wip\Utility\MetricsUtility')
      ->setConstructorArgs([$client])
      ->setMethods(['sendMetric', 'sendMtdSystemFailure', 'endTiming'])
      ->getMock();

    $this->wip = new BasicWip();
    $this->wip->setMetricsUtility($metrics);
    $this->wip->setUuid((string) \Ramsey\Uuid\Uuid::uuid4());
    $this->wipWorker = $this->getMock(
      'Acquia\Wip\Runtime\WipWorker',
      array('getTask')
    );

    $this->pool = new WipPool();
    $this->wip->setWipLog(new WipLog(new SqliteWipLogStore()));
    $this->task = $this->pool->addTask($this->wip);
    $this->wipWorker->method('getTask')->willReturn($this->task);
    $this->wipWorker->setTaskId($this->task->getId());
    $this->wipWorker->getTask()->getWipIterator()->setExitCode(IteratorStatus::ERROR_USER);

    $metrics->expects($this->once())
      ->method('endTiming')
      ->with('wip.system.job_time.task_start');
    $metrics->expects($this->exactly(2))
      ->method('sendMetric')
      ->withConsecutive(
        ['count', 'wip.system.job_status.job_started', 1],
        ['count', 'wip.system.job_status.user_error', 1]
      );
    $metrics->expects($this->once())
      ->method('sendMtdSystemFailure')
      ->with(FALSE);
    $this->go();
  }

  /**
   * Tests that system_error metric is sent on system error failure.
   */
  public function testSendSystemErrorMetric() {
    $client = $this->getMock('GuzzleHttp\Client');
    $metrics = $this->getMockBuilder('Acquia\Wip\Utility\MetricsUtility')
      ->setConstructorArgs([$client])
      ->setMethods(['sendMetric', 'sendMtdSystemFailure', 'endTiming'])
      ->getMock();

    $this->wip = new BasicWip();
    $this->wip->setMetricsUtility($metrics);
    $this->wip->setUuid((string) \Ramsey\Uuid\Uuid::uuid4());
    $this->wipWorker = $this->getMock(
      'Acquia\Wip\Runtime\WipWorker',
      array('getTask')
    );

    $this->pool = new WipPool();
    $this->wip->setWipLog(new WipLog(new SqliteWipLogStore()));
    $this->task = $this->pool->addTask($this->wip);
    $this->wipWorker->method('getTask')->willReturn($this->task);
    $this->wipWorker->setTaskId($this->task->getId());
    $this->wipWorker->getTask()->getWipIterator()->setExitCode(IteratorStatus::ERROR_SYSTEM);

    $metrics->expects($this->once())
      ->method('endTiming')
      ->with('wip.system.job_time.task_start');
    $metrics->expects($this->exactly(2))
      ->method('sendMetric')
      ->withConsecutive(
        ['count', 'wip.system.job_status.job_started', 1],
        ['count', 'wip.system.job_status.system_error', 1]
      );
    $metrics->expects($this->once())
      ->method('sendMtdSystemFailure')
      ->with(TRUE);
    $this->go();
  }

  /**
   * Tests that the onFinish lifecycle method is called.
   */
  public function testOnFinish() {
    $this->wip->expects($this->once())->method('onFinish');
    $this->go();
    $this->assertEquals(TaskStatus::COMPLETE, $this->wipWorker->getTask()->getStatus());
  }

  /**
   * Tests that the onFinish lifecycle method is not called twice.
   */
  public function testGoBeyondFinish() {
    $this->wip->expects($this->once())->method('onFinish');
    $this->go();
    // Try calling for the iterator to move to the next state again to ensure
    // the onFinish method is not called again.
    try {
      $this->wipWorker->getTask()->getWipIterator()->moveToNextState();
    } catch (\Exception $e) {
    }
  }

  /**
   * Tests that the onProcess lifecycle method is called.
   */
  public function testProcessingStatus() {
    $this->wipWorker->getTask()->setStatus(TaskStatus::NOT_STARTED);
    $this->wip->expects($this->once())->method('onProcess');
    $this->wipWorker->process();
  }

  /**
   * Tests that the onWait lifecycle method is called.
   */
  public function testWaitStatus() {
    $config = <<<EOT
\$acquia.wip.worker.wait.max => 0
EOT;

    WipFactory::addConfiguration($config);
    $this->task->setStatus(TaskStatus::NOT_STARTED);
    $this->poolStorage->save($this->task);
    $this->wip->method('second')->willReturn('');
    $this->wip->expects($this->once())->method('onWait');
    $this->wip->setStateTable($this->stateTableWithWait);
    $this->wipWorker->getTask()->getWipIterator()->initialize($this->wip);
    $this->wipWorker->getTask()->getWipIterator()->compileStateTable();
    $this->assertEquals(TaskStatus::NOT_STARTED, $this->wipWorker->getTask()->getStatus());

    // Only do a single cycle.
    $this->wipWorker->process();
  }

  /**
   * Tests that the restart lifecycle method is called.
   */
  public function testOnRestart() {
    $this->wip->expects($this->once())->method('onRestart');
    $this->go();
    $this->wipWorker->getTask()->getWipIterator()->restart();
  }

  /**
   * Tests that the onFail lifecycle method is called.
   *
   * @dataProvider errorTypeProvider
   */
  public function testOnFail($error_type) {
    $this->wip->expects($this->once())->method('onFail');
    if ($this->wipWorker->getTask()->getWipIterator() instanceof StateTableIterator) {
      $this->wipWorker->getTask()->getWipIterator()->setExitCode($error_type);
    }
    $this->go();
    $this->wipWorker->getTask()->getWipIterator()->restart();
  }

  /**
   * Tests that the onTerminate lifecycle method is called.
   */
  public function testOnTerminate() {
    $this->wip->expects($this->once())->method('onTerminate');
    if ($this->wipWorker->getTask()->getWipIterator() instanceof StateTableIterator) {
      $this->wipWorker->getTask()->getWipIterator()->setExitCode(IteratorStatus::TERMINATED);
    }
    $this->go();
    $this->wipWorker->getTask()->getWipIterator()->restart();
  }

  /**
   * Runs the iterator until finished.
   */
  private function go() {
    do {
      $this->wipWorker->process();
    } while ($this->wipWorker->getTask()->getStatus() !== TaskStatus::COMPLETE);
  }

}
