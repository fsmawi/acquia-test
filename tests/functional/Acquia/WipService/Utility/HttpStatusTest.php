<?php

namespace Acquia\WipService\Utility;

use Acquia\WipService\Test\AbstractFunctionalTest;
use Acquia\Wip\WipFactory;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests various HTTP status-related behavior.
 */
class HttpStatusTest extends AbstractFunctionalTest {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    WipFactory::setConfigPath('config/config.factory.test.cfg');
  }

  /**
   * Provides a list of path and method pairs.
   *
   * @return array
   *   The list of HTTP methods and their corresponding paths.
   */
  public function pathProvider() {
    return array(
      array('GET', '/build-steps/public-key'),
      array('GET', '/tasks'),
      array('GET', '/tasks/3'),
      array('GET', '/logs'),
      array('POST', '/tasks'),
      array('POST', '/logs'),
      array('POST', '/cron'),
      array('PUT', '/cron/3'),
      array('POST', '/signal/3'),
      array('GET', '/serialized-object/3'),
      array('GET', '/task-summary'),
    );
  }

  /**
   * Provides a list of valid AND invalid path and method pairs.
   *
   * @return array
   *   The list of HTTP methods and their corresponding paths.
   */
  public function validAndInvalidPathProvider() {
    $valid_paths = $this->pathProvider();
    $invalid_paths = array(
      array('PUT', '/build-steps/public-key'),
      array('POST', '/task-summary'),
      array('GET', '/some/nonexistent/path'),
      array('POST', '/some/nonexistent/path'),
      array('DELETE', 'task-summary'),
    );

    return (array_merge($valid_paths, $invalid_paths));
  }

  /**
   * Tests that OPTIONS requests return status code 204 and all allowed methods.
   *
   * @param string $path
   *   The path to test.
   *
   * @dataProvider pathProvider
   */
  public function testOptionsRequestResponseCode($method, $path) {
    $client = $this->createClient();
    $client->request('OPTIONS', $path);
    $response = $client->getResponse();
    $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());

    // Tests that all allowed methods are returned in an OPTIONS request.
    $expected_allowed_methods = $this->app['http.allowed_methods'];
    $headers = $response->headers;
    $actual_allowed_methods = $headers->get('Access-Control-Allow-Methods');
    $this->assertEquals(implode(',', $expected_allowed_methods), $actual_allowed_methods);
  }

  /**
   * Tests that HEAD requests return the same status code as GET requests.
   *
   * @param string $path
   *   The path to test.
   *
   * @dataProvider validAndInvalidPathProvider
   */
  public function testHeadRequests($method, $path) {
    // Any routes without GET will cause cromwwell to echo an error. This is expected behaviour.
    $client = $this->createClient();
    $client->request('HEAD', $path);
    $head_response_code = $client->getResponse()->getStatusCode();

    $client->request('GET', $path);
    $get_response_code = $client->getResponse()->getStatusCode();

    $this->assertEquals($get_response_code, $head_response_code);
  }

  /**
   * Tests that requests to nonexistent resources return status code 404.
   */
  public function testNotFoundResponseCode() {
    // This will cause cromwell to log an error for no route found. This is expected behaviour.
    $client = $this->createClient();
    $client->request('GET', '/nonexistent/path');
    $response = $client->getResponse();
    $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
  }

}
