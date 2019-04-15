<?php

namespace Acquia\WipService\Metrics;

use Acquia\Cromwell\Client;
use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\DependencyManager;
use Acquia\Wip\Metrics\MetricsRelayInterface;
use CromwellThrift\LatencyMetric;

/**
 * Defines an integration client for the Cromwell service.
 */
class CromwellIntegrationClient extends Client implements DependencyManagedInterface {

  /**
   * The dependency manager.
   *
   * @var DependencyManager
   */
  private $dependencyManager;

  /**
   * The pattern for the metrics namespace.
   *
   * @var string
   */
  private static $displayNamePattern = 'rest_api.%s.response_time';

  /**
   * {@inheritdoc}
   */
  public function __construct($product, $env, $cromwell_host = '127.0.0.1', $options = []) {
    parent::__construct($product, $env, $cromwell_host, $options);
    $this->dependencyManager = new DependencyManager();
    $this->dependencyManager->addDependencies($this->getDependencies());
  }

  /**
   * Gets the dependency manager for the class.
   *
   * @return DependencyManager
   *   The dependency manager.
   */
  public function getDependencyManager() {
    return $this->dependencyManager;
  }

  /**
   * Gets the pattern for metrics' display names.
   *
   * @return string
   *   The pattern for metrics' display names.
   */
  public static function getDisplayNamePattern() {
    return self::$displayNamePattern;
  }

  /**
   * Flushes the backlog.
   *
   * Unlike in the parent Client class, this method sends data to both Cromwell
   * and the metrics relay.
   */
  public function flush() {
    if (!empty($this->backlog)) {
      $this->sendMetrics($this->backlog);
      $this->sendToMetricsRelay($this->backlog);
      $this->backlog = [];
    }
  }

  /**
   * Sends the given metrics to the metrics relay.
   *
   * @param LatencyMetric[] $metrics
   *   The array of metrics to send.
   */
  private function sendToMetricsRelay($metrics) {
    if (empty($metrics)) {
      return;
    }

    foreach ($metrics as $metric) {
      $name = $this->getDisplayMetricName($metric->metric);
      $time = $metric->value;

      $this->getMetricsRelay()->timing($name, $time);
    }
  }

  /**
   * Retrieves the metrics relay.
   *
   * @return MetricsRelayInterface
   *   The metrics relay.
   */
  protected function getMetricsRelay() {
    return $this->dependencyManager->getDependency('acquia.wip.metrics.relay');
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.metrics.relay' => 'Acquia\Wip\Metrics\MetricsRelayInterface',
    );
  }

  /**
   * Returns a display name based on the given metric name.
   *
   * If the given metric name is empty, return it. Otherwise, replace all '/'
   * with '.' and add more namespaces to help organize the data.
   *
   * @param string $name
   *   The name of the metric as returned by the RequestTimer class.
   *
   * @return string
   *   The display name.
   */
  public function getDisplayMetricName($name) {
    if (empty($name)) {
      return '';
    }

    // The request is for docroot (/). Change '/' to '/docroot'.
    if (substr($name, -2) == ' /') {
      $name .= 'docroot';
    }
    // Replace all forward slashes with a dot for displaying in Hosted Graphite.
    $name = preg_replace('#(\s)*/#', '.', $name);
    // Strip trailing periods.
    if (substr($name, -1) == '.') {
      $name = substr($name, 0, strlen($name) - 1);
    }
    $name = sprintf(self::$displayNamePattern, $name);

    return $name;
  }

}
