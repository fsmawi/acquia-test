<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\Runtime\WipPoolController;
use Acquia\Wip\Storage\WipPoolStoreInterface;
use Acquia\Wip\TaskStatus;
use Acquia\Wip\Test\PrivateStable\Storage\WipPoolStoreTest;
use Acquia\Wip\WipFactory;

/**
 * Tests the WipPoolController class.
 */
class WipPoolControllerTest extends \PHPUnit_Framework_TestCase {
  /**
   * The WipPoolStore instance.
   *
   * @var WipPoolStoreInterface
   */
  private $wipPoolStore;

  /**
   * Make sure pause is not on.
   */
  public function setUp() {
    $this->wipPoolStore = WipFactory::getObject('acquia.wip.storage.wippool');
    $this->wipPoolStore->initialize();
    (new WipPoolController())->resumeGlobal();
  }

  /**
   * Tests that the pauseGlobal method works.
   */
  public function testHardPauseGlobal() {
    $controller = new WipPoolController();
    $this->assertFalse($controller->isHardPausedGlobal());
    $controller->hardPauseGlobal();
    $this->assertTrue($controller->isHardPausedGlobal());
    $controller->resumeGlobal();
    $this->assertFalse($controller->isHardPausedGlobal());
  }

  /**
   * Tests that the softPauseGlobal method works.
   */
  public function testSoftPauseGlobal() {
    $controller = new WipPoolController();
    $this->assertFalse($controller->isSoftPausedGlobal());
    $controller->softPauseGlobal();
    $this->assertTrue($controller->isSoftPausedGlobal());
    $controller->resumeGlobal();
    $this->assertFalse($controller->isSoftPausedGlobal());
  }

  /**
   * Tests that the hardPauseGroups method works.
   */
  public function testGroupHardPause() {
    $groups = array('test');

    $controller = new WipPoolController();
    $this->assertEmpty($controller->getHardPausedGroups());
    $controller->hardPauseGroups($groups);
    $this->assertEquals($groups, $controller->getHardPausedGroups());
    $success = $controller->resumeGroups($groups);
    $this->assertTrue($success);
    $this->assertEmpty($controller->getHardPausedGroups());
  }

  /**
   * Tests that the softPauseGroups method works.
   */
  public function testGroupSoftPause() {
    $groups = array('test');

    $controller = new WipPoolController();
    $this->assertEmpty($controller->getSoftPausedGroups());
    $controller->softPauseGroups($groups);
    $this->assertEquals($groups, $controller->getSoftPausedGroups());
    $success = $controller->resumeGroups($groups);
    $this->assertTrue($success);
    $this->assertEmpty($controller->getSoftPausedGroups());
  }

  /**
   * Tests that the pauseTask and resumeTask methods work.
   */
  public function testPauseTask() {
    for ($created = 0; $created < 2; $created++) {
      $task = WipPoolStoreTest::generateTask();
      $task->setStatus(TaskStatus::PROCESSING);
      $this->wipPoolStore->save($task);
      $tasks[] = $task;
    }

    $controller = WipPoolController::getWipPoolController();
    $this->assertCount(2, $controller->getTasksInProgress());
    $success = $controller->pauseTask(1);
    $this->assertTrue($success);
    $controller->resumeTask(1);
  }

}
