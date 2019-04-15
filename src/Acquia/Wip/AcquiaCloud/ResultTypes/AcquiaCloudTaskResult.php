<?php

namespace Acquia\Wip\AcquiaCloud\ResultTypes;

use Acquia\Wip\AcquiaCloud\AcquiaCloudResult;
use Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudTaskInfo;

/**
 * Provides information about a particular task.
 */
class AcquiaCloudTaskResult extends AcquiaCloudResult {

  /**
   * Sets the task info into this result instance.
   *
   * @param AcquiaCloudTaskInfo|mixed $task_data
   *   The data.
   */
  public function setData($task_data) {
    if (!$task_data instanceof AcquiaCloudTaskInfo) {
      throw new \InvalidArgumentException('The task_data parameter must be an instance of AcquiaCloudTaskInfo');
    }
    parent::setData($task_data);
  }

  /**
   * {@inheritdoc}
   */
  public function isSuccess() {
    $result = parent::isSuccess();
    if ($result) {
      $result = $this->getData()->isSuccess();
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function forceFail($reason = NULL) {
    $this->setExitCode(self::FORCE_FAIL_EXIT_CODE);
  }

}
