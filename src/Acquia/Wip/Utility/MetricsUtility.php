<?php

namespace Acquia\Wip\Utility;

use Acquia\WipService\App;
use Acquia\WipService\Metrics\HostedGraphiteMetricsRelay;
use Acquia\Wip\DependencyManager;
use GuzzleHttp\Client;

/**
 * Contains some useful metrics functions.
 */
class MetricsUtility {

  /**
   * The array of transition states whose time to be tracked.
   *
   * @var array
   */
  const TRANSITION_STATES_TO_BE_TIME_TRACKED = [
    'containerWipStart',
    'containerWipInvoke',
    'containerWipContainerLaunched',
    'containerWipSetupEnvironment',
    'containerWipCheckSsh',
    'containerWipCheckResources',
    'ensureBuildUser',
    'ensureVcsUri',
    'establishWorkspaceSshKey',
    'extractSshKeyId',
    'createGitWrapper',
    'writeUserEnvironmentVars',
    'reportPipelinesMetaData',
  ];

  /**
   * The DependencyManager instance.
   *
   * @var DependencyManager
   */
  private $dependencyManager;

  /**
   * The http client.
   *
   * @var Client
   */
  private $client;

  /**
   * The metric relay.
   *
   * @var HostedGraphiteMetricsRelay
   */
  private $relay;

  /**
   * Holds all the timings that have not yet been completed.
   *
   * @var array
   */
  private $timings = array();

  /**
   * Initializes a new instance of BasicWip.
   */
  public function __construct() {
    $this->dependencyManager = new DependencyManager();
    $dependencies = $this->getDependencies();
    if (!empty($dependencies)) {
      $this->dependencyManager->addDependencies($dependencies);
    }

    $this->relay = $this->dependencyManager->getDependency('acquia.wip.metrics.relay');
  }

  /**
   * Sets the http client.
   *
   * @param Client $client
   *    The http client.
   */
  public function setClient(Client $client) {
    $this->client = $client;
  }

  /**
   * Gets the http client.
   *
   * @return Client
   *   The http client.
   */
  public function getClient() {
    return !is_null($this->client) ? $this->client : new Client();
  }

  /**
   * Gets the known dependencies.
   */
  public function getDependencies() {
    return array(
      'acquia.wip.metrics.relay' => 'Acquia\Wip\Metrics\MetricsRelayInterface',
    );
  }

  /**
   * Sets the metric relay.
   *
   * @param HostedGraphiteMetricsRelay $relay
   *    The metric relay.
   */
  public function setRelay(HostedGraphiteMetricsRelay $relay) {
    $this->relay = $relay;
  }

  /**
   * Gets the metric relay.
   *
   * @return HostedGraphiteMetricsRelay
   *    The metric relay.
   */
  public function getRelay() {
    return $this->relay;
  }

  /**
   * Get the timing for a specfic key.
   *
   * @param string $key
   *    The metric key name.
   *
   * @return float|null
   *    The start time of the metric key, returns null if the key doesn't exist.
   */
  public function getTiming($key) {
    return isset($this->timings[$key]) ? $this->timings[$key] : NULL;
  }

  /**
   * Get all the timings.
   *
   * @return array
   *    The start times of all keys, returns null if the key doesn't exist.
   */
  public function getTimings() {
    return $this->timings;
  }

  /**
   * Starts the timing for a key.
   *
   * @param string $key
   *    The metric key name.
   */
  public function startTiming($key) {
    $this->timings[$key] = gettimeofday(TRUE);
  }

  /**
   * Ends the timing for a key and sends it to relay.
   *
   * @param string $key
   *    The metric key name.
   * @param int $sampleRate
   *    The sample rate.
   *
   * @return float|null
   *    Returns time elapsed, returns null if the metric key doesn't exist.
   */
  public function endTiming($key, $sampleRate = 1.0) {
    $end = gettimeofday(TRUE);
    try {
      if (isset($this->timings[$key])) {
        $timing = ($end - $this->timings[$key]) * 1000;
        $this->relay->timing($key, $timing, $sampleRate);
        unset($this->timings[$key]);

        return $timing;
      }
    } catch (\Exception $e) {
    }

    return NULL;
  }

  /**
   * Sends metric to signalFx.
   *
   * @param string $type
   *   The metric type.
   * @param string $name
   *   The metric name.
   * @param string $value
   *   The metric value.
   */
  public function sendMetric($type, $name, $value) {
    $this->relay->$type($name, $value);
  }

  /**
   * Sends month-to-date system errors metric.
   *
   * This methode calculates the month-to-date system errors using the signalfx api
   * and send the total as gauge metric.
   *
   * @param bool $systemError
   *   The system error flag.
   */
  public function sendMtdSystemFailure($systemError = TRUE) {
    $signalfx_token = App::getApp()['services.metrics']['signalfx_token'];
    $mts = strtotime('first day of ' . date('F Y'));
    $mts *= 1000;
    $ts = time(TRUE);
    $ts *= 1000;

    $system_error = sprintf("derive.%s.wip.system.job_status.system_error", $this->relay->getNamespace());

    try {
      $response = $this->getClient()->get('https://api.signalfx.com/v1/timeserieswindow', [
        'query' => [
          "query" => "sf_metric:\"$system_error\"",
          "startMs" => $mts,
          "endMs" => $ts,
          "resolution" => 60000,
        ],
        'headers' => [
          "Content-Type" => "application/json",
          "X-SF-TOKEN" => $signalfx_token,
        ],
      ]);

      $body = $response->getBody();
      $output = json_decode($body, TRUE);

      /*
      An example of output.
      {
      "data" : {
      "DLxiZbGAcaM" : [ [ 1507636800000, 1 ], [ 1507647600000, 2 ], [ 1508148000000, 1 ] ],
      "DLgBaJOAYMo" : [ [ 1507345200000, 1 ], [ 1507431600000, 1 ] ],
      },
      "errors" : [ ]
      }
       */
      $total = 0;
      foreach ($output['data'] as $value) {
        $total += array_reduce($value, function ($acc, $u) {
            $acc += $u[1];
            return $acc;
        }, 0);
      }

      // As we need to wait (about 5s) after sending a metric to get the latest value,
      // we need to increment the total to reflect the real value.
      if ($systemError) {
        $total++;
      }

      $this->sendMetric('gauge', 'wip.system.job_status.mtd_system_error', $total);
    } catch (\Exception $e) {
    }
  }

}
