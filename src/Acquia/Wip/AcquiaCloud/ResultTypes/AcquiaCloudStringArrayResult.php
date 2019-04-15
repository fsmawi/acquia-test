<?php

namespace Acquia\Wip\AcquiaCloud\ResultTypes;

use Acquia\Wip\AcquiaCloud\AcquiaCloudResult;

/**
 * A result containing a set of strings.
 *
 * This is used to hold a set of server names, for example.
 */
class AcquiaCloudStringArrayResult extends AcquiaCloudResult {

  /**
   * Sets the string array data into this result instance.
   *
   * @param string[] $data
   *   The data.
   */
  public function setData($data) {
    if (!is_array($data)) {
      throw new \InvalidArgumentException('The data parameter must be an array.');
    }
    foreach ($data as $element) {
      if (!is_string($element)) {
        throw new \InvalidArgumentException('The data parameter must be an array of string instances.');
      }
    }
    parent::setData($data);
  }

}
