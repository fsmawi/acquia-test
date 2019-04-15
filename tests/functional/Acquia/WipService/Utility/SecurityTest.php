<?php

namespace Acquia\WipService\Utility;

use Acquia\WipService\Test\AbstractFunctionalTest;
use Symfony\Component\HttpFoundation\Response;

/**
 * Performs various security-related unit tests.
 */
class SecurityTest extends AbstractFunctionalTest {

  /**
   * The routes for the API defined in the service description.
   *
   * @var array
   */
  private $routes = array();

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    foreach ($this->app['api.versions'] as $description) {
      $this->routes = array_merge($this->routes, $description['operations']);
    }
  }

  /**
   * Tests that authentication fails when no credentials are provided.
   */
  public function testAuthenticationFailureNoCredentials() {
    foreach ($this->routes as $route) {
      // Purposefully avoid using authentication credentials.
      $client = $this->createClient();
      $client->request($route['httpMethod'], $route['uri']);
      $response = $client->getResponse();
      $this->assertSame(
        Response::HTTP_UNAUTHORIZED,
        $response->getStatusCode(),
        'Expected 401 status code not received.'
      );
    }
  }

  /**
   * Tests that authentication fails when bad credentials are provided.
   */
  public function testAuthenticationFailureBadCredentials() {
    foreach ($this->routes as $route) {
      // Use non-existent authentication credentials.
      $server = array(
        'PHP_AUTH_USER' => 'user',
        'PHP_AUTH_PW'   => 'password',
      );
      $client = $this->createClient(NULL, $server);
      $client->request($route['httpMethod'], $route['uri']);
      $response = $client->getResponse();
      $this->assertSame(
        Response::HTTP_UNAUTHORIZED,
        $response->getStatusCode(),
        'Expected 401 status code not received.'
      );
    }
  }

  /**
   * Tests that routes not accessible to regular users return a 401.
   */
  public function testAccessDeniedForUserRole() {
    $routes = array();
    foreach ($this->routes as $route) {
      if ($route['access'] === TRUE) {
        continue;
      }
      if ($route['access'] === FALSE) {
        $routes[] = $route;
        break;
      }
      if (!in_array('ROLE_USER', $route['access'])) {
        $routes[] = $route;
      }
    }
    foreach ($routes as $route) {
      $client = $this->createClient('ROLE_USER');
      $client->request($route['httpMethod'], $route['uri']);
      $response = $client->getResponse();
      $this->assertTrue($response->isForbidden(), 'Expected 403 status code not received.');
    }
  }

  /**
   * Test that access is granted as expected for the user roles.
   */
  public function testAccessGranted() {
    foreach (array('ROLE_USER', 'ROLE_ADMIN') as $role) {
      $client = $this->createClient($role);
      $client->request('GET', '/ping');
      $response = $client->getResponse();
      $this->assertTrue($response->isOk(), 'Expected 200 status code not received.');
    }
  }

}
