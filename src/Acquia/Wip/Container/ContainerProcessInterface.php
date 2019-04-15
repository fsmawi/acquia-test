<?php

namespace Acquia\Wip\Container;

use Acquia\Wip\Signal\ContainerCompleteSignal;
use Acquia\Wip\WipProcessInterface;

/**
 * Represents a running WIP task delegated to a container.
 */
interface ContainerProcessInterface extends WipProcessInterface {

  /**
   * Sets the container that will be used by this Process instance.
   *
   * @param ContainerInterface $container
   *   An instance of ContainerInterface representing the container.
   */
  public function setContainer(ContainerInterface $container);

  /**
   * Gets the container associated with this Process instance.
   *
   * @return ContainerInterface
   *   An instance of ContainerInterface representing the container.
   */
  public function getContainer();

  /**
   * Indicates whether the container managed by this process has started.
   *
   * This method should return TRUE only when the container is ready to
   * configure and assign work to.
   *
   * @return bool
   *   TRUE if the container has been started; FALSE otherwise.
   */
  public function hasStarted();

  /**
   * Checks whether the container managed by this process has been configured.
   *
   * @return bool
   *   TRUE if the container has been configured; FALSE otherwise.
   */
  public function isConfigured();

  /**
   * Indicates whether the container launch has failed.
   *
   * The launch will be considered failed if the container fails to enter the
   * running state.
   *
   * @return bool
   *   TRUE if the container has failed to launch; FALSE otherwise.
   */
  public function launchFailed();

  /**
   * Creates an instance of ContainerResult from the specified signal.
   *
   * @param ContainerCompleteSignal $signal
   *   The signal from which the ContainerResult instance will be created.
   *
   * @return ContainerResultInterface
   *   The newly created ContainerResult instance.
   */
  public function getResultFromSignal(ContainerCompleteSignal $signal);

}
