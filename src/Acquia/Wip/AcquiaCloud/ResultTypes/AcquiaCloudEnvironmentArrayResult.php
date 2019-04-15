<?php

namespace Acquia\Wip\AcquiaCloud\ResultTypes;

use Acquia\Wip\AcquiaCloud\AcquiaCloudResult;
use Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudEnvironmentInfo;

/**
 * Provides information about a set of environments.
 */
class AcquiaCloudEnvironmentArrayResult extends AcquiaCloudResult {

  /**
   * Sets the data into this result instance.
   *
   * @param AcquiaCloudEnvironmentInfo[] $data
   *   The data.
   */
  public function setData($data) {
    if (!is_array($data)) {
      throw new \InvalidArgumentException('The data parameter must be an array.');
    }
    foreach ($data as $value) {
      if (!$value instanceof AcquiaCloudEnvironmentInfo) {
        throw new \InvalidArgumentException(
          'The data parameter must only contain instances of AcquiaCloudEnvironmentInfo.'
        );
      }
    }
    parent::setData($data);
  }

}
