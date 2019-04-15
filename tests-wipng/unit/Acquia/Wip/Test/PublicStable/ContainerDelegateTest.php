<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\Objects\ContainerDelegate\ContainerDelegate;
use Acquia\Wip\WipTaskConfig;

/**
 * Missing summary.
 */
class ContainerDelegateTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @var ContainerDelegate
   */
  private $wip = NULL;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    $this->wip = new ContainerDelegate();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   *
   * @expectedException \DomainException
   */
  public function testGetWorkIdWithNoConfiguration() {
    $this->wip->getWorkId();
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testGetWorkIdWithBasicWip() {
    $config = $this->createBasicWipTaskConfig();
    $this->wip->setWipTaskConfig($config);
    $work_id = $this->wip->getWorkId();
    $this->assertNotEmpty($work_id);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testGetWorkIdForBasicWipSameConfigNotEqual() {
    $config = $this->createBasicWipTaskConfig();
    $this->wip->setWipTaskConfig($config);
    $work_id = $this->wip->getWorkId();

    $new_wip = new ContainerDelegate();
    $new_wip->setWipTaskConfig($config);
    $this->assertNotEquals($work_id, $new_wip->getWorkId());
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testGetWorkIdWithBuildStepsWip() {
    $uri = 'uri';
    $vcs_path = 'vcs_path';
    $deploy_path = 'deploy-path';
    $config = $this->createBuildStepsWipTaskConfig($this->createTaskOptions($uri, $vcs_path, $deploy_path));
    $this->wip->setWipTaskConfig($config);
    $work_id = $this->wip->getWorkId();
    $this->assertNotEmpty($work_id);
  }

  /**
   * Missing summary.
   *
   * @group Wip
   */
  public function testGetWorkIdWithBuildStepsWipSameConfigEqual() {
    $uri = 'uri';
    $vcs_path = 'vcs_path';
    $deploy_path = 'deploy-path';
    $config = $this->createBuildStepsWipTaskConfig($this->createTaskOptions($uri, $vcs_path, $deploy_path));
    $this->wip->setWipTaskConfig($config);
    $work_id = $this->wip->getWorkId();

    // Create a new wip with the same config.
    $new_wip = new ContainerDelegate();
    $config = $this->createBuildStepsWipTaskConfig($this->createTaskOptions($uri, $vcs_path, $deploy_path));
    $new_wip->setWipTaskConfig($config);
    $this->assertEquals($work_id, $new_wip->getWorkId());
  }

  /**
   * Missing summary.
   */
  private function createBasicWipTaskConfig($options = NULL) {
    if (empty($options)) {
      $options = new \stdClass();
    }
    $result = new WipTaskConfig();
    $result->setClassId('Acquia\Wip\Implementation\BasicWip');
    $result->setOptions($options);
    return $result;
  }

  /**
   * Missing summary.
   */
  private function createBuildStepsWipTaskConfig($options) {
    $result = new WipTaskConfig();
    $result->setClassId('Acquia\Wip\Modules\NativeModule\BuildSteps');
    $result->setOptions($options);
    return $result;
  }

  /**
   * Missing summary.
   */
  private function createTaskOptions($uri = NULL, $build_path = NULL, $deploy_path = NULL) {
    $result = new \stdClass();
    if (!empty($uri)) {
      $result->vcsUri = $uri;
    }
    if (!empty($build_path)) {
      $result->vcsPath = $build_path;
    }
    if (!empty($deploy_path)) {
      $result->deployVcsPath = $deploy_path;
    }
    return $result;
  }

}
