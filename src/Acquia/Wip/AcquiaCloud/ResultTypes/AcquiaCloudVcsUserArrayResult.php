<?php

namespace Acquia\Wip\AcquiaCloud\ResultTypes;

use Acquia\Wip\AcquiaCloud\AcquiaCloudResult;
use Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudVcsUserInfo;

/**
 * Provides information about a set of VCS users.
 */
class AcquiaCloudVcsUserArrayResult extends AcquiaCloudResult {

  /**
   * Sets the data into this result instance.
   *
   * @param AcquiaCloudVcsUserInfo[] $data
   *   The data.
   */
  public function setData($data) {
    if (!is_array($data)) {
      throw new \InvalidArgumentException('The data parameter must be an array.');
    }
    foreach ($data as $value) {
      if (!$value instanceof AcquiaCloudVcsUserInfo) {
        throw new \InvalidArgumentException(
          'The data parameter must only contain instances of AcquiaCloudVcsUserInfo.'
        );
      }
    }
    parent::setData($data);
  }

}
