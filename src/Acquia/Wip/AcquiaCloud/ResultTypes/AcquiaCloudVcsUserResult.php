<?php

namespace Acquia\Wip\AcquiaCloud\ResultTypes;

use Acquia\Wip\AcquiaCloud\AcquiaCloudResult;
use Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudVcsUserInfo;

/**
 * Provides information about a particular VCS user.
 */
class AcquiaCloudVcsUserResult extends AcquiaCloudResult {

  /**
   * Sets the data into this result instance.
   *
   * @param AcquiaCloudVcsUserInfo|mixed $data
   *   The data.
   */
  public function setData($data) {
    if (!$data instanceof AcquiaCloudVcsUserInfo) {
      throw new \InvalidArgumentException('The data parameter must be an instance of AcquiaCloudVcsUserInfo.');
    }
    parent::setData($data);
  }

}
