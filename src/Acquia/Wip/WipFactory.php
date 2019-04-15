<?php

namespace Acquia\Wip;

/**
 * Instantiates instances of named interfaces.
 *
 * The mapping between an interface and the concrete class that should be
 * instantiated must be enumerated in a configuration file.
 *
 * ```` php
 * <interface_name> => <concrete_class_name> [singleton] ; comment
 * ...
 * ````
 *
 * White space is ignored when parsing the file.  Comments start at the
 * semicolon and end at the end of the line.
 */
class WipFactory {
  /**
   * The path to the configuration file.
   *
   * @var string
   */
  private static $configurationPath = NULL;

  /**
   * The configuration document.
   *
   * @var string
   */
  private static $configuration = NULL;

  /**
   * The mapping between interfaces and concrete classes.
   *
   * @var array
   */
  private static $interfaceMap = NULL;

  /**
   * The set of static objects, for which the same instance should be supplied.
   *
   * @var array
   */
  private static $singletonObjects = array();

  /**
   * The set of legal modifiers.
   *
   * @var array
   */
  private static $legalModifiers = array('singleton');

  /**
   * Sets the configuration path.
   *
   * @param string $configuration_path
   *   The path to the configuration file that holds the mapping between
   *   interfaces and concrete classes for a particular runtime container.
   *
   * @throws \InvalidArgumentException
   *   If the configuration path does not reference an existing file.
   */
  public static function setConfigPath($configuration_path) {
    if (!is_string($configuration_path) || empty($configuration_path)) {
      throw new \InvalidArgumentException('The $configuration_path argument must be a non-empty string.');
    }
    if (!file_exists($configuration_path)) {
      throw new \InvalidArgumentException(
        sprintf('The configuration path %s does not exist.', escapeshellarg($configuration_path))
      );
    }
    self::$configurationPath = $configuration_path;
    self::$configuration = NULL;
    self::$interfaceMap = NULL;
  }

  /**
   * Applies overrides to the configuration, if an override file is present.
   */
  public static function applyOverrides() {
    try {
      $ah_site_group = self::getHostingSiteGroup();
      $ah_site_env = self::getHostingEnvironment();
      $has_hosting_environment = TRUE;
    } catch (\Exception $e) {
      $has_hosting_environment = FALSE;
    }

    // The base configuration file may have been specified.
    $application_config = self::getConfigPath();
    if (empty($application_config)) {
      // The base configuration file was not specified; use the default.
      $application_config = sprintf('%s/config/config.factory.cfg', getcwd());
    }

    // This is an ordered list of the configuration files that will be applied,
    // ensuring that service configuration and user configuration can override
    // any of the settings.
    // @todo include all config.factory.cfg's under the modules directory
    $override_paths = array(
      'application' => $application_config,
      'machine' => '/wip/config/config.factory.cfg',
      'service' => $has_hosting_environment ? sprintf(
        '/mnt/files/%s.%s/nobackup/config/config.factory.cfg',
        $ah_site_group,
        $ah_site_env
      ) : '',
      'user' => sprintf('%s/.wip-service/config/config.factory.cfg', getenv('HOME')),
    );
    foreach ($override_paths as $name => $path) {
      if (file_exists($path)) {
        // Only apply the application configuration when running the unit tests.
        // Note that any configuration added via WipFactory::setConfiguration
        // or WipFactory::addConfiguration will be applied after all of the
        // configuration files have been loaded.
        if ($name == 'user' || $name === 'application' || strpos($_SERVER['SCRIPT_FILENAME'], 'phpunit') === FALSE) {
          self::applyConfiguration(file_get_contents($path));
        }
      }
    }
  }

  /**
   * Returns the configuration path.
   *
   * @return string
   *   The path.
   */
  public static function getConfigPath() {
    return self::$configurationPath;
  }

  /**
   * Sets the configuration that maps dependencies to concrete classes.
   *
   * @param string $configuration
   *   The configuration.
   */
  public static function setConfiguration($configuration) {
    if (empty($configuration) || !is_string($configuration)) {
      throw new \InvalidArgumentException('The configuration parameter must be a non-empty string.');
    }
    self::$configuration = $configuration;
    self::reset();
  }

  /**
   * Adds the specified configuration to the existing one.
   *
   * @param string $configuration
   *   The configuration.
   */
  public static function addConfiguration($configuration) {
    if (empty($configuration) || !is_string($configuration)) {
      throw new \InvalidArgumentException('The configuration parameter must be a non-empty string.');
    }
    if (empty(self::$configuration)) {
      self::$configuration = $configuration;
    } else {
      // Append the configuration to the existing one.
      self::$configuration .= "\n" . $configuration;
    }
    self::applyConfiguration($configuration);
  }

  /**
   * Adds an interface to concrete class mapping.
   *
   * @param string $interface
   *   The fully-qualified interface name.
   * @param string $concrete_class
   *   The fully-qualified concrete class name.
   * @param bool $singleton
   *   Optional.  If TRUE, the concrete class will be treated as a singleton.
   *   The default value is FALSE.
   */
  public static function addMapping($interface, $concrete_class, $singleton = FALSE) {
    if (empty($interface) || !is_string($interface)) {
      throw new \InvalidArgumentException('The interface parameter must be a non-empty string.');
    }
    if (empty($concrete_class) || !is_string($concrete_class)) {
      throw new \InvalidArgumentException('The concrete_class parameter must be a non-empty string.');
    }
    if (!is_bool($singleton)) {
      throw new \InvalidArgumentException('The singleton parameter must be a boolean.');
    }
    $mapping = self::createMapping($interface, $concrete_class, $singleton ? 'singleton' : NULL);
    self::registerMapping($mapping);
  }

  /**
   * Removes an interface to concrete class mapping.
   *
   * @param string $interface
   *   The fully-qualified interface name.
   */
  public static function removeMapping($interface) {
    self::clearSingleton($interface);
    if (isset(self::$interfaceMap[$interface])) {
      unset(self::$interfaceMap[$interface]);
    }
  }

  /**
   * Clears a stored singleton, allowing it to be re-instantiated.
   *
   * @param string $interface
   *   The interface.
   */
  public static function clearSingleton($interface) {
    if (isset(self::$interfaceMap[$interface])) {
      $mapping = self::$interfaceMap[$interface];
      if (property_exists($mapping, 'concreteClass')) {
        if (isset(self::$singletonObjects[$mapping->concreteClass])) {
          unset(self::$singletonObjects[$mapping->concreteClass]);
        }
      }
    }
  }

  /**
   * Registers the specified mapping such that new instances can be created.
   *
   * @param object $mapping
   *   The mapping object that associates an interface to a concrete class.
   */
  private static function registerMapping($mapping) {
    if (empty($mapping->interface)) {
      throw new \InvalidArgumentException('The "mapping" parameter must contain a field named "interface".');
    }
    if (!is_array(self::$interfaceMap)) {
      self::$interfaceMap = array();
    }
    self::$interfaceMap[$mapping->interface] = $mapping;
  }

  /**
   * Gets a concrete instance for the specified interface.
   *
   * @param string $interface
   *   The interface for which a concrete class is required.
   *
   * @return mixed
   *   A concrete instance of the specified interface.
   *
   * @throws \InvalidArgumentException
   *   If the interface is not specified or does not exist in the configuration
   *   file.
   */
  public static function getObject($interface) {
    if (!is_string($interface) || empty($interface)) {
      throw new \InvalidArgumentException('The $interface parameter must be a non-empty string.');
    }
    if (self::$interfaceMap === NULL) {
      self::reset();
    }
    if (empty(self::$interfaceMap[$interface])) {
      throw new \InvalidArgumentException(
        sprintf(
          'The interface %s is not registered in the configuration file %s.',
          $interface,
          self::$configurationPath
        )
      );
    }

    $mapping = self::$interfaceMap[$interface];

    if ($mapping->type == 'config') {
      $result = $mapping->value;
    } elseif ($mapping->modifier === 'singleton' && !empty(self::$singletonObjects[$mapping->concreteClass])) {
      $result = self::$singletonObjects[$mapping->concreteClass];
    } else {
      $args = func_get_args();
      array_shift($args);
      $reflection = new \ReflectionClass($mapping->concreteClass);
      if (NULL !== $reflection->getConstructor()) {
        $result = $reflection->newInstanceArgs($args);
      } else {
        // The class has no constructor.
        $result = $reflection->newInstanceArgs();
      }

      if ($mapping->modifier === 'singleton') {
        self::$singletonObjects[$mapping->concreteClass] = $result;
      }
      // TODO: Once instantiated, verify that it implements the specified interface, but only in 'production' mode.
    }
    return $result;
  }

  /**
   * Reads a string property from the configuration.
   *
   * @param string $property_name
   *   The property name.  This should be prefixed with '$'.
   * @param string $default_value
   *   Optional.  The default value.
   *
   * @return string
   *   The property value, interpreted as a string, or the default value if the
   *   property is not defined or cannot be read.
   */
  public static function getString($property_name, $default_value = NULL) {
    $result = $default_value;
    try {
      $result = trim(WipFactory::getObject($property_name));
    } catch (\Exception $e) {
      // An exception will be encountered if the value does not exist in the
      // configuration file or it cannot be read for some reason. Since this
      // method has a default value feature, we need to swallow the exception to
      // allow the default value to be returned.
    }
    return $result;
  }

  /**
   * Reads a string property and interprets it as a filesystem path.
   *
   * Any dangerous characters found in the path will cause the path to be
   * rejected.
   *
   * @param string $property_name
   *   The property name.  This should be prefixed with '$'.
   * @param string $default_value
   *   Optional.  The default value.
   *
   * @return string
   *   The property value, interpreted as a filesystem path or the default value
   *   if the property is not defined or cannot be read.
   *
   * @throws \RuntimeException
   *   If the resulting path contains illegal or dangerous characters.
   */
  public static function getPath($property_name, $default_value = NULL) {
    $result = self::getString($property_name, $default_value);
    if (!empty($result)) {
      // Make sure the directory separator is correct for the current platform.
      $result = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $result);

      // Always generate an absolute path.
      if ($result[0] !== DIRECTORY_SEPARATOR) {
        $result = getcwd() . DIRECTORY_SEPARATOR . $result;
      }

      // Verify that the result makes sense as a path by checking for illegal
      // and dangerous characters in the path.
      if (!preg_match(sprintf('/^[a-zA-Z0-9._\-\%s]+$/', DIRECTORY_SEPARATOR), $result)) {
        throw new \RuntimeException(
          sprintf(
            'The %s property in the configuration file contains a path with dangerous characters: "%s".',
            $property_name,
            $result
          )
        );
      }
    }
    return $result;
  }

  /**
   * Reads an integer property from the configuration.
   *
   * @param string $property_name
   *   The property name.  This should be prefixed with '$'.
   * @param int $default_value
   *   Optional.  The default value.
   *
   * @return int
   *   The property value, interpreted as an integer, or the default value if
   *   the property is not defined or cannot be read.
   */
  public static function getInt($property_name, $default_value) {
    $result = $default_value;
    try {
      $property_value = WipFactory::getObject($property_name);
      $matches = array();
      if (1 === preg_match('/^\s*(-?\d+)\s*$/', $property_value, $matches)) {
        if (is_numeric($matches[1])) {
          $result = intval($matches[1]);
        }
      }
    } catch (\Exception $e) {
    }
    return $result;
  }

  /**
   * Reads a boolean value from the configuration.
   *
   * @param string $property_name
   *   The property name.  This should be prefixed with '$'.
   * @param mixed $default_value
   *   Optional.  The default value.
   *
   * @return null|bool
   *   The property value, interpreted as a boolean, or the default value as a
   *   boolean if the property is not defined or cannot be read.
   */
  public static function getBool($property_name, $default_value = NULL) {
    $result = $default_value;
    try {
      $result = strtolower(trim(WipFactory::getObject($property_name)));
    } catch (\Exception $e) {
      // An exception will be encountered if the value does not exist in the
      // configuration file or it cannot be read for some reason. Since this
      // method has a default value feature, we need to swallow the exception to
      // allow the default value to be returned.
    }
    // Using in_array with strict checking enabled to ensure we're evaluating
    // the correct data types. Boolean literals and boolean-like integers are
    // added to the lists so we can pass them as $default_value.
    if ($result !== NULL) {
      if (in_array($result, array('true', 'yes', 'on', '1', 1, TRUE), TRUE)) {
        $result = TRUE;
      } elseif (in_array($result, array('false', 'no', 'off', '0', 0, FALSE), TRUE)) {
        $result = FALSE;
      } else {
        throw new \InvalidArgumentException(
          sprintf('The value of %s could not be coerced to a boolean: %s', $property_name, var_export($result, TRUE))
        );
      }
    }
    return $result;
  }

  /**
   * Reads an integer array property from the configuration.
   *
   * @param string $property_name
   *   The property name.  This should be prefixed with '$'.
   * @param int[] $default_value
   *   Optional.  The default value.
   *
   * @return int[]
   *   The property value, interpreted as an integer array, or the default
   *   value if the property is not defined or cannot be read.
   */
  public static function getIntArray($property_name, $default_value = array()) {
    $result = $default_value;
    try {
      $int_values = array();
      $matches = array();
      $value = WipFactory::getObject($property_name);
      if (1 === preg_match('/^\s*\[([^\]]+)\]\s*$/', $value, $matches)) {
        $values = explode(',', $matches[1]);
        foreach ($values as $value) {
          $value = trim($value);
          if (!empty($value) && is_numeric($value)) {
            $int_values[] = intval($value);
          }
        }
        $result = $int_values;
      }
    } catch (\Exception $e) {
    }
    return $result;
  }

  /**
   * Resets the interface map to the original configuration.
   *
   * This feature is useful in cases where a test needs to swap out the
   * concrete class for a particular interface.  Calling reset guarantees that
   * change does not affect the remainder of the tests.
   */
  public static function reset() {
    self::$interfaceMap = array();
    self::applyOverrides();

    // Interpret any configurations set with either WipFactory::setConfiguration
    // or WipFactory::addConfiguration.
    if (!empty(self::$configuration)) {
      self::applyConfiguration(self::$configuration);
    }
  }

  /**
   * Applies the specified configuration to the existing mapping.
   *
   * This can result in additional mappings being established or old mappings
   * being replaced.
   *
   * @param string $configuration
   *   Configuration text.
   */
  private static function applyConfiguration($configuration) {
    $lines = explode("\n", $configuration);
    $line_count = count($lines);
    for ($line_number = 1; $line_number <= $line_count; $line_number++) {
      $mapping = self::parseLine(trim($lines[$line_number - 1]), $line_number);
      if (!empty($mapping)) {
        self::registerMapping($mapping);
      }
    }
  }

  /**
   * Returns the Acquia Hosting site group name.
   *
   * @return string
   *   The site group name.
   *
   * @throws \DomainException
   *   If the hosting site group environment variable is not set.
   */
  public static function getHostingSiteGroup() {
    $result = getenv('AH_SITE_GROUP');
    if ($result === FALSE) {
      throw new \DomainException(
        'The Acquia Hosting site group name is not set in the "AH_SITE_GROUP" environment variable.'
      );
    }
    return $result;
  }

  /**
   * Returns the Acquia Hosting environment name.
   *
   * @return string
   *   The environment name.
   *
   * @throws \DomainException
   *   If the AH_SITE_ENVIRONMENT environment variable is not set.
   */
  public static function getHostingEnvironment() {
    $result = getenv('AH_SITE_ENVIRONMENT');
    if ($result === FALSE) {
      throw new \DomainException(
        'The Acquia Hosting environment name is not set in the "AH_SITE_GROUP" environment variable.'
      );
    }
    return $result;
  }

  /**
   * Parses a single line of the configuration file.
   *
   * @param string $line
   *   The text of the line.
   * @param int $line_number
   *   The line number.
   *
   * @return object|null
   *   An object that contains the elements within the line of configuration.
   *   The elements include 'interface', indicating the fully-qualified
   *   interface name, 'concreteClass', indicating the fully-qualified concrete
   *   class name that will be instantiated when the associated interface is
   *   requested, and optionally a modifier that indicates whether the class
   *   is a singleton.  NULL is returned if the line does not contain a valid
   *   mapping.
   *
   * @throws \UnexpectedValueException
   *   If there is a syntax error in the configuration file.
   */
  private static function parseLine($line, $line_number) {
    $result = NULL;
    $line = trim($line);

    if (empty($line)) {
      // Ignore.
    } elseif (self::parseComment($line)) {
      // Ignore.
    } else {
      $result = self::parseMapping($line);
      if ($result === NULL) {
        throw new \UnexpectedValueException(
          sprintf('Syntax error in %s line %d (%s)', self::$configurationPath, $line_number, $line)
        );
      }
    }
    return $result;
  }

  /**
   * Tries to parse the specified line as a comment.
   *
   * @param string $line
   *   The line from the configuration file to parse.
   *
   * @return bool
   *   TRUE if the specified line represents a comment; FALSE otherwise.
   */
  private static function parseComment($line) {
    $result = FALSE;
    if (1 === preg_match('/^;.*$/', $line, $matches)) {
      $result = TRUE;
    }
    return $result;
  }

  /**
   * Tries to parse the specified line as an interface mapping.
   *
   * @param string $line
   *   The line from the configuration file to parse.
   *
   * @return object|null
   *   An object that contains the elements within the line of configuration.
   *   The elements include 'interface', indicating the fully-qualified
   *   interface name, 'concreteClass', indicating the fully-qualified concrete
   *   class name that will be instantiated when the associated interface is
   *   requested, and optionally a modifier that indicates whether the class
   *   is a singleton.  NULL is returned if the line does not contain a valid
   *   mapping.
   */
  private static function parseMapping($line) {
    $result = NULL;
    if (1 === preg_match('/^(?P<label>\$[^\s]+)\s+=>\s*(?P<value>.+)$/', $line, $matches)) {
      $result = self::createStringMapping($matches['label'], $matches['value']);
    } elseif (1 === preg_match('/^([^\s]+)\s+=>\s+([^\s]+)(\s+[^\s]+)?(\s*;.*)?$/', $line, $matches)) {
      // This is a line representing the mapping between an interface and a
      // concrete class.
      $interface = $matches[1];
      $concrete_class = $matches[2];
      $modifier = NULL;
      if (count($matches) >= 4) {
        $modifier = strtolower(trim($matches[3]));
        if (!in_array($modifier, self::$legalModifiers)) {
          $modifier = NULL;
        }
      }
      $result = self::createMapping($interface, $concrete_class, $modifier);
    }

    return $result;
  }

  /**
   * Creates a mapping object that associates an interface with a concrete class.
   *
   * @param string $interface
   *   The fully-qualified interface name.
   * @param string $concrete_class
   *   The fully-qualified concrete class name.
   * @param string $modifier
   *   Optional.  Any modifier for the association.  An example would be
   *   'singleton' to indicate only one of the specified concrete class should
   *   ever be instantiated.
   *
   * @return object
   *   An object that contains the relationship between an interface and its
   *   concrete class name.
   */
  private static function createMapping($interface, $concrete_class, $modifier = NULL) {
    $result = new \stdClass();
    $result->interface = $interface;
    $result->concreteClass = $concrete_class;
    $result->modifier = $modifier;
    $result->type = 'object';
    return $result;
  }

  /**
   * Creates a mapping that is simply a string literal, not an object.
   *
   * @param string $key
   *   The key.
   * @param string $value
   *   The value.
   *
   * @return object
   *   The mapping object.
   */
  private static function createStringMapping($key, $value) {
    $result = new \stdClass();
    $result->interface = $key;
    $result->value = $value;
    $result->type = 'config';
    return $result;
  }

}
