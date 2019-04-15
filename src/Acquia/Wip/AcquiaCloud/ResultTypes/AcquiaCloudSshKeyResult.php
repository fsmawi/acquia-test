<?php

namespace Acquia\Wip\AcquiaCloud\ResultTypes;

use Acquia\Wip\AcquiaCloud\AcquiaCloudResult;
use Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudSshKeyInfo;

/**
 * Provides information about a particular ssh key.
 */
class AcquiaCloudSshKeyResult extends AcquiaCloudResult {

  /**
   * Sets the data into this result instance.
   *
   * @param AcquiaCloudSshKeyInfo|mixed $data
   *   The data.
   */
  public function setData($data) {
    if (!$data instanceof AcquiaCloudSshKeyInfo) {
      throw new \InvalidArgumentException('The $data parameter must be an instance of AcquiaCloudSshKeyInfo');
    }
    parent::setData($data);
  }

}
