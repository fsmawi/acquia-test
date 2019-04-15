<?php

namespace Acquia\WipService\Resource\v1;

use Acquia\WipIntegrations\DoctrineORM\WipLogStore;
use Acquia\WipIntegrations\DoctrineORM\WipPoolStore;
use Acquia\WipService\Test\AbstractFunctionalTest;
use Acquia\Wip\Modules\NativeModule\BuildSteps;
use Acquia\Wip\Task;
use Acquia\Wip\TaskExitStatus;
use Acquia\Wip\TaskPriority;
use Acquia\Wip\Test\PrivateStable\Storage\WipPoolStoreTest;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogLevel;
use Symfony\Component\HttpFoundation\Response;
use Teapot\StatusCode;

/**
 * Tests TaskResource.
 */
class TaskResourceTest extends AbstractFunctionalTest {

  /**
   * The Wip pool storage instance.
   *
   * @var WipPoolStore
   */
  private $storage;

  /**
   * The Wip log storage instance.
   *
   * @var WipLogStore
   */
  private $wipLogStore;

  /**
   * A generated task instance.
   *
   * @var Task
   */
  private $task;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    WipFactory::setConfigPath('config/config.factory.test.cfg');
    $this->storage = WipFactory::getObject('acquia.wip.storage.wippool');
    $this->task = WipPoolStoreTest::generateTask();
    $this->wipLogStore = new WipLogStore();
  }

  /**
   * Fetches a task by user role.
   *
   * @param string|int $id
   *   The task ID value.
   * @param string $role
   *   (optional) The user role.
   *
   * @return array
   *   An array containing the response body content.
   */
  private function requestTask($id, $role = 'ROLE_ADMIN') {
    $client = $this->createClient($role);
    // Do not using %d here to avoid coercing non-integers to signed decimals.
    $client->request('GET', sprintf('/tasks/%s', $id));
    $response = $client->getResponse();
    $task = json_decode($response->getContent(), TRUE);
    return $task;
  }

  /**
   * Tests that 404 Not Found is returned for a non-existent task.
   */
  public function testGetTaskNotFound() {
    // Don't create any tasks before executing the request.
    $response = $this->requestTask(1, 'ROLE_ADMIN');
    $this->assertSame(404, $response['code']);
    $this->assertSame('Not Found', $response['status']);
    $this->assertSame('Resource not found.', $response['message']);
  }

  /**
   * Tests that a validation error is returned if the ID is invalid.
   *
   * @param int $id
   *   The task ID.
   *
   * @dataProvider invalidIdProvider
   */
  public function testGetTaskInvalidId($id) {
    $response = $this->requestTask($id, 'ROLE_ADMIN');
    $this->assertSame(400, $response['code']);
    $this->assertSame('Bad Request', $response['status']);
    $this->assertStringStartsWith('An error occurred during validation', $response['message']);
  }

  /**
   * Provides invalid task ID values.
   *
   * @return array[]
   *   An multidimensional array of parameters.
   */
  public function invalidIdProvider() {
    return array(
      array('string'),
      array('1.1'),
      array('-1'),
      array(0),
    );
  }

  /**
   * Tests that 200 OK is returned for a task that exists.
   */
  public function testGetTaskOk() {
    $this->storage->save($this->task);

    $client = $this->createClient('ROLE_ADMIN');
    $client->request('GET', '/tasks/1');
    $response = $client->getResponse();
    $this->assertSame(200, $response->getStatusCode());
  }

  /**
   * Tests that the expected properties exists on the returned task resource.
   */
  public function testGetTaskProperties() {
    $this->storage->save($this->task);

    $task = $this->requestTask(1, 'ROLE_ADMIN');
    $expected_properties = array(
      '_links',
      'claimed_time',
      'class',
      'completed_time',
      'created_time',
      'delegated',
      'exit_message',
      'exit_status',
      'group_name',
      'id',
      'lease_time',
      'name',
      'parent',
      'paused',
      'priority',
      'is_prioritized',
      'resource_id',
      'start_time',
      'status',
      'timeout',
      'uuid',
      'wake_time',
      'client_job_id',
    );
    $this->assertEmpty(array_diff(array_keys($task), $expected_properties));
    $this->assertEmpty(array_diff($expected_properties, array_keys($task)));
  }

  /**
   * Tests that task properties are of the expected data types.
   */
  public function testGetTaskValueDataTypes() {
    $this->storage->save($this->task);

    $task = $this->requestTask(1, 'ROLE_ADMIN');
    $this->assertInternalType('array', $task['_links']);
    $this->assertInternalType('int', $task['claimed_time']);
    $this->assertInternalType('string', $task['class']);
    $this->assertInternalType('int', $task['completed_time']);
    $this->assertInternalType('int', $task['created_time']);
    $this->assertInternalType('bool', $task['delegated']);
    $this->assertInternalType('string', $task['exit_message']);
    $this->assertInternalType('int', $task['exit_status']);
    $this->assertInternalType('string', $task['group_name']);
    $this->assertInternalType('int', $task['id']);
    $this->assertInternalType('int', $task['lease_time']);
    $this->assertInternalType('string', $task['name']);
    $this->assertInternalType('int', $task['parent']);
    $this->assertInternalType('bool', $task['paused']);
    $this->assertInternalType('int', $task['priority']);
    $this->assertInternalType('string', $task['resource_id']);
    $this->assertInternalType('int', $task['start_time']);
    $this->assertInternalType('int', $task['status']);
    $this->assertInternalType('int', $task['timeout']);
    $this->assertInternalType('string', $task['uuid']);
    $this->assertInternalType('int', $task['wake_time']);
    $this->assertInternalType('string', $task['client_job_id']);
    $this->assertInternalType('bool', $task['is_prioritized']);
  }

  /**
   * Tests that the HAL resource href attributes are as expected.
   */
  public function testGetTaskHalResourceLinks() {
    // Generate 5 tasks.
    for ($i = 1; $i <= 5; $i++) {
      $this->storage->save(WipPoolStoreTest::generateTask());
    }

    for ($id = 1; $id <= 5; $id++) {
      $task = $this->requestTask($id, 'ROLE_ADMIN');
      $expected = "http://localhost/tasks/{$id}";
      $this->assertEquals($expected, $task['_links']['self']['href']);
    }
  }

  /**
   * Tests the POST method by creating a BuildSteps task.
   */
  public function testPostActionParameters() {
    $request_body = (object) array(
      'options' => (object) array(
        'vcsUri' => 'test',
        'vcsPath' => 'test',
        'deployVcsPath' => 'test',
      ),
    );
    $client = $this->createClient('ROLE_ADMIN');
    $client->request('POST', '/tasks', array(), array(), array(), json_encode($request_body));
    $response = $client->getResponse();

    // Check we got the expected status code.
    $this->assertEquals(200, $response->getStatusCode());
    $task = json_decode($response->getContent());
    $this->assertSame(1, $task->task_id);

    // Check that we got a Buildsteps task by default.
    $task_object = $this->requestTask($task->task_id, 'ROLE_ADMIN');
    $this->assertEquals('BuildSteps', $task_object['group_name']);
    $this->assertEquals(FALSE, $task_object['is_prioritized']);
  }

  /**
   * Tests the POST method by creating a BuildSteps task.
   */
  public function testPostActionEnvironment() {
    $request_body = (object) array(
      'options' => (object) array(
        'vcsUri' => 'test',
        'vcsPath' => 'test',
        'deployVcsPath' => 'test',
        'environmentVariables' => (object) array(
          'test' => 'test',
        ),
      ),
    );
    $client = $this->createClient('ROLE_ADMIN');
    $client->request('POST', '/tasks', array(), array(), array(), json_encode($request_body));
    $response = $client->getResponse();

    // Check we got the expected status code.
    $this->assertEquals(200, $response->getStatusCode());
    $task = json_decode($response->getContent());
    $this->assertSame(1, $task->task_id);
    $task = $this->storage->get($task->task_id);
    $task->loadWipIterator();
    $options = $task->getWipIterator()->getWip()->getOptions();
    $task->getWipIterator()->getWip()->generateWorkId();
    // Options no longer contain the sensitive information.
    $this->assertEquals(FALSE, isset($options->environmentVariables));
    /** @var BuildSteps $wip */
    $wip = $task->getWipIterator()->getWip();
    $reflection = new \ReflectionClass(get_class($wip));
    $property = $reflection->getProperty('secureUserEnvironmentVariables');
    $property->setAccessible(TRUE);
    $encrypted_value = $property->getValue($wip);
    $this->assertEquals('test', key($encrypted_value));
    $this->assertNotEquals('test', current($encrypted_value));
    // Ensure that values are decrypted.
    $encrypted_value = (array) $wip->getUserEnvironmentVariables();
    $this->assertEquals('test', key($encrypted_value));
    $this->assertEquals('test', current($encrypted_value));
  }

  /**
   * Tests that tasks get created with the correct group names.
   *
   * @param string $groupName
   *   The task group name.
   *
   * @dataProvider taskGroupProvider
   */
  public function testPostTaskGroups($groupName) {
    $data = array(
      'vcsUri' => 'test',
      'vcsPath' => 'test',
      'taskType' => $groupName,
    );
    if ($groupName == 'BuildSteps') {
      $data['deployVcsPath'] = 'my-path';
    }
    $request_body = (object) array(
      'options' => (object) $data,
    );
    $client = $this->createClient('ROLE_ADMIN');
    $client->request('POST', '/tasks', array(), array(), array(), json_encode($request_body));
    $task = json_decode($client->getResponse()->getContent());

    // Check that we got the correct task type.
    $task_object = $this->requestTask($task->task_id, 'ROLE_ADMIN');
    $this->assertEquals($groupName, $task_object['group_name']);
  }

  /**
   * Tests that users with non-admin level access can post tasks.
   */
  public function testNonAdminPostTask() {
    $request_body = (object) array(
      'options' => (object) array(
        'vcsUri' => 'test',
        'vcsPath' => 'test',
      ),
    );
    $client = $this->createClient('ROLE_USER');
    $client->request('POST', '/tasks', array(), array(), array(), json_encode($request_body));
    $task = json_decode($client->getResponse()->getContent());

    // Check that we can make the call without an exception being thrown.
    $this->requestTask($task->task_id, 'ROLE_USER');
  }

  /**
   * Provides parameters to test task group names.
   *
   * @return array[]
   *   An multidimensional array of group names.
   */
  public function taskGroupProvider() {
    return array(
      array('BuildSteps'),
      array('Canary'),
    );
  }

  /**
   * Tests that each user role has access to what they should.
   */
  public function testGetTaskByUserRole() {
    $user_uuid = $this->app['security.client_users']['ROLE_USER']['username'];
    $admin_uuid = $this->app['security.client_users']['ROLE_ADMIN']['username'];

    $user_task = WipPoolStoreTest::generateTask($user_uuid);
    $admin_task = WipPoolStoreTest::generateTask($admin_uuid);
    $this->storage->save($user_task);
    $this->storage->save($admin_task);

    // Users can access a task they own.
    $task = $this->requestTask($user_task->getId(), 'ROLE_USER');
    $this->assertSame($user_task->getUuid(), $task['uuid']);

    // Users cannot access a task they do not own.
    $response = $this->requestTask($admin_task->getId(), 'ROLE_USER');
    $this->assertSame(Response::HTTP_NOT_FOUND, $response['code']);

    // Admins can access a task they own.
    $task = $this->requestTask($admin_task->getId(), 'ROLE_ADMIN');
    $this->assertSame($admin_task->getUuid(), $task['uuid']);

    // Admins can access a task they do not own.
    $task = $this->requestTask($user_task->getId(), 'ROLE_ADMIN');
    $this->assertSame($user_task->getUuid(), $task['uuid']);
  }

  /**
   * Tests that new tasks are associated with the user who made the call.
   */
  public function testPostTaskUserIsOwner() {
    $users = $this->app['security.client_users'];

    $request_body = (object) array(
      'options' => (object) array(
        'vcsUri' => 'test',
        'vcsPath' => 'test',
        'deployVcsPath' => 'test',
      ),
    );
    foreach (array('ROLE_ADMIN', 'ROLE_USER') as $role) {
      $client = $this->createClient($role);
      $client->request('POST', '/tasks', array(), array(), array(), json_encode($request_body));
      $response = $client->getResponse();
      $this->assertEquals(200, $response->getStatusCode());
      $posted_task = json_decode($response->getContent(), TRUE);
      $fetched_task = $this->requestTask($posted_task['task_id'], $role);
      // Check that the UUID of the task matches the actual username.
      $this->assertSame($users[$role]['username'], $fetched_task['uuid']);
    }
  }

  /**
   * Tests that only admins can pause and resume a task.
   *
   * @param string $user_role
   *   The user role to execute the request as.
   * @param string $method
   *   The HTTP request method.
   * @param int $status_code
   *   The expected status code of the response.
   *
   * @dataProvider pauseByUserRoleProvider
   */
  public function testPauseByUserRole($user_role, $method, $status_code) {
    $this->storage->save($this->task);

    $client = $this->createClient($user_role);
    $client->request($method, '/tasks/1/pause');
    $response = $client->getResponse();
    $this->assertSame($status_code, $response->getStatusCode());

    if ($user_role === 'ROLE_ADMIN') {
      // PUT requests should pause, and DELETE request should resume.
      $expected_paused_state = $method === 'PUT';
      $task = json_decode($response->getContent(), TRUE);
      $this->assertEquals($expected_paused_state, $task['paused']);
    }
  }

  /**
   * Provides parameters to test task pause state modification by user role.
   *
   * @return array[]
   *   An multidimensional array of parameters.
   */
  public function pauseByUserRoleProvider() {
    return array(
      // Users cannot pause tasks.
      array('ROLE_USER', 'PUT', StatusCode::FORBIDDEN),
      // Users cannot resume tasks.
      array('ROLE_USER', 'DELETE', StatusCode::FORBIDDEN),
      // Admins can pause tasks.
      array('ROLE_ADMIN', 'PUT', StatusCode::OK),
      // Admins can resume tasks.
      array('ROLE_ADMIN', 'DELETE', StatusCode::OK),
    );
  }

  /**
   * Tests that a 404 is returned when attempting to modify a non-existent task.
   */
  public function testPauseTaskNotFound() {
    $client = $this->createClient('ROLE_ADMIN');

    foreach (array('PUT', 'DELETE') as $method) {
      $client->request($method, '/tasks/1/pause');
      $response = $client->getResponse();
      $this->assertTrue($response->isNotFound());
    }
  }

  /**
   * Tests that validation errors are returned when using an invalid ID.
   *
   * @param string|int $id
   *   The invalid ID.
   *
   * @dataProvider invalidIdProvider
   */
  public function testPauseInvalidId($id) {
    $client = $this->createClient('ROLE_ADMIN');

    foreach (array('PUT', 'DELETE') as $method) {
      $client->request($method, sprintf('/tasks/%s/pause', $id));
      $response = $client->getResponse();
      $this->assertTrue($response->isClientError());
      $error = json_decode($response->getContent(), TRUE);
      $this->assertStringStartsWith('An error occurred during validation', $error['message']);
      $this->assertStringStartsWith('Invalid integer value for id parameter', $error['violations'][0]);
    }
  }

  /**
   * Tests that the resource location of the task entity is correct.
   */
  public function testPauseTaskResourceLocation() {
    $this->storage->save($this->task);

    $client = $this->createClient('ROLE_ADMIN');
    $client->request('PUT', '/tasks/1/pause');
    $response = $client->getResponse();
    $task = json_decode($response->getContent(), TRUE);
    $this->assertEquals('http://localhost/tasks/1', $task['_links']['self']['href']);
  }

  /**
   * Tests that the terminate test works correctly.
   */
  public function testTerminateTask() {
    $request_body = (object) array(
      'options' => (object) array(
        'vcsUri' => 'test',
        'vcsPath' => 'test',
        'deployVcsPath' => 'test',
      ),
    );
    $client = $this->createClient('ROLE_ADMIN');
    $client->request('POST', '/tasks', array(), array(), array(), json_encode($request_body));
    $response = $client->getResponse();

    // Check we got the expected status code.
    $this->assertEquals(200, $response->getStatusCode());
    $task = json_decode($response->getContent());
    $path = '/tasks/' . $task->task_id . '/terminate';
    $client->request('PUT', $path);
    $response = $client->getResponse();
    $this->assertEquals(200, $response->getStatusCode());

    // Test with a task that is already completed.
    $task_in_storage = $this->storage->get(1);
    $task_in_storage->setExitStatus(TaskExitStatus::COMPLETED);
    $this->storage->save($task_in_storage);
    $client->request('PUT', $path);
    $response = $client->getResponse();
    $this->assertEquals(400, $response->getStatusCode());

    // Nonexistent ID.
    $client->request('PUT', '/tasks/9999/terminate');
    $response = $client->getResponse();
    $this->assertEquals(404, $response->getStatusCode());
  }

  /**
   * Tests that messages are logged correctly when modifying task paused status.
   *
   * @param string $method
   *   The HTTP request method.
   * @param string $action
   *   The action being performed, which should be contained in the log message.
   *
   * @dataProvider pauseModificationLogMessageProvider
   */
  public function testPauseModificationLogMessages($method, $action) {
    $this->storage->save($this->task);
    $client = $this->createClient('ROLE_ADMIN');

    $client->request($method, '/tasks/1/pause');
    $logs = $this->wipLogStore->load(NULL, 0, 10, 'DESC', WipLogLevel::TRACE, WipLogLevel::FATAL, FALSE);
    $this->assertGreaterThan(0, count($logs));
    $found = FALSE;
    $message = sprintf(
      '%s task 1 on behalf of user %s from 127.0.0.1',
      $action,
      $this->app['security.client_users']['ROLE_ADMIN']['username']
    );
    foreach ($logs as $log) {
      if (strstr($log->getMessage(), $message)) {
        $found = TRUE;
        break;
      }
    }
    $this->assertTrue($found);
  }

  /**
   * Provides data for testing pause state modification log messages.
   *
   * @return array[]
   *   An multidimensional array of parameters.
   */
  public function pauseModificationLogMessageProvider() {
    return array(
      array('PUT', 'Paused'),
      array('DELETE', 'Resumed'),
    );
  }

  /**
   * Tests that the complete task entity is returned from the pause endpoints.
   */
  public function testPauseCompleteTaskEntity() {
    $this->storage->save($this->task);
    $client = $this->createClient('ROLE_ADMIN');

    foreach (array('PUT', 'DELETE') as $method) {
      $client->request($method, '/tasks/1/pause');
      $response = $client->getResponse();
      $task = json_decode($response->getContent(), TRUE);
      $expected_properties = array(
        '_links',
        'claimed_time',
        'class',
        'completed_time',
        'created_time',
        'delegated',
        'exit_message',
        'exit_status',
        'group_name',
        'id',
        'lease_time',
        'name',
        'parent',
        'paused',
        'priority',
        'is_prioritized',
        'resource_id',
        'start_time',
        'status',
        'timeout',
        'uuid',
        'wake_time',
        'client_job_id',
      );
      $this->assertEmpty(array_diff(array_keys($task), $expected_properties));
      $this->assertEmpty(array_diff($expected_properties, array_keys($task)));
    }
  }

  /**
   * Tests that a Canary task cannot be run by a user, as it is admin-only.
   *
   * @param array $body
   *   The request body.
   *
   * @dataProvider canaryRequestProvider
   */
  public function testPostCanaryTaskUserIsAdmin($body) {
    $request_body = (object) $body;
    $client = $this->createClient('ROLE_USER');
    $client->request('POST', '/tasks', array(), array(), array(), json_encode($request_body));
    $response = $client->getResponse();

    // Check we got the expected status code and error message.
    $this->assertEquals(403, $response->getStatusCode());
    $this->assertEquals(
      "Unable to start the task. Error message: Access Denied.",
      json_decode($response->getContent(), TRUE)['message']
    );
  }

  /**
   * Tests that a Canary task has critical priority.
   *
   * @param array $body
   *   The request body.
   *
   * @dataProvider canaryRequestProvider
   */
  public function testCanaryTaskHasCriticalPriority($body) {
    $request_body = (object) $body;
    $client = $this->createClient('ROLE_ADMIN');
    $client->request('POST', '/tasks', array(), array(), array(), json_encode($request_body));
    $response = $client->getResponse();

    // Check that the priority level is CRITICAL.
    $task = json_decode($response->getContent());
    $task_object = $this->requestTask($task->task_id, 'ROLE_ADMIN');
    $this->assertEquals(TaskPriority::CRITICAL, $task_object['priority']);
    $this->assertEquals(TRUE, $task_object['is_prioritized']);
  }

  /**
   * Provides a canary request body for testing.
   *
   * @return array
   *   The canary request body.
   */
  public function canaryRequestProvider() {
    return array(
      array(
        array(
          'options' => (object) array(
            'vcsUri' => 'test',
            'vcsPath' => 'test',
            'taskType' => 'canary',
          ),
        ),
        array(
          'options' => (object) array(
            'vcsUri' => 'test',
            'vcsPath' => 'test',
            'taskType' => 'container_canary',
          ),
        ),
      ),
    );
  }

}
