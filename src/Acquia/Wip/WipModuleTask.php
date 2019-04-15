<?php

namespace Acquia\Wip;

/**
 * Describes a task within a Wip module.
 */
class WipModuleTask implements WipModuleTaskInterface {

  /**
   * The wip module task name.
   *
   * @var string
   */
  private $name = 'NotSet';

  /**
   * The module name to which this task belongs.
   *
   * @var string
   */
  private $moduleName = 'NotSet';

  /**
   * The full qualified class name.
   *
   * @var string
   */
  private $className = NULL;

  /**
   * The concurrency group name.
   *
   * @var string
   */
  private $groupName = NULL;

  /**
   * The WipLogLevel value for this task.
   *
   * @var int
   */
  private $logLevel = WipLogLevel::INFO;

  /**
   * The TaskPriority value for this task.
   *
   * @var int
   */
  private $priority = TaskPriority::MEDIUM;

  /**
   * Creates an instance of WipModuleTask.
   *
   * @param string $module_name
   *   The name of the module to which this task belongs.
   * @param string $name
   *   The name of this task.
   * @param string $class_name
   *   The class name for this task.
   * @param string $group_name
   *   The concurrency group name for this task.
   * @param string $log_level
   *   The log level for this task.
   * @param string $priority
   *   The priority for this task.
   *
   * @returns WipModuleTask
   *   The new instance.
   */
  public function __construct(
    $module_name,
    $name,
    $class_name,
    $group_name,
    $log_level = NULL,
    $priority = NULL) {
    $this->setModuleName($module_name);
    $this->setName($name);
    $this->setClassName($class_name);
    $this->setGroupName($group_name);
    if (NULL !== $log_level) {
      $this->setLogLevel($log_level);
    }
    if (NULL !== $priority) {
      $this->setPriority($priority);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getModuleName() {
    return $this->moduleName;
  }

  /**
   * {@inheritdoc}
   */
  public function setModuleName($module_name) {
    if (!is_string($module_name) || trim($module_name) == FALSE) {
      throw new \InvalidArgumentException('The "module_name" parameter must be a non-empty string.');
    }
    $this->moduleName = trim($module_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Sets the task name.
   *
   * @param string $name
   *   The task name.
   */
  private function setName($name) {
    if (!is_string($name) || trim($name) == FALSE) {
      throw new \InvalidArgumentException('The "name" parameter must be a non-empty string.');
    }
    $this->name = trim($name);
  }

  /**
   * {@inheritdoc}
   */
  public function getClassName() {
    if (empty($this->className)) {
      $factory_name = sprintf('$wip.modules.%s.%s.class_name', $this->moduleName, $this->name);
      $this->className = WipFactory::getString($factory_name);
    }

    return $this->className;
  }

  /**
   * Sets the class name of the task implementation.
   *
   * @param string $class_name
   *   The class name.
   */
  private function setClassName($class_name) {
    if (!is_string($class_name) || trim($class_name) == FALSE) {
      throw new \InvalidArgumentException('The "class_name" parameter must be a non-empty string.');
    }
    $this->className = trim($class_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupName() {
    if (empty($this->groupName)) {
      $factory_name = sprintf('$wip.modules.%s.%s.group_name', $this->moduleName, $this->name);
      $this->groupName = WipFactory::getString($factory_name);
    }
    return $this->groupName;
  }

  /**
   * Sets the group name of the task.
   *
   * @param string $group_name
   *   The group name.
   */
  private function setGroupName($group_name) {
    if (!is_string($group_name) || trim($group_name) == FALSE) {
      throw new \InvalidArgumentException('The "group_name" parameter must be a non-empty string.');
    }
    $this->groupName = trim($group_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getLogLevel() {
    if (empty($this->logLevel)) {
      $factory_name = sprintf('$wip.modules.%s.%s.log_level', $this->moduleName, $this->name);
      $log_level = WipFactory::getString($factory_name);
      $this->logLevel = WipLogLevel::toInt($log_level);
    }

    return $this->logLevel;
  }

  /**
   * Sets the log level.
   *
   * @param string | int $log_level
   *   The log level value or label.
   */
  private function setLogLevel($log_level) {
    if (is_int($log_level) && WipLogLevel::isValid($log_level)) {
      $this->logLevel = $log_level;
    } elseif (!is_string($log_level) || trim($log_level) == FALSE) {
      throw new \InvalidArgumentException('The "log_level" parameter must be a non-empty string.');
    } else {
      $this->logLevel = WipLogLevel::toInt(trim($log_level));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPriority() {
    if (!TaskPriority::isValid($this->priority)) {
      $factory_name = sprintf('$wip.modules.%s.%s.priority', $this->moduleName, $this->name);
      $priority = WipFactory::getString($factory_name, 'Medium');
      $this->priority = TaskPriority::toInt($priority);
    }
    return $this->priority;
  }

  /**
   * {@inheritdoc}
   */
  public function setPriority($priority) {
    if (is_int($priority) && TaskPriority::isValid($priority)) {
      $this->priority = $priority;
    } elseif (!is_string($priority) || trim($priority) == FALSE) {
      throw new \InvalidArgumentException('The "priority" parameter must be a non-empty string.');
    } else {
      $this->priority = TaskPriority::toInt(trim($priority));
    }
  }

}
