<?php

namespace Acquia\Wip;

/**
 * Describes the interface for wip modules.
 */
interface WipModuleInterface {

  /**
   * Gets the version.
   *
   * @return string
   *   The version, in any format.
   */
  public function getVersion();

  /**
   * Sets the version.
   *
   * @param string $version
   *   The version, in any format.
   */
  public function setVersion($version);

  /**
   * Gets the module name.
   *
   * @return string
   *   The module name.
   */
  public function getName();

  /**
   * Sets the module name.
   *
   * @param string $name
   *   The module name.
   */
  public function setName($name);

  /**
   * Gets the directory where the module resources are located.
   *
   * @return string
   *   The absolute path of the module resources.
   */
  public function getDirectory();

  /**
   * Sets the directory in which the module files will be placed.
   *
   * @param string $directory
   *   The name of the top level directory for this module.
   */
  public function setDirectory($directory);

  /**
   * Enables this module.
   */
  public function enable();

  /**
   * Disables this module.
   */
  public function disable();

  /**
   * Indicates whether this module is enabled.
   *
   * @return bool
   *   TRUE if this module is enabled; FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Indicates whether this module is ready.
   *
   * @return bool
   *   TRUE if this module is ready; FALSE otherwise.
   */
  public function isReady();

  /**
   * Sets the ready value.
   *
   * @param bool $ready
   *   Whether the module is ready.
   */
  public function setReady($ready);

  /**
   * Sets the include files.
   *
   * @param string[] $file_paths
   *   The paths to the include files.
   */
  public function setIncludes($file_paths);

  /**
   * Gets the set of include files this module requires.
   *
   * @return string[]
   *   An array of file paths.
   */
  public function getIncludes();

  /**
   * Gets the path of the module's configuration file.
   *
   * @return string
   *   The absolute path of the configuration file.
   */
  public function getConfigFile();

  /**
   * Returns the full absolute path to the given path within the module.
   *
   * @param string $path
   *   The path.
   *
   * @return string
   *   The full absolute path.
   */
  public function getAbsolutePath($path);

  /**
   * Sets the tasks provided by this module instance.
   *
   * @param WipModuleTaskInterface[] $tasks
   *   The tasks.
   */
  public function setTasks($tasks);

  /**
   * Gets the set of tasks within this module.
   *
   * @return WipModuleTaskInterface[]
   *   The tasks.
   */
  public function getTasks();

  /**
   * Gets the task within this module having the specified name.
   *
   * @param string $task_name
   *   The task name.
   *
   * @return WipModuleTaskInterface
   *   The task.
   */
  public function getTask($task_name);

  /**
   * Requires all of the includes associated with this module.
   */
  public function requireIncludes();

  /**
   * Sets the git URI from which the module code can be cloned.
   *
   * @param string $vcs_uri
   *   The URI to the module's git repository.
   */
  public function setVcsUri($vcs_uri);

  /**
   * Gets the git URI from which the module can be cloned.
   *
   * @return string
   *   The URI to the module's git repository.
   */
  public function getVcsUri();

  /**
   * Sets the git tag or branch representing the module version to deploy.
   *
   * @param string $vcs_path
   *   The git tag or branch.
   */
  public function setVcsPath($vcs_path);

  /**
   * Gets the git tag or branch representing the module version to deploy.
   *
   * @return string
   *   The git tag or branch.
   */
  public function getVcsPath();

}
