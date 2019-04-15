<?php

namespace Acquia\Wip\Storage;

use Acquia\Wip\RuntimeDataEntryInterface;

/**
 * The RuntimeDataEntryStoreInterface is used to store runtime data.
 */
interface RuntimeDataEntryStoreInterface {

  /**
   * Saves runtime data from the specified completed task.
   *
   * @param string $role_name
   *   The name of the data set.
   * @param string $customer_id
   *   An ID that identifies the customer.  This is in the table because the
   *   runtime distribution will likely vary significantly from customer to
   *   customer because they have different hardware and different
   *   configurations.  Generally the customer's site group will be used here.
   * @param int $run_time
   *   The run time of the new data entry measured in seconds.
   *
   * @return bool
   *   TRUE if the entry was saved successfully; FALSE otherwise.
   */
  public function save($role_name, $customer_id, $run_time);

  /**
   * Loads runtime data for the specified name.
   *
   * @param string $name
   *   The name of the data to retrieve.
   * @param string $customer_id
   *   The ID that identifies the customer.
   *
   * @return RuntimeDataEntryInterface
   *   The runtime data for the specified name.
   */
  public function load($name, $customer_id);

  /**
   * Deletes runtime data for the specified name.
   *
   * @param string $name
   *   The name of the data to delete.
   * @param string $customer_id
   *   Optional. The ID that identifies a customer.  If not provided, all
   *   data with the specified name will be deleted.
   */
  public function delete($name, $customer_id = NULL);

}
