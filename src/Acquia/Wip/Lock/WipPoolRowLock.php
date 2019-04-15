<?php

namespace Acquia\Wip\Lock;

use Acquia\Wip\DependencyManagerInterface;
use Acquia\Wip\WipFactory;

/**
 * Provides a wrapper for wip pool locks.
 */
class WipPoolRowLock extends RowLock implements RowLockInterface {

  /**
   * The name of the associated resource in the factory configuration.
   */
  const RESOURCE_NAME = 'acquia.wip.lock.rowlock.wippool';

  /**
   * The lock prefix used for updates to a wip_pool row.
   */
  const LOCK_PREFIX_UPDATE = 'wip-pool-lock-update';

  /**
   * The lock prefix used for locking task execution to a single process.
   */
  const LOCK_PREFIX_EXECUTE = 'wip-pool-lock-execute';

  /**
   * Creates a new instance of WipPoolRowLock.
   *
   * @param int $object_id
   *   Optional. The object Id.
   * @param string $lock_prefix
   *   Optional. If provided the specified prefix will be used instead of the
   *   default.
   */
  public function __construct($object_id = NULL, $lock_prefix = self::LOCK_PREFIX_UPDATE) {
    parent::__construct();
    if (NULL !== $object_id) {
      $this->setObjectId($object_id);
    }
    if (empty($lock_prefix)) {
      $lock_prefix = self::LOCK_PREFIX_UPDATE;
    }
    $this->setPrefix($lock_prefix);
  }

  /**
   * Gets the WipPoolRowLock instance.
   *
   * @param int $task_id
   *   The ID of the Task.
   * @param string $lock_prefix
   *   Optional. If provided the specified prefix will be used instead of the
   *   default.
   * @param DependencyManagerInterface $dependency_manager
   *   Optional. The DependencyManager instance.
   *
   * @return RowLockInterface
   *   The row lock used for the WipPool.
   *
   * @throws \Acquia\Wip\Exception\DependencyMissingException
   *   If a DependencyManager instance is provided but the row lock has not
   *   been set as a dependency.
   */
  public static function getWipPoolRowLock(
    $task_id,
    $lock_prefix = self::LOCK_PREFIX_UPDATE,
    DependencyManagerInterface $dependency_manager = NULL
  ) {
    $result = NULL;
    if (empty($lock_prefix)) {
      $lock_prefix = self::LOCK_PREFIX_UPDATE;
    }
    if (NULL !== $dependency_manager) {
      $result = $dependency_manager->getDependency(self::RESOURCE_NAME);
      $result->setObjectId($task_id);
      $result->setPrefix($lock_prefix);
    } else {
      try {
        $result = WipFactory::getObject(self::RESOURCE_NAME);
        $result->setObjectId($task_id);
        $result->setPrefix($lock_prefix);
      } catch (\Exception $e) {
        // Fall back to a new instance of WipPoolRowLock.
        $result = new self($task_id, $lock_prefix);
      }
    }
    return $result;
  }

}
