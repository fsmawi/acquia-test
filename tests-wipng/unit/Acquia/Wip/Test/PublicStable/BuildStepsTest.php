<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\Modules\NativeModule\BuildSteps;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipTaskConfig;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * Missing summary.
 */
class BuildStepsTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @var BuildSteps
   */
  private $wip = NULL;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    $this->wip = new BuildSteps();
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   *
   * @group Wip
   *
   * @expectedException \DomainException
   */
  public function testGetWorkIdWithNoConfigSet() {
    $this->wip->getWorkId();
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   *
   * @group Wip
   */
  public function testSetConfig() {
    $uri = 'uri';
    $build_path = 'build_path';
    $deploy_path = 'deploy_path';
    $config = $this->createWipTaskConfig($this->createTaskOptions($uri, $build_path, $deploy_path));
    $this->wip->setWipTaskConfig($config);

    $this->assertEquals($build_path, $this->wip->getBuildVcsPath());
    $this->assertEquals($deploy_path, $this->wip->getDeployVcsPath());
  }

  /**
   * Set up the config for the pipeline client.
   *
   * @param string $api_key
   *   The API key.
   * @param string $api_secret
   *   The API secret.
   *
   * @group Wip
   *
   * @return \stdClass
   *   The configuration.
   */
  private function getConfig($api_key, $api_secret) {
    $config = new \stdClass();
    $config->vcsUri = 'uri';
    $config->vcsPath = 'build_path';
    $config->deployVcsPath = 'deploy_path';
    $config->authToken = 'auth_token';
    $config->pipelineApiKey = $api_key;
    $config->pipelineApiSecret = $api_secret;
    $config->pipelineJobId = 'job_id';
    $config->pipelineEndpoint = 'https://example.com';
    $config->pipelineVerify = TRUE;

    return $config;
  }

  /**
   * Tests that the auth middleware is configured properly.
   *
   * @group BuildSteps
   *
   * @group Wip
   */
  public function testAuthMiddlewareConfig() {
    $api_key = 'api_key';
    $api_secret = 'api_secret';

    // Setup.
    $config = $this->getConfig($api_key, $api_secret);

    $task_config = $this->createWipTaskConfig($config);
    $this->wip->setWipTaskConfig($task_config);

    // Test the general properties.
    $this->assertEquals($config->authToken, $this->wip->getPipelineAuthToken());
    $this->assertEquals($config->pipelineEndpoint, $this->wip->getPipelineEndpoint());
    $this->assertEquals($api_key, $this->wip->getPipelineApiKey());
    $this->assertEquals($api_secret, $this->wip->getPipelineApiSecret());
    $this->assertEquals($config->pipelineJobId, $this->wip->getPipelineJobId());
    $this->assertEquals($config->pipelineVerify, $this->wip->getPipelineVerify());

    // Test the client config.
    $client = $this->wip->getPipelineClient();
    $this->assertEquals($config->pipelineEndpoint, $client->getConfig('base_uri'));
    $this->assertEquals($config->pipelineVerify, $client->getConfig('verify'));

    // Prove that the Acquia HMAC middleware is present with NO token.
    $handlers = $client->getConfig('handler')->__toString();
    $this->assertContains('AcquiaHmacMiddleware', $handlers);

    // Prove that the Acquia HMAC middleware is NOT present with a token.
    $client = $this->wip->getPipelineClient($config->authToken);
    $handlers = $client->getConfig('handler')->__toString();
    $this->assertNotContains('AcquiaHmacMiddleware', $handlers);
  }

  /**
   * Tests that the retry handler works for connection exceptions.
   *
   * @group BuildSteps
   *
   * @group Wip
   */
  public function testRetryConnection() {
    $mock = new MockHandler(
      [
        new ConnectException('Error', new Request('GET', 'test')),
        new Response(200),
      ]
    );

    $stack = HandlerStack::create($mock);
    $this->wip->addRetryHandler($stack);
    $client = new Client(['handler' => $stack]);
    $this->assertEquals(200, $client->request('GET', '/')->getStatusCode());
  }

  /**
   * Tests that the retry handler is added for pipeline client.
   *
   * @group BuildSteps
   *
   * @group Wip
   */
  public function testRetryHandlerAdded() {
    $api_key = 'api_key';
    $api_secret = 'api_secret';

    // Setup.
    $config = $this->getConfig($api_key, $api_secret);

    $task_config = $this->createWipTaskConfig($config);
    $this->wip->setWipTaskConfig($task_config);
    $client = $this->wip->getPipelineClient($config->authToken);
    $handlers = $client->getConfig('handler')->__toString();
    $this->assertContains('PipelineRetries', $handlers);

    $client = $this->wip->getPipelineClient();
    $handlers = $client->getConfig('handler')->__toString();
    $this->assertContains('PipelineRetries', $handlers);
  }

  /**
   * Tests that the retry handler works for http exceptions.
   *
   * @group BuildSteps
   *
   * @group Wip
   */
  public function testRetryHttp() {
    $mock = new MockHandler(
      [
        new Response(404),
        new Response(200),
      ]
    );

    $stack = HandlerStack::create($mock);
    $this->wip->addRetryHandler($stack);
    $client = new Client(['handler' => $stack]);
    $this->assertEquals(200, $client->request('GET', '/')->getStatusCode());
  }

  /**
   * Tests that the client exception is thrown.
   *
   * @group BuildSteps
   *
   * @group Wip
   *
   * @expectedException \GuzzleHttp\Exception\ClientException
   */
  public function testRetryLimitHttp() {
    $retry_limit = WipFactory::getInt('$acquia.pipeline.client.retires', 5);
    $responses = [];
    for ($count = 0; $count <= $retry_limit; $count++) {
      $responses[] = new Response(404);
    }
    $mock = new MockHandler($responses);

    $stack = HandlerStack::create($mock);
    $this->wip->addRetryHandler($stack);
    $client = new Client(['handler' => $stack]);
    $client->request('GET', '/')->getStatusCode();
  }

  /**
   * Tests that the connection exception is thrown.
   *
   * @group BuildSteps
   *
   * @group Wip
   *
   * @expectedException \GuzzleHttp\Exception\ConnectException
   */
  public function testRetryLimitConnection() {
    $retry_limit = WipFactory::getInt('$acquia.pipeline.client.retires', 5);
    $responses = [];
    for ($count = 0; $count <= $retry_limit; $count++) {
      $responses[] = new ConnectException('Error', new Request('GET', 'test'));
    }
    $mock = new MockHandler($responses);

    $stack = HandlerStack::create($mock);
    $this->wip->addRetryHandler($stack);
    $client = new Client(['handler' => $stack]);
    $client->request('GET', '/')->getStatusCode();
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   *
   * @group Wip
   */
  public function testSetConfigNoDeployPath() {
    $uri = 'uri';
    $build_path = 'build_path';
    $config = $this->createWipTaskConfig($this->createTaskOptions($uri, $build_path));
    $this->wip->setWipTaskConfig($config);

    $this->assertEquals($build_path, $this->wip->getBuildVcsPath());
    // No path is set by default.
    $this->assertEquals(NULL, $this->wip->getDeployVcsPath());
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   *
   * @group Wip
   */
  public function testGetWorkIdEqual() {
    $uri = 'uri';
    $build_path = 'build_path';
    $deploy_path = 'my-deploy-path';
    $config = $this->createWipTaskConfig($this->createTaskOptions($uri, $build_path, $deploy_path));
    $this->wip->setWipTaskConfig($config);
    $work_id = $this->wip->getWorkId();

    // Now make another Wip and compare work IDs.
    $new_wip = new BuildSteps();
    $new_wip->setWipTaskConfig($config);
    $this->assertEquals($work_id, $new_wip->getWorkId());
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   *
   * @group Wip
   */
  public function testWorkIdForDifferentUriIsUnequal() {
    $uri = 'uri';
    $build_path = 'build_path';
    $deploy_path = 'my-deploy-path';
    $config = $this->createWipTaskConfig($this->createTaskOptions($uri, $build_path, $deploy_path));
    $this->wip->setWipTaskConfig($config);
    $work_id = $this->wip->getWorkId();

    // Now make another Wip and compare work IDs.
    $new_uri = 'uri2';
    $new_wip = new BuildSteps();
    $new_wip->setWipTaskConfig(
      $this->createWipTaskConfig(
        $this->createTaskOptions($new_uri, $build_path, $deploy_path)
      )
    );
    $this->assertNotEquals($work_id, $new_wip->getWorkId());
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   *
   * @group Wip
   */
  public function testWorkIdForDifferentBuildPathIsUnequal() {
    $uri = 'uri';
    $build_path = 'build_path';
    $deploy_path = 'my-deploy-path';
    $config = $this->createWipTaskConfig($this->createTaskOptions($uri, $build_path, $deploy_path));
    $this->wip->setWipTaskConfig($config);
    $work_id = $this->wip->getWorkId();

    // Now make another Wip and compare work IDs, we generated the sha from deploy
    // path so change that as well.
    $new_build_path = 'build_path_2';
    $deploy_path = 'my-deploy-path-2';
    $new_wip = new BuildSteps();
    $new_wip->setWipTaskConfig(
      $this->createWipTaskConfig(
        $this->createTaskOptions($uri, $new_build_path, $deploy_path)
      )
    );
    $this->assertNotEquals($work_id, $new_wip->getWorkId());
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   *
   * @group Wip
   */
  public function testWorkIdForDifferentDeployPathIsUnequal() {
    $uri = 'uri';
    $build_path = 'build_path';
    $deploy_path = 'my-deploy-path';
    $config = $this->createWipTaskConfig($this->createTaskOptions($uri, $build_path, $deploy_path));
    $this->wip->setWipTaskConfig($config);
    $work_id = $this->wip->getWorkId();

    // Now make another Wip and compare work IDs.
    $new_deploy_path = 'deploy_path';
    $new_wip = new BuildSteps();
    $options = $this->createTaskOptions($uri, $build_path, $new_deploy_path);
    $config = $this->createWipTaskConfig($options);
    $new_wip->setWipTaskConfig($config);
    $this->assertNotEquals($work_id, $new_wip->getWorkId());
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   *
   * @group Wip
   */
  public function testWorkIdDoesNotChange() {
    $uri = 'uri';
    $build_path = 'build_path';
    $deploy_path = 'deploy_path';
    $config = $this->createWipTaskConfig($this->createTaskOptions($uri, $build_path, $deploy_path));
    $this->wip->setWipTaskConfig($config);
    $work_id = $this->wip->getWorkId();

    $new_config = $this->createWipTaskConfig($this->createTaskOptions($uri, $build_path, $deploy_path));
    $this->wip->setWipTaskConfig($new_config);
    $this->assertEquals($work_id, $this->wip->getWorkId());
  }

  /**
   * Missing summary.
   */
  private function createWipTaskConfig($options) {
    $result = new WipTaskConfig();
    $result->setClassId('Acquia\Wip\Objects\BuildSteps\BuildSteps');
    $result->setOptions($options);
    return $result;
  }

  /**
   * Missing summary.
   */
  private function createTaskOptions($uri = NULL, $build_path = NULL, $deploy_path = NULL) {
    $result = new \stdClass();
    if (!empty($uri)) {
      $result->vcsUri = $uri;
    }
    if (!empty($build_path)) {
      $result->vcsPath = $build_path;
    }
    if (!empty($deploy_path)) {
      $result->deployVcsPath = $deploy_path;
    }
    return $result;
  }

}
