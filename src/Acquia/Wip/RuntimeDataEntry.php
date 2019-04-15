<?php

namespace Acquia\Wip;

use Acquia\Wip\Storage\RuntimeDataEntryStoreInterface;

/**
 * Describes runtime data in a way that statistical analysis can be performed.
 */
class RuntimeDataEntry implements RuntimeDataEntryInterface {

  /**
   * Indicates the number of data elements that would be statistically valid.
   */
  const DATA_COUNT_THRESHOLD = 200;

  /**
   * Indicates the run time which we consider a task to have failed.
   */
  const SIGMA_THRESHOLD = 3;

  /**
   * The multiplier used to determine how long to wait between queries.
   */
  const WAIT_THRESHOLD = 0.33;

  /**
   * The name associated with this data entry.
   *
   * @var string
   */
  private $name = NULL;

  /**
   * The number of data elements in the data set.
   *
   * @var int
   */
  private $count = 0;

  /**
   * The average of all data in the data set.
   *
   * @var float
   */
  private $average = 0;

  /**
   * The sum of the square of all data elements in the data set.
   *
   * @var float
   */
  private $sumOfDataSquared = 0;

  /**
   * Initializes this instance.
   *
   * @param string $name
   *   The name of the data.
   * @param int $count
   *   The count of data elements in the set.
   * @param float $average
   *   The average of the data elements in the set.
   * @param int $sum_of_data_squared
   *   The sum of the square of the data elements.
   */
  public function initialize($name, $count, $average, $sum_of_data_squared) {
    $this->setName($name);
    $this->setCount($count);
    $this->setAverage($average);
    $this->setSumOfTheDataSquared($sum_of_data_squared);
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Sets the name of the data .
   *
   * @param string $name
   *   The data name.
   */
  protected function setName($name) {
    if (!is_string($name) || empty($name)) {
      throw new \InvalidArgumentException('The name parameter must be a non-empty string.');
    }
    $this->name = $name;
  }

  /**
   * {@inheritdoc}
   */
  public function getCount() {
    return $this->count;
  }

  /**
   * Sets the count of all data elements in the data set.
   *
   * @param int $count
   *   The count of the data elements.
   */
  protected function setCount($count) {
    if (!is_int($count) || $count < 0) {
      throw new \InvalidArgumentException('The count parameter must be a positive integer.');
    }
    $this->count = $count;
  }

  /**
   * {@inheritdoc}
   */
  public function getAverage() {
    return $this->average;
  }

  /**
   * Sets the average of the data items in the set.
   *
   * @param float $average
   *   The data average.
   */
  protected function setAverage($average) {
    if (!is_float($average) || $average < 0) {
      throw new \InvalidArgumentException('The average parameter must be a positive float value.');
    }
    $this->average = $average;
  }

  /**
   * {@inheritdoc}
   */
  public function getSumOfTheDataSquared() {
    return $this->sumOfDataSquared;
  }

  /**
   * Sets the sum of the square of all of the data in the set.
   *
   * @param int $sum_of_data_squared
   *   The sum of the square of each data element.
   */
  protected function setSumOfTheDataSquared($sum_of_data_squared) {
    if (!is_int($sum_of_data_squared) || $sum_of_data_squared < 0) {
      throw new \InvalidArgumentException('The sum_of_data_squared parameter must be a non-negative integer.');
    }
    $this->sumOfDataSquared = $sum_of_data_squared;
  }

  /**
   * {@inheritdoc}
   */
  public static function getMaximumExpectedRuntime($name, $customer_id) {
    $result = 30;
    $data = self::loadData($name, $customer_id);
    if (!empty($data) && $data->getCount() >= self::DATA_COUNT_THRESHOLD) {
      $sigma = self::getSigma($data);

      $max_runtime = ceil(self::SIGMA_THRESHOLD * $sigma);

      // A minimum runtime of 30 seconds.
      $result = max($result, $max_runtime);
    } else {
      // TODO: Create a custom exception for this.
      throw new \RuntimeException('Not enough data to estimate maximum time.');
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public static function getExpectedWaitTime($name, $customer_id) {
    $result = 30;
    $data = self::loadData($name, $customer_id);
    if (!empty($data)) {
      $wait_time = ceil(self::WAIT_THRESHOLD * $data->getAverage());
      $result = max($result, $wait_time);
    }
    return $result;
  }

  /**
   * Computes sigma, the standard deviation of runtime for the specified data.
   *
   * @param RuntimeDataEntryInterface $data
   *   The data.
   *
   * @return float
   *   Sigma.
   */
  protected static function getSigma(RuntimeDataEntryInterface $data) {
    $one_over_n_squared = 1.0 / pow($data->getCount(), 2);
    $sigma = $data->getAverage() +
      sqrt($one_over_n_squared * ($data->getSumOfTheDataSquared() - pow($data->getAverage(), 2)));
    return $sigma;
  }

  /**
   * Loads runtime data for the specified name.
   *
   * @param string $name
   *   The name associated with the runtime data.
   * @param string $customer_id
   *   The ID associated with the customer.
   *
   * @return RuntimeDataEntryInterface
   *   The runtime data.
   */
  protected static function loadData($name, $customer_id) {
    /** @var RuntimeDataEntryStoreInterface $runtime_data_entry_store */
    $runtime_data_entry_store = WipFactory::getObject('acquia.wip.dataentrystore');
    $data = $runtime_data_entry_store->load($name, $customer_id);
    return $data;
  }

}
