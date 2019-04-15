<?php

namespace Acquia\Wip\AcquiaCloud\ResultTypes;

use Acquia\Wip\AcquiaCloud\AcquiaCloudResult;
use Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudServerInfo;

/**
 * Provides information about a set of servers.
 */
class AcquiaCloudServerArrayResult extends AcquiaCloudResult {

  /**
   * Sets the data into this result instance.
   *
   * @param AcquiaCloudServerInfo[] $data
   *   The data.
   */
  public function setData($data) {
    if (!is_array($data)) {
      throw new \InvalidArgumentException('The data parameter must be an array.');
    }
    foreach ($data as $value) {
      if (!$value instanceof AcquiaCloudServerInfo) {
        throw new \InvalidArgumentException('The data parameter must only contain instances of AcquiaCloudServerInfo.');
      }
    }
    parent::setData($data);
  }

}
