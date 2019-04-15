<?php

namespace Acquia\WipService\Metrics;

use Acquia\WipService\App;
use Acquia\Wip\Metrics\BasicMetricsRelay;

/**
 * Sends data to the HostedGraphite service via their statsd collector.
 */
class HostedGraphiteMetricsRelay extends BasicMetricsRelay {

  /**
   * The statsd client that connects to the remote server.
   *
   * @var \Domnikl\Statsd\Client
   */
  private $statsd;

  /**
   * The connection maintained by the statsd client.
   *
   * @var \Domnikl\Statsd\Connection
   */
  private $connection;

  /**
   * Creates a new instance of HostedGraphiteMetricsRelay.
   *
   * @param array $config
   *   An array of configuration data to override the defaults.
   */
  public function __construct(array $config = NULL) {
    $defaults = array(
      'api_key' => 'unknown',
      'namespace' => 'test',
      'machine_namespace' => TRUE,
      'host' => 'localhost',
      'port' => 8125,
      'enabled' => FALSE,
      'batching' => TRUE,
    );

    // If the runtime config was not specified, assume it is specified in the
    // application config.
    if ($config == NULL) {
      $config = App::getApp()['services.metrics'];
    }
    $config = array_merge($defaults, $config);

    // If the service is enabled, send data over the network, otherwise keep
    // all data in memory for verification purposes.
    if ($config['enabled']) {
      $connection = new \Domnikl\Statsd\Connection\UdpSocket(
        $config['host'],
        $config['port'],
        0,
        TRUE
      );
    } else {
      $connection = new \Domnikl\Statsd\Connection\InMemory();
    }

    // In order to delineate between service machines and container machines
    // we need to namespace them seperately.
    $machine = 'service.mock_host';
    if ($config['machine_namespace']) {
      if (getenv('WIP_CONTAINERIZED')) {
        $machine = 'container.common';
      } else {
        $hostname = preg_replace('/[^a-zA-Z0-9]/', '_', gethostname());
        $machine = sprintf('service.%s', $hostname);
      }
    }
    $namespace = sprintf(
      '%s.%s.%s',
      $config['api_key'],
      $config['namespace'],
      $machine
    );

    $this->connection = $connection;
    $this->statsd = new \Domnikl\Statsd\Client($connection, $namespace);
  }

  /**
   * {@inheritdoc}
   */
  public function increment($namespace, $sample_rate = 1.0) {
    parent::increment($namespace, $sample_rate);
    $this->statsd->increment($namespace, $sample_rate);
  }

  /**
   * {@inheritdoc}
   */
  public function decrement($namespace, $sample_rate = 1.0) {
    parent::decrement($namespace, $sample_rate);
    $this->statsd->decrement($namespace, $sample_rate);
  }

  /**
   * {@inheritdoc}
   */
  public function count($namespace, $value, $sample_rate = 1.0) {
    parent::count($namespace, $value, $sample_rate);
    $this->statsd->count($namespace, $value, $sample_rate);
  }

  /**
   * {@inheritdoc}
   */
  public function timing($namespace, $value, $sample_rate = 1.0) {
    parent::timing($namespace, $value, $sample_rate = 1.0);
    $this->statsd->timing($namespace, $value, $sample_rate);
  }

  /**
   * {@inheritdoc}
   */
  public function startTiming($namespace) {
    parent::startTiming($namespace);
    $this->statsd->startTiming($namespace);
  }

  /**
   * {@inheritdoc}
   */
  public function endTiming($namespace, $sample_rate = 1.0) {
    parent::endTiming($namespace, $sample_rate = 1.0);
    $this->statsd->endTiming($namespace, $sample_rate);
  }

  /**
   * {@inheritdoc}
   */
  public function startMemoryProfile($namespace) {
    parent::startMemoryProfile($namespace);
    $this->statsd->startMemoryProfile($namespace);
  }

  /**
   * {@inheritdoc}
   */
  public function endMemoryProfile($namespace, $sample_rate = 1.0) {
    parent::endMemoryProfile($namespace, $sample_rate);
    $this->statsd->endMemoryProfile($namespace, $sample_rate);
  }

  /**
   * {@inheritdoc}
   */
  public function gauge($namespace, $value) {
    parent::gauge($namespace, $value);
    $this->statsd->gauge($namespace, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function set($namespace, $value) {
    parent::set($namespace, $value);
    $this->statsd->set($namespace, $value);
  }

  /**
   * Gets the protected connection from the statsd object.
   *
   * The main purpose of retrieving the connection is for testing purposes.
   *
   * @return \Domnikl\Statsd\Connection.
   *   The connection object.
   */
  public function getConnection() {
    return $this->connection;
  }

  /**
   * {@inheritdoc}
   */
  public function getNamespace() {
    return $this->statsd->getNamespace();
  }

}
