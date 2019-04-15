<?php

namespace Acquia\Wip\Container;

use Acquia\Wip\Signal\ContainerCompleteSignal;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipProcess;
use Acquia\Wip\WipResultInterface;

/**
 * Wraps a container invocation as a process implementation.
 */
class ContainerProcess extends WipProcess implements ContainerProcessInterface {

  /**
   * The container this Process instance is associated with.
   *
   * @var ContainerInterface
   */
  private $container = NULL;

  /**
   * {@inheritdoc}
   */
  public function setContainer(ContainerInterface $container) {
    $this->container = $container;
    $this->addSuccessExitCode(0);
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
    return ContainerResult::createUniqueId($this->getPid(), $this->getStartTime());
  }

  /**
   * {@inheritdoc}
   */
  public function hasStarted() {
    return $this->container->hasStarted();
  }

  /**
   * {@inheritdoc}
   */
  public function isConfigured() {
    return $this->container->isConfigured();
  }

  /**
   * {@inheritdoc}
   */
  public function launchFailed() {
    return $this->container->launchFailed();
  }

  /**
   * {@inheritdoc}
   */
  public function kill(WipLogInterface $logger) {
    $this->container->kill();
    return $this->hasCompleted($logger);
  }

  /**
   * {@inheritdoc}
   */
  public function setResult(WipResultInterface $result) {
    if (!$result instanceof ContainerResultInterface) {
      throw new \InvalidArgumentException('The result parameter must be of type ContainerResultInterface.');
    }
    parent::setResult($result);
  }

  /**
   * {@inheritdoc}
   */
  public function getResult(WipLogInterface $wip_log, $fetch = FALSE) {
    $result = parent::getResult($wip_log);
    if (empty($result) && $fetch) {
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getResultFromSignal(ContainerCompleteSignal $signal) {
    $result = new ContainerResult();
    $result->populateFromProcess($this);
    try {
      $result->setEndTime(intval($signal->getEndTime()));
    } catch (\Exception $e) {
    }
    try {
      $result->setExitCode(intval($signal->getExitCode()));
    } catch (\Exception $e) {
    }
    try {
      $result->setExitMessage(strval($signal->getExitMessage()));
    } catch (\Exception $e) {
    }
    $result->setContainer($this->getContainer());
    $this->setResult($result);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function forceFail($reason, WipLogInterface $logger) {
    parent::forceFail($reason, $logger);

    // Set a failed result, which will prevent the
    // ContainerApi::checkContainerStatus method from spinning forever.
    $result = new ContainerResult();
    $result->populateFromProcess($this);
    try {
      $result->setEndTime(time());
    } catch (\Exception $e) {
    }
    $result->setContainer($this->getContainer());
    $this->setResult($result);
  }

}
