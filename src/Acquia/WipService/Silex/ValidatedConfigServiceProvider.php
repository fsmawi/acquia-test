<?php

/**
 * This service provider is based on Igorw\Silex\ConfigServiceProvider that cannot be extended easily.
 */

namespace Acquia\WipService\Silex;

use Acquia\WipService\Silex\ConfigValidator\ConfigValidatorInterface;
use Igorw\Silex\ChainConfigDriver;
use Igorw\Silex\ConfigDriver;
use Igorw\Silex\JsonConfigDriver;
use Igorw\Silex\PhpConfigDriver;
use Igorw\Silex\TomlConfigDriver;
use Igorw\Silex\YamlConfigDriver;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Defines a service provider for validated configurations.
 */
class ValidatedConfigServiceProvider implements ServiceProviderInterface {

  /**
   * The configuration file to load.
   *
   * @var string
   */
  protected $filename;

  /**
   * The configuration validator.
   *
   * @var ConfigValidatorInterface
   */
  protected $validator;

  /**
   * The configuration driver.
   *
   * @var ChainConfigDriver
   */
  protected $driver;

  /**
   * ValidatedConfigServiceProvider constructor.
   *
   * @param string $filename
   *   The configuration file to load.
   * @param ConfigValidatorInterface $validator
   *   The configuration validator.
   * @param ConfigDriver|null $driver
   *   The configuration driver.
   */
  public function __construct($filename, ConfigValidatorInterface $validator, ConfigDriver $driver = NULL) {
    $this->filename = $filename;
    $this->validator = $validator;

    $this->driver = $driver ?: new ChainConfigDriver(array(
      new PhpConfigDriver(),
      new YamlConfigDriver(),
      new JsonConfigDriver(),
      new TomlConfigDriver(),
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function register(Application $app) {
    $config = $this->readConfig();
    $this->validateConfig($config);
    $this->merge($app, $config);
  }

  /**
   * {@inheritdoc}
   */
  public function boot(Application $app) {
  }

  /**
   * Merge configuration.
   *
   * @param \Silex\Application $app
   *   The application.
   * @param array $config
   *   The configuration.
   */
  protected function merge(Application $app, array $config) {
    foreach ($config as $name => $value) {
      if (isset($app[$name]) && is_array($value)) {
        $app[$name] = $this->mergeRecursively($app[$name], $value);
      } else {
        $app[$name] = $value;
      }
    }
  }

  /**
   * Merge values recursively.
   *
   * @param array $current_value
   *   The current value.
   * @param array $new_value
   *   The new value.
   *
   * @return array
   *   The merged value.
   */
  protected function mergeRecursively(array $current_value, array $new_value) {
    foreach ($new_value as $name => $value) {
      if (is_array($value) && isset($current_value[$name])) {
        $current_value[$name] = $this->mergeRecursively($current_value[$name], $value);
      } else {
        $current_value[$name] = $value;
      }
    }

    return $current_value;
  }

  /**
   * Read the configuration file.
   *
   * @return mixed
   *   The loaded configuration.
   */
  protected function readConfig() {
    if (!$this->filename) {
      throw new \RuntimeException('A valid configuration file must be passed before reading the config.');
    }

    if (!file_exists($this->filename)) {
      throw new \InvalidArgumentException(
        sprintf("The config file '%s' does not exist.", $this->filename)
      );
    }

    if ($this->driver->supports($this->filename)) {
      return $this->driver->load($this->filename);
    }

    throw new \InvalidArgumentException(
      sprintf("The config file '%s' appears to have an invalid format.", $this->filename)
    );
  }

  /**
   * Runs the validation against the given configuration.
   *
   * @param mixed $config
   *   The configuration to validate.
   */
  protected function validateConfig($config) {
    $this->validator->validate($config);
  }

}
