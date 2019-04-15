<?php

namespace Acquia\Wip\AcquiaCloud\ResultTypes;

use Acquia\Wip\AcquiaCloud\AcquiaCloudResult;
use Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudEnvironmentInfo;

/**
 * Provides information about a particular environment.
 */
class AcquiaCloudEnvironmentResult extends AcquiaCloudResult {

  /**
   * Sets the data into this result instance.
   *
   * @param AcquiaCloudEnvironmentInfo|mixed $data
   *   The data.
   */
  public function setData($data) {
    if (!$data instanceof AcquiaCloudEnvironmentInfo) {
      throw new \InvalidArgumentException('The data parameter must be of type AcquiaCloudEnvironmentInfo');
    }
    parent::setData($data);
  }

}
