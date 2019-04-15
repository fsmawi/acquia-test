<?php

namespace Acquia\WipService\Metrics;

use CromwellThrift\LatencyMetric;

/**
 * Missing summary.
 */
class CromwellIntegrationClientTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @var CromwellIntegrationClient
   */
  private $client;

  /**
   * Missing summary.
   *
   * @var HostedGraphiteMetricsRelay
   */
  private $mockRelay;

  /**
   * Missing summary.
   *
   * @var LatencyMetric[]
   */
  private $backlog;

  /**
   * Gets configuration for a testing relay.
   *
   * @return array
   *   The configuration array.
   */
  private function getRelayConfig() {
    return array(
      'api_key' => 'unknown',
      'namespace' => 'test',
      'enabled' => FALSE,
      'machine_namespace' => FALSE,
    );
  }

  /**
   * Gets the backlog of LatencyMetric objects for testing.
   *
   * @return LatencyMetric[]
   *   The metric.
   */
  private function getBacklog() {
    $backlog = [];

    for ($i = 0; $i < 3; $i++) {
      $backlog[] = new LatencyMetric([
        "product" => $this->client->product,
        "host" => $this->client->host,
        // The second part consists of a request URI, which will always
        // start with a forward slash.
        "metric" => sprintf('metric /%d', $i),
        "environment" => $this->client->env,
        "value" => 100,
      ]);
    }

    return $backlog;
  }

  /**
   * Missing summary.
   */
  public function setUp() {
    $this->mockRelay = $this->getMock(
      'Acquia\WipService\Metrics\HostedGraphiteMetricsRelay',
      array('timing'),
      $this->getRelayConfig()
    );
    $this->client = new CromwellIntegrationClient('test_product', 'test_environment');
    $this->client->getDependencyManager()->swapDependency('acquia.wip.metrics.relay', $this->mockRelay);

    if (empty($this->backlog)) {
      $this->backlog = $this->getBacklog();
    }
  }

  /**
   * Tests that the flush method empties out the backlog after it is called.
   */
  public function testFlush() {
    $this->mockRelay->expects($this->atleastOnce())->method('timing');

    $this->client->backlog = $this->backlog;
    $this->client->flush();
    $this->assertEmpty($this->client->backlog);
  }

  /**
   * Tests that metrics in the backlog are sent to the metrics relay.
   */
  public function testSendToMetricsRelay() {
    $backlog_size = count($this->backlog);
    $this->mockRelay->expects($this->exactly($backlog_size))->method('timing');

    $this->client->backlog = $this->backlog;
    $this->client->flush();
  }

  /**
   * Tests that nothing is sent to the metrics relay if the backlog is empty.
   */
  public function testSendEmptyBacklogToMetricsRelay() {
    $this->mockRelay->expects($this->never())->method('timing');

    $this->client->backlog = array();
    $this->client->flush();
  }

  /**
   * Tests that the correct display names are generated.
   */
  public function testGetDisplayMetricName() {
    // Test empty name.
    $this->assertEmpty($this->client->getDisplayMetricName(''));

    // Test other names.
    $path1 = 'GET /request/uri';
    $path2 = 'POST /request';
    $path3 = 'GET /';
    $path4 = 'GET /logs/';

    // The expected path should not contain any forward slashes and should
    // contain periods instead.
    $expected_path1 = sprintf($this->client->getDisplayNamePattern(), 'GET.request.uri');
    $expected_path2 = sprintf($this->client->getDisplayNamePattern(), 'POST.request');
    // Request to '/' is a special case. The path is replaced with 'docroot'.
    $expected_path3 = sprintf($this->client->getDisplayNamePattern(), 'GET.docroot');
    // Display names for paths ending in '/' should not end in two periods.
    $expected_path4 = sprintf($this->client->getDisplayNamePattern(), 'GET.logs');

    $this->assertEquals($expected_path1, $this->client->getDisplayMetricName($path1));
    $this->assertEquals($expected_path2, $this->client->getDisplayMetricName($path2));
    $this->assertEquals($expected_path3, $this->client->getDisplayMetricName($path3));
    $this->assertEquals($expected_path4, $this->client->getDisplayMetricName($path4));
  }

}
