<?php

namespace Acquia\WipService\Console;

use Acquia\Wip\DependencyManager;
use Acquia\Wip\Runtime\WipPoolController;

/**
 * A trait that provides general functionality to console commands.
 */
trait WipConsoleCommandTrait {

  /**
   * Dependency manager.
   *
   * @var DependencyManager
   */
  protected $dependencyManager;

  /**
   * Replaces a dependency with another concrete instance.
   *
   * @param string $name
   *   The declared name of the dependency or service to replace.
   * @param object $object
   *   The instance to use to replace the declared dependency.
   */
  public function swapDependency($name, $object) {
    $this->dependencyManager->swapDependency($name, $object);
  }

  /**
   * Implements DependencyManagedInterface::getDependencies().
   */
  public function getDependencies() {
    return array(
      'acquia.wip.lock.global' => '\Acquia\Wip\LockInterface',
      'acquia.wip.storage.thread'  => '\Acquia\Wip\Storage\ThreadStoreInterface',
      'acquia.wip.storage.wippool' => '\Acquia\Wip\Storage\WipPoolStoreInterface',
      'acquia.wip.storage.state' => '\Acquia\Wip\Storage\StateStoreInterface',
      'acquia.wip.threadpool' => '\Acquia\Wip\Runtime\ThreadPoolInterface',
      'acquia.wip.wiplog' => '\Acquia\Wip\WipLogInterface',
      'acquia.wip.wiplogstore' => '\Acquia\Wip\Storage\WipLogStoreInterface',
      'acquia.wip.storage.wip' => 'Acquia\Wip\Storage\WipStoreInterface',
      'acquia.wip.pool' => 'Acquia\Wip\Runtime\WipPoolInterface',
      'acquia.wip.notification' => 'Acquia\Wip\Notification\NotificationInterface',
      'acquia.wipservice.mysql.utility' => 'Acquia\WipService\MySql\Utility',
      'acquia.wip.storage.signal' => '\Acquia\Wip\Storage\SignalStoreInterface',
      'acquia.wip.metrics.relay' => 'Acquia\Wip\Metrics\MetricsRelayInterface',
      'acquia.wip.storage.ecs_cluster' => 'Acquia\WipInterface\EcsClusterStoreInterface',
      WipPoolController::RESOURCE_NAME => 'Acquia\Wip\Runtime\WipPoolControllerInterface',
    );
  }

  /**
   * Retrieves the Silex app from the Console app.
   */
  public function getBaseApplication() {
    return $this->getApplication()->getBaseApplication();
  }

  /**
   * Retrieves the Silex app from the Console app.
   */
  public function getAppDirectory() {
    return $this->getApplication()->getAppDirectory();
  }

}
