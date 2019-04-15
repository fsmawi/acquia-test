<?php

namespace Acquia\WipService\Test;

use Acquia\WipService\Console\WipConsoleCommand;
use Acquia\Wip\Storage\StateStoreInterface;
use Acquia\Wip\WipFactory;
use Igorw\Silex\ConfigServiceProvider;
use Silex\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\Response;

/**
 * Missing summary.
 */
abstract class AbstractFunctionalTest extends WebTestCase {

  use CommonTestTrait;

  /**
   * Registers the testing configuration in the application.
   *
   * This is useful for switching out the global config with the testing config.
   * If your tests need to use the SQLite database instead of the MySQL backend,
   * the test class should call this method during setup.
   */
  public function registerTestingConfig() {
    $root_dir = $this->app['root_dir'];
    $this->app->register(new ConfigServiceProvider($root_dir . '/config/config.global.testing.yml'));
    WipFactory::setConfigPath($root_dir . '/config/config.factory.test.cfg');
  }

  /**
   * Creates an HTTP client.
   *
   * @param string|null $role
   *   The user role to use for authentication.
   * @param array $server
   *   Server parameters for the request.
   *
   * @return \Symfony\Component\HttpKernel\Client
   *   An instance of Client.
   */
  public function createClient($role = NULL, array $server = array()) {
    if ($role !== NULL) {
      $users = $this->app['security.client_users'];
      $server += array(
        'PHP_AUTH_USER' => $users[$role]['username'],
        'PHP_AUTH_PW'   => $users[$role]['password'],
      );
    }
    return parent::createClient($server);
  }

  /**
   * Asserts that a response contains an HTTP header name and value pair.
   *
   * @param string $name
   *   The header name.
   * @param string $value
   *   The header value.
   * @param Response $response
   *   The response to check.
   */
  public function assertResponseHeader($name, $value, Response $response) {
    $this->assertTrue(
      $response->headers->has($name),
      'Missing HTTP header: ' . $name
    );
    $this->assertSame(
      $value,
      $response->headers->get($name),
      'The HTTP header does not contain the expected value.'
    );
  }

  /**
   * Asserts that a response contains the expected fields and associated values.
   *
   * @param array $expected
   *   An array of fields and values to assert exist in the response body.
   * @param Response $response
   *   The response to check.
   */
  public function assertResponseContent($expected, Response $response) {
    $content = json_decode($response->getContent(), TRUE);
    $this->assertNotNull($content, 'Failed to decode the response body.');
    foreach ($expected as $key => $value) {
      $this->assertArrayHasKey(
        $key,
        $content,
        sprintf('Missing "%s" field in the response body.', $key)
      );
      $this->assertSame(
        $value,
        $content[$key],
        sprintf(
          'The "%s" body field does not contain the expected value.',
          $key
        )
      );
    }
  }

  /**
   * Asserts that the response from the Wip Service REST API is 404 Not Found.
   *
   * @param Response $response
   *   The response to check.
   */
  public function assertHttpNotFoundResponse(Response $response) {
    $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    $content = json_decode($response->getContent(), TRUE);
    $this->assertEquals('Resource not found.', $content['message']);
  }

  /**
   * Asserts the contents of stored state data by key.
   *
   * @param array|null $expected
   *   The expected data in an array, or NULL if the assertion should be that
   *   the state record does not exist.
   * @param string $key
   *   The state key by which to retrieve the state data.
   */
  public function assertStateData($expected, $key) {
    /** @var StateStoreInterface $storage */
    $storage = WipFactory::getObject('acquia.wip.storage.state');
    $actual = $storage->get($key);
    $this->assertSame($expected, $actual);
  }

  /**
   * Executes a console command.
   *
   * @param WipConsoleCommand $command
   *   Optional. The console command instance.
   * @param string $command_name
   *   The name of the command to execute.
   * @param array $arguments
   *   An array of options and arguments to pass to the command.
   *
   * @return CommandTester
   *   The CommandTester instance for making assertions about.
   */
  public function executeCommand(WipConsoleCommand $command, $command_name, array $arguments = array()) {
    $application = $this->app['console'];
    $application->add($command);
    $command = $application->find($command_name);
    $tester = new CommandTester($command);
    $default_arguments = array(
      'command' => $command->getName(),
    );
    $tester->execute(array_merge($default_arguments, $arguments));
    return $tester;
  }

}
