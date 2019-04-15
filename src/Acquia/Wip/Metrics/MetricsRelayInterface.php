<?php

namespace Acquia\Wip\Metrics;

/**
 * Describes the interface for collecting operational metrics.
 */
interface MetricsRelayInterface {

  /**
   * Increments a counter value.
   *
   * @param string $namespace
   *   The namespace of the metric.
   * @param float $sample_rate
   *   The sample rate of the metric.
   *
   * @throws \InvalidArgumentException
   *   When the provided arguments are invalid.
   */
  public function increment($namespace, $sample_rate = 1.0);

  /**
   * Decrements a counter value.
   *
   * @param string $namespace
   *   The namespace of the metric.
   * @param float $sample_rate
   *   The sample rate of the metric.
   *
   * @throws \InvalidArgumentException
   *   When the provided arguments are invalid.
   */
  public function decrement($namespace, $sample_rate = 1.0);

  /**
   * Sets a counter value.
   *
   * @param string $namespace
   *   The namespace of the metric.
   * @param int $value
   *   The value of the metric (int or float).
   * @param float $sample_rate
   *   The sample rate of the metric.
   *
   * @throws \InvalidArgumentException
   *   When the provided arguments are invalid.
   */
  public function count($namespace, $value, $sample_rate = 1.0);

  /**
   * Sets a timer value.
   *
   * @param string $namespace
   *   The namespace of the metric.
   * @param int $value
   *   The value of the metric (int or float).
   * @param float $sample_rate
   *   The sample rate of the metric.
   *
   * @throws \InvalidArgumentException
   *   When the provided arguments are invalid.
   */
  public function timing($namespace, $value, $sample_rate = 1.0);

  /**
   * Starts a timer operation.
   *
   * @param string $namespace
   *   The namespace of the metric.
   *
   * @throws \InvalidArgumentException
   *   When the provided arguments are invalid.
   */
  public function startTiming($namespace);

  /**
   * Ends and sends a previously started timer.
   *
   * @param string $namespace
   *   The namespace of the metric.
   * @param float $sample_rate
   *   The sample rate of the metric.
   *
   * @throws \InvalidArgumentException
   *   When the provided arguments are invalid.
   */
  public function endTiming($namespace, $sample_rate = 1.0);

  /**
   * Starts a memory profile operation.
   *
   * @param string $namespace
   *   The namespace of the metric.
   *
   * @throws \InvalidArgumentException
   *   When the provided arguments are invalid.
   */
  public function startMemoryProfile($namespace);

  /**
   * Ends a previously started memory profile.
   *
   * @param string $namespace
   *   The namespace of the metric.
   * @param float $sample_rate
   *   The sample rate of the metric.
   *
   * @throws \InvalidArgumentException
   *   When the provided arguments are invalid.
   */
  public function endMemoryProfile($namespace, $sample_rate = 1.0);

  /**
   * Sets a gauge value.
   *
   * @param string $namespace
   *   The namespace of the metric.
   * @param float $value
   *   The value of the metric (int or float).
   *
   * @throws \InvalidArgumentException
   *   When the provided arguments are invalid.
   */
  public function gauge($namespace, $value);

  /**
   * Sets a unique value in a set.
   *
   * @param string $namespace
   *   The namespace of the metric.
   * @param float $value
   *   The value of the metric (int or float).
   *
   * @throws \InvalidArgumentException
   *   When the provided arguments are invalid.
   */
  public function set($namespace, $value);

  /**
   * Gets the key namespace.
   *
   * @return string
   *   The namespace.
   */
  public function getNamespace();

}
