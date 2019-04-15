<?php

namespace Acquia\Wip\AcquiaCloud\ResultTypes;

use Acquia\Wip\AcquiaCloud\AcquiaCloudResult;

/**
 * Contains information that consists of a single string.
 */
class AcquiaCloudStringResult extends AcquiaCloudResult {

  /**
   * Sets the data into this result instance.
   *
   * @param string $data
   *   The data.
   */
  public function setData($data) {
    if (!is_string($data)) {
      throw new \InvalidArgumentException('The data parameter must be a string.');
    }
    parent::setData($data);
  }

}
