<?php

namespace Acquia\WipIntegrations\DoctrineORM;

use Acquia\WipIntegrations\DoctrineORM\Entities\StateStoreEntry;
use Acquia\WipService\App;
use Acquia\Wip\DependencyManager;
use Acquia\Wip\Metrics\MetricsRelayInterface;
use Acquia\Wip\Storage\StateStoreInterface;
use Doctrine\ORM\EntityManagerInterface;
use Silex\Application;

/**
 * Provides CRUD features for state data using Doctrine ORM.
 *
 * @copydetails StateStoreInterface
 */
class StateStore implements StateStoreInterface {

  /**
   * The entity name.
   */
  const ENTITY_NAME = 'Acquia\WipIntegrations\DoctrineORM\Entities\StateStoreEntry';

  /**
   * The prefix for metrics.
   */
  const METRIC_PREFIX = 'wip.system.outages.';

  /**
   * The name of the active thread.
   */
  const ACTIVE_THREAD_NAME = 'acquia.wip.threadpool.active';

  /**
   * The entity manager.
   *
   * @var EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The interface to send the timing metrics to.
   *
   * @var MetricsRelayInterface
   */
  private $relay;

  /**
   * Dependency manager.
   *
   * @var DependencyManager
   */
  protected $dependencyManager;

  /**
   * Creates a new instance of StateStore.
   */
  public function __construct() {
    $this->entityManager = App::getEntityManager();

    $this->dependencyManager = new DependencyManager();
    $this->dependencyManager->addDependencies($this->getDependencies());

    $this->relay = $this->dependencyManager->getDependency('acquia.wip.metrics.relay');
  }

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

    return $this->getField($key, 'value', $default_value);
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime($key, $default_value = NULL) {
    if (empty($key) || !is_string($key)) {
      throw new \InvalidArgumentException('The "key" argument must be a string.');
    }

    return $this->getField($key, 'changed', $default_value);
  }

  /**
   * Helps to retrieve either the value or the "changed" field of a key.
   *
   * @param string $key
   *   The key that this data was stored under.
   * @param string $field
   *   The field to retrieve.
   * @param mixed $default_value
   *   Optional. The default value for this state.
   *
   * @return mixed
   *   The data that was retrieved, or NULL on failure.
   */
  private function getField($key, $field, $default_value = NULL) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    /** @var StateStoreEntry $state */
    $state = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->find($key);

    if ($state) {
      if ($field == 'changed') {
        return $state->getChanged();
      }
      return unserialize($state->getValue());
    } else {
      return $default_value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    if (empty($key) || !is_string($key)) {
      throw new \InvalidArgumentException('The "key" argument must be a string.');
    }

    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    $state = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->find($key);

    if (!$state) {
      $state = new StateStoreEntry();
      $state->setName($key);
      $state->setChanged(time());

      if ($key !== self::ACTIVE_THREAD_NAME) {
        $gauge_name = self::METRIC_PREFIX . $key;
        $this->relay->gauge($gauge_name, 1);
      }
    }
    $state->setValue(serialize($value));

    $this->entityManager->persist($state);
    $this->entityManager->flush();
  }

  /**
   * {@inheritdoc}
   */
  public function delete($key) {
    if (empty($key) || !is_string($key)) {
      throw new \InvalidArgumentException('The "key" argument must be a string.');
    }
    // Get the timestamp when the state was changed.
    $time_changed = $this->getChangedTime($key);

    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    $state = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->find($key);

    if ($state) {
      $this->entityManager->remove($state);
      $this->entityManager->flush();

      if ($key !== self::ACTIVE_THREAD_NAME) {
        $current_time = time();
        $elapsed_time = $current_time - $time_changed;
        $gauge_name = self::METRIC_PREFIX . $key;
        $timer_name = $gauge_name . '.duration';

        // The timer relays the elapsed time since the key value was inserted
        // into the table, whereas the gauge provides a binary value of
        // whether the key is currently in the table.
        $this->relay->timing($timer_name, $elapsed_time);
        $this->relay->gauge($gauge_name, 0);
      }
    }
  }

}
