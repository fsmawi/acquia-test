<?php

namespace Acquia\Wip\AcquiaCloud\ResultTypes;

use Acquia\Wip\AcquiaCloud\AcquiaCloudResult;
use Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudDatabaseInfo;

/**
 * Holds a single instance of AcquiaCloudDatabaseInfo.
 */
class AcquiaCloudDatabaseResult extends AcquiaCloudResult {

  /**
   * Sets the data into this result instance.
   *
   * @param AcquiaCloudDatabaseInfo|mixed $data
   *   The data.
   */
  public function setData($data) {
    if (!$data instanceof AcquiaCloudDatabaseInfo) {
      throw new \InvalidArgumentException('The data parameter must be of type AcquiaCloudDatabaseInfo.');
    }
    parent::setData($data);
  }

}
