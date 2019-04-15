<?php

namespace Acquia\Wip\AcquiaCloud;

use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudTaskResult;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipProcessInterface;

/**
 * This interface represents a running AcquiaCloud task.
 */
interface AcquiaCloudProcessInterface extends WipProcessInterface {

  /**
   * Gets the task info.
   *
   * @param WipLogInterface $logger
   *   The logger.
   *
   * @return AcquiaCloudTaskResult
   *   The task information.
   */
  public function getTaskInfo(WipLogInterface $logger);

  /**
   * Sets the error message associated with this process.
   *
   * @param string|\Exception $e
   *   The error.
   * @param WipLogInterface $logger
   *   The logger.
   */
  public function setError($e, WipLogInterface $logger);

  /**
   * Gets the error message associated with this process.
   *
   * @return string
   *   The error.
   */
  public function getError();

  /**
   * Sets the class name of the TaskInfo object associated with this process.
   *
   * @param string $class_name
   *   The class name.
   */
  public function setTaskInfoClass($class_name);

  /**
   * Gets the class name of the TaskInfo object associated with this process.
   *
   * @return string|null
   *   The TaskInfo class name, or NULL if the class name has not been set.  If
   *   not set, the resulting TaskInfo will be of type AcquiaCloudTaskResult.
   */
  public function getTaskInfoClass();

}
