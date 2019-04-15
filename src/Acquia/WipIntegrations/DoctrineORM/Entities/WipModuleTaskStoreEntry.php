<?php

namespace Acquia\WipIntegrations\DoctrineORM\Entities;

use Acquia\Wip\TaskPriority;
use Acquia\Wip\WipLogLevel;
use Acquia\Wip\WipModuleTask;
use Acquia\Wip\WipModuleTaskInterface;

/**
 * Defines an entity for storing a task.
 *
 * @Entity @Table(name="wip_module_task", options={"engine"="InnoDB"})
 */
class WipModuleTaskStoreEntry {

  /**
   * The name of the task.
   *
   * @var string
   *
   * @Id @Column(type="string", length=255, unique=true)
   */
  private $name;

  /**
   * The name of the module name to which this task belongs.
   *
   * @var string
   *
   * @ManyToOne(targetEntity="Acquia\WipIntegrations\DoctrineORM\Entities\WipModuleStoreEntry")
   *
   * @Column(type="string", name="module_name", nullable=false, length=255)
   *
   * @JoinColumn(name="module_name_join", referencedColumnName="name")
   */
  private $moduleName;

  /**
   * The class name for this task.
   *
   * @var string
   *
   * @Column(name="class_name", type="string", length=255)
   */
  private $className;

  /**
   * The concurrency group name for this task.
   *
   * @var string
   *
   * @Column(name="group_name", type="string", length=255)
   */
  private $groupName;

  /**
   * The log level for this task.
   *
   * @var string
   *
   * @Column(name="log_level", type="string", length=16)
   */
  private $logLevel;

  /**
   * The priority for this task.
   *
   * @var string
   *
   * @Column(type="string", length=16)
   */
  private $priority;

  /**
   * Gets the module name.
   *
   * @return string
   *   The module name.
   */
  public function getModuleName() {
    return $this->moduleName;
  }

  /**
   * Sets the module name.
   *
   * @param string $name
   *   The module name.
   */
  public function setModuleName($name) {
    $this->moduleName = $name;
  }

  /**
   * Gets the name of the task.
   *
   * @return string
   *   The task type.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Sets the name of the task.
   *
   * @param string $name
   *   The name of the task.
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * Gets the class name of the task.
   *
   * @return string
   *   The class name.
   */
  public function getClassName() {
    return $this->className;
  }

  /**
   * Sets the class name of the task.
   *
   * @param string $class_name
   *   The class name.
   */
  public function setClassName($class_name) {
    $this->className = $class_name;
  }

  /**
   * Gets the group name of the task.
   *
   * @return string
   *   The group name.
   */
  public function getGroupName() {
    return $this->groupName;
  }

  /**
   * Sets the group name of the task.
   *
   * @param string $group_name
   *   The group name.
   */
  public function setGroupName($group_name) {
    $this->groupName = $group_name;
  }

  /**
   * Gets the log level of the task.
   *
   * @return string
   *   The log level.
   */
  public function getLogLevel() {
    return $this->logLevel;
  }

  /**
   * Sets the log level of the task.
   *
   * @param string $log_level
   *   The log level.
   */
  public function setLogLevel($log_level) {
    $this->logLevel = $log_level;
  }

  /**
   * Gets the priority of the task.
   *
   * @return string
   *   The priority.
   */
  public function getPriority() {
    return $this->priority;
  }

  /**
   * Sets the priority of the task.
   *
   * @param string $priority
   *   The priority.
   */
  public function setPriority($priority) {
    $this->priority = $priority;
  }

  /**
   * Converts a WipModuleTaskInterface to a WipModuleTaskStoreEntry.
   *
   * @param WipModuleTaskInterface $task
   *   The task to convert.
   *
   * @return WipModuleTaskStoreEntry
   *   The resulting entry.
   */
  public static function fromWipModuleTask(WipModuleTaskInterface $task) {
    $entry = new WipModuleTaskStoreEntry();

    $entry->setModuleName($task->getModuleName());
    $entry->setName($task->getName());
    $entry->setClassName($task->getClassName());
    $entry->setGroupName($task->getGroupName());
    $entry->setLogLevel(WipLogLevel::toString($task->getLogLevel()));
    $entry->setPriority(TaskPriority::tostring($task->getPriority()));

    return $entry;
  }

  /**
   * Converts this WipModuleTaskStoreEntry to a WipModuleTaskInterface.
   *
   * @return WipModuleTaskInterface
   *   The converted entry.
   */
  public function toWipModuleTask() {
    $name = $this->getName();
    $module_name = $this->getModuleName();
    $class_name = $this->getClassName();
    $group_name = $this->getGroupName();
    $log_level = $this->getLogLevel();
    $priority = $this->getPriority();

    $task = new WipModuleTask($module_name, $name, $class_name, $group_name, $log_level, $priority);

    return $task;
  }

}
