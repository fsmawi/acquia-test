<?php

namespace Acquia\Wip\Objects\Modules;

use Acquia\Wip\WipModuleInterface;
use Acquia\Wip\WipModuleTask;

/**
 * A class that reads the configurations for Wip modules.
 */
class WipModuleConfigReader {

  /**
   * The version of the module.
   *
   * @var string $version
   */
  private $version = NULL;

  /**
   * The include files for the module.
   *
   * @var array $includes
   */
  private $includes = [];

  /**
   * Whether the module is enabled.
   *
   * @var bool $enabled
   */
  private $enabled = TRUE;

  /**
   * The array of task types provided by the module.
   *
   * @var array $tasks
   */
  private $tasks = [];

  /**
   * Parses the given configuration data.
   *
   * @param string $name
   *   The module name.
   * @param string $config_data
   *   The configuration data.
   */
  public function parse($name, $config_data) {
    if (!is_string($name) || trim($name) == FALSE) {
      throw new \InvalidArgumentException('The "name" parameter must be a non-empty string.');
    }
    $name = trim($name);

    // Use the TRUE argument to parse with sections.
    $config = parse_ini_string($config_data, TRUE);
    if (empty($config)) {
      throw new \RuntimeException(
        "Failed to read the configuration. Please make sure that the 'config_data' parameter contains a valid configuration in ini format."
      );
    }

    $this->parseValues($config);
    $this->parseTasks($name, $config);
  }

  /**
   * Populates the specified module with information from the config file.
   *
   * @param WipModuleInterface $module
   *   The module instance.
   * @param string $config_data
   *   Optional. The configuration data. If the data is not provided, the
   *   configuration file associated with the specified module will be used to
   *   load the configuration data.
   */
  public static function populateModule(WipModuleInterface $module, $config_data = NULL) {
    if (NULL === $config_data) {
      $config_path = $module->getConfigFile();
      if (is_readable($config_path)) {
        $config_data = file_get_contents($config_path);
      } else {
        throw new \DomainException(sprintf('Unable to read the module configuration file "%s".', $config_path));
      }
    }
    $config = new WipModuleConfigReader();
    $config->parse($module->getName(), $config_data);
    $module->setVersion($config->getVersion());
    $module->setIncludes($config->getIncludes());
    $module->setTasks($config->getTasks());
  }

  /**
   * Gets the version of the module.
   *
   * @return string
   *   The version of the module.
   */
  public function getVersion() {
    return $this->version;
  }

  /**
   * Gets the include files for the module.
   *
   * @return array
   *   The include files for the module.
   */
  public function getIncludes() {
    return $this->includes;
  }

  /**
   * Gets whether the module is enabled.
   *
   * @return bool
   *   Whether the module is enabled.
   */
  public function getEnabled() {
    return $this->enabled;
  }

  /**
   * Gets the array of task types provided by the module.
   *
   * @return array
   *   The array of task types provided by the module.
   */
  public function getTasks() {
    return $this->tasks;
  }

  /**
   * Parses an array of tasks from the config file.
   *
   * @param string $name
   *   The module name.
   * @param array $config_file_contents
   *   The array containing the config file's contents.
   */
  private function parseTasks($name, $config_file_contents) {
    foreach ($config_file_contents as $key => $values) {
      // Only task definitions are in arrays.
      if (is_array($values)) {
        // Check that the two required parameters (other than task name) exist.
        if (!isset($values['class_name']) || !isset($values['group_name'])) {
          throw new \RuntimeException(
            sprintf(
              'Failed to parse the configuration. Please make sure that the task definition "%s" is valid.',
              $key
            )
          );
        }

        $task_name = $name . '/' . $key;
        $task = new WipModuleTask(
          $name,
          $task_name,
          $values['class_name'],
          $values['group_name'],
          isset($values['log_level']) ? $values['log_level'] : NULL,
          isset($values['priority']) ? $values['priority'] : NULL
        );
        $this->tasks[$task_name] = $task;
      }
    }
  }

  /**
   * Parses the module's values from the config file.
   *
   * @param array $config_file_contents
   *   The array containing the config file's contents.
   */
  private function parseValues($config_file_contents) {
    $this->version = $config_file_contents['version'];
    if (isset($config_file_contents['includes'])) {
      $this->includes = explode(';', $config_file_contents['includes']);
    }
    if (isset($config_file_contents['enabled'])) {
      $this->enabled = $config_file_contents['enabled'];
    }
  }

}
