<?php

namespace Acquia\Wip\AcquiaCloud\ResultTypes;

use Acquia\Wip\AcquiaCloud\AcquiaCloudResult;
use Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudSiteInfo;

/**
 * Contains information about a particular site.
 */
class AcquiaCloudSiteResult extends AcquiaCloudResult {

  /**
   * Sets the data into this result instance.
   *
   * @param AcquiaCloudSiteInfo|mixed $data
   *   The data.
   */
  public function setData($data) {
    if (!$data instanceof AcquiaCloudSiteInfo) {
      throw new \InvalidArgumentException('The data parameter must be an instance of AcquiaCloudSiteInfo.');
    }
    parent::setData($data);
  }

}
