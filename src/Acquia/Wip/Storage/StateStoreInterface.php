<?php

namespace Acquia\Wip\Storage;

use Acquia\Wip\DependencyManagedInterface;

/**
 * The StateStoreInterface provides storage for transient information.
 *
 * Typically this will be used to store information about the internal state of
 * the runtime, but can be used for any arbitrary transient data that is not
 * persistent configuration.
 */
interface StateStoreInterface extends DependencyManagedInterface {

  /**
   * Retrieves a piece of state data.
   *
   * @param string $key
   *   The key that this data was stored under.
   * @param mixed $default_value
   *   Optional. The default value for this state.
   *
   * @return mixed
   *   The value that was retrieved, or the default value or NULL on failure.
   *
   * @throws \InvalidArgumentException
   *   If the key argument is not a non-empty string.
   */
  public function get($key, $default_value = NULL);

  /**
   * Retrieves the "changed" field of a key.
   *
   * @param string $key
   *   The key that this data was stored under.
   * @param mixed $default_value
   *   Optional. The default value for this state.
   *
   * @return mixed
   *   The Unix timestamp indicating when the specified key had changed, or
   *   the default value or NULL on failure.
   *
   * @throws \InvalidArgumentException
   *   If the key argument is not a non-empty string.
   */
  public function getChangedTime($key, $default_value = NULL);

  /**
   * Stores a piece of state data.
   *
   * @param string $key
   *   The key by which to refer to this data.
   * @param mixed $value
   *   The data itself.
   *
   * @throws \InvalidArgumentException
   *   If the key argument is not a non-empty string.
   */
  public function set($key, $value);

  /**
   * Removes a piece of state data.
   *
   * @param string $key
   *   The key that was used when storing the piece of state data to be
   *   retrieved.
   *
   * @throws \InvalidArgumentException
   *   If the key argument is not a non-empty string.
   */
  public function delete($key);

}
