<?php

namespace Acquia\Wip\AcquiaCloud\ResultTypes;

use Acquia\Wip\AcquiaCloud\AcquiaCloudResult;
use Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudDatabaseInfo;

/**
 * Contains an array of AcquiaCloudDatabaseInfo objects.
 */
class AcquiaCloudDatabaseArrayResult extends AcquiaCloudResult {

  /**
   * Sets the data into this result instance.
   *
   * @param AcquiaCloudDatabaseInfo[] $data
   *   The data.
   */
  public function setData($data) {
    if (!is_array($data)) {
      throw new \InvalidArgumentException('The data parameter must be an array.');
    }
    foreach ($data as $value) {
      if (!$value instanceof AcquiaCloudDatabaseInfo) {
        throw new \InvalidArgumentException(
          'The data parameter must only contain instances of AcquiaCloudDatabaseInfo.'
        );
      }
    }
    parent::setData($data);
  }

}
