<?php

namespace Acquia\Wip\Metrics;

/**
 * Implements the MetricsRelayInterface for testing but does nothing.
 */
class BasicMetricsRelay implements MetricsRelayInterface {

  /**
   * Validates a namespace argument.
   *
   * @param mixed $namespace
   *   The metric namespace to be validated.
   *
   * @throws \InvalidArgumentException
   *   Strict validation for method arguments.
   */
  protected function validateNamespace($namespace) {
    if (empty($namespace) || !is_string($namespace)) {
      throw new \InvalidArgumentException(sprintf(
        'A metric namespace must be a non-empty string. "%s" is invalid.',
        var_export($namespace, TRUE)
      ));
    }
  }

  /**
   * Validates a sample rate argument.
   *
   * @param mixed $sample_rate
   *   The metric sample rate to be validated.
   *
   * @throws \InvalidArgumentException
   *   Strict validation for method arguments.
   */
  protected function validateSampleRate($sample_rate) {
    if (!is_float($sample_rate) || $sample_rate < 0 || $sample_rate > 1.0) {
      throw new \InvalidArgumentException(sprintf(
        'A metric sample rate must be a float between 0 and 1. "%s" is invalid.',
        var_export($sample_rate, TRUE)
      ));
    }
  }

  /**
   * Validates a numeric argument.
   *
   * @param mixed $value
   *   The metric value to be validated.
   *
   * @throws \InvalidArgumentException
   *   Strict validation for method arguments.
   */
  protected function validateValueNumeric($value) {
    if (!is_numeric($value)) {
      throw new \InvalidArgumentException(sprintf(
        'A metric value must be numeric. "%s" is invalid.',
        var_export($value, TRUE)
      ));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function increment($namespace, $sample_rate = 1.0) {
    $this->validateNamespace($namespace);
    $this->validateSampleRate($sample_rate);
  }

  /**
   * {@inheritdoc}
   */
  public function decrement($namespace, $sample_rate = 1.0) {
    $this->validateNamespace($namespace);
    $this->validateSampleRate($sample_rate);
  }

  /**
   * {@inheritdoc}
   */
  public function count($namespace, $value, $sample_rate = 1.0) {
    $this->validateNamespace($namespace);
    $this->validateValueNumeric($value);
    $this->validateSampleRate($sample_rate);
  }

  /**
   * {@inheritdoc}
   */
  public function timing($namespace, $value, $sample_rate = 1.0) {
    $this->validateNamespace($namespace);
    $this->validateValueNumeric($value);
    $this->validateSampleRate($sample_rate);
  }

  /**
   * {@inheritdoc}
   */
  public function startTiming($namespace) {
    $this->validateNamespace($namespace);
  }

  /**
   * {@inheritdoc}
   */
  public function endTiming($namespace, $sample_rate = 1.0) {
    $this->validateNamespace($namespace);
    $this->validateSampleRate($sample_rate);
  }

  /**
   * {@inheritdoc}
   */
  public function startMemoryProfile($namespace) {
    $this->validateNamespace($namespace);
  }

  /**
   * {@inheritdoc}
   */
  public function endMemoryProfile($namespace, $sample_rate = 1.0) {
    $this->validateNamespace($namespace);
    $this->validateSampleRate($sample_rate);
  }

  /**
   * {@inheritdoc}
   */
  public function gauge($namespace, $value) {
    $this->validateNamespace($namespace);
    $this->validateValueNumeric($value);
  }

  /**
   * {@inheritdoc}
   */
  public function set($namespace, $value) {
    $this->validateNamespace($namespace);
    $this->validateValueNumeric($value);
  }

  /**
   * {@inheritdoc}
   */
  public function getNamespace() {
    return '';
  }

}
