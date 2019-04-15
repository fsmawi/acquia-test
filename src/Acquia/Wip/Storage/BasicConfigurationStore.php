<?php

namespace Acquia\Wip\Storage;

/**
 * Provides a base class to test configuration data storage.
 *
 * @copydetails ConfigurationStoreInterface
 */
class BasicConfigurationStore implements ConfigurationStoreInterface {

  /**
   * Storage implementation as an array.
   *
   * @var array
   */
  private $data = array();

  /**
   * {@inheritdoc}
   */
  public function get($key, $default = NULL) {
    return isset($this->data[$key]) ? unserialize($this->data[$key]) : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    // Just doing this serialized to more closely match a real-world
    // implementation.
    $this->data[$key] = serialize($value);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($key) {
    if (isset($this->data[$key])) {
      unset($this->data[$key]);
    }
  }

}
