<?php

namespace Acquia\WipService\Silex\ConfigValidator;

use Acquia\Wip\State\Maintenance;

/**
 * Validates the service description configuration.
 */
class ServiceDescriptionConfigValidator implements ConfigValidatorInterface {
  const ALLOWED_DURING_MAINTENANCE = 'allowedDuringMaintenance';

  /**
   * {@inheritdoc}
   */
  public function validate($config) {
    if (!is_array($config)) {
      throw new InvalidConfigurationException('The service description configuration must be an array.');
    }

    if (!array_key_exists('operations', $config)) {
      throw new InvalidConfigurationException(
        'The service description configuration requires a top-level "operations" member.'
      );
    }

    if (!is_array($config['operations'])) {
      throw new InvalidConfigurationException(
        'The "operations" member of the service description configuration must be an array.'
      );
    }

    foreach ($config['operations'] as $route_name => $route) {
      if (array_key_exists(self::ALLOWED_DURING_MAINTENANCE, $route)) {
        $current_config_path = sprintf('operations.%s.allowedDuringMaintenance', $route_name);
        if (!is_array($route[self::ALLOWED_DURING_MAINTENANCE])) {
          throw new InvalidConfigurationException(
            'The allowedDuringMaintenance parameter must be an array.',
            $current_config_path
          );
        }

        foreach ($route[self::ALLOWED_DURING_MAINTENANCE] as $mode) {
          if (!Maintenance::isValidValue($mode)) {
            throw new InvalidConfigurationException(
              sprintf('Invalid maintenance mode "%s".', $mode),
              $current_config_path
            );
          }
        }
      }
    }
  }

}
