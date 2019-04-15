<?php

namespace Acquia\WipIntegrations\DoctrineORM\Entities;

use Acquia\WipIntegrations\DoctrineORM\WipPoolStore;
use Acquia\WipService\Test\AbstractFunctionalTest;
use Acquia\Wip\Implementation\BasicTestWip;
use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\Task;
use Acquia\Wip\TaskExitStatus;
use Acquia\Wip\TaskInterface;
use Acquia\Wip\TaskPriority;
use Acquia\Wip\TaskStatus;
use Acquia\Wip\WipFactory;
use Ramsey\Uuid\Uuid;

/**
 * Missing summary.
 */
class WipPoolRepositoryFunctionalTest extends AbstractFunctionalTest {

  /**
   * The WipPoolStore instance.
   *
   * @var WipPoolStore
   */
  private $wipPoolStore;

  /**
   * The WipStore instance.
   *
   * @var \Acquia\Wip\Storage\WipStoreInterface
   */
  private $wipStore;

  /**
   * A task to be used.
   *
   * @var Task
   */
  private $task;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    WipFactory::setConfigPath('config/config.factory.test.cfg');

    $this->wipPoolStore = WipFactory::getObject('acquia.wip.storage.wippool');
    $this->wipStore = WipFactory::getObject('acquia.wip.storage.wip');

    $wip = new BasicWip();
    $wip->setUuid((string) Uuid::uuid4());
    $wip_iterator = WipFactory::getObject('acquia.wip.iterator');
    $wip_iterator->initialize($wip);
    $this->task = new Task();
    $this->task->setUuid($wip->getUuid());
    $this->task->setWipIterator($wip_iterator);
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testWipTaskAddWithoutIterator() {
    $task = new Task();
    $task->setUuid((string) Uuid::uuid4());
    $this->wipPoolStore->save($task);
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testWipTaskAddWithEmptyWorkId() {
    try {
      $wip = new BasicWip();
      $wip->setUuid((string) Uuid::uuid4());
      $task = new Task();
      $task->setUuid($wip->getUuid());
      $iterator = WipFactory::getObject('acquia.wip.iterator');
      $iterator->initialize($wip);
      $task->setWipIterator($iterator);
      $task->setStatus(TaskStatus::PROCESSING);
    } catch (\InvalidArgumentException $e) {
      throw new \Exception($e->getMessage());
    }
    $task->setWorkId('');
  }

  /**
   * Missing summary.
   */
  public function testWipTaskUpdateWithoutIterator() {
    $this->task->setParentId(rand());
    $this->task->setName(md5(rand()));
    $this->task->setGroupName(md5(rand()));
    $this->task->setCreatedTimestamp(rand());
    $this->task->setPriority(TaskPriority::LOW);
    $this->task->setStatus(TaskStatus::PROCESSING);
    $this->task->setExitStatus(TaskExitStatus::ERROR_SYSTEM);
    $this->task->setWakeTimestamp(rand());
    $this->task->setStartTimestamp(rand());
    $this->task->setCompletedTimestamp(rand());
    $this->task->setClaimedTimestamp(rand());
    $this->task->setLeaseTime(rand());
    $this->task->setTimeout(rand());
    $this->task->setPause((bool) rand(0, 1));
    $this->task->setExitMessage(md5(rand()));
    $this->task->setResourceId(md5(rand()));
    $this->task->setUuid((string) Uuid::uuid4());

    $this->wipPoolStore->save($this->task);

    $task_check = $this->wipPoolStore->get($this->task->getId());
    $this->assertNotEmpty($task_check);
    $this->assertEmpty($task_check->getWipIterator());
    $this->assertEquals($this->task->getParentId(), $task_check->getParentId());
    $this->assertEquals($this->task->getName(), $task_check->getName());
    $this->assertEquals($this->task->getGroupName(), $task_check->getGroupName());
    $this->assertEquals($this->task->getCreatedTimestamp(), $task_check->getCreatedTimestamp());
    $this->assertEquals($this->task->getPriority(), $task_check->getPriority());
    $this->assertEquals($this->task->getStatus(), $task_check->getStatus());
    $this->assertEquals($this->task->getExitStatus(), $task_check->getExitStatus());
    $this->assertEquals($this->task->getWakeTimestamp(), $task_check->getWakeTimestamp());
    $this->assertEquals($this->task->getStartTimestamp(), $task_check->getStartTimestamp());
    $this->assertEquals($this->task->getCompletedTimestamp(), $task_check->getCompletedTimestamp());
    $this->assertEquals($this->task->getClaimedTimestamp(), $task_check->getClaimedTimestamp());
    $this->assertEquals($this->task->getLeaseTime(), $task_check->getLeaseTime());
    $this->assertEquals($this->task->getTimeout(), $task_check->getTimeout());
    $this->assertEquals($this->task->isPaused(), $task_check->isPaused());
    $this->assertEquals($this->task->getExitMessage(), $task_check->getExitMessage());
    $this->assertEquals($this->task->getResourceId(), $task_check->getResourceId());
    $this->assertEquals($this->task->getUuid(), $task_check->getUuid());

    $this->wipPoolStore->save($task_check);
  }

  /**
   * Missing summary.
   */
  public function testWipTaskAdd() {
    // Add a task to the Wip pool.
    $this->wipPoolStore->save($this->task);

    // Ensure it was stored properly.
    $task_check = $this->wipPoolStore->get($this->task->getId());
    $this->assertNotEmpty($task_check);
    $this->assertEquals($this->task->getId(), $task_check->getId());
  }

  /**
   * Missing summary.
   */
  public function testWipTaskRemove() {
    // Add a task to the Wip pool.
    $this->wipPoolStore->save($this->task);

    // Ensure it was stored properly.
    $task_check = $this->wipPoolStore->get($this->task->getId());
    $this->assertNotEmpty($task_check);
    $this->assertEquals($this->task->getId(), $task_check->getId());

    // Remove the task and ensure it succeeded.
    $this->wipPoolStore->remove($this->task);
    $task_check = $this->wipPoolStore->get($this->task->getId());
    $this->assertEmpty($task_check);
  }

  /**
   * Tests that sorts work.
   *
   * @group concurrency
   */
  public function testWipTaskGetNextPriority() {
    $wip = new BasicWip();
    $wip->setUuid((string) Uuid::uuid4());
    $task1 = new Task();
    $task1->setUuid($wip->getUuid());
    $iterator = WipFactory::getObject('acquia.wip.iterator');
    $iterator->initialize($wip);
    $task1->setWipIterator($iterator);
    $task1->setStatus(TaskStatus::NOT_STARTED);
    $this->wipPoolStore->save($task1);

    $wip = new BasicWip();
    $wip->setUuid((string) Uuid::uuid4());
    $task2 = new Task();
    $task2->setUuid($wip->getUuid());
    $iterator = WipFactory::getObject('acquia.wip.iterator');
    $iterator->initialize($wip);
    $task2->setWipIterator($iterator);
    $task2->setStatus(TaskStatus::NOT_STARTED);
    $task2->setPriority(TaskPriority::CRITICAL);
    $task2->setIsPrioritized(TRUE);
    $this->wipPoolStore->save($task2);

    $wip = new BasicWip();
    $wip->setUuid((string) Uuid::uuid4());
    $task3 = new Task();
    $task3->setUuid($wip->getUuid());
    $iterator = WipFactory::getObject('acquia.wip.iterator');
    $iterator->initialize($wip);
    $task3->setWipIterator($iterator);
    $task3->setStatus(TaskStatus::NOT_STARTED);
    $task3->setIsTerminating(TRUE);
    $this->wipPoolStore->save($task3);

    // Ensure that a not yet running task can be claimed.
    $next_tasks = $this->wipPoolStore->getNextTasks(3);
    $this->assertNotEmpty($next_tasks);
    $task_ids = [];
    foreach ($next_tasks as $next_task) {
      $task_ids[] = $next_task->getId();
    }
    $this->assertEquals($task2->getId(), $task_ids[0]);
    $this->assertEquals($task3->getId(), $task_ids[1]);
    $this->assertEquals($task1->getId(), $task_ids[2]);
  }

  /**
   * Missing summary.
   *
   * @group concurrency
   */
  public function testWipTaskGetNext() {
    $wip = new BasicWip();
    $wip->setUuid((string) Uuid::uuid4());
    $task1 = new Task();
    $task1->setUuid($wip->getUuid());
    $iterator = WipFactory::getObject('acquia.wip.iterator');
    $iterator->initialize($wip);
    $task1->setWipIterator($iterator);
    $task1->setStatus(TaskStatus::PROCESSING);
    $this->wipPoolStore->save($task1);

    $wip = new BasicWip();
    $wip->setUuid((string) Uuid::uuid4());
    $task2 = new Task();
    $task2->setUuid($wip->getUuid());
    $iterator = WipFactory::getObject('acquia.wip.iterator');
    $iterator->initialize($wip);
    $task2->setWipIterator($iterator);
    $task2->setStatus(TaskStatus::NOT_STARTED);
    $this->wipPoolStore->save($task2);

    $wip = new BasicWip();
    $wip->setUuid((string) Uuid::uuid4());
    $task3 = new Task();
    $task3->setUuid($wip->getUuid());
    $task3_work_id = $wip->getWorkId();
    $iterator = WipFactory::getObject('acquia.wip.iterator');
    $iterator->initialize($wip);
    $task3->setWipIterator($iterator);
    $task3->setStatus(TaskStatus::PROCESSING);
    $this->wipPoolStore->save($task3);

    // Ensure that a not yet running task can be claimed.
    $next_tasks = $this->wipPoolStore->getNextTasks();
    $this->assertNotEmpty($next_tasks);
    $next_task = reset($next_tasks);
    $this->assertEquals(TaskStatus::NOT_STARTED, $next_task->getStatus());

    // Ensure that we cannot claim a task which has a work id matching a task
    // in process. We do this by making sure that there are no waiting tasks,
    // then add a task with a work id that matches one of the running tasks.
    // If the feature is working, we should not be able to start the new task.
    $wip = new BasicTestWip();
    $wip->setUuid((string) Uuid::uuid4());
    $wip->setWorkId($task3_work_id);
    $task3v2 = new Task();
    $task3v2->setUuid($wip->getUuid());
    $iterator = WipFactory::getObject('acquia.wip.iterator');
    $iterator->initialize($wip);
    $task3v2->setWipIterator($iterator);
    $task3v2->setWorkId($task3_work_id);
    $task3v2->setStatus(TaskStatus::NOT_STARTED);
    $this->wipPoolStore->save($task3v2);

    // Mark the second task as processing to ensure that the next in line is
    // the task3v2 task.
    $task2->setStatus(TaskStatus::PROCESSING);
    $this->wipPoolStore->save($task2);

    // Attempt to get the task3v2 task, which should not work, because task3 is
    // in process.
    $next_tasks = $this->wipPoolStore->getNextTasks();
    $next_task = reset($next_tasks);
    $this->assertEmpty($next_task);

    // Mark task3 as complete and see if we can get now task3v2.
    $task3->setStatus(TaskStatus::COMPLETE);
    $this->wipPoolStore->save($task3);
    $next_tasks = $this->wipPoolStore->getNextTasks();
    $next_task = reset($next_tasks);
    $this->assertNotEmpty($next_tasks);
    $this->assertEquals($task3v2->getId(), $next_task->getId());
  }

  /**
   * Missing summary.
   *
   * @group concurrency
   */
  public function testAddConcurrency() {
    $connection = $this->entityManager->getConnection();

    $wip = new BasicWip();
    $wip->setUuid((string) Uuid::uuid4());
    $task = new Task();
    $task->setUuid($wip->getUuid());
    $iterator = WipFactory::getObject('acquia.wip.iterator');
    $iterator->initialize($wip);
    $task->setWipIterator($iterator);
    $task->setStatus(TaskStatus::NOT_STARTED);
    $task->setGroupName((string) rand(1, 1000000));
    $this->wipPoolStore->save($task);

    // Check that the concurrency table is empty to start with.
    $concurrency = $connection->fetchAssoc('SELECT * FROM wip_group_concurrency');
    $this->assertEmpty($concurrency);

    // getNextTasks, followed by startProgress() is what the WipWorker manages
    // for us under normal operation.  Tests that WipWorker calls this hook for
    // us live in the WipWorker tests within WIPNG library.  stopProgress is
    // also called by WipWorker.
    $next_tasks = $this->wipPoolStore->getNextTasks();
    $next_task = reset($next_tasks);
    $this->wipPoolStore->startProgress($next_task);

    $concurrency = $connection->fetchAssoc('SELECT * FROM wip_group_concurrency');
    $this->assertEquals($next_task->getGroupName(), $concurrency['group_name']);
    $this->assertEquals($next_task->getId(), $concurrency['wid']);

    $next_task->setStatus(TaskStatus::WAITING);
    $this->wipPoolStore->save($next_task);
    $this->wipPoolStore->stopProgress($next_task);

    // Concurrency shouldn't be removed if the task is waiting.
    $concurrency = $connection->fetchAssoc('SELECT * FROM wip_group_concurrency');
    $this->assertEquals($next_task->getGroupName(), $concurrency['group_name']);
    $this->assertEquals($next_task->getId(), $concurrency['wid']);

    $next_task->setStatus(TaskStatus::COMPLETE);
    $this->wipPoolStore->save($next_task);
    $this->wipPoolStore->stopProgress($next_task);

    // Concurrency should be removed if the task is done.
    $concurrency = $connection->fetchAssoc('SELECT * FROM wip_group_concurrency');
    $this->assertEmpty($concurrency);
  }

  /**
   * Missing summary.
   *
   * @group slow
   *
   * @group concurrency
   */
  public function testTaskConcurrency() {
    $connection = $this->entityManager->getConnection();

    $group1 = rand(1, PHP_INT_MAX);
    $group2 = rand(1, PHP_INT_MAX);
    $group3 = rand(1, PHP_INT_MAX);

    // Add tasks belonging to 3 different groups, 10 in each group.
    for ($i = 0; $i < 10; ++$i) {
      $this->addGroupTask($group1);
      $this->addGroupTask($group2);
      $this->addGroupTask($group3);
    }

    // Specify max concurrency of 1 for the first and 2 for the second group.
    $sql = 'INSERT IGNORE INTO wip_group_max_concurrency (group_name, max_count) VALUES (:group_name, :max)';
    $connection->executeUpdate($sql, array(':group_name' => $group1, ':max' => 1));
    $connection->executeUpdate($sql, array(':group_name' => $group2, ':max' => 2));
    // Set group 3 to have concurrency of 0: none of these should run.
    $connection->executeUpdate($sql, array(':group_name' => $group3, ':max' => 0));

    // The first 3 tasks should be exactly 1 from group 1 and 2 from group2, but
    // we don't guarantee the order in which they come out.
    $group1_tasks = 0;
    $group2_tasks = 0;
    // Instead of waiting for the process to eventually get the new max concurrency from the table,
    // clear cached values immediately.
    $this->wipPoolStore->clearCachedConcurrencyGroups();
    /* @var TaskInterface[] $next_tasks */
    $next_tasks = $this->wipPoolStore->getNextTasks(10);
    foreach ($next_tasks as $next_task) {
      // startProgress is what actually sets concurrency.
      $this->wipPoolStore->startProgress($next_task);
      if ($next_task->getGroupName() == $group1) {
        $group1_tasks++;
      } elseif ($next_task->getGroupName() == $group2) {
        $group2_tasks++;
      } else {
        // If we reach here, something is broken - BOOM.
        $this->assertTrue(FALSE);
      }
      // Save status as in progress so we don't pull this same task again.
      $next_task->setStatus(TaskStatus::PROCESSING);
      $this->wipPoolStore->save($next_task);
    }
    $this->assertEquals(1, $group1_tasks);
    $this->assertEquals(2, $group2_tasks);

    // Check there are some available tasks left in the pool.
    $select_sql = 'SELECT * FROM wip_pool WHERE run_status = :status';
    $result = $connection->fetchAll($select_sql, array(':status' => TaskStatus::NOT_STARTED));
    $this->assertNotEmpty($result);

    // At this point, we have hit max concurrency for all groups in the pool: we
    // should no longer get given tasks, even though there are some left in the
    // table.
    $next_tasks = $this->wipPoolStore->getNextTasks(10);
    foreach ($next_tasks as $next_task) {
      if ($next_task) {
        $this->wipPoolStore->startProgress($next_task);
        $next_task->setStatus(TaskStatus::PROCESSING);
        $this->wipPoolStore->save($next_task);
      }
      $this->assertFalse($next_task);
      usleep(100000);
    }

    // Remove concurrency block from group 3 and set to a new value.
    $group3_concurrency = rand(1, 10);
    $delete_sql = 'DELETE FROM wip_group_max_concurrency WHERE group_name = :group_name';
    $connection->executeUpdate($delete_sql, array(':group_name' => $group3));
    $insert_sqlq = 'INSERT IGNORE INTO wip_group_max_concurrency (group_name, max_count) VALUES (:group_name, :max)';
    $connection->executeUpdate($insert_sqlq, array(':group_name' => $group3, ':max' => $group3_concurrency));

    // Run getNextTasks 40 more times, only expecting to get less than 10 more
    // objects back (all tasks of group 3 only - the count depends on
    // $group3_concurrency).
    $tasks = array();
    // Instead of waiting for the process to eventually get the new max concurrency from the table,
    // clear cached values immediately.
    $this->wipPoolStore->clearCachedConcurrencyGroups();
    $next_tasks = $this->wipPoolStore->getNextTasks(40);
    foreach ($next_tasks as $next_task) {
      if ($next_task) {
        $this->wipPoolStore->startProgress($next_task);
        $next_task->setStatus(TaskStatus::PROCESSING);
        $this->wipPoolStore->save($next_task);
        $tasks[] = $next_task;
        $this->assertEquals($group3, $next_task->getGroupName());
      }
      usleep(100000);
    }
    $this->assertCount($group3_concurrency, $tasks);
  }

  /**
   * Ensure that we can clean up the max group concurrency table.
   *
   * @group concurrency
   */
  public function testCleanupConcurrency() {
    $group_name = rand(1, PHP_INT_MAX);

    $connection = $this->entityManager->getConnection();
    $sql = 'INSERT IGNORE INTO wip_group_max_concurrency (group_name, max_count) VALUES (:group_name, :max)';
    $connection->executeUpdate($sql, array(':group_name' => $group_name, ':max' => 1));

    $this->addGroupTask($group_name);
    $this->addGroupTask($group_name);

    $next_tasks = $this->wipPoolStore->getNextTasks();
    $first_task = reset($next_tasks);
    // startProgress is what actually sets concurrency.
    $this->wipPoolStore->startProgress($first_task);
    // Save status as in progress so we don't pull this same task again.
    $first_task->setStatus(TaskStatus::PROCESSING);
    $this->wipPoolStore->save($first_task);

    // Force the store to clean up concurrency, this should do nothing.
    $this->wipPoolStore->cleanupConcurrency();

    // Concurrency should limit us to just the one task in progress.
    $next_tasks = $this->wipPoolStore->getNextTasks();
    $no_task = reset($next_tasks);
    $this->assertEmpty($no_task);

    // Simulate a non-clean exit for the first task.
    $first_task->setExitStatus(TaskExitStatus::ERROR_SYSTEM);
    $this->wipPoolStore->save($first_task);

    // Concurrency should still limit us to just the one task in progress.
    $next_tasks = $this->wipPoolStore->getNextTasks();
    $no_task = reset($next_tasks);
    $this->assertEmpty($no_task);

    // Force the store to clean up concurrency.
    $this->wipPoolStore->cleanupConcurrency();

    // Prove that we can get the second task after the cleanup.
    $next_tasks = $this->wipPoolStore->getNextTasks();
    $second_task = reset($next_tasks);
    $this->assertNotEmpty($second_task);
    $this->assertNotEquals($first_task->getId(), $second_task->getId());
  }

  /**
   * Missing summary.
   */
  public function testNoClaimed() {
    // Just ensure that a claimed task will not be returned by getNextTask.
    $wip = new BasicWip();
    $wip->setUuid((string) Uuid::uuid4());
    $task = new Task();
    $task->setUuid($wip->getUuid());
    $iterator = WipFactory::getObject('acquia.wip.iterator');
    $iterator->initialize($wip);
    $task->setWipIterator($iterator);
    $task->setStatus(TaskStatus::NOT_STARTED);
    $task->setClaimedTimestamp(time());
    $task->setGroupName($group = (string) rand(1, PHP_INT_MAX));
    $this->wipPoolStore->save($task);

    $next_tasks = $this->wipPoolStore->getNextTasks();
    $next_task = reset($next_tasks);
    $this->assertFalse($next_task);

    $task->setClaimedTimestamp(Task::NOT_CLAIMED);
    $this->wipPoolStore->save($task);

    $next_tasks = $this->wipPoolStore->getNextTasks();
    $next_task = reset($next_tasks);
    $this->assertNotEmpty($next_task);
    $this->assertEquals($group, $next_task->getGroupName());
  }

  /**
   * Missing summary.
   */
  public function testTaskOrder() {
    // Add a handful of tasks.
    $group = rand(1, PHP_INT_MAX);

    // Notch concurrency up to 8 so that all tasks in this test can run
    // concurrently.
    $connection = $this->entityManager->getConnection();
    $sql = 'INSERT IGNORE INTO wip_group_max_concurrency (group_name, max_count) VALUES (:group_name, :max)';
    $connection->executeUpdate($sql, array(':group_name' => $group, ':max' => 8));

    // Just check first that priority ordering works ok.  Add them in a random
    // order to avoid testing insertion order.
    $first = $this->addGroupTask($group, TaskPriority::MEDIUM);
    $second = $this->addGroupTask($group, TaskPriority::LOW);
    $third = $this->addGroupTask($group, TaskPriority::CRITICAL);
    $fourth = $this->addGroupTask($group, TaskPriority::HIGH);

    $next_tasks = $this->wipPoolStore->getNextTasks();
    $next = reset($next_tasks);
    $next->setStatus(TaskStatus::PROCESSING);
    $this->wipPoolStore->save($next);
    $this->assertEquals($third->getId(), $next->getId());
    $next_tasks = $this->wipPoolStore->getNextTasks();
    $next = reset($next_tasks);
    $next->setStatus(TaskStatus::PROCESSING);
    $this->wipPoolStore->save($next);
    $this->assertEquals($fourth->getId(), $next->getId());
    $next_tasks = $this->wipPoolStore->getNextTasks();
    $next = reset($next_tasks);
    $next->setStatus(TaskStatus::PROCESSING);
    $this->wipPoolStore->save($next);
    $this->assertEquals($first->getId(), $next->getId());
    $next_tasks = $this->wipPoolStore->getNextTasks();
    $next = reset($next_tasks);
    $next->setStatus(TaskStatus::PROCESSING);
    $this->wipPoolStore->save($next);
    $this->assertEquals($second->getId(), $next->getId());

    // Repeat the test, but doctor the lowest one so that status = WAITING. We
    // need to set all the tasks to have the same wake timestamp so that it's a
    // fair comparison.
    $first = $this->addGroupTask($group, TaskPriority::MEDIUM);
    $first->setWakeTimestamp(time() - 1);
    $this->wipPoolStore->save($first);
    $second = $this->addGroupTask($group, TaskPriority::LOW);
    // Just set the lowest priority item to waiting and ensure it's in the
    // concurrency table to hopefully force it to be the highest priority item.
    $second->setStatus(TaskStatus::WAITING);
    $second->setWakeTimestamp(time() - 1);
    $this->wipPoolStore->save($second);
    $this->wipPoolStore->startProgress($second);
    $third = $this->addGroupTask($group, TaskPriority::CRITICAL);
    $third->setWakeTimestamp(time() - 1);
    $this->wipPoolStore->save($third);
    $fourth = $this->addGroupTask($group, TaskPriority::HIGH);
    $fourth->setWakeTimestamp(time() - 1);
    $this->wipPoolStore->save($fourth);

    // Assert that our expected task is the first one we get out of the pool:
    // we're using low priority to attempt to confuse the system, but the first
    // task we expect out is one which is waiting, and is logged in the
    // concurrency table, where all other criteria are the same.
    $next_tasks = $this->wipPoolStore->getNextTasks();
    $next = reset($next_tasks);
    $next->setStatus(TaskStatus::PROCESSING);
    $this->wipPoolStore->save($next);
    $this->assertEquals($second->getId(), $next->getId());
  }

  /**
   * Builds and adds a task to the pool with a given group name.
   *
   * @param string $group_name
   *   The group name.
   * @param int $priority
   *   The priority.
   *
   * @return Task
   *   The task.
   */
  private function addGroupTask($group_name, $priority = TaskPriority::MEDIUM) {
    $wip = new BasicWip();
    $wip->setUuid((string) Uuid::uuid4());
    $task = new Task();
    $task->setUuid($wip->getUuid());
    $iterator = WipFactory::getObject('acquia.wip.iterator');
    $iterator->initialize($wip);
    $task->setWipIterator($iterator);
    $task->setStatus(TaskStatus::NOT_STARTED);
    $task->setGroupName((string) $group_name);
    $task->setPriority($priority);
    $this->wipPoolStore->save($task);
    return $task;
  }

  /**
   * Missing summary.
   */
  public function testPrune() {
    $delete_time_limit = time();

    $wip = new BasicWip();
    $wip->setUuid((string) Uuid::uuid4());
    $iterator = WipFactory::getObject('acquia.wip.iterator');
    $iterator->initialize($wip);

    // Add an old and finished task to be deleted.
    $old_finished_task1 = new Task();
    $old_finished_task1->setUuid($wip->getUuid());
    $old_finished_task1->setWipIterator($iterator);
    $old_finished_task1->setStatus(TaskStatus::COMPLETE);
    $old_finished_task1->setCreatedTimestamp($delete_time_limit);
    $this->wipPoolStore->save($old_finished_task1);
    $task_check = $this->wipPoolStore->get($old_finished_task1->getId());
    $this->assertNotEmpty($task_check);
    $this->assertEquals($old_finished_task1->getId(), $task_check->getId());
    $this->wipStore->save($old_finished_task1->getId(), $iterator);
    $wip_check = $this->wipStore->get($old_finished_task1->getId());
    $this->assertNotEmpty($wip_check);

    $wip = new BasicWip();
    $wip->setUuid((string) Uuid::uuid4());
    $iterator->initialize($wip);

    // Add an old and finished task to be deleted.
    $old_finished_task2 = new Task();
    $old_finished_task2->setUuid($wip->getUuid());
    $old_finished_task2->setWipIterator($iterator);
    $old_finished_task2->setStatus(TaskStatus::COMPLETE);
    $old_finished_task2->setCreatedTimestamp($delete_time_limit);
    $this->wipPoolStore->save($old_finished_task2);
    $task_check = $this->wipPoolStore->get($old_finished_task2->getId());
    $this->assertNotEmpty($task_check);
    $this->assertEquals($old_finished_task2->getId(), $task_check->getId());
    $this->wipStore->save($old_finished_task2->getId(), $iterator);
    $wip_check = $this->wipStore->get($old_finished_task2->getId());
    $this->assertNotEmpty($wip_check);

    $wip = new BasicWip();
    $wip->setUuid((string) Uuid::uuid4());
    $iterator->initialize($wip);

    // Add an old and unfinished task that should not be deleted.
    $old_unfinished_task = new Task();
    $old_unfinished_task->setUuid($wip->getUuid());
    $old_unfinished_task->setWipIterator($iterator);
    $old_unfinished_task->setStatus(TaskStatus::RESTARTED);
    $old_unfinished_task->setCreatedTimestamp($delete_time_limit);
    $this->wipPoolStore->save($old_unfinished_task);
    $task_check = $this->wipPoolStore->get($old_unfinished_task->getId());
    $this->assertNotEmpty($task_check);
    $this->assertEquals($old_unfinished_task->getId(), $task_check->getId());
    $this->wipStore->save($old_unfinished_task->getId(), $iterator);
    $wip_check = $this->wipStore->get($old_unfinished_task->getId());
    $this->assertNotEmpty($wip_check);

    $wip = new BasicWip();
    $wip->setUuid((string) Uuid::uuid4());
    $iterator->initialize($wip);

    // Add a recent finished task to should not be deleted.
    $recent_finished_task = new Task();
    $recent_finished_task->setUuid($wip->getUuid());
    $recent_finished_task->setWipIterator($iterator);
    $recent_finished_task->setStatus(TaskStatus::COMPLETE);
    $recent_finished_task->setCreatedTimestamp($delete_time_limit + 1);
    $this->wipPoolStore->save($recent_finished_task);
    $task_check = $this->wipPoolStore->get($recent_finished_task->getId());
    $this->assertNotEmpty($task_check);
    $this->assertEquals($recent_finished_task->getId(), $task_check->getId());
    $this->wipStore->save($recent_finished_task->getId(), $iterator);
    $wip_check = $this->wipStore->get($recent_finished_task->getId());
    $this->assertNotEmpty($wip_check);

    $result = $this->wipPoolStore->prune($delete_time_limit);

    // Check that the old and finished tasks and their Wip objects were deleted.
    $task_check = $this->wipPoolStore->get($old_finished_task1->getId());
    $this->assertEmpty($task_check);
    $wip_check = $this->wipStore->get($old_finished_task1->getId());
    $this->assertEmpty($wip_check);

    $task_check = $this->wipPoolStore->get($old_finished_task2->getId());
    $this->assertEmpty($task_check);
    $wip_check = $this->wipStore->get($old_finished_task2->getId());
    $this->assertEmpty($wip_check);

    // Check that the old unfinished and recent tasks are still in place.
    $task_check = $this->wipPoolStore->get($old_unfinished_task->getId());
    $this->assertNotEmpty($task_check);
    $this->assertEquals($old_unfinished_task->getId(), $task_check->getId());
    $wip_check = $this->wipStore->get($old_unfinished_task->getId());
    $this->assertNotEmpty($wip_check);

    $task_check = $this->wipPoolStore->get($recent_finished_task->getId());
    $this->assertNotEmpty($task_check);
    $this->assertEquals($recent_finished_task->getId(), $task_check->getId());
    $wip_check = $this->wipStore->get($recent_finished_task->getId());
    $this->assertNotEmpty($wip_check);

    // The prune's result should be FALSE, as in no more items to be deleted.
    $this->assertFalse($result);
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testPruneInvalidTimestamp() {
    $this->wipPoolStore->prune(NULL);
  }

  /**
   * Missing summary.
   */
  public function testPruneLimited() {
    $delete_time_limit = time();

    $wip = new BasicWip();
    $wip->setUuid((string) Uuid::uuid4());
    $iterator = WipFactory::getObject('acquia.wip.iterator');
    $iterator->initialize($wip);

    // Add an old and finished thread to be deleted.
    $old_finished_task1 = new Task();
    $old_finished_task1->setUuid($wip->getUuid());
    $old_finished_task1->setWipIterator($iterator);
    $old_finished_task1->setStatus(TaskStatus::COMPLETE);
    $old_finished_task1->setCreatedTimestamp($delete_time_limit);
    $this->wipPoolStore->save($old_finished_task1);
    $task_check = $this->wipPoolStore->get($old_finished_task1->getId());
    $this->assertNotEmpty($task_check);
    $this->assertEquals($old_finished_task1->getId(), $task_check->getId());

    // Add an old and finished thread to be deleted.
    $old_finished_task2 = new Task();
    $old_finished_task2->setUuid($wip->getUuid());
    $old_finished_task2->setWipIterator($iterator);
    $old_finished_task2->setStatus(TaskStatus::COMPLETE);
    $old_finished_task2->setCreatedTimestamp($delete_time_limit);
    $this->wipPoolStore->save($old_finished_task2);
    $task_check = $this->wipPoolStore->get($old_finished_task2->getId());
    $this->assertNotEmpty($task_check);
    $this->assertEquals($old_finished_task2->getId(), $task_check->getId());

    $result = $this->wipPoolStore->prune($delete_time_limit, 1);

    // One of the threads must be deleted and the other must be still around.
    $task_check1 = $this->wipPoolStore->get($old_finished_task1->getId());
    $task_check2 = $this->wipPoolStore->get($old_finished_task2->getId());
    $this->assertTrue(empty($task_check1) xor empty($task_check2));
    // The prune's result should be TRUE, as in there are more items to be
    // deleted.
    $this->assertTrue($result);
  }

  /**
   * Missing summary.
   */
  public function testPruneWhenEmpty() {
    $this->wipPoolStore->prune(time());
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testPruneInvalidLimit() {
    $this->wipPoolStore->prune(time(), NULL);
  }

  /**
   * Missing summary.
   */
  public function testGetChildren() {
    $wip = new BasicWip();
    $wip->setUuid((string) Uuid::uuid4());
    $iterator = WipFactory::getObject('acquia.wip.iterator');
    $iterator->initialize($wip);

    // Add a task that will act as a parent.
    $parent_task = new Task();
    $parent_task->setUuid($wip->getUuid());
    $parent_task->setWipIterator($iterator);
    $this->wipPoolStore->save($parent_task);
    $task_check = $this->wipPoolStore->get($parent_task->getId());
    $this->assertNotEmpty($task_check);
    $this->assertEquals($parent_task->getId(), $task_check->getId());

    // Ensure that the list of children are empty.
    $retrieved_children_task_ids = $this->wipPoolStore->getChildrenTaskIds($parent_task->getId());
    $this->assertEquals(array(), $retrieved_children_task_ids);

    // Add some children tasks to the parent.
    $children_task_ids = array();
    for ($i = 0; $i < rand(5, 10); $i++) {
      $children_task = new Task();
      $children_task->setUuid($wip->getUuid());
      $children_task->setWipIterator($iterator);
      $children_task->setParentId($parent_task->getId());
      $this->wipPoolStore->save($children_task);
      $task_check = $this->wipPoolStore->get($children_task->getId());
      $this->assertNotEmpty($task_check);
      $this->assertEquals($children_task->getId(), $task_check->getId());
      $children_task_ids[] = $children_task->getId();
    }

    // Add a task that will act as a parent but not queried upon.
    $noisy_parent_task = new Task();
    $noisy_parent_task->setUuid($wip->getUuid());
    $noisy_parent_task->setWipIterator($iterator);
    $this->wipPoolStore->save($noisy_parent_task);
    $task_check = $this->wipPoolStore->get($noisy_parent_task->getId());
    $this->assertNotEmpty($task_check);
    $this->assertEquals($noisy_parent_task->getId(), $task_check->getId());

    // Add some children tasks to the noise parent.
    for ($i = 0; $i < rand(1, 5); $i++) {
      $children_task = new Task();
      $children_task->setUuid($wip->getUuid());
      $children_task->setWipIterator($iterator);
      $children_task->setParentId($noisy_parent_task->getId());
      $this->wipPoolStore->save($children_task);
      $task_check = $this->wipPoolStore->get($children_task->getId());
      $this->assertNotEmpty($task_check);
      $this->assertEquals($children_task->getId(), $task_check->getId());
    }

    $retrieved_children_task_ids = $this->wipPoolStore->getChildrenTaskIds($parent_task->getId());

    $this->assertEquals($children_task_ids, $retrieved_children_task_ids);
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testGetChildrenInvalidArgumentNonInteger() {
    $this->wipPoolStore->getChildrenTaskIds(NULL);
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testGetChildrenInvalidArgumentInteger() {
    $this->wipPoolStore->getChildrenTaskIds(0);
  }

}
