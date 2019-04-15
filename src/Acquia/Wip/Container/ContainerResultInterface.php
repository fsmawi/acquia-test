<?php

namespace Acquia\Wip\Container;

use Acquia\Wip\WipResultInterface;

/**
 * Represents the result of a completed container process.
 */
interface ContainerResultInterface extends WipResultInterface {

  /**
   * Sets the Container instance associated with this result.
   *
   * @param ContainerInterface $container
   *   An instance of ContainerInterface representing the container.
   */
  public function setContainer(ContainerInterface $container);

  /**
   * Gets the Container instance associated with this result.
   *
   * @return ContainerInterface
   *   An instance of ContainerInterface representing the container.
   */
  public function getContainer();

}
