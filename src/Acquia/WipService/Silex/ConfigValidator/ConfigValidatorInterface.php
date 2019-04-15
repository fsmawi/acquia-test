<?php

namespace Acquia\WipService\Silex\ConfigValidator;

/**
 * Configuration validator interface used by the ValidatedConfigServiceProvider.
 */
interface ConfigValidatorInterface {

  /**
   * Validate the given configuration.
   *
   * @param mixed $config
   *   The configuration to validate.
   *
   * @throws InvalidConfigurationException
   *   The details of the configuration error.
   */
  public function validate($config);

}
