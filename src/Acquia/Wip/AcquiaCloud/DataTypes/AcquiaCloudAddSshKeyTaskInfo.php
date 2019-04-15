<?php

namespace Acquia\Wip\AcquiaCloud\DataTypes;

/**
 * Contains task information specific to adding an SSH key.
 *
 * This task information includes the ID of the SSH key that was added.  This
 * key ID is required for removing the SSH key.
 */
class AcquiaCloudAddSshKeyTaskInfo extends AcquiaCloudTaskInfo {

  /**
   * Extracts the SSH key ID from the result.
   *
   * @return int|null
   *   The key ID if it was included in the result; NULL otherwise.
   */
  public function getKeyId() {
    $result = NULL;
    $data = $this->getResult();
    if (is_array($data) && array_key_exists('sshkeyid', $data) &&
      is_numeric($data['sshkeyid'])
    ) {
      $result = intval($data['sshkeyid']);
    }
    return $result;
  }

}
