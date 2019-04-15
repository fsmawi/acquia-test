<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\WipService\Metrics\HostedGraphiteMetricsRelay;
use Acquia\Wip\Utility\MetricsUtility;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

/**
 * Missing summary.
 */
class MetricsUtilityTest extends \PHPUnit_Framework_TestCase {

  /**
   * Tests that sendMetric works as expected.
   */
  public function testSendMetric() {
    $config = ['namespace' => 'test'];
    $relay = $this->getMockBuilder('Acquia\WipService\Metrics\HostedGraphiteMetricsRelay')
      ->setConstructorArgs([$config])
      ->setMethods(['count'])
      ->getMock();

    $metric = new MetricsUtility();
    $metric->setRelay($relay);

    $relay->expects($this->once())
      ->method('count')
      ->with('metric.name', 1);

    $metric->sendMetric('count', 'metric.name', 1);
  }

  /**
   * Tests that sendMetric works as expected.
   */
  public function testEndTiming() {
    $config = ['namespace' => 'test'];
    $relay = $this->getMockBuilder('Acquia\WipService\Metrics\HostedGraphiteMetricsRelay')
      ->setConstructorArgs([$config])
      ->setMethods(['timing'])
      ->getMock();

    $metric = new MetricsUtility();
    $metric->setRelay($relay);

    $relay->expects($this->once())
      ->method('timing');

    $metric->startTiming('metric.name');
    $this->assertGreaterThan(0, $metric->getTiming('metric.name'));
    $time_diff = $metric->endTiming('metric.name');
    $this->assertGreaterThan(0, $time_diff);
    $this->assertEquals($metric->getTiming('metric.name'), NULL);
  }

  /**
   * Tests that SendMtdSystemFailure works as expected.
   */
  public function testSendMtdSystemFailure() {
    $response_body = [
      'data' => [
        'DLxiZbGAcaM' => [
          [1507636800000, 1],
          [1507647600000, 2],
          [1508148000000, 1],
        ],
        'DLgBaJOAYMo' => [
          [1507345200000, 1],
          [1507431600000, 1],
        ],
      ],
      'errors' => [],
    ];

    $mock = new MockHandler([
      new Response(200, [], json_encode($response_body)),
    ]);

    $handler = HandlerStack::create($mock);
    $client = new Client(['handler' => $handler]);

    $metric = $this->getMockBuilder('Acquia\Wip\Utility\MetricsUtility')
      ->setMethods(['sendMetric'])
      ->getMock();
    $metric->setClient($client);

    $metric->expects($this->once())
      ->method('sendMetric')
      ->with('gauge', 'wip.system.job_status.mtd_system_error', 7);

    $metric->sendMtdSystemFailure();
  }

  /**
   * Tests the sendMtdSystemFailure methode without incremeting metric.
   */
  public function testSendMtdSystemFailureMetricWithoutIncrementingMetric() {
    $response_body = [
      'data' => [
        'DLxiZbGAcaM' => [
          [1507636800000, 1],
          [1507647600000, 2],
          [1508148000000, 1],
        ],
        'DLgBaJOAYMo' => [
          [1507345200000, 1],
          [1507431600000, 1],
        ],
      ],
      'errors' => [],
    ];

    $mock = new MockHandler([
      new Response(200, [], json_encode($response_body)),
    ]);

    $handler = HandlerStack::create($mock);
    $client = new Client(['handler' => $handler]);

    $metric = $this->getMockBuilder('Acquia\Wip\Utility\MetricsUtility')
      ->setMethods(['sendMetric'])
      ->getMock();
    $metric->setClient($client);

    $metric->expects($this->once())
      ->method('sendMetric')
      ->with('gauge', 'wip.system.job_status.mtd_system_error', 6);

    $metric->sendMtdSystemFailure(FALSE);
  }

}
