<?php

namespace Acquia\Wip\Storage;

use Acquia\Wip\DependencyManager;
use Acquia\Wip\Exception\NotImplementedException;

/**
 * Provides a base class to test State data storage.
 *
 * @copydetails StateStoreInterface
 */
class BasicStateStore implements StateStoreInterface {

  /**
   * Storage implementation as an array.
   *
   * @var array
   */
  private $data = array();

  /**
   * The DependencyManager instance.
   *
   * @var DependencyManager
   */
  protected $dependencyManager;

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.metrics.relay' => 'Acquia\Wip\Metrics\MetricsRelayInterface',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function get($key, $default_value = NULL) {
    if (empty($key) || !is_string($key)) {
      throw new \InvalidArgumentException('The "key" argument must be a string.');
    }

    return isset($this->data[$key]) ? unserialize($this->data[$key]) : $default_value;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime($key, $default_value = NULL) {
    throw new NotImplementedException('This method has not been implemented.');
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    if (empty($key) || !is_string($key)) {
      throw new \InvalidArgumentException('The "key" argument must be a string.');
    }

    // Just doing this serialized to more closely match a real-world
    // implementation.
    $this->data[$key] = serialize($value);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($key) {
    if (empty($key) || !is_string($key)) {
      throw new \InvalidArgumentException('The "key" argument must be a string.');
    }

    if (isset($this->data[$key])) {
      unset($this->data[$key]);
    }
  }

}
