<?php

namespace Acquia\Wip\Test\PrivateStable\Storage;

use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\Storage\WipPoolStoreInterface;
use Acquia\Wip\Task;
use Acquia\Wip\TaskStatus;
use Acquia\Wip\WipFactory;
use Ramsey\Uuid\Uuid;

/**
 * Missing summary.
 */
class WipPoolStoreTest extends \PHPUnit_Framework_TestCase {

  /**
   * The WipPoolStore instance.
   *
   * @var WipPoolStoreInterface
   */
  private $wipPoolStore;

  /**
   * A task to be used.
   *
   * @var Task
   */
  private $task;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    $this->wipPoolStore = WipFactory::getObject('acquia.wip.storage.wippool');
    $this->wipPoolStore->initialize();
    $this->task = self::generateTask();
  }

  /**
   * Generates a task.
   *
   * @param string $uuid
   *   (optional) The UUID of the user to associate with the task.
   *
   * @return Task
   *   An instance of Task.
   */
  public static function generateTask($uuid = NULL) {
    if ($uuid === NULL) {
      $uuid = (string) Uuid::uuid4();
    }
    $wip = new BasicWip();
    $wip->setUuid($uuid);
    $wip_iterator = WipFactory::getObject('acquia.wip.iterator');
    $wip_iterator->initialize($wip);
    $task = new Task();
    $task->setUuid($wip->getUuid());
    $task->setWipIterator($wip_iterator);
    return $task;
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
   */
  public function testWipTaskUpdateWithoutIterator() {
    $task = new Task();
    $task->setUuid((string) Uuid::uuid4());
    $task->setId(rand());
    $this->wipPoolStore->save($task);
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
   * Missing summary.
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
