<?php

namespace Acquia\Wip\Test\PrivateStable\Objects;

use Acquia\Wip\Iterators\BasicIterator\StateTableIterator;
use Acquia\Wip\Objects\ContainerDelegate\ContainerDelegate;

/**
 * Missing summary.
 */
class ContainerDelegateTest extends \PHPUnit_Framework_TestCase {

  /**
   * The Wip ID.
   *
   * @var int
   */
  private $wipId = 15;

  /**
   * The Wip object.
   *
   * @var ContainerDelegate
   */
  private $wip = NULL;

  /**
   * The iterator.
   *
   * @var StateTableIterator
   */
  private $iterator = NULL;

  /**
   * Missing summary.
   */
  public function setUp() {
    $this->wip = new ContainerDelegate();
    $this->iterator = new StateTableIterator();
    $this->iterator->initialize($this->wip);
    $this->iterator->compileStateTable();
    $this->iterator->setId($this->wipId);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testGroup() {
    $obj = new ContainerDelegate();
    $original_group = $obj->getGroup();
    $this->assertNotEmpty($original_group);
    $group = 'my group';
    $obj->setGroup($group);
    $this->assertEquals($group, $obj->getGroup());
    $this->assertNotEquals($original_group, $group);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetGroupWrongType() {
    $group = 15;
    $obj = new ContainerDelegate();
    $obj->setGroup($group);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetGroupEmpty() {
    $group = '';
    $obj = new ContainerDelegate();
    $obj->setGroup($group);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testStart() {
    $this->wip->start($this->iterator->getWipContext('start'));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testWaitForContainer() {
    $this->wip->waitForContainer();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testKillContainer() {
    $this->wip->killContainer($this->iterator->getWipContext('killContainer'));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testAlertContainerStillRunning() {
    $this->wip->alertContainerStillRunning();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testCheckConfiguration() {
    $this->wip->checkConfiguration();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testWaitForContainerLaunch() {
    $context = $this->iterator->getWipContext('waitForContainerLaunch');
    $this->assertEquals('uninitialized', $this->wip->waitForContainerLaunch($context));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testVerifyContainerInitialization() {
    $context = $this->iterator->getWipContext('verifyContainerInitialization');
    $this->assertEquals('fail', $this->wip->verifyContainerInitialization($context));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testCheckContainerIsKilled() {
    $this->assertEquals('success', $this->wip->checkcontainerIsKilled());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testFinishContainer() {
    $this->wip->finishContainer();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testCheckShouldKillContainer() {
    $this->assertEquals('yes', $this->wip->checkShouldKillContainer());
    $this->wip->killContainerUponCompletion(FALSE);
    $this->assertEquals('no', $this->wip->checkShouldKillContainer());
    $this->wip->killContainerUponCompletion(TRUE);
    $this->assertEquals('yes', $this->wip->checkShouldKillContainer());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testFinish() {
    $this->wip->finish($this->iterator->getwipContext('finish'));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testFailure() {
    $this->wip->failure($this->iterator->getwipContext('failure'));
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testOnStart() {
    $this->wip->onStart();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testOnFinish() {
    $this->wip->onFinish();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testOnFail() {
    $this->wip->onFail();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testProcessRemainingSignals() {
    $this->wip->processRemainingSignals();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testReleaseResources() {
    $this->wip->releaseResources($this->iterator->getWipContext('releaseResources'));
  }

}
