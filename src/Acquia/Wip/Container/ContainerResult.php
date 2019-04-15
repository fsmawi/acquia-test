<?php

namespace Acquia\Wip\Container;

use Acquia\Wip\WipResult;

/**
 * Represents the result of a completed container process.
 */
class ContainerResult extends WipResult implements ContainerResultInterface {

  /**
   * The container instance representing the running container.
   *
   * @var ContainerInterface
   */
  private $container = NULL;

  /**
   * {@inheritdoc}
   */
  public function setContainer(ContainerInterface $container) {
    $this->container = $container;
  }

  /**
   * {@inheritdoc}
   */
  public function getContainer() {
    return $this->container;
  }

  /**
   * {@inheritdoc}
   */
  public function setPid($pid) {
    if (empty($pid) || !is_string($pid)) {
      throw new \InvalidArgumentException('The pid parameter must be a string.');
    }
    parent::setPid($pid);
  }

  /**
   * {@inheritdoc}
   */
  public function getUniqueId() {
    return self::createUniqueId($this->getPid(), $this->getStartTime());
  }

  /**
   * {@inheritdoc}
   */
  public static function createUniqueId($pid, $start_time) {
    return sprintf('%s@%d', $pid, $start_time);
  }

}
