<?php

namespace Acquia\Wip;

/**
 * Represents a Wip task within a module.
 *
 * When a request to invoke a particular type of task is received, the request
 * is matched with a particular instance of a WipModuleTask. The metadata
 * therein provides the details for instantiating the right class.
 */
interface WipModuleTaskInterface {

  /**
   * Gets the module name, which is a unique identifier.
   *
   * @return string
   *   An identifier for this task's module.
   */
  public function getModuleName();
  
  /**
   * Sets the module name, which is a unique identifier.
   *
   * @param string $module_name
   *   An identifier for this task's module.
   */
  public function setModuleName($module_name);

  /**
   * Gets the task name.
   *
   * @return string
   *   The name of this task.
   */
  public function getName();

  /**
   * Gets the name of the class that implements the specified task name.
   *
   * Note: This value can be overridden using the WipFactory.
   *
   * @return string
   *   The fully qualified class name.
   */
  public function getClassName();

  /**
   * Gets the log level for this task.
   *
   * Note: This value can be overridden using the WipFactory.
   *
   * @return int
   *   The WipLogLevel for this task.
   */
  public function getLogLevel();

  /**
   * Gets the concurrency group name.
   *
   * Note: This value can be overridden using the WipFactory.
   *
   * @return string
   *   The concurrency group name.
   */
  public function getGroupName();

  /**
   * Gets the priority associated with this task.
   *
   * @return int
   *   The task priority.
   */
  public function getPriority();

  /**
   * Sets the task priority.
   *
   * @param string | int $priority
   *   The priority value or label.
   */
  public function setPriority($priority);

}
