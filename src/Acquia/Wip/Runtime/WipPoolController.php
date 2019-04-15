<?php

namespace Acquia\Wip\Runtime;

use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\DependencyManager;
use Acquia\Wip\Exception\DependencyMissingException;
use Acquia\Wip\State\GlobalPause;
use Acquia\Wip\State\GroupPause;
use Acquia\Wip\Storage\BasicWipPoolStore;
use Acquia\Wip\Storage\StateStoreInterface;
use Acquia\Wip\TaskStatus;
use Acquia\Wip\WipFactory;

/**
 * A simple implementation of WipPoolControllerInterface.
 */
class WipPoolController implements WipPoolControllerInterface, DependencyManagedInterface {

  /**
   * The WipFactory resource name.
   */
  const RESOURCE_NAME = 'acquia.wip.pool.controller';

  /**
   * The DependencyManager instance.
   *
   * @var DependencyManager
   */
  protected $dependencyManager;

  /**
   * Initializes this instance of WipPoolController.
   */
  public function __construct() {
    $this->dependencyManager = new DependencyManager();
    $dependencies = $this->getDependencies();
    if (!empty($dependencies)) {
      $this->dependencyManager->addDependencies($dependencies);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.storage.state' => 'Acquia\Wip\Storage\StateStoreInterface',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function hardPauseGlobal() {
    $this->setVariable(GlobalPause::STATE_NAME, GlobalPause::HARD_PAUSE);
    return $this->getTasksInProgress();
  }

  /**
   * {@inheritdoc}
   */
  public function softPauseGlobal() {
    $this->setVariable(GlobalPause::STATE_NAME, GlobalPause::SOFT_PAUSE);
    return $this->getTasksInProgress();
  }

  /**
   * {@inheritdoc}
   */
  public function resumeGlobal() {
    $this->getStorage()->delete(GlobalPause::STATE_NAME);
    return $this->getGlobalPause() === GlobalPause::OFF;
  }

  /**
   * {@inheritdoc}
   */
  public function getGlobalPause() {
    return $this->getVariable(GlobalPause::STATE_NAME, GlobalPause::$defaultValue);
  }

  /**
   * {@inheritdoc}
   */
  public function isHardPausedGlobal() {
    return $this->getGlobalPause() === GlobalPause::HARD_PAUSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isSoftPausedGlobal() {
    return $this->getGlobalPause() === GlobalPause::SOFT_PAUSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hardPauseGroups($groups) {
    $paused_groups = array_unique(array_merge($this->getHardPausedGroups(), $groups));
    $this->setVariable(GroupPause::HARD_STATE_NAME, $paused_groups);
    return $this->getTasksInProgress($groups);
  }

  /**
   * {@inheritdoc}
   */
  public function softPauseGroups($groups) {
    $this->hardPauseGroups($groups);
    $paused_groups = array_unique(array_merge($this->getSoftPausedGroups(), $groups));
    $this->setVariable(GroupPause::SOFT_STATE_NAME, $paused_groups);
    return $this->getTasksInProgress($groups);
  }

  /**
   * {@inheritdoc}
   */
  public function getHardPausedGroups() {
    return $this->getVariable(GroupPause::HARD_STATE_NAME, GroupPause::$defaultValue);
  }

  /**
   * {@inheritdoc}
   */
  public function getSoftPausedGroups() {
    return $this->getVariable(GroupPause::SOFT_STATE_NAME, GroupPause::$defaultValue);
  }

  /**
   * {@inheritdoc}
   */
  public function resumeGroups($groups) {
    $hard_paused_groups = $this->getHardPausedGroups();
    $hard_paused_groups = array_diff($hard_paused_groups, $groups);
    $this->setVariable(GroupPause::HARD_STATE_NAME, $hard_paused_groups);

    $soft_paused_groups = $this->getSoftPausedGroups();
    $soft_paused_groups = array_diff($soft_paused_groups, $groups);
    $this->setVariable(GroupPause::SOFT_STATE_NAME, $soft_paused_groups);

    $groups_not_paused = array_intersect($hard_paused_groups, $groups);
    return count($groups_not_paused) === 0;
  }

  /**
   * {@inheritdoc}
   */
  public function pauseTask($task_id) {
    return BasicWipPoolStore::getWipPoolStore()->pauseTask($task_id);
  }

  /**
   * {@inheritdoc}
   */
  public function resumeTask($task_id) {
    return BasicWipPoolStore::getWipPoolStore()->resumeTask($task_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getTasksInProgress($groups = NULL) {
    $wip_pool_store = BasicWipPoolStore::getWipPoolStore();
    if (!is_array($groups)) {
      $groups = array($groups);
    }

    $result = array();
    foreach ($groups as $group) {
      $processing = $wip_pool_store->load(0, 200, 'ASC', TaskStatus::PROCESSING, NULL, $group);
      $waiting = $wip_pool_store->load(0, 200, 'ASC', TaskStatus::WAITING, NULL, $group);
      $result = array_merge($result, $processing, $waiting);
    }
    // We are dealing with objects so don't test as strings which is default for array_unique.
    return array_unique($result, SORT_REGULAR);
  }

  /**
   * {@inheritdoc}
   */
  public function getTasksInProcessing($groups = NULL) {
    $wip_pool_store = BasicWipPoolStore::getWipPoolStore();
    if (!is_array($groups)) {
      $groups = array($groups);
    }

    $result = array();
    foreach ($groups as $group) {
      $processing = $wip_pool_store->load(0, 200, 'ASC', TaskStatus::PROCESSING, NULL, $group);
      $result = array_merge($result, $processing);
    }
    return array_unique($result, SORT_REGULAR);
  }

  /**
   * Sets the specified key / value pair into persistent storage.
   *
   * @param string $key
   *   The key name.
   * @param mixed $value
   *   The value.
   */
  private function setVariable($key, $value) {
    $this->getStorage()->set($key, $value);
  }

  /**
   * Gets the value corresponding to the specified key from persistent storage.
   *
   * @param string $key
   *   The key name.
   * @param mixed $default_value
   *   Optional. The default value. This will be returned if there is no value
   *   currently associated with the specified key.
   *
   * @return mixed
   *   The value.
   */
  private function getVariable($key, $default_value = NULL) {
    return $this->getStorage()->get($key, $default_value);
  }

  /**
   * Gets the variable storage.
   *
   * @return StateStoreInterface
   *   The variable storage.
   */
  private function getStorage() {
    $result = $this->dependencyManager->getDependency('acquia.wip.storage.state');
    if (!$result instanceof StateStoreInterface) {
      throw new \DomainException(
        'Configuration error - the "acquia.wip.storage.state" resource should be of type StateStoreInterface.'
      );
    }
    return $result;
  }

  /**
   * Gets the WipPoolController instance.
   *
   * @param DependencyManager $dependency_manager
   *   Optional. If provided the specified dependency manager will be used to
   *   resolve the WipPoolController; otherwise the WipFactory will be used.
   *
   * @return WipPoolControllerInterface
   *   The controller.
   *
   * @throws DependencyMissingException
   *   If a dependency manager was provided for which the WipPoolController
   *   dependency could not be met.
   */
  public static function getWipPoolController(DependencyManager $dependency_manager = NULL) {
    $result = NULL;
    if (NULL !== $dependency_manager) {
      $result = $dependency_manager->getDependency(self::RESOURCE_NAME);
    } else {
      try {
        $result = WipFactory::getObject(self::RESOURCE_NAME);
      } catch (\Exception $e) {
        // Fall back to a new instance of WipPoolController.
        $result = new self();
      }
    }
    return $result;
  }

}
