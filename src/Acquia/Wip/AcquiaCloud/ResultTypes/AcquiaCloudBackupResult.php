<?php

namespace Acquia\Wip\AcquiaCloud\ResultTypes;

use Acquia\Wip\AcquiaCloud\AcquiaCloudResult;
use Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudBackupInfo;

/**
 * This result contains a single instance of AcquiaCloudBackupInfo.
 */
class AcquiaCloudBackupResult extends AcquiaCloudResult {

  /**
   * Sets the data into this result instance.
   *
   * @param AcquiaCloudBackupInfo|mixed $data
   *   The data.
   */
  public function setData($data) {
    if (!$data instanceof AcquiaCloudBackupInfo) {
      throw new \InvalidArgumentException('The data parameter must be an instance of AcquiaCloudBackupInfo.');
    }
    parent::setData($data);
  }

}
