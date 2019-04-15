<?php

namespace Acquia\WipIntegrations;

use Acquia\WipIntegrations\Container\EcsContainer;
use Acquia\WipIntegrations\DoctrineORM\Entities\EcsClusterStoreEntry;
use Acquia\WipService\Test\BasicTaskDefinitionStore;
use Acquia\Wip\Container\ContainerInterface;
use Acquia\Wip\Signal\CallbackInterface;
use Acquia\Wip\Task;
use Acquia\Wip\WipFactory;
use Aws\Ecs\Exception\EcsException;

/**
 * Missing summary.
 */
class EcsContainerTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @var EcsContainer
   */
  private $ecsContainer;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();

    require __DIR__ . '/../../../../../app/app.php';
    WipFactory::setConfigPath('config/config.factory.test.cfg');

    $this->ecsContainer = new EcsContainer();
    $storage = new BasicTaskDefinitionStore();

    // Swap task definition storage for an implementation that stores in memory.
    $this->ecsContainer->dependencyManager->swapDependency('acquia.wip.storage.task_definition', $storage);
  }

  /**
   * Missing summary.
   */
  public function testTaskDefinitionChanged() {
    // Test that task definitions register as changed if the previously stored
    // is different or empty.
    // @todo
  }

  /**
   * Missing summary.
   */
  public function testMergeConfiguration() {
    // @todo - test that the default + overrides merging works.
  }

  /**
   * Verifies that a container override is stored securely if marked as secure.
   */
  public function testContainerOverrideIsSecure() {
    $unique_string = sha1(strval(mt_rand()));
    $container = new EcsContainer();
    $container->addContainerOverride('MY_TEST', $unique_string);
    $this->assertContains($unique_string, serialize($container));
    $container->addContainerOverride('MY_TEST', $unique_string, TRUE);
    $this->assertNotContains($unique_string, serialize($container));
  }

}
