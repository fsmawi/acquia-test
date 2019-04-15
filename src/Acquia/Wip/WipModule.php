<?php

namespace Acquia\Wip;

use Acquia\WipIntegrations\DoctrineORM\WipModuleTaskStore;
use Acquia\Wip\Storage\WipModuleTaskStoreInterface;

/**
 * Describes all relevant values of a wip module.
 */
class WipModule implements WipModuleInterface {

  /**
   * The module version.
   *
   * @var string
   */
  private $version = 'NotSet';

  /**
   * The module name.
   *
   * @var string
   */
  private $name = 'NotSet';

  /**
   * The directory in which the module files reside.
   *
   * @var string
   */
  private $directory = NULL;

  /**
   * Indicates whether this module is enabled.
   *
   * @var bool
   */
  private $enabled = TRUE;

  /**
   * Indicates whether this module is ready.
   *
   * @var bool
   */
  private $ready = FALSE;

  /**
   * The required include files.
   *
   * @var string[]
   */
  private $includes = array();

  /**
   * The URI to the git repository from which the module can be cloned.
   *
   * @var string
   */
  private $vcsUri;

  /**
   * The git tag or branch representing the module version to deploy.
   *
   * @var string
   */
  private $vcsPath;

  /**
   * The absolute path to the directory with all the modules in it.
   *
   * @var string
   */
  private $moduleDirectory = NULL;

  /**
   * The dependency manager.
   *
   * @var DependencyManager
   */
  private $dependencyManager;

  /**
   * The tasks provided by this module.
   *
   * @var WipModuleTaskInterface[]
   */
  private $tasks = array();

  /**
   * Creates an instance of WipModule.
   *
   * @param string $name
   *   The name of this module.
   * @param string $vcs_uri
   *   The VCS URI.
   * @param string $vcs_path
   *   The VCS PATH.
   */
  public function __construct($name = NULL, $vcs_uri = NULL, $vcs_path = NULL) {
    if (NULL !== $name) {
      $this->setName($name);
    }
    if (NULL !== $vcs_uri) {
      $this->setVcsUri($vcs_uri);
    }
    if (NULL !== $vcs_path) {
      $this->setVcsPath($vcs_path);
    }
    $this->dependencyManager = new DependencyManager();
    $dependencies = $this->getDependencies();
    if (!empty($dependencies)) {
      $this->dependencyManager->addDependencies($dependencies);
    }
  }

  /**
   * Implements DependencyManagedInterface::getDependencies().
   */
  public function getDependencies() {
    return array(
      'acquia.wip.storage.module_task' => '\Acquia\Wip\Storage\WipModuleTaskStoreInterface',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion() {
    return $this->version;
  }

  /**
   * {@inheritdoc}
   */
  public function setVersion($version) {
    if (!is_string($version) || trim($version) == FALSE) {
      throw new \InvalidArgumentException('The "version" parameter must be a non-empty string.');
    }
    $this->version = trim($version);
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    if (!is_string($name) || trim($name) == FALSE) {
      throw new \InvalidArgumentException('The "name" parameter must be a non-empty string.');
    }
    $this->name = $name;
  }

  /**
   * {@inheritdoc}
   */
  public function getDirectory() {
    $result = $this->directory;
    if (empty($result)) {
      $result = $this->sanitizeStringForFilesystem($this->getName());
    }
    return $result;
  }

  /**
   * Sanitizes the specified string for use as a filename.
   *
   * @param string $string
   *   The string to sanitize.
   *
   * @return string
   *   The sanitized string.
   */
  private function sanitizeStringForFilesystem($string) {
    return preg_replace('[^a-zA-Z0-9\-\_]', '', $string);
  }

  /**
   * {@inheritdoc}
   */
  public function setDirectory($directory) {
    if (!is_string($directory) || trim($directory) == FALSE) {
      throw new \InvalidArgumentException('The "directory" parameter must be a non-empty string.');
    }
    $this->directory = $directory;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function enable() {
    $this->enabled = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function disable() {
    $this->enabled = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isReady() {
    return $this->ready;
  }

  /**
   * {@inheritdoc}
   */
  public function setReady($ready) {
    if (!is_bool($ready)) {
      throw new \InvalidArgumentException('The "ready" parameter must be a boolean.');
    }
    $this->ready = $ready;
  }

  /**
   * {@inheritdoc}
   */
  public function getIncludes() {
    return $this->includes;
  }

  /**
   * {@inheritdoc}
   */
  public function setIncludes($file_paths) {
    if (!is_array($file_paths)) {
      throw new \InvalidArgumentException('The "file_paths" parameter must be a string array.');
    }
    $this->includes = array();
    foreach ($file_paths as $include_file) {
      $this->addInclude($include_file);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setTasks($tasks) {
    $error_message = 'The "tasks" parameter must be an array of WipModuleTasks.';
    if (!is_array($tasks)) {
      throw new \InvalidArgumentException($error_message);
    }
    $filtered_tasks = array_filter(
      $tasks,
      function ($object) {
        return $object instanceof WipModuleTaskInterface;
      }
    );
    if ($tasks != $filtered_tasks) {
      throw new \InvalidArgumentException($error_message);
    }

    $this->tasks = $tasks;
  }

  /**
   * {@inheritdoc}
   */
  public function getTasks() {
    $result = $this->tasks;
    if (empty($result)) {
      try {
        $task_store = $this->getTaskStore();
        $tasks = $task_store->getTasksByModuleName($this->getName());
        if (!empty($tasks)) {
          $this->tasks = $result = $tasks;
        }
      } catch (\Exception $e) {
        // Ignore.
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getTask($task_name) {
    $result = NULL;
    $tasks = $this->getTasks();
    if (is_array($tasks)) {
      /** @var WipModuleTaskInterface $task */
      foreach ($tasks as $task) {
        if ($task_name === $task->getName()) {
          $result = $task;
          break;
        }
      }
    }
    return $result;
  }

  /**
   * Adds the specified include file.
   *
   * @param string $file_path
   *   The file path to include.
   */
  private function addInclude($file_path) {
    if (!is_string($file_path) || trim($file_path) == FALSE) {
      throw new \InvalidArgumentException('The "file_path" parameter must be a non-empty string.');
    }
    $this->includes[] = $file_path;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigFile() {
    return $this->getAbsolutePath($this->getDirectory() . '/module.ini');
  }

  /**
   * {@inheritdoc}
   */
  public function getAbsolutePath($path) {
    $result = $path;
    if (substr($result, 0, 1) !== DIRECTORY_SEPARATOR) {
      $result = $this->getModuleDirectory() . DIRECTORY_SEPARATOR . $result;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getModuleDirectory() {
    if ($this->moduleDirectory === NULL) {
      // Default to /mnt/tmp/site.env/.
      $env = Environment::getRuntimeEnvironment();
      $directory_prefix = sprintf(
        '/mnt/tmp/%s.%s',
        $env->getSitegroup(),
        $env->getEnvironmentName()
      );
      $directory_prefix = WipFactory::getString('$wip.modules.directory_prefix', $directory_prefix);

      $directory = WipFactory::getString('$wip.modules.directory', 'modules');
      $this->moduleDirectory = $directory_prefix . DIRECTORY_SEPARATOR . $directory;
    }
    return $this->moduleDirectory;
  }

  /**
   * {@inheritdoc}
   */
  public function requireIncludes() {
    $includes = $this->getIncludes();
    foreach ($includes as $include) {
      $absolute_path = $this->getAbsolutePath($this->getDirectory() . DIRECTORY_SEPARATOR . $include);
      require_once $absolute_path;
    }
  }

  /**
   * Sets the git URI from which the module code can be cloned.
   *
   * @param string $vcs_uri
   *   The URI to the module's git repository.
   */
  public function setVcsUri($vcs_uri) {
    if (!is_string($vcs_uri) || trim($vcs_uri) == FALSE) {
      throw new \InvalidArgumentException('The "vcs_uri" parameter must be a non-empty string.');
    }
    $this->vcsUri = $vcs_uri;
  }

  /**
   * Gets the git URI from which the module can be cloned.
   *
   * @return string
   *   The URI to the module's git repository.
   */
  public function getVcsUri() {
    return $this->vcsUri;
  }

  /**
   * Sets the git tag or branch representing the module version to deploy.
   *
   * @param string $vcs_path
   *   The git tag or branch.
   */
  public function setVcsPath($vcs_path) {
    if (!is_string($vcs_path) || trim($vcs_path) == FALSE) {
      throw new \InvalidArgumentException('The "vcs_path" parameter must be a non-empty string.');
    }
    $this->vcsPath = $vcs_path;
  }

  /**
   * Gets the git tag or branch representing the module version to deploy.
   *
   * @return string
   *   The git tag or branch.
   */
  public function getVcsPath() {
    return $this->vcsPath;
  }

  /**
   * Gets the module task storage.
   *
   * @return WipModuleTaskStoreInterface
   *   The task store.
   */
  private function getTaskStore() {
    return WipModuleTaskStore::getWipModuleTaskStore($this->dependencyManager);
  }

}
