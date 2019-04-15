<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\DependencyManager;
use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\Implementation\SqliteWipLogStore;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\Implementation\WipTaskApi;
use Acquia\Wip\Iterators\BasicIterator\WipContext;
use Acquia\Wip\Runtime\WipPoolInterface;
use Acquia\Wip\Storage\WipPoolStoreInterface;
use Acquia\Wip\TaskExitStatus;
use Acquia\Wip\TaskStatus;
use Acquia\Wip\WipContextInterface;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogInterface;

/**
 * Test the WIP task interface.
 */
class WipTaskInterfaceTest extends \PHPUnit_Framework_TestCase {

  /**
   * The DependencyManager instance.
   *
   * @var DependencyManager
   */
  public $dependencyManager;

  /**
   * The WipTaskApi instance.
   *
   * @var WipTaskApi
   */
  private $wipApi = NULL;

  /**
   * The WipContext.
   *
   * @var WipContextInterface
   */
  private $context = NULL;

  /**
   * The logger.
   *
   * @var WipLogInterface
   */
  private $logger = NULL;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    WipFactory::setConfigPath('tests-wipng/unit/Acquia/Wip/Test/factory.cfg');
    WipFactory::reset();
    $this->dependencyManager = new DependencyManager();
    $this->dependencyManager->addDependencies($this->getDependencies());
    $this->wipApi = new WipTaskApi();
    $this->context = new WipContext();
    $this->logger = new WipLog(new SqliteWipLogStore());
  }

  /**
   * Provides a list of types of error exit status for testing.
   *
   * @return array
   *   The array of values provided.
   */
  public function errorTypeProvider() {
    return array(
      array(TaskExitStatus::ERROR_SYSTEM),
      array(TaskExitStatus::ERROR_USER),
    );
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipTask
   */
  public function testInstantiate() {
    $this->assertInstanceOf('Acquia\Wip\WipTaskInterface', $this->wipApi);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipTask
   */
  public function testAddChild() {
    $wip = new BasicWip();
    $wip->setUuid((string) \Ramsey\Uuid\Uuid::uuid4());
    $wip->setWipLog(new WipLog(new SqliteWipLogStore()));
    $id = $this->wipApi->addChild($wip, $this->context);
    $this->assertNotEmpty($id);
    $this->assertTrue(isset($this->context->wip->processes));
    $this->assertTrue(is_array($this->context->wip->processes));
    $this->assertNotEmpty($this->context->wip->processes);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipTask
   */
  public function testGetChildStatus() {
    $status = $this->wipApi->getWipTaskStatus($this->context, $this->logger);
    $this->assertEquals('uninitialized', $status);
    $wip = new BasicWip();
    $wip->setUuid((string) \Ramsey\Uuid\Uuid::uuid4());
    $wip->setWipLog(new WipLog(new SqliteWipLogStore()));
    $wip_process = $this->wipApi->addChild($wip, $this->context);
    $status = $this->wipApi->getWipTaskStatus($this->context, $this->logger);
    $this->assertEquals('wait', $status);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipTask
   *
   * @dataProvider errorTypeProvider
   */
  public function testGetFailChildStatus($error_type) {
    $status = $this->wipApi->getWipTaskStatus($this->context, $this->logger);
    $this->assertEquals('uninitialized', $status);
    $wip = new BasicWip();
    $wip->setUuid((string) \Ramsey\Uuid\Uuid::uuid4());
    $wip->setWipLog($this->logger);

    // Force the Wip pool store to return the task with a given exit status.
    $wip_process = $this->wipApi->addChild($wip, $this->context);
    $task = $wip_process->getTask();
    $task->setExitStatus($error_type);
    $task->setStatus(TaskStatus::COMPLETE);
    $now = time();
    $task->setStartTimestamp($now - mt_rand(1, $now - 1));
    $task->setCompletedTimestamp($now);
    $this->getWipPoolStore()->save($task);

    $status = $this->wipApi->getWipTaskStatus($this->context, $this->logger);
    $this->assertEquals('fail', $status);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipTask
   */
  public function testGetSuccessWipStatus() {
    $status = $this->wipApi->getWipTaskStatus($this->context, $this->logger);
    $this->assertEquals('uninitialized', $status);
    $wip = new BasicWip();
    $wip->setUuid((string) \Ramsey\Uuid\Uuid::uuid4());
    $wip->setWipLog($this->logger);

    $process = $this->wipApi->addChild($wip, $this->context);
    $task = $process->getTask();
    $task->setExitStatus(TaskExitStatus::COMPLETED);
    $task->setStatus(TaskStatus::COMPLETE);
    $now = time();
    $task->setStartTimestamp($now - mt_rand(1, $now - 1));
    $task->setCompletedTimestamp($now);
    $this->getWipPoolStore()->save($task);

    $status = $this->wipApi->getWipTaskStatus($this->context, $this->logger);
    $this->assertEquals('success', $status);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipTask
   */
  public function testGetSuccessWipStatusStartTimeNotSet() {
    $status = $this->wipApi->getWipTaskStatus($this->context, $this->logger);
    $this->assertEquals('uninitialized', $status);
    $wip = new BasicWip();
    $wip->setUuid((string) \Ramsey\Uuid\Uuid::uuid4());
    $wip->setWipLog($this->logger);

    $process = $this->wipApi->addChild($wip, $this->context);
    $task = $process->getTask();
    $task->setExitStatus(TaskExitStatus::COMPLETED);
    $task->setStatus(TaskStatus::COMPLETE);
    $now = time();
    $task->setCompletedTimestamp($now);
    $this->getWipPoolStore()->save($task);

    $status = $this->wipApi->getWipTaskStatus($this->context, $this->logger);
    $this->assertEquals('success', $status);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipTask
   */
  public function testGetSuccessWipStatusEndTimeNotSet() {
    $status = $this->wipApi->getWipTaskStatus($this->context, $this->logger);
    $this->assertEquals('uninitialized', $status);
    $wip = new BasicWip();
    $wip->setUuid((string) \Ramsey\Uuid\Uuid::uuid4());
    $wip->setWipLog($this->logger);

    $process = $this->wipApi->addChild($wip, $this->context);
    $task = $process->getTask();
    $task->setExitStatus(TaskExitStatus::COMPLETED);
    $task->setStatus(TaskStatus::COMPLETE);
    $now = time();
    $task->setStartTimestamp($now - mt_rand(1, $now - 1));
    $this->getWipPoolStore()->save($task);

    $status = $this->wipApi->getWipTaskStatus($this->context, $this->logger);
    $this->assertEquals('success', $status);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipTask
   */
  public function testGetSuccessWipStatusStartAndEndTimeNotSet() {
    $status = $this->wipApi->getWipTaskStatus($this->context, $this->logger);
    $this->assertEquals('uninitialized', $status);
    $wip = new BasicWip();
    $wip->setUuid((string) \Ramsey\Uuid\Uuid::uuid4());
    $wip->setWipLog($this->logger);

    $process = $this->wipApi->addChild($wip, $this->context);
    $task = $process->getTask();
    $task->setExitStatus(TaskExitStatus::COMPLETED);
    $task->setStatus(TaskStatus::COMPLETE);
    $this->getWipPoolStore()->save($task);

    $status = $this->wipApi->getWipTaskStatus($this->context, $this->logger);
    $this->assertEquals('success', $status);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   * @group WipTask
   */
  public function testGetEmptyFailChildStatus() {
    $status = $this->wipApi->getWipTaskStatus($this->context, $this->logger);
    $this->assertEquals('uninitialized', $status);
    $wip = new BasicWip();
    $wip->setUuid((string) \Ramsey\Uuid\Uuid::uuid4());
    $wip->setWipLog($this->logger);

    $process = $this->wipApi->addChild($wip, $this->context);
    $task = $process->getTask();
    $task->setStatus(TaskStatus::COMPLETE);
    $task->setExitStatus(TaskExitStatus::ERROR_SYSTEM);
    $this->getWipPoolStore()->save($task);

    $status = $this->wipApi->getWipTaskStatus($this->context, $this->logger);
    $this->assertEquals('fail', $status);
  }

  /**
   * Gets the WipPool instance to use.
   *
   * @return WipPoolInterface
   *   The wip pool.
   *
   * @throws \Acquia\Wip\Exception\DependencyMissingException
   *   If the WipPool could not be found.
   */
  protected function getWipPool() {
    return $this->dependencyManager->getDependency('acquia.wip.pool');
  }

  /**
   * Gets the WipPool storage instance to use.
   *
   * @return WipPoolStoreInterface
   *   The WipPoolStore.
   *
   * @throws \Acquia\Wip\Exception\DependencyMissingException
   *   If the WipPoolStoreInterface implementation could not be found.
   */
  protected function getWipPoolStore() {
    return $this->dependencyManager->getDependency('acquia.wip.storage.wippool');
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.pool' => 'Acquia\Wip\Runtime\WipPool',
      'acquia.wip.storage.wippool' => 'Acquia\Wip\Storage\WipPoolStoreInterface',
    );
  }

}
