<?php

namespace Acquia\Wip\Container;

use Acquia\Wip\WipTaskConfig;

/**
 * A fake implementation of ContainerInterface.
 */
class NullContainer extends AbstractContainer implements ContainerInterface {

  /**
   * Whether the container has started yet.
   *
   * @var bool
   */
  protected $started = TRUE;

  /**
   * {@inheritdoc}
   */
  public function hasStarted() {
    return $this->started;
  }

  /**
   * {@inheritdoc}
   */
  public function setStarted($started) {
    $this->started = $started;
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $this->setPid(sprintf('pid%d', mt_rand()));
    $process = parent::run();
    return $process;
  }

  /**
   * {@inheritdoc}
   */
  public function initializeContainer(ContainerProcessInterface $process, WipTaskConfig $configuration) {
    $this->setConfigured(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function kill() {
  }

  /**
   * {@inheritdoc}
   */
  public function addContainerOverride($key, $value, $secure = FALSE) {
    if ($secure) {
      $this->addSecureOverrideKey($key);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getContainerStatus($force_load = FALSE) {
    return 'STOPPED';
  }

  /**
   * {@inheritdoc}
   */
  public function getContainerNextStatus($force_load = FALSE) {
    return 'STOPPED';
  }

}
