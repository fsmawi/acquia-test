<?php

namespace Acquia\WipService\Resource\v1;

use Acquia\WipIntegrations\DoctrineORM\WipLogStore;
use Acquia\WipIntegrations\DoctrineORM\WipPoolStore;
use Acquia\WipService\Test\AbstractFunctionalTest;
use Acquia\Wip\State\GlobalPause;
use Acquia\Wip\State\Maintenance;
use Acquia\Wip\Storage\StateStoreInterface;
use Acquia\Wip\TaskStatus;
use Acquia\Wip\Test\PrivateStable\Storage\WipPoolStoreTest;
use Acquia\Wip\WipFactory;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests that StateResource behaves as expected.
 */
class StateResourceTest extends AbstractFunctionalTest {

  /**
   * The Wip log storage instance.
   *
   * @var WipLogStore
   */
  private $wipLogStore;

  /**
   * The Wip pool storage instance.
   *
   * @var WipPoolStore
   */
  private $wipPoolStore;

  /**
   * The state storage instance.
   *
   * @var StateStoreInterface
   */
  private $stateStore;

  /**
   * An associative array of state names and their default values.
   *
   * @var array
   */
  private $defaultValues = array();

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->wipLogStore = new WipLogStore();
    $this->wipPoolStore = WipFactory::getObject('acquia.wip.storage.wippool');
    $this->stateStore = WipFactory::getObject('acquia.wip.storage.state');
    $this->defaultValues = array(
      Maintenance::STATE_NAME => Maintenance::$defaultValue,
      GlobalPause::STATE_NAME => GlobalPause::$defaultValue,
    );
  }

  /**
   * Tests getting the maintenance mode as user role before it has been set.
   */
  public function testGetMaintenanceModeAsRole() {
    foreach (array('ROLE_USER', 'ROLE_ADMIN') as $role) {
      $client = $this->createClient($role);
      $client->request('GET', '/state/' . Maintenance::STATE_NAME);
      $response = $client->getResponse();
      $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
      $expected = $this->getStateData(Maintenance::STATE_NAME, Maintenance::OFF);
      $this->assertResponseContent($expected, $response);
      $this->assertStateData(NULL, MAINTENANCE::STATE_NAME);
    }
  }

  /**
   * Tests that attempting to modify maintenance mode as a user is forbidden.
   */
  public function testSetMaintenanceModeAsUserAccessDenied() {
    foreach (array('PUT', 'DELETE') as $method) {
      $client = $this->createClient('ROLE_USER');
      $client->request($method, '/state/' . Maintenance::STATE_NAME);
      $response = $client->getResponse();
      $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }
  }

  /**
   * Tests that modifying state works as expected.
   *
   * @param string $method
   *   The HTTP method. Either PUT or DELETE.
   * @param string $name
   *   The name of the state.
   * @param int $starting_mode
   *   The starting state to set before issuing the HTTP request.
   * @param int $ending_mode
   *   The ending state to assert following the HTTP request.
   * @param string|null $ending_state
   *   The raw ending state value from the storage API to assert following the
   *   HTTP request.
   *
   * @dataProvider stateModificationProvider
   */
  public function testModifyStateSuccess($method, $name, $starting_mode, $ending_mode, $ending_state) {
    // Set the starting state via the storage API.
    $this->stateStore->set($name, $starting_mode);

    // Delete the state via the REST API.
    $client = $this->createClient('ROLE_ADMIN');
    switch ($method) {
      case 'PUT':
        $content = json_encode(array('value' => $ending_mode));
        $client->request('PUT', '/state/' . $name, [], [], [], $content);
        if ($ending_mode === 'off') {
          $this->assertNull($this->stateStore->get($name));
        }
        break;

      case 'DELETE':
        $client->request('DELETE', '/state/' . $name);
        $this->assertNull($this->stateStore->get($name));
        break;
    }
    $response = $client->getResponse();
    $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

    // Assert that the state was deleted via the storage API.
    $expected = $this->getStateData($name, $ending_mode);
    $this->assertResponseContent($expected, $response);
    $this->assertStateData($ending_state, $name);
  }

  /**
   * Provides parameters for testing before and after PUT and DELETE requests.
   *
   * @return array
   *   A multidimensional array of parameters.
   */
  public function stateModificationProvider() {
    $maintenance = 'wip.application.maintenance';
    $pause = 'wip.pool.pause.global';

    return array(
      // DELETE maintenance state.
      array('DELETE', $maintenance, 'off', 'off', NULL),
      array('DELETE', $maintenance, 'full', 'off', NULL),
      // PUT maintenance state.
      array('PUT', $maintenance, 'off', 'off', NULL),
      array('PUT', $maintenance, 'off', 'full', 'full'),
      array('PUT', $maintenance, 'full', 'off', NULL),
      array('PUT', $maintenance, 'full', 'full', 'full'),
      // DELETE pause state.
      array('DELETE', $pause, 'off', 'off', NULL),
      array('DELETE', $pause, 'hard_pause', 'off', NULL),
      array('DELETE', $pause, 'soft_pause', 'off', NULL),
      // PUT pause state.
      array('PUT', $pause, 'off', 'off', NULL),
      array('PUT', $pause, 'off', 'hard_pause', 'hard_pause'),
      array('PUT', $pause, 'hard_pause', 'off', NULL),
      array('PUT', $pause, 'hard_pause', 'hard_pause', 'hard_pause'),
      array('PUT', $pause, 'off', 'soft_pause', 'soft_pause'),
      array('PUT', $pause, 'soft_pause', 'off', NULL),
      array('PUT', $pause, 'soft_pause', 'soft_pause', 'soft_pause'),
    );
  }

  /**
   * Tests that attempting to get an invalid state name returns a 404.
   *
   * @param string $name
   *   The invalid state name.
   *
   * @dataProvider invalidStateNameProvider
   */
  public function testGetInvalidState($name) {
    foreach (array('ROLE_USER', 'ROLE_ADMIN') as $role) {
      $client = $this->createClient($role);
      $client->request('GET', '/state/' . $name);
      $response = $client->getResponse();
      $this->assertHttpNotFoundResponse($response);
    }
  }

  /**
   * Tests that attempting to modify an invalid state name returns a 404.
   *
   * @param string $name
   *   The invalid state name.
   *
   * @dataProvider invalidStateNameProvider
   */
  public function testModifyInvalidState($name) {
    foreach (array('PUT', 'DELETE') as $method) {
      $client = $this->createClient('ROLE_ADMIN');
      $client->request($method, '/state/' . $name);
      $response = $client->getResponse();
      $this->assertHttpNotFoundResponse($response);
    }
  }

  /**
   * Provides invalid state name parameters.
   *
   * @return array
   *   A multidimensional array of parameters.
   */
  public function invalidStateNameProvider() {
    return array(
      array('invalid.state'),
      array('wip.something.something'),
      array('1'),
      array(-123),
    );
  }

  /**
   * Tests that a malformed entity in a PUT request returns a 422 response.
   *
   * @param array $body
   *   An array representing the the request body content.
   * @param string $violation
   *   The expected validation error message.
   *
   * @dataProvider invalidRequestEntityProvider
   */
  public function testPutStateInvalidRequestEntity($body, $violation) {
    if (!is_string($body)) {
      $body = json_encode($body);
    }
    $client = $this->createClient('ROLE_ADMIN');

    $states = array(
      'maintenance' => Maintenance::STATE_NAME,
      'global pause' => GlobalPause::STATE_NAME,
    );
    foreach ($states as $readable => $state_name) {
      $client->request('PUT', '/state/' . $state_name, [], [], [], $body);
      $response = $client->getResponse();
      $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
      $content = json_decode($response->getContent(), TRUE);

      // Check that the invalid input causes a violation error response.
      $this->assertStringStartsWith('An error occurred during validation.', $content['message']);

      // Check that the specific violation error is triggered.
      if (strpos($violation, 'Malformed request entity') === 0) {
        $found = FALSE;
        foreach ($content['violations'] as $violation_message) {
          if (strpos($violation_message, $violation) === 0) {
            $found = TRUE;
          }
        }
        $this->assertTrue($found);
      } else {
        $this->assertContains(sprintf($violation, $readable), $content['violations']);
      }
    }
  }

  /**
   * Provides invalid request entities for setting maintenance mode.
   *
   * @return array
   *   A multidimensional array of parameters.
   */
  public function invalidRequestEntityProvider() {
    return array(
      // A valid property name of "value" containing invalid values.
      array(
        (object) array('value' => 5),
        'Invalid %s mode value for value parameter, 5 given.',
      ),
      array(
        (object) array('value' => -5),
        'Invalid %s mode value for value parameter, -5 given.',
      ),
      array(
        (object) array('value' => 'foo'),
        'Invalid %s mode value for value parameter, "foo" given.',
      ),
      array(
        (object) array('value' => TRUE),
        'Invalid %s mode value for value parameter, true given.',
      ),
      // Invalid property name.
      array(
        (object) array('key' => 1),
        'Missing required parameters: value.',
      ),
      // Empty request body.
      array(
        new \stdClass(),
        'Missing required parameters: value.',
      ),
      // Primitive types, blatantly invalid.
      array(
        TRUE,
        'Missing required parameters: value.',
      ),
      array(
        0,
        'Missing required parameters: value.',
      ),
      array(
        1,
        'Missing required parameters: value.',
      ),
      // Malformed JSON. The reason could be different based on PHP version so
      // omit that from the expected violation message.
      array(
        '{"foo": bar}',
        'Malformed request entity. The message payload was empty or could not be decoded. Error code: 4; Reason: ',
      ),
      array(
        '{123}',
        'Malformed request entity. The message payload was empty or could not be decoded. Error code: 4; Reason: ',
      ),
    );
  }

  /**
   * Tests that the expected HTTP headers are returned during maintenance mode.
   */
  public function testHeadersInMaintenanceMode() {
    // Enable maintenance mode.
    $this->stateStore->set(Maintenance::STATE_NAME, Maintenance::FULL);

    foreach (array('ROLE_USER', 'ROLE_ADMIN') as $role) {
      $client = $this->createClient($role);
      // Attempt to use an endpoint that should be blocked during maintenance
      // mode.
      $client->request('GET', '/ping');
      $response = $client->getResponse();
      $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
      $this->assertResponseHeader('X-Maintenance-Mode', 'full', $response);
      $content = json_decode($response->getContent(), TRUE);
      $this->assertEquals('This service is in maintenance mode.', $content['message']);
    }
  }

  /**
   * Tests that the expected messages are logged when modifying state.
   *
   * @param string $method
   *   The HTTP method.
   * @param string $name
   *   The name of the state.
   * @param int $value
   *   The value of the state.
   * @param string $expected
   *   The expected message.
   *
   * @dataProvider stateModificationLogMessagesProvider
   */
  public function testModifyStateLogMessages($method, $name, $value, $expected) {
    $client = $this->createClient('ROLE_ADMIN');
    switch ($method) {
      case 'PUT':
        $body = json_encode(array('value' => $value));
        $client->request('PUT', '/state/' . $name, [], [], [], $body);
        break;

      case 'DELETE':
        $client->request('DELETE', '/state/' . $name);
        break;
    }
    $response = $client->getResponse();
    $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

    $entries = $this->wipLogStore->load();
    $action = sprintf($expected, $name, var_export($value, TRUE));
    $regexp = sprintf('/%s on behalf of user .* from .*/', $action);
    $message = $entries[0]->getMessage();
    $this->assertRegExp($regexp, $message);
  }

  /**
   * Provides parameters for testing state modification log messages.
   *
   * @return array
   *   A multidimensional array of parameters.
   */
  public function stateModificationLogMessagesProvider() {
    $maintenance = 'wip.application.maintenance';
    $pause = 'wip.pool.pause.global';

    return array(
      array('PUT', $maintenance, 'full', 'Set state %s to %s'),
      array('PUT', $maintenance, 'off', 'Deleted state %s'),
      array('DELETE', $maintenance, NULL, 'Deleted state %s'),
      array('PUT', $pause, 'soft_pause', 'Enabled global soft-pause'),
      array('PUT', $pause, 'hard_pause', 'Enabled global hard-pause'),
      array('PUT', $pause, 'off', 'Deleted state %s'),
      array('DELETE', $pause, 'off', 'Deleted state %s'),
    );
  }

  /**
   * Tests that tasks in progress are in the response and are as expected.
   */
  public function testTasksInProgress() {
    // Generate tasks in progress.
    $tasks = array();
    for ($i = 0; $i < 5; $i++) {
      $task = WipPoolStoreTest::generateTask();
      $task->setStatus(TaskStatus::PROCESSING);
      $this->wipPoolStore->save($task);
      $tasks[] = $task;
    }
    $tasks = TaskCollectionResource::toPartials($tasks);

    // GET, PUT, and DELETE the maintenance and pause states.
    $states = array(
      Maintenance::STATE_NAME => Maintenance::FULL,
      GlobalPause::STATE_NAME => GlobalPause::HARD_PAUSE,
    );
    foreach ($states as $state_name => $mode) {
      foreach (array('GET', 'PUT', 'DELETE') as $method) {
        $client = $this->createClient('ROLE_ADMIN');
        switch ($method) {
          case 'GET':
            $client->request('GET', '/state/' . $state_name);
            break;

          case 'PUT':
            $body = json_encode(array('value' => $mode));
            $client->request('PUT', '/state/' . $state_name, [], [], [], $body);
            break;

          case 'DELETE':
            $client->request('DELETE', '/state/' . $state_name);
            break;
        }
        // Assert the expected number and format of the tasks in progress.
        $response = $client->getResponse();
        $content = json_decode($response->getContent(), TRUE);
        $this->assertCount(5, $content['tasks_in_progress']);
        $this->assertEquals($tasks, $content['tasks_in_progress']);
      }
    }
  }

  /**
   * Gets dummy state data for checking HTTP response content.
   *
   * @param int $key
   *   The state key.
   * @param int $value
   *   The state value.
   *
   * @return array
   *   An array representing the stored state record.
   */
  private function getStateData($key, $value) {
    return array(
      'key' => $key,
      'value' => $value,
    );
  }

}
