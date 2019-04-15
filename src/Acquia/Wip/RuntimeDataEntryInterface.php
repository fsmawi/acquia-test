<?php

namespace Acquia\Wip;

/**
 * Describes runtime data in a way that statistical analysis can be performed.
 */
interface RuntimeDataEntryInterface {

  /**
   * Gets the name of the data.
   *
   * For an Acquia Cloud task, this would be the queue name.
   *
   * @return string
   *   The data name.
   */
  public function getName();

  /**
   * Gets the number of data items in the set.
   *
   * @return int
   *   The number of data items.
   */
  public function getCount();

  /**
   * Gets the average of the data items in the set.
   *
   * @return float
   *   The data average.
   */
  public function getAverage();

  /**
   * Gets the sum of the square of all of the data in the set.
   *
   * @return int
   *   The sum of the square of each data element.
   */
  public function getSumOfTheDataSquared();

  /**
   * Indicates the maximum expected run time for a task.
   *
   * @param string $name
   *   The name associated with the type of asynchronous process.
   * @param string $customer_id
   *   The ID associated with the customer.
   *
   * @return int
   *   The number of seconds before a task should be considered failed.
   *
   * @throws \Exception
   *   If there is not enough data to indicate the maximum run time.
   */
  public static function getMaximumExpectedRuntime($name, $customer_id);

  /**
   * Indicates the period of time to wait between process queries.
   *
   * @param string $name
   *   The name associated with the type of asynchronous process.
   * @param string $customer_id
   *   The ID associated with the customer.
   *
   * @return int
   *   The number of seconds the monitoring process should wait between queries.
   */
  public static function getExpectedWaitTime($name, $customer_id);

}
