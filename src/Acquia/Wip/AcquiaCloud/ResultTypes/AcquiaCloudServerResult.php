<?php

namespace Acquia\Wip\AcquiaCloud\ResultTypes;

use Acquia\Wip\AcquiaCloud\AcquiaCloudResult;
use Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudServerInfo;

/**
 * Provides information about a particular server.
 */
class AcquiaCloudServerResult extends AcquiaCloudResult {

  /**
   * Sets the data into this result instance.
   *
   * @param AcquiaCloudServerInfo|mixed $data
   *   The data.
   */
  public function setData($data) {
    if (!$data instanceof AcquiaCloudServerInfo) {
      throw new \InvalidArgumentException('The data parameter must be an instance of AcquiaCloudServerInfo');
    }
    parent::setData($data);
  }

}
