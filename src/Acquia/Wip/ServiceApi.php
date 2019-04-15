<?php

namespace Acquia\Wip;

use Acquia\Wip\Storage\RuntimeDataEntryStoreInterface;

/**
 * Contains methods common to all services.
 */
class ServiceApi implements ServiceApiInterface {

  /**
   * {@inheritdoc}
   */
  public function recordProcessRuntime($name, $client_id, $runtime) {
    // @todo - there is currently no production storage implementation for the
    // RuntimeDataEntryStoreInterface. See MS-1122. Enable this code once there
    // is a proper implementation.
    /** @var RuntimeDataEntryStoreInterface $data_store */
    // $data_store = WipFactory::getObject('acquia.wip.dataentrystore');
    // $data_store->save($name, $client_id, abs($runtime));
  }

  /**
   * {@inheritdoc}
   */
  public function hasProcessRunTooLong($name, $client_id, $start_time) {
    $result = FALSE;
    $omit_tasks = $this->getProcessNamesToOmit();
    if (!in_array($name, $omit_tasks)) {
      try {
        // The maximum runtime should give a little slack in case the system is
        // under load and not responding optimally.
        $maximum_runtime_offset = WipFactory::getObject('$acquia.wip.runtime.maxoffset');
        $max_runtime = $maximum_runtime_offset + RuntimeDataEntry::getMaximumExpectedRuntime($name, $client_id);
        if (time() - $start_time > $max_runtime) {
          // The process has been running too long.
          $result = TRUE;
        }
      } catch (\Exception $e) {
        // There is not enough data to calculate the maximum runtime.
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessNamesToOmit() {
    $result = array();
    try {
      $omit_tasks = trim(WipFactory::getObject('$acquia.wip.runtime.omittasks'));
      if (!empty($omit_tasks)) {
        // Separate the elements in the set.
        $omit_tasks = array_map('trim', explode(',', $omit_tasks));
        if (count($omit_tasks) > 0) {
          $result = $omit_tasks;
        }
      }
    } catch (\Exception $e) {
      // No tasks to be omitted.
    }
    return $result;
  }

}
