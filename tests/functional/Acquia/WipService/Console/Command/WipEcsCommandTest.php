<?php

namespace Acquia\WipService\Console\Command;

use Acquia\WipInterface\EcsClusterStoreInterface;
use Acquia\WipService\Console\AbstractWipCtlTest;
use Acquia\WipService\Console\Commands\WipDeleteEcsClusterCommand;
use Acquia\WipService\Console\Commands\WipSaveEcsClusterCommand;
use Acquia\WipService\Console\Commands\WipSetEcsClusterCommand;
use Acquia\Wip\Storage\StateStoreInterface;
use Acquia\Wip\WipFactory;

/**
 * Tests ECS logic.
 */
class WipEcsCommandsTest extends AbstractWipCtlTest {

  /**
   * The state storage instance.
   *
   * @var StateStoreInterface
   */
  private $stateStore;

  /**
   * Cluster storage.
   *
   * @var EcsClusterStoreInterface
   */
  protected $clusterStorage;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->registerTestingConfig();
    $this->stateStore = WipFactory::getObject('acquia.wip.storage.state');
    $this->clusterStorage = WipFactory::getObject('acquia.wip.storage.ecs_cluster');
  }

  /**
   * Tests operations for cluster config.
   */
  public function testExecute() {
    $name = 'my-test';
    $arguments = [
      'name' => $name,
      'cluster' => 'myCluster',
      'secret' => 'mySecret',
      'key' => 'myKey',
    ];
    $tester = $this->executeCommand(new WipSaveEcsClusterCommand(), 'save-ecs-cluster', $arguments);
    $output = $tester->getDisplay();
    $this->assertContains("New cluster configuration [$name] added", $output);
    $cluster = $this->clusterStorage->load($name);
    $this->assertEquals('my-test', $cluster->getName());
    $this->assertEquals('us-east-1', $cluster->getRegion());

    // Do an update.
    $arguments['--region'] = 'testRegion';
    $tester = $this->executeCommand(new WipSaveEcsClusterCommand(), 'save-ecs-cluster', $arguments);
    $output = $tester->getDisplay();
    $this->assertContains("Cluster configuration [$name] updated", $output);
    $cluster = $this->clusterStorage->load($name);
    $this->assertEquals($name, $cluster->getName());
    $this->assertEquals('testRegion', $cluster->getRegion());

    // Attempt to set new active cluster.
    $tester = $this->executeCommand(new WipSetEcsClusterCommand(), 'set-active-cluster', ['--name' => $name]);
    $output = $tester->getDisplay();
    $this->assertContains("The active cluster has been set to [$name]", $output);
    $config_name = $this->stateStore->get('acquia.wip.ecs_cluster.name', 'default');
    $this->assertEquals($name, $config_name);

    // Add default cluster so we can swap back the active config.
    $arguments = [
      'name' => 'default',
      'cluster' => 'myCluster',
      'secret' => 'mySecret',
      'key' => 'myKey',
    ];
    $this->executeCommand(new WipSaveEcsClusterCommand(), 'save-ecs-cluster', $arguments);

    // Set new active cluster to default.
    $tester = $this->executeCommand(new WipSetEcsClusterCommand(), 'set-active-cluster', []);
    $output = $tester->getDisplay();
    $this->assertContains('The active cluster has been set to [default]', $output);
    $config_name = $this->stateStore->get('acquia.wip.ecs_cluster.name', 'default');
    $this->assertEquals('default', $config_name);

    // Delete my-test.
    $tester = $this->executeCommand(new WipDeleteEcsClusterCommand(), 'delete-ecs-cluster', ['name' => $name]);
    $output = $tester->getDisplay();
    $this->assertContains("The cluster [$name] has been deleted.", $output);
    $cluster = $this->clusterStorage->load($name);
    $this->assertEmpty($cluster);
  }

  /**
   * Tests setting an invalid cluster with no alternatives.
   *
   * @expectedException \RuntimeException
   * @expectedExceptionMessage The cluster called [my-test2] does not exist. No clusters exist that can be made active.
   */
  public function testInvalidActive() {
    $this->executeCommand(new WipSetEcsClusterCommand(), 'set-active-cluster', ['--name' => 'my-test2']);
  }

  /**
   * Tests setting an invalid cluster with alternatives.
   *
   * @expectedException \RuntimeException
   * @expectedExceptionMessage The cluster called [my-test2] does not exist. Available clusters my-test.
   */
  public function testInvalidActiveList() {
    $arguments = [
      'name' => 'my-test',
      'cluster' => 'myCluster',
      'secret' => 'mySecret',
      'key' => 'myKey',
    ];
    $this->executeCommand(new WipSaveEcsClusterCommand(), 'save-ecs-cluster', $arguments);
    $this->executeCommand(new WipSetEcsClusterCommand(), 'set-active-cluster', ['--name' => 'my-test2']);
  }

  /**
   * Tests deleting an invalid cluster with no alternatives.
   *
   * @expectedException \RuntimeException
   * @expectedExceptionMessage The cluster called [my-test2] does not exist. No clusters exist that can be deleted.
   */
  public function testDeleteInvalid() {
    $this->executeCommand(new WipDeleteEcsClusterCommand(), 'delete-ecs-cluster', ['name' => 'my-test2']);
  }

  /**
   * Tests deleting an invalid cluster with alternatives.
   *
   * @expectedException \RuntimeException
   * @expectedExceptionMessage The cluster called [my-test2] does not exist. The following clusters my-test can be deleted.
   */
  public function testDeleteInvalidList() {
    $arguments = [
      'name' => 'my-test',
      'cluster' => 'myCluster',
      'secret' => 'mySecret',
      'key' => 'myKey',
    ];
    $this->executeCommand(new WipSaveEcsClusterCommand(), 'save-ecs-cluster', $arguments);
    $this->executeCommand(new WipDeleteEcsClusterCommand(), 'delete-ecs-cluster', ['name' => 'my-test2']);
  }

  /**
   * Tests deleting an active cluster.
   *
   * @expectedException \RuntimeException
   * @expectedExceptionMessage The cluster called [my-test] is active and can not be deleted.
   */
  public function testDeleteActive() {
    $arguments = [
      'name' => 'my-test',
      'cluster' => 'myCluster',
      'secret' => 'mySecret',
      'key' => 'myKey',
    ];
    $this->executeCommand(new WipSaveEcsClusterCommand(), 'save-ecs-cluster', $arguments);
    $this->executeCommand(new WipSetEcsClusterCommand(), 'set-active-cluster', ['--name' => 'my-test']);
    $this->executeCommand(new WipDeleteEcsClusterCommand(), 'delete-ecs-cluster', ['name' => 'my-test']);
  }

  /**
   * Tests deleting the default cluster.
   *
   * @expectedException \RuntimeException
   * @expectedExceptionMessage The default cluster can not be deleted.
   */
  public function testDeleteDefault() {
    $this->executeCommand(new WipDeleteEcsClusterCommand(), 'delete-ecs-cluster', ['name' => 'default']);
  }

}
