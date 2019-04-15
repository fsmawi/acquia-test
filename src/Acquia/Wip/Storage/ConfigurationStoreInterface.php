<?php

namespace Acquia\Wip\Storage;

/**
 * The ConfigurationStoreInterface provides storage for runtime configuration.
 *
 * Typically this will be used to store configurations that we might want to be
 * able to change on the fly without changing configuration files.
 */
interface ConfigurationStoreInterface {

  /**
   * Retrieve a piece of configuration data.
   *
   * @param string $key
   *   The key that this configuration was stored under.
   * @param mixed $default
   *   A default value to return if no stored value is found.
   *
   * @return mixed
   *   The configuration that was retrieved, or the default or NULL on failure.
   */
  public function get($key, $default = NULL);

  /**
   * Store a piece of configuration data.
   *
   * @param string $key
   *   The key by which to refer to this configuration.
   * @param mixed $value
   *   The value to store.
   */
  public function set($key, $value);

  /**
   * Removes a piece of configuration.
   *
   * @param string $key
   *   The key that was used when storing this configuration.
   */
  public function delete($key);

}
