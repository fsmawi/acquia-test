<?php

namespace Acquia\WipService\Console\Command;

use Acquia\WipIntegrations\DoctrineORM\WipLogStore;
use Acquia\WipService\Console\AbstractWipToolTest;
use Acquia\WipService\Console\Commands\DeleteRecordsCommand;
use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\Runtime\WipPool;
use Acquia\Wip\Storage\WipLogStoreInterface;
use Acquia\Wip\TaskStatus;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipLogLevel;
use Igorw\Silex\ConfigServiceProvider;
use Ramsey\Uuid\Uuid;

/**
 * Tests that the DeleteRecordsCommandTest behaves as expected.
 */
class DeleteRecordsCommandTest extends AbstractWipToolTest {

  /**
   * The wip log store.
   *
   * @var WipLogStoreInterface
   */
  private $storage;

  /**
   * The wip log entity.
   *
   * @var WipLogInterface
   */
  private $wipLog;

  /**
   * A basic wip object.
   *
   * @var BasicWip
   */
  private $basicWip;

  /**
   * The wip pool.
   *
   * @var WipPool
   */
  private $pool;

  /**
   * Message displayed when no buildsteps are deleted.
   */
  const NO_BUILDSTEPS = 'Record clean up complete: 0 Buildsteps wip object(s) deleted';

  /**
   * Message displayed when no canarys are deleted.
   */
  const NO_CANARY = 'Record clean up complete: 0 Canary wip object(s) deleted';

  /**
   * Message displayed when buildsteps are deleted.
   */
  const DELETED_BUILDSTEPS = 'Record clean up complete: 1 Buildsteps wip object(s) deleted';

  /**
   * Message displayed when canarys are deleted.
   */
  const DELETED_CANARY = 'Record clean up complete: 1 Canary wip object(s) deleted';

  /**
   * Message displayed when logs are deleted.
   */
  const LOGS_MESSAGE = 'System log clean up complete.';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->storage = new WipLogStore($this->app);
    $this->wipLog = new WipLog($this->storage);
    $this->basicWip = new BasicWip();
    $this->basicWip->setUuid((string) Uuid::uuid4());
    $this->pool = new WipPool();

    WipFactory::setConfigPath('config/config.factory.test.cfg');
    $root_dir = $this->app['root_dir'];
    $this->app->register(new ConfigServiceProvider($root_dir . '/config/config.orm.testing.yml'));
  }

  /**
   * List of database clean tasks to check.
   *
   * Provides sleep time, task name and expected messages.
   *
   * @return array
   *   List of wip tasks to process.
   */
  public function wipTaskProvider() {
    $tasks = [];
    // Make sure nothing is removed when a task is not started.
    foreach (['Buildsteps', 'Canary'] as $task) {
      foreach ([0, 2, 5] as $time) {
        $tasks[] = [
          $time,
          $task,
          [
            self::NO_BUILDSTEPS,
            self::NO_CANARY,
          ],
          TaskStatus::NOT_STARTED,
        ];
      }
    }
    // Set up data that will be deleted.
    $tasks[] = [
      2,
      'Buildsteps',
      [
        self::DELETED_BUILDSTEPS,
        self::NO_CANARY,
      ],
      TaskStatus::COMPLETE,
    ];
    $tasks[] = [
      5,
      'Canary',
      [
        self::NO_BUILDSTEPS,
        self::DELETED_CANARY,
      ],
      TaskStatus::COMPLETE,
    ];
    return $tasks;
  }

  /**
   * Test to ensure database clean up works as excepted.
   *
   * @param int $sleep
   *   Time in seconds to sleep for.
   * @param string $task
   *   The database element to set data up for for purposes of clean up.
   * @param array $messages
   *   List of messages the console is expected to display.
   *
   * @dataProvider wipTaskProvider
   */
  public function testWipPoolDeletions($sleep, $task, $messages, $status) {
    $this->basicWip->setGroup($task);
    $task = $this->pool->addTask($this->basicWip);
    $task->setStatus($status);
    $this->pool->saveTask($task);
    sleep($sleep);
    $stdout = $this->executeCommand(new DeleteRecordsCommand(), 'dbcleanup')->getDisplay();
    foreach ($messages as $message) {
      $this->assertContains($message, $stdout);
    }
  }

  /**
   * List of database clean tasks to check.
   *
   * Provides sleep time and excepted counts.
   *
   * @return array
   *   List of log.
   */
  public function logProvider() {
    return [
      [0, 1],
      [2, 0],
    ];
  }

  /**
   * Test to ensure database clean up works as excepted.
   *
   * @param int $sleep
   *   Time in seconds to sleep for.
   * @param int $excepted_records
   *   Count of records found in database.
   *
   * @dataProvider logProvider
   */
  public function testLogDeletions($sleep, $excepted_records) {
    $this->wipLog->log(WipLogLevel::TRACE, 'message', 0);
    sleep($sleep);
    $this->executeCommand(new DeleteRecordsCommand(), 'dbcleanup');
    $records = $this->wipLog->getStore()->load(0);
    $this->assertEquals(count($records), $excepted_records);
  }

}
