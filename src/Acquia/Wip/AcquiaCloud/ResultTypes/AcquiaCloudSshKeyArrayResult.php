<?php

namespace Acquia\Wip\AcquiaCloud\ResultTypes;

use Acquia\Wip\AcquiaCloud\AcquiaCloudResult;
use Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudSshKeyInfo;

/**
 * Provides information about a set of ssh keys.
 */
class AcquiaCloudSshKeyArrayResult extends AcquiaCloudResult {

  /**
   * Sets the ssh key array data into this result instance.
   *
   * @param AcquiaCloudSshKeyInfo[] $data
   *   The data.
   */
  public function setData($data) {
    if (!is_array($data)) {
      throw new \InvalidArgumentException('The data parameter must be an array.');
    }
    foreach ($data as $element) {
      if (!($element instanceof AcquiaCloudSshKeyInfo)) {
        throw new \InvalidArgumentException('The data parameter must be an array of AcquiaCloudSshKeyInfo instances.');
      }
    }
    parent::setData($data);
  }

}
