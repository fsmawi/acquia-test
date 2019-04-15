<?php

namespace Acquia\Wip\AcquiaCloud\ResultTypes;

use Acquia\Wip\AcquiaCloud\AcquiaCloudResult;
use Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudBackupInfo;

/**
 * This result class contains an array of AcquiaCloudBackupInfo.
 */
class AcquiaCloudBackupArrayResult extends AcquiaCloudResult {

  /**
   * Sets the data into this result instance.
   *
   * @param AcquiaCloudBackupInfo[] $data
   *   The data.
   */
  public function setData($data) {
    if (!is_array($data)) {
      throw new \InvalidArgumentException('The data parameter must be an array.');
    }
    foreach ($data as $value) {
      if (!$value instanceof AcquiaCloudBackupInfo) {
        throw new \InvalidArgumentException('The data parameter must only contain instances of AcquiaCloudBackupInfo.');
      }
    }
    parent::setData($data);
  }

}
