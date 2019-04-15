<?php

namespace Acquia\Wip\AcquiaCloud\ResultTypes;

use Acquia\Wip\AcquiaCloud\AcquiaCloudResult;
use Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudTaskInfo;

/**
 * Provides information about a set of tasks.
 */
class AcquiaCloudTaskArrayResult extends AcquiaCloudResult {

  /**
   * Sets the task info into this result instance.
   *
   * @param AcquiaCloudTaskInfo[] $task_data
   *   The data.
   */
  public function setData($task_data) {
    if (!is_array($task_data)) {
      throw new \InvalidArgumentException('The task_data parameter must be an array.');
    }
    foreach ($task_data as $task) {
      if (!($task instanceof AcquiaCloudTaskInfo)) {
        throw new \InvalidArgumentException(
          'The task_data parameter must be an array of AcquiaCloudTaskInfo instances.'
        );
      }
    }
    parent::setData($task_data);
  }

}
