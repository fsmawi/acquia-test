<?php

namespace Acquia\WipService\Resource\v1;

use Acquia\WipIntegrations\DoctrineORM\WipLogStore;
use Acquia\WipService\Test\AbstractFunctionalTest;
use Acquia\WipService\Validator\Constraints\RangeParameterValidator;
use Acquia\Wip\Runtime\WipPoolController;
use Acquia\Wip\Storage\WipPoolStoreInterface;
use Acquia\Wip\TaskPriority;
use Acquia\Wip\TaskStatus;
use Acquia\Wip\Test\PrivateStable\Storage\WipPoolStoreTest;
use Acquia\Wip\WipFactory;
use Teapot\StatusCode;

/**
 * Missing summary.
 */
class TaskCollectionResourceTest extends AbstractFunctionalTest {

  /**
   * The Wip pool storage instance.
   *
   * @var WipPoolStoreInterface
   */
  private $storage;

  /**
   * The Wip pool controller instance.
   *
   * @var WipPoolController
   */
  private $controller;

  /**
   * The Wip log store instance.
   *
   * @var WipLogStore
   */
  private $wipLogStore;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->storage = WipFactory::getObject('acquia.wip.storage.wippool');
    $this->controller = WipFactory::getObject(WipPoolController::RESOURCE_NAME);
    $this->wipLogStore = new WipLogStore();
  }

  /**
   * Tests that the tasks collection resource data model is correct.
   */
  public function testGetTasksDataModel() {
    for ($i = 1; $i <= 5; $i++) {
      $task = WipPoolStoreTest::generateTask();
      $this->storage->save($task);
    }

    $client = $this->createClient('ROLE_ADMIN');
    $client->request('GET', '/tasks');
    $response = $client->getResponse();
    $json = json_decode($response->getContent());
    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame(5, $json->count);
    $this->assertCount(5, $json->_embedded->tasks);
    $tasks = $json->_embedded->tasks;
    foreach ($tasks as $task) {
      $expected_properties = array(
        '_links',
        'claimed_time',
        'class',
        'completed_time',
        'created_time',
        'exit_message',
        'exit_status',
        'group_name',
        'id',
        'lease_time',
        'max_run_time',
        'name',
        'parent',
        'paused',
        'priority',
        'resource_id',
        'start_time',
        'status',
        'uuid',
        'wake_time',
        'client_job_id',
      );
      $this->assertEmpty(array_diff(array_keys((array) $task), $expected_properties));
      $this->assertEmpty(array_diff($expected_properties, array_keys((array) $task)));
      $this->assertInternalType('object', $task->_links);
      $this->assertInternalType('integer', $task->claimed_time);
      $this->assertInternalType('string', $task->class);
      $this->assertInternalType('integer', $task->completed_time);
      $this->assertInternalType('integer', $task->created_time);
      $this->assertInternalType('string', $task->exit_message);
      $this->assertInternalType('integer', $task->exit_status);
      $this->assertInternalType('string', $task->group_name);
      $this->assertInternalType('integer', $task->id);
      $this->assertInternalType('integer', $task->lease_time);
      $this->assertInternalType('integer', $task->max_run_time);
      $this->assertInternalType('string', $task->name);
      $this->assertInternalType('integer', $task->parent);
      $this->assertInternalType('boolean', $task->paused);
      $this->assertInternalType('integer', $task->priority);
      $this->assertInternalType('string', $task->resource_id);
      $this->assertInternalType('integer', $task->start_time);
      $this->assertInternalType('integer', $task->status);
      $this->assertInternalType('string', $task->uuid);
      $this->assertInternalType('integer', $task->wake_time);
      $this->assertInternalType('string', $task->client_job_id);
    }
  }

  /**
   * Tests that 200 is returned when there are no tasks.
   */
  public function testGetTasksNotFound() {
    // Don't create any tasks before executing the request.
    $client = $this->createClient('ROLE_ADMIN');
    $client->request('GET', '/tasks');
    $response = $client->getResponse();
    $json = json_decode($response->getContent());
    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame(0, $json->count);
  }

  /**
   * Tests that the limit parameter works as expected.
   */
  public function testGetTasksLimit() {
    for ($i = 1; $i <= 5; $i++) {
      $task = WipPoolStoreTest::generateTask();
      $this->storage->save($task);
    }

    $client = $this->createClient('ROLE_ADMIN');
    foreach (range(1, 5) as $limit) {
      $client->request('GET', '/tasks', array('limit' => (string) $limit));
      $response = $client->getResponse();
      $json = json_decode($response->getContent());
      $this->assertSame(200, $response->getStatusCode());
      $this->assertSame(5, $json->count);
      $this->assertCount($limit, $json->_embedded->tasks);
    }
  }

  /**
   * Tests that validation fails when the limit parameter is out of range.
   *
   * @dataProvider limitOutOfRangeProvider
   */
  public function testGetTasksLimitOutOfRange($limit) {
    $client = $this->createClient('ROLE_ADMIN');
    $client->request('GET', '/tasks', array('limit' => $limit));
    $response = $client->getResponse();
    $json = json_decode($response->getContent());
    $this->assertSame(400, $json->code);
    $this->assertSame('Bad Request', $json->status);
    $this->assertSame('An error occurred during validation.', $json->message);
    $this->assertNotEmpty($json->violations);
    $this->assertRegexp('/The limit parameter should be -?\d+ or (more|less), -?\d+ given./', $json->violations[0]);
  }

  /**
   * Missing summary.
   */
  public function limitOutOfRangeProvider() {
    return array(
      array('-1'),
      array('0'),
      array('101'),
    );
  }

  /**
   * Tests that validation fails when the limit parameter is invalid.
   *
   * @dataProvider invalidIntegerProvider
   */
  public function testGetTasksLimitInvalid($limit) {
    $client = $this->createClient('ROLE_ADMIN');
    $client->request('GET', '/tasks', array('limit' => $limit));
    $response = $client->getResponse();
    $json = json_decode($response->getContent());
    $this->assertSame(400, $json->code);
    $this->assertSame('Bad Request', $json->status);
    $this->assertSame('An error occurred during validation.', $json->message);
    $this->assertNotEmpty($json->violations);
    $this->assertStringStartsWith('The limit parameter should be a whole number', $json->violations[0]);
  }

  /**
   * Tests that the range parameter validator works with various input.
   *
   * @param mixed $expected
   *   The expected result value.
   * @param mixed $value
   *   The value to check.
   * @param bool $allow_decimal
   *   Whether to allow decimal numbers. When set to FALSE, only whole numbers
   *   will be allowed.
   *
   * @dataProvider rangeParameterProvider
   */
  public function testRangeParameterConstraintIsValid($expected, $value, $allow_decimal) {
    $valid = RangeParameterValidator::isValid($value, $allow_decimal);
    $this->assertSame($expected, $valid);
  }

  /**
   * Missing summary.
   */
  public function rangeParameterProvider() {
    return array(
      array(TRUE, '1', FALSE),
      array(TRUE, '10', TRUE),
      array(TRUE, '-10', FALSE),
      array(FALSE, '1.1', FALSE),
      array(FALSE, '0.99', FALSE),
      array(TRUE, '1.0', TRUE),
      array(TRUE, '1.00001', TRUE),
      array(TRUE, '0.99999', TRUE),
      array(FALSE, 'string', TRUE),
      array(FALSE, 'string', FALSE),
      array(FALSE, '1 string', TRUE),
      array(FALSE, '2.00 string', FALSE),
    );
  }

  /**
   * Tests that the page parameter works as expected.
   */
  public function testGetTasksPage() {
    for ($i = 1; $i <= 5; $i++) {
      $task = WipPoolStoreTest::generateTask();
      $this->storage->save($task);
    }

    $client = $this->createClient('ROLE_ADMIN');
    foreach (range(1, 5) as $page) {
      $client->request('GET', '/tasks', array('limit' => '1', 'page' => (string) $page));
      $response = $client->getResponse();
      $json = json_decode($response->getContent());
      $this->assertSame(200, $response->getStatusCode());
      $this->assertSame(5, $json->count);
      $this->assertCount(1, $json->_embedded->tasks);
      $expected = $page;
      $task = $json->_embedded->tasks[0];
      $this->assertSame($expected, $task->id);
    }
  }

  /**
   * Tests that validation fails when the page parameter is out of range.
   */
  public function testGetTasksPageOutOfRange() {
    $client = $this->createClient('ROLE_ADMIN');
    $client->request('GET', '/tasks', array('page' => '0'));
    $response = $client->getResponse();
    $json = json_decode($response->getContent());
    $this->assertSame(400, $json->code);
    $this->assertSame('Bad Request', $json->status);
    $this->assertSame('An error occurred during validation.', $json->message);
    $this->assertNotEmpty($json->violations);
    $this->assertSame('The page parameter should be 1 or more, 0 given.', $json->violations[0]);
  }

  /**
   * Tests that validation fails when the page parameter is invalid.
   *
   * @dataProvider invalidIntegerProvider
   */
  public function testGetTasksPageInvalid($page) {
    $client = $this->createClient('ROLE_ADMIN');
    $client->request('GET', '/tasks', array('page' => $page));
    $response = $client->getResponse();
    $json = json_decode($response->getContent());
    $this->assertSame(400, $json->code);
    $this->assertSame('Bad Request', $json->status);
    $this->assertSame('An error occurred during validation.', $json->message);
    $this->assertNotEmpty($json->violations);
    $this->assertStringStartsWith('The page parameter should be a whole number', $json->violations[0]);
  }

  /**
   * Missing summary.
   */
  public function invalidIntegerProvider() {
    return array(
      array('string'),
      array('1.1'),
    );
  }

  /**
   * Tests that the order parameter works as expected.
   *
   * @dataProvider sortOrderProvider
   */
  public function testGetTasksSortOrder($order, $expected_order) {
    for ($i = 1; $i <= 5; $i++) {
      $task = WipPoolStoreTest::generateTask();
      $this->storage->save($task);
    }

    $client = $this->createClient('ROLE_ADMIN');
    $client->request('GET', '/tasks', array('order' => $order));
    $response = $client->getResponse();
    $json = json_decode($response->getContent());
    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame(5, $json->count);
    $this->assertCount(5, $json->_embedded->tasks);
    foreach ($expected_order as $expected) {
      $task = array_shift($json->_embedded->tasks);
      $this->assertSame($expected, $task->id);
    }
  }

  /**
   * Missing summary.
   */
  public function sortOrderProvider() {
    return array(
      array('ASC', range(1, 5)),
      array('asc', range(1, 5)),
      array('DESC', range(5, 1)),
      array('desc', range(5, 1)),
    );
  }

  /**
   * Tests that validation fails when the order parameter is invalid.
   *
   * @dataProvider invalidSortOrderProvider
   */
  public function testGetTasksSortOrderInvalid($order) {
    $client = $this->createClient('ROLE_ADMIN');
    $client->request('GET', '/tasks', array('order' => $order));
    $response = $client->getResponse();
    $json = json_decode($response->getContent());
    $this->assertSame(400, $json->code);
    $this->assertSame('Bad Request', $json->status);
    $this->assertSame('An error occurred during validation.', $json->message);
    $this->assertNotEmpty($json->violations);
    $this->assertSame('The value of the order parameter is not a valid choice.', $json->violations[0]);
  }

  /**
   * Missing summary.
   */
  public function invalidSortOrderProvider() {
    return array(
      array('string'),
      array('1'),
      array('10'),
      array('-10'),
    );
  }

  /**
   * Tests that the status parameter works as expected.
   */
  public function testGetTasksStatus() {
    $valid_values = TaskStatus::getValues();

    foreach ($valid_values as $status) {
      $task = WipPoolStoreTest::generateTask();
      $task->setStatus($status);
      $this->storage->save($task);
    }

    foreach ($valid_values as $status) {
      $client = $this->createClient('ROLE_ADMIN');
      $client->request('GET', '/tasks', array('status' => (string) $status));
      $response = $client->getResponse();
      $json = json_decode($response->getContent());
      $this->assertSame(200, $response->getStatusCode());
      $this->assertSame(1, $json->count);
      $this->assertCount(1, $json->_embedded->tasks);
      $task = $json->_embedded->tasks[0];
      $this->assertSame($status, $task->status);
    }
  }

  /**
   * Tests that validation fails when the status parameter is invalid.
   *
   * @dataProvider invalidStatusProvider
   */
  public function testGetTasksStatusInvalid($status) {
    $client = $this->createClient('ROLE_ADMIN');
    $client->request('GET', '/tasks', array('status' => (string) $status));
    $response = $client->getResponse();
    $json = json_decode($response->getContent());
    $this->assertSame(400, $json->code);
    $this->assertSame('Bad Request', $json->status);
    $this->assertSame('An error occurred during validation.', $json->message);
    $this->assertNotEmpty($json->violations);
    $this->assertContains('Invalid task status value for status parameter', $json->violations[0]);
  }

  /**
   * Missing summary.
   */
  public function invalidStatusProvider() {
    $upper_bound = max(TaskStatus::getValues());
    $lower_bound = min(TaskStatus::getValues());
    return array(
      array('string'),
      array('1.1'),
      array('-10'),
      array($upper_bound + 1),
      array($lower_bound - 1),
    );
  }

  /**
   * Tests that the parent parameter works as expected.
   */
  public function testGetTasksParent() {
    for ($i = 1; $i <= 5; $i++) {
      $task = WipPoolStoreTest::generateTask();
      $this->storage->save($task);
    }
    for ($i = 1; $i <= 5; $i++) {
      $task = WipPoolStoreTest::generateTask();
      $task->setParentId($i);
      $this->storage->save($task);
    }

    $client = $this->createClient('ROLE_ADMIN');

    $client->request('GET', '/tasks', array('parent' => '0'));
    $response = $client->getResponse();
    $json = json_decode($response->getContent());
    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame(5, $json->count);
    $this->assertCount(5, $json->_embedded->tasks);
    $tasks = $json->_embedded->tasks;
    foreach ($tasks as $task) {
      $this->assertSame(0, $task->parent);
    }

    foreach (range(1, 5) as $parent) {
      $client->request('GET', '/tasks', array('parent' => (string) $parent));
      $response = $client->getResponse();
      $json = json_decode($response->getContent());
      $this->assertSame(200, $response->getStatusCode());
      $this->assertSame(1, $json->count);
      $this->assertCount(1, $json->_embedded->tasks);
      $task = $json->_embedded->tasks[0];
      $this->assertSame($parent, $task->parent);
    }
  }

  /**
   * Tests that validation fails when the parent parameter is invalid.
   *
   * @dataProvider invalidParentProvider
   */
  public function testGetTasksParentInvalid($parent) {
    $client = $this->createClient('ROLE_ADMIN');
    $client->request('GET', '/tasks', array('parent' => $parent));
    $response = $client->getResponse();
    $json = json_decode($response->getContent());
    $this->assertSame(400, $json->code);
    $this->assertSame('Bad Request', $json->status);
    $this->assertSame('An error occurred during validation.', $json->message);
    $this->assertNotEmpty($json->violations);
    $this->assertStringStartsWith('Invalid integer value for parent parameter', $json->violations[0]);
  }

  /**
   * Missing summary.
   */
  public function invalidParentProvider() {
    return array(
      array('string'),
      array('1.1'),
      array('-10'),
    );
  }

  /**
   * Tests that valid paused parameter values are accepted.
   *
   * @dataProvider validPausedProvider
   */
  public function testGetTasksPaused($paused, $expected) {
    $task = WipPoolStoreTest::generateTask();
    $task->setPause($expected);
    $this->storage->save($task);

    $client = $this->createClient('ROLE_ADMIN');
    $client->request('GET', '/tasks', array('paused' => $paused));
    $response = $client->getResponse();
    $json = json_decode($response->getContent());
    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame(1, $json->count);
    $this->assertCount(1, $json->_embedded->tasks);
    $task = $json->_embedded->tasks[0];
    $this->assertSame($expected, $task->paused);
  }

  /**
   * Missing summary.
   */
  public function validPausedProvider() {
    return array(
      array('1', TRUE),
      array('0', FALSE),
      array('yes', TRUE),
      array('no', FALSE),
      array('on', TRUE),
      array('off', FALSE),
    );
  }

  /**
   * Tests that validation fails when the paused parameter is invalid.
   *
   * @dataProvider invalidPausedProvider
   */
  public function testGetTasksPausedInvalid($paused) {
    $client = $this->createClient('ROLE_ADMIN');
    $client->request('GET', '/tasks', array('paused' => $paused));
    $response = $client->getResponse();
    $json = json_decode($response->getContent());
    $this->assertSame(400, $json->code);
    $this->assertSame('Bad Request', $json->status);
    $this->assertSame('An error occurred during validation.', $json->message);
    $this->assertNotEmpty($json->violations);
    $this->assertStringStartsWith('Invalid boolean value for paused parameter', $json->violations[0]);
  }

  /**
   * Missing summary.
   */
  public function invalidPausedProvider() {
    return array(
      array('string'),
      array('1.1'),
      array('-10'),
    );
  }

  /**
   * Tests that valid priority parameter values are accepted.
   */
  public function testGetTasksPriority() {
    $valid_values = TaskPriority::getValues();

    foreach ($valid_values as $priority) {
      $task = WipPoolStoreTest::generateTask();
      $task->setPriority($priority);
      $this->storage->save($task);
    }

    foreach ($valid_values as $priority) {
      $client = $this->createClient('ROLE_ADMIN');
      $client->request('GET', '/tasks', array('priority' => (string) $priority));
      $response = $client->getResponse();
      $json = json_decode($response->getContent());
      $this->assertSame(200, $response->getStatusCode());
      $this->assertSame(1, $json->count);
      $this->assertCount(1, $json->_embedded->tasks);
      $task = $json->_embedded->tasks[0];
      $this->assertSame($priority, $task->priority);
    }
  }

  /**
   * Tests that validation fails when the priority parameter is invalid.
   *
   * @dataProvider invalidPriorityProvider
   */
  public function testGetTasksPriorityInvalid($priority) {
    $client = $this->createClient('ROLE_ADMIN');
    $client->request('GET', '/tasks', array('priority' => (string) $priority));
    $response = $client->getResponse();
    $json = json_decode($response->getContent());
    $this->assertSame(400, $json->code);
    $this->assertSame('Bad Request', $json->status);
    $this->assertSame('An error occurred during validation.', $json->message);
    $this->assertNotEmpty($json->violations);
    $this->assertStringStartsWith('Invalid task priority value for priority parameter', $json->violations[0]);
  }

  /**
   * Missing summary.
   */
  public function invalidPriorityProvider() {
    $upper_bound = max(TaskPriority::getValues());
    $lower_bound = min(TaskPriority::getValues());
    return array(
      array('string'),
      array('1.1'),
      array('-10'),
      array($upper_bound + 1),
      array($lower_bound - 1),
    );
  }

  /**
   * Tests that the uuid parameter works as expected.
   */
  public function testGetTasksUuid() {
    $uuids = array();
    for ($i = 1; $i <= 5; $i++) {
      $task = WipPoolStoreTest::generateTask();
      $uuid = (string) \Ramsey\Uuid\Uuid::uuid4();
      $uuids[] = $uuid;
      $task->setUuid($uuid);
      $this->storage->save($task);
    }

    $client = $this->createClient('ROLE_ADMIN');

    foreach ($uuids as $uuid) {
      $client->request('GET', '/tasks', array('uuid' => $uuid));
      $response = $client->getResponse();
      $json = json_decode($response->getContent());
      $this->assertSame(200, $response->getStatusCode());
      $this->assertSame(1, $json->count);
      $this->assertCount(1, $json->_embedded->tasks);
      $task = $json->_embedded->tasks[0];
      $this->assertSame($uuid, $task->uuid);
    }
  }

  /**
   * Tests that validation fails when the uuid parameter is invalid.
   *
   * @dataProvider invalidUuidProvider
   */
  public function testGetTasksUuidInvalid($uuid) {
    $client = $this->createClient('ROLE_ADMIN');
    $client->request('GET', '/tasks', array('uuid' => $uuid));
    $response = $client->getResponse();
    $json = json_decode($response->getContent());
    $this->assertSame(400, $json->code);
    $this->assertSame('Bad Request', $json->status);
    $this->assertSame('An error occurred during validation.', $json->message);
    $this->assertNotEmpty($json->violations);
    $this->assertStringStartsWith('This is not a valid UUID', $json->violations[0]);
  }

  /**
   * Missing summary.
   */
  public function invalidUuidProvider() {
    return array(
      array(1),
      array(1.1),
      array(-10),
      array(TRUE),
    );
  }

  /**
   * Tests that the count field works as expected.
   *
   * @param string $parameter
   *   The name of the GET parameter.
   * @param string $setter
   *   The name of the setter to mutate the value of the field on the task.
   * @param mixed $value
   *   The value of the field.
   *
   * @dataProvider countTasksProvider
   */
  public function testGetTaskCount($parameter, $setter, $value) {
    for ($i = 1; $i <= 5; $i++) {
      $task = WipPoolStoreTest::generateTask();
      $this->storage->save($task);
    }
    for ($i = 1; $i <= 2; $i++) {
      $task = WipPoolStoreTest::generateTask();
      $task->{$setter}($value);
      $this->storage->save($task);
    }

    $client = $this->createClient('ROLE_ADMIN');
    $client->request('GET', '/tasks', array($parameter => (string) $value));
    $response = $client->getResponse();
    $json = json_decode($response->getContent());
    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame(2, $json->count);
    $this->assertCount(2, $json->_embedded->tasks);
    $task = $json->_embedded->tasks[0];
    $this->assertSame($value, $task->{$parameter});
  }

  /**
   * Missing summary.
   */
  public function countTasksProvider() {
    return array(
      array('status', 'setStatus', TaskStatus::COMPLETE),
      array('parent', 'setParentId', rand()),
      array('group_name', 'setGroupName', (string) rand()),
      array('paused', 'setPause', TRUE),
      array('priority', 'setPriority', TaskPriority::CRITICAL),
      array('uuid', 'setUuid', (string) \Ramsey\Uuid\Uuid::uuid4()),
    );
  }

  /**
   * Missing summary.
   */
  public function testGetTasksPaging() {
    for ($i = 1; $i <= 5; $i++) {
      $task = WipPoolStoreTest::generateTask();
      $this->storage->save($task);
    }

    $client = $this->createClient('ROLE_ADMIN');
    foreach (range(1, 5) as $page) {
      $client->request('GET', '/tasks', array('limit' => '1', 'page' => (string) $page));
      $response = $client->getResponse();
      $json = json_decode($response->getContent());
      $this->assertSame(200, $response->getStatusCode());
      $this->assertSame(5, $json->count);
      $this->assertCount(1, $json->_embedded->tasks);
      $task = $json->_embedded->tasks[0];
      $this->assertSame($page, $task->id);
    }
  }

  /**
   * Tests that user roles only have access to the tasks they should have.
   */
  public function testGetTasksByUserRole() {
    $users = $this->app['security.client_users'];
    $user_uuid = $users['ROLE_USER']['username'];
    $admin_uuid = $users['ROLE_ADMIN']['username'];

    for ($i = 0; $i < 5; $i++) {
      $task = WipPoolStoreTest::generateTask($user_uuid);
      $this->storage->save($task);
    }
    for ($i = 0; $i < 5; $i++) {
      $task = WipPoolStoreTest::generateTask($admin_uuid);
      $this->storage->save($task);
    }

    $tasks_by_role = array(
      'ROLE_ADMIN' => 10, // Admin role can view all tasks.
      'ROLE_USER' => 5, // User role can only view tasks they own.
    );
    foreach ($tasks_by_role as $role => $count) {
      $client = $this->createClient($role);
      $client->request('GET', '/tasks');
      $response = $client->getResponse();
      $this->assertTrue($response->isOk());
      $json = json_decode($response->getContent());
      // The count should reflect the tasks the user has access to.
      $this->assertSame($count, $json->count);
      // The number of returned tasks should match the total number of tasks.
      $this->assertCount($count, $json->_embedded->tasks);
    }
  }

  /**
   * Tests access to the task-summary endpoint by user role.
   */
  public function testGetTaskSummaryByUserRole() {
    for ($i = 0; $i < 5; $i++) {
      $task = WipPoolStoreTest::generateTask();
      $task->setStatus(TaskStatus::NOT_STARTED);
      $this->storage->save($task);
    }

    // The user role should not have access to this endpoint.
    $client = $this->createClient('ROLE_USER');
    $client->request('GET', '/task-summary');
    $response = $client->getResponse();
    $this->assertTrue($response->isForbidden());

    // The admin role should have access and the response should be well-formed.
    $client = $this->createClient('ROLE_ADMIN');
    $client->request('GET', '/task-summary');
    $response = $client->getResponse();
    $this->assertTrue($response->isOk());
    $json = json_decode($response->getContent(), TRUE);
    $statuses = TaskStatus::getValues();
    foreach ($statuses as $status) {
      $status_label = TaskStatus::getLabel($status);
      $this->assertArrayHasKey($status_label, $json['counts']);
      if ($status_label !== 'Not started') {
        $this->assertSame(0, $json['counts'][$status_label]);
      }
    }
    $this->assertSame(5, $json['counts']['Not started']);
  }

  /**
   * Tests that the groups parameter is required for task pause and resume.
   *
   * @group GroupPause
   */
  public function testPauseMissingGroupsParameter() {
    $client = $this->createClient('ROLE_ADMIN');

    foreach (array('PUT', 'DELETE') as $method) {
      $client->request($method, '/tasks/pause');
      $response = $client->getResponse();
      $this->assertSame(422, $response->getStatusCode());
      $content = json_decode($response->getContent(), TRUE);
      $this->assertStringStartsWith(
        'Missing required groups query parameter',
        $content['message']
      );
    }
  }

  /**
   * Tests that groups are paused as expected after both pause and resume requests.
   *
   * @param string $type
   *   The type of pause.
   * @param string[] $pause_groups
   *   An array of group names to pause.
   * @param string[] $resume_groups
   *   An array of group names to resume.
   * @param string[] $still_paused_groups
   *   An array of group names that should still be paused after the resume request.
   *
   * @dataProvider resumeGroupProvider
   */
  public function testResumeGroups($type, $pause_groups, $resume_groups, $still_paused_groups) {
    for ($i = 0; $i < 5; $i++) {
      $task = WipPoolStoreTest::generateTask();
      $task->setGroupName('a');
      $this->storage->save($task);

      $task = WipPoolStoreTest::generateTask();
      $task->setGroupName('b');
      $this->storage->save($task);
    }

    $client = $this->createClient('ROLE_ADMIN');
    $client->request('PUT', '/tasks/pause', array(
      'type' => $type,
      'groups' => implode(',', $pause_groups),
    ));
    $response = $client->getResponse();
    $this->assertTrue($response->isOk());
    $content = json_decode($response->getContent(), TRUE);
    $this->assertEquals(array_values($pause_groups), array_values($content['paused_groups']));

    $client->request('DELETE', '/tasks/pause', array(
      'type' => $type,
      'groups' => implode(',', $resume_groups),
    ));
    $response = $client->getResponse();
    $this->assertTrue($response->isOk());
    $content = json_decode($response->getContent(), TRUE);
    $this->assertEquals(array_values($still_paused_groups), array_values($content['paused_groups']));
  }

  /**
   * Provides pause type and groups of arrays as parameters.
   *
   * @return array
   *   A multidimensional array of parameters.
   */
  public function resumeGroupProvider() {
    return array(
      array('hard', array('a', 'b'), array('a', 'b'), array()),
      array('hard', array('a', 'b'), array('a'), array('b')),
      array('hard', array('a', 'b'), array('b'), array('a')),
      array('hard', array('a', 'b'), array('c'), array('a', 'b')),
      array('hard', array('a', 'b'), array(), array('a', 'b')),
      array('soft', array('a', 'b'), array('a', 'b'), array()),
      array('soft', array('a', 'b'), array('a'), array('b')),
      array('soft', array('a', 'b'), array('b'), array('a')),
      array('soft', array('a', 'b'), array(), array('a', 'b')),
      array('soft', array('a', 'b'), array('c'), array('a', 'b')),
      array('soft', array('a', 'b'), array('', NULL), array('a', 'b')),
    );
  }

  /**
   * Tests that only admins can pause and resume a task group.
   *
   * @param string $user_role
   *   The user role to execute the request as.
   * @param string $method
   *   The HTTP request method.
   * @param int $status_code
   *   The expected status code of the response.
   *
   * @dataProvider groupPauseByUserRoleProvider
   */
  public function testGroupPauseByUserRole($user_role, $method, $status_code) {
    for ($i = 0; $i < 5; $i++) {
      $task = WipPoolStoreTest::generateTask();
      $task->setGroupName('test');
      $this->storage->save($task);
    }

    $client = $this->createClient($user_role);
    $client->request($method, '/tasks/pause', array('groups' => 'test'));
    $response = $client->getResponse();
    $this->assertSame($status_code, $response->getStatusCode());

    if ($user_role === 'ROLE_ADMIN') {
      // PUT requests should pause group, and DELETE request should resume group.
      $content = json_decode($response->getContent(), TRUE);
      $paused_groups = $content['paused_groups'];
      if ($method === 'PUT') {
        $this->assertEquals(array('test'), $paused_groups);
      } else {
        $this->assertEmpty($paused_groups);
      }
    }
  }

  /**
   * Provides parameters to test task group pause state modification by user role.
   *
   * @return array[]
   *   An multidimensional array of parameters.
   */
  public function groupPauseByUserRoleProvider() {
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
   * Tests that group pause does not accept invalid type parameter values.
   *
   * @param string $type
   *   The invalid type value.
   *
   * @dataProvider invalidPauseTypeProvider
   *
   * @group GroupPause
   */
  public function testPauseInvalidTypeParameter($type) {
    $client = $this->createClient('ROLE_ADMIN');
    $parameters = array(
      'groups' => 'foo',
      'type' => $type,
    );
    $client->request('PUT', '/tasks/pause', $parameters);
    $response = $client->getResponse();
    $this->assertSame(StatusCode::BAD_REQUEST, $response->getStatusCode());
    $content = json_decode($response->getContent(), TRUE);
    $this->assertSame(
      sprintf(
        'Unrecognised type query parameter value %s. Expected either soft or hard.',
        $type
      ),
      $content['message']
    );
  }

  /**
   * Provides invalid type parameter values.
   *
   * @return array
   *   A multidimensional array of parameters.
   */
  public function invalidPauseTypeProvider() {
    return array(
      array('0'),
      array('1'),
      array('100'),
      array('-10'),
      array('1.1'),
      array('foo'),
      array('soft-pause'),
      array('hard-pause'),
    );
  }

  /**
   * Tests that various combinations of pause state are applied as expected.
   *
   * @param string $type
   *   The type of pause, either "hard" or "soft".
   * @param string[] $groups
   *   A list of groups to pause.
   *
   * @dataProvider pauseGroupProvider
   *
   * @group GroupPause
   */
  public function testPauseTaskGroups($type, $groups) {
    // Generate tasks.
    $in_progress_statuses = array(
      TaskStatus::WAITING,
      TaskStatus::PROCESSING,
    );
    $max = 5;
    for ($i = 0; $i < $max; $i++) {
      $task = WipPoolStoreTest::generateTask();
      $this->storage->save($task); // No specific group name.

      $task = WipPoolStoreTest::generateTask();
      $task->setGroupName('GroupName1');
      $status = array_rand(array_flip($in_progress_statuses));
      $task->setStatus($status);
      $this->storage->save($task);

      $task = WipPoolStoreTest::generateTask();
      $task->setGroupName('GroupName2');
      $status = array_rand(array_flip($in_progress_statuses));
      $task->setStatus($status);
      $this->storage->save($task);
    }

    $client = $this->createClient('ROLE_ADMIN');

    $parameters = array(
      'type' => $type,
      'groups' => implode(',', $groups),
    );
    $client->request('PUT', '/tasks/pause', $parameters);
    $response = $client->getResponse();
    $this->assertTrue($response->isOk());
    $content = json_decode($response->getContent(), TRUE);
    $this->assertSame($max * count($groups), $content['count']);
    $this->assertCount($max * count($groups), $content['_embedded']['tasks_in_progress']);
    foreach ($content['tasks'] as $task_entity) {
      $this->assertContains($task_entity['group'], array('GroupName1', 'GroupName2'));
    }
    $this->assertEquals($groups, $content['paused_groups']);
    switch ($type) {
      case 'hard':
        $this->assertEquals($groups, $this->controller->getHardPausedGroups());
        break;

      case 'soft':
        $this->assertEquals($groups, $this->controller->getSoftPausedGroups());
        break;
    }
  }

  /**
   * Provides parameters for testing group pause.
   *
   * @return array
   *   A multidimensional array of parameters.
   */
  public function pauseGroupProvider() {
    return array(
      array(
        'hard',
        array('GroupName1'),
      ),
      array(
        'hard',
        array('GroupName1', 'GroupName2'),
      ),
      array(
        'soft',
        array('GroupName1'),
      ),
      array(
        'soft',
        array('GroupName1', 'GroupName2'),
      ),
    );
  }

  /**
   * Tests that messages are logged correctly when modifying group pause status.
   *
   * @param string $type
   *   The type of pause. Either "hard" or "soft".
   * @param string[] $expected_messages
   *   A list of expected messages in regexp format.
   *
   * @dataProvider pauseGroupModificationLogMessageProvider
   *
   * @group GroupPause
   */
  public function testGroupPauseModificationLogMessages($type, $expected_messages) {
    $client = $this->createClient('ROLE_ADMIN');

    $parameters = array(
      'type' => $type,
      'groups' => 'GroupName',
    );
    $client->request('PUT', '/tasks/pause', $parameters);
    $messages = $this->wipLogStore->load();
    foreach ($expected_messages as $key => $expected_message) {
      $username = $this->app['security.client_users']['ROLE_ADMIN']['username'];
      $this->assertRegExp(
        sprintf($expected_message, $username),
        $messages[$key]->getMessage()
      );
    }
  }

  /**
   * Provides data for checking the expected log messages when pausing tasks.
   *
   * @return array
   *   A multidimensional array of parameters.
   */
  public function pauseGroupModificationLogMessageProvider() {
    return array(
      array(
        'soft',
        array(
          '/Soft-paused tasks in groups GroupName on behalf of user %s from .*/',
          '/There are 0 tasks in progress after issuing the soft-pause request on behalf of user %s from .*/',
        ),
      ),
      array(
        'hard',
        array(
          '/Paused tasks in groups GroupName on behalf of user %s from .*/',
          '/There are 0 tasks in progress after issuing the hard-pause request on behalf of user %s from .*/',
        ),
      ),
    );
  }

  /**
   * Tests the GetTasksInProcessingV1 endpoint.
   */
  public function testGetTasksInProcessing() {
    $task_processing = WipPoolStoreTest::generateTask();
    $task_processing->setStatus(TaskStatus::PROCESSING);
    $this->storage->save($task_processing);

    $task_waiting = WipPoolStoreTest::generateTask();
    $task_waiting->setStatus(TaskStatus::WAITING);
    $this->storage->save($task_waiting);

    $task_complete = WipPoolStoreTest::generateTask();
    $task_complete->setStatus(TaskStatus::COMPLETE);
    $this->storage->save($task_complete);

    $task_not_ready = WipPoolStoreTest::generateTask();
    $task_not_ready->setStatus(TaskStatus::NOT_READY);
    $this->storage->save($task_not_ready);

    $task_restarted = WipPoolStoreTest::generateTask();
    $task_restarted->setStatus(TaskStatus::RESTARTED);
    $this->storage->save($task_restarted);

    $task_not_started = WipPoolStoreTest::generateTask();
    $task_not_started->setStatus(TaskStatus::NOT_STARTED);
    $this->storage->save($task_not_started);

    $client = $this->createClient('ROLE_ADMIN');
    $client->request('GET', '/tasks/in_processing');
    $response = $client->getResponse();
    $json = json_decode($response->getContent());
    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame(1, $json->count);
    $this->assertCount(1, $json->_embedded->tasks);
    $task = $json->_embedded->tasks[0];
    $this->assertEquals(TaskStatus::PROCESSING, $task->status);
  }

  /**
   * Tests that the client_job_id parameter works as expected.
   */
  public function testGetWithPipelineJobId() {
    $job_uuids = array();
    for ($i = 1; $i <= 5; $i++) {
      $task = WipPoolStoreTest::generateTask();
      $uuid = (string) \Ramsey\Uuid\Uuid::uuid4();
      $job_uuids[] = $uuid;
      $task->setClientJobId($uuid);
      $this->storage->save($task);
    }

    $client = $this->createClient('ROLE_ADMIN');

    foreach ($job_uuids as $uuid) {
      $client->request('GET', '/tasks', array('client_job_id' => $uuid));
      $response = $client->getResponse();
      $json = json_decode($response->getContent());
      $this->assertSame(200, $response->getStatusCode());
      $this->assertSame(1, $json->count);
      $this->assertCount(1, $json->_embedded->tasks);
      $task = $json->_embedded->tasks[0];
      $this->assertSame($uuid, $task->client_job_id);
    }
  }

  /**
   * Tests that validation fails when the client_job_id parameter is invalid.
   *
   * @dataProvider invalidUuidProvider
   */
  public function testGetWithInvalidPipelineJobId($uuid) {
    $client = $this->createClient('ROLE_ADMIN');
    $client->request('GET', '/tasks', array('client_job_id' => $uuid));
    $response = $client->getResponse();
    $json = json_decode($response->getContent());
    $this->assertSame(400, $json->code);
    $this->assertSame('Bad Request', $json->status);
    $this->assertSame('An error occurred during validation.', $json->message);
    $this->assertNotEmpty($json->violations);
    $this->assertStringStartsWith('This is not a valid UUID', $json->violations[0]);
  }

}
