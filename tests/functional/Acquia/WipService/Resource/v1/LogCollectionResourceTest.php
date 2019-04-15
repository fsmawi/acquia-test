<?php

namespace Acquia\WipService\Resource\v1;

use Acquia\WipIntegrations\DoctrineORM\WipLogStore;
use Acquia\WipIntegrations\DoctrineORM\WipPoolStore;
use Acquia\WipService\Test\AbstractFunctionalTest;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\Implementation\WipLogEntry;
use Acquia\Wip\Test\PrivateStable\Storage\WipPoolStoreTest;
use Acquia\Wip\WipLogLevel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Client;

/**
 * Missing summary.
 */
class LogCollectionResourceTest extends AbstractFunctionalTest {

  /**
   * Missing summary.
   *
   * @var WipLogStore
   */
  private $logStore;

  /**
   * Missing summary.
   *
   * @var WipPoolStore
   */
  private $poolStore;

  /**
   * Missing summary.
   *
   * @var WipLog
   */
  private $wipLog;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->logStore = new WipLogStore($this->app);
    $this->wipLog = new WipLog($this->logStore);
    $this->poolStore = new WipPoolStore();
  }

  /**
   * Generate random log entries.
   *
   * @param int $count
   *   The number of log entries to generate.
   * @param bool $readable
   *   Whether the logs generated should be readable.
   */
  private function generateLogEntries($count, $readable, $object_id = NULL) {
    if (!is_int($count) || $count < 1) {
      throw new \RuntimeException('The count parameter to the generateLogEntries must be a positive integer.');
    }
    for ($i = 0; $i < $count; $i++) {
      $level = array_rand(WipLogLevel::getAll());
      if ($object_id === NULL) {
        $object_id = rand(1, 5);
      }
      $this->wipLog->log($level, 'message', $object_id, $readable);
    }
  }

  /**
   * Provides integer string values for testing.
   *
   * @return array
   *   The array of values provided.
   */
  public function integerStringProvider() {
    return array(
      array('123'),
      array('7'),
    );
  }

  /**
   * Provides empty/null values for testing.
   *
   * @return array
   *   The array of values provided.
   */
  public function emptyValueProvider() {
    return array(
      array(NULL),
      array(''),
      array(array()),
    );
  }

  /**
   * Gets the JSON response body from the Client instance.
   *
   * @param Client $client
   *   A Client instance.
   *
   * @return mixed
   *   A JSON-encoded string, unless something went terribly wrong.
   */
  public function getJsonResponse(Client $client) {
    $response = $client->getResponse();
    $content = $response->getContent();
    return json_decode($content);
  }

  /**
   * Test NOT FOUND error is generated when no logs exist for request.
   */
  public function testResponseNotFound() {
    $client = $this->createClient('ROLE_ADMIN');

    // No log entries.
    $client->request('GET', '/logs');
    $response = $client->getResponse();
    $this->assertEquals($response->getStatusCode(), 404, 'Expected 404 status not received.');
  }

  /**
   * Test OK response is generated when logs exist for request.
   */
  public function testResponseOk() {
    $this->generateLogEntries(5, TRUE);
    $client = $this->createClient('ROLE_ADMIN');

    // No parameters.
    $client->request('GET', '/logs');
    $response = $client->getResponse();
    $this->assertTrue($response->isOk(), 'Expected 200 status not received.');
  }

  /**
   * Test page and limit parameters behave correctly.
   */
  public function testParametersPageAndLimit() {
    $this->generateLogEntries(5, TRUE);
    $client = $this->createClient('ROLE_ADMIN');

    // Page 0 is below the minimum of 1.
    $client->request('GET', '/logs', array('page' => 0));
    $response = $client->getResponse();
    $this->assertTrue($response->isClientError(), 'Expected 4xx status code not received.');

    // A limit of 0 below the minimum of 1.
    $client->request('GET', '/logs', array('limit' => 0));
    $response = $client->getResponse();
    $this->assertTrue($response->isClientError(), 'Expected 4xx status code not received.');

    // A limit of 101 is above the maximum of 100.
    $client->request('GET', '/logs', array('limit' => 101));
    $response = $client->getResponse();
    $this->assertTrue($response->isClientError(), 'Expected 4xx status code not received.');

    // We should be able to get to pages 1, 2, and 3 with a limit of 2.
    foreach (range(1, 3) as $page) {
      $client->request('GET', '/logs', array('limit' => 2, 'page' => $page));
      $response = $client->getResponse();
      $this->assertTrue($response->isOk(), 'Expected 200 status code not received.');
    }

    // Since there are only 8 log items (every successful GET /log also generates one),
    // this should be out of bounds and generate a 404.
    $client->request('GET', '/logs', array('page' => 2, 'limit' => 8));
    $response = $client->getResponse();
    $this->assertEquals($response->getStatusCode(), 404, 'Expected 404 status code not received.');
  }

  /**
   * Test null parameter values generate client errors.
   */
  public function testParametersNull() {
    $client = $this->createClient('ROLE_ADMIN');
    $allowed_parameters = $this->app['api.versions']['v1']['operations']['GetLogsV1']['parameters'];
    foreach ($allowed_parameters as $parameter_name => $parameter) {
      $client->request('GET', '/logs', array($parameter_name));
      $response = $client->getResponse();
      $this->assertSame(
        Response::HTTP_BAD_REQUEST,
        $response->getStatusCode(),
        'Expected 400 status code not received.'
      );
      $json = $this->getJsonResponse($client);
      $this->assertTrue(count($json->violations) === 1, 'The number of violations is incorrect.');
      $this->assertEquals($json->violations[0], 'Invalid parameters given: 0.', 'Parameter mismatch.');
    }
  }

  /**
   * Test unexpected parameter values generate client errors.
   */
  public function testParametersUnexpectedValue() {
    $client = $this->createClient('ROLE_ADMIN');
    $allowed_parameters = $this->app['api.versions']['v1']['operations']['GetLogsV1']['parameters'];
    foreach ($allowed_parameters as $parameter_name => $parameter) {
      $client->request('GET', '/logs', array($parameter_name => 'foo'));
      $response = $client->getResponse();
      $this->assertSame(
        Response::HTTP_BAD_REQUEST,
        $response->getStatusCode(),
        'Expected 400 status code not received.'
      );
      $json = $this->getJsonResponse($client);
      $this->assertTrue(count($json->violations) === 1, 'The number of violations is incorrect.');
      $this->assertRegExp(
        '/' . $parameter_name . '/i',
        $json->violations[0],
        'The name of the paramter is not in the returned violations.'
      );
    }
  }

  /**
   * Test unexpected parameters generate client errors.
   */
  public function testParametersDisallowed() {
    $client = $this->createClient('ROLE_ADMIN');
    $this->generateLogEntries(5, TRUE);

    $client->request('GET', '/logs', array('foo' => NULL));
    $response = $client->getResponse();
    $json = $this->getJsonResponse($client);
    $this->assertTrue($response->isClientError(), 'Expected 4xx status code not received.');
    $this->assertTrue(count($json->violations) === 1, 'The number of violations is incorrect.');
    $this->assertEquals($json->violations[0], 'Invalid parameters given: foo.', 'Parameter mismatch.');

    $client->request('GET', '/logs', array('foo' => 1));
    $response = $client->getResponse();
    $json = $this->getJsonResponse($client);
    $this->assertTrue($response->isClientError(), 'Expected 4xx status code not received.');
    $this->assertTrue(count($json->violations) === 1, 'The number of violations is incorrect.');
    $this->assertEquals($json->violations[0], 'Invalid parameters given: foo.', 'Parameter mismatch.');

    $client->request('GET', '/logs', array('foo' => 1, 'bar' => 2));
    $response = $client->getResponse();
    $json = $this->getJsonResponse($client);
    $this->assertTrue($response->isClientError(), 'Expected 4xx status code not received.');
    $this->assertTrue(count($json->violations) === 1, 'The number of violations is incorrect.');
    $this->assertEquals($json->violations[0], 'Invalid parameters given: foo, bar.', 'Parameter mismatch');

    $client->request(
      'GET',
      '/logs',
      array(
        'foo' => 1,
        'page' => 1,
        'bar' => 1,
        'limit' => 1,
        'baz' => NULL,
      )
    );
    $response = $client->getResponse();
    $json = $this->getJsonResponse($client);
    $this->assertTrue($response->isClientError(), 'Expected 4xx status code not received.');
    $this->assertTrue(count($json->violations) === 1, 'The number of violations is incorrect.');
    $this->assertEquals($json->violations[0], 'Invalid parameters given: foo, bar, baz.', 'Parameter mismatch.');
  }

  /**
   * Test minimum- and maximum-level parameters behave correctly.
   */
  public function testParametersLogLevels() {
    $client = $this->createClient('ROLE_ADMIN');

    $allowed_levels = WipLogLevel::getAll();
    foreach (array_flip($allowed_levels) as $level) {
      $this->wipLog->log($level, 'message', 1, TRUE);
    }

    foreach (array('minimum-level', 'maximum-level') as $parameter) {
      // Each label and integer representing a valid log level should be
      // accepted.
      foreach ($allowed_levels as $level => $label) {
        $client->request('GET', '/logs', array($parameter => $label));
        $response = $client->getResponse();
        $this->assertTrue($response->isOk(), 'Expected 200 status code not received.');
      }
      // One less than the lowest log level should not be accepted.
      $min = min(array_flip($allowed_levels));
      $client->request('GET', '/logs', array($parameter => $min - 1));
      $response = $client->getResponse();
      $this->assertTrue($response->isClientError(), 'Expected 4xx status code not received.');
      // One more than the highest log level should not be accepted.
      $max = max(array_flip($allowed_levels));
      $client->request('GET', '/logs', array($parameter => $max + 1));
      $response = $client->getResponse();
      $this->assertTrue($response->isClientError(), 'Expected 4xx status code not received.');
    }
  }

  /**
   * Test resources are being returned in the requested order.
   */
  public function testParametersSortOrder() {
    $client = $this->createClient('ROLE_ADMIN');
    $this->generateLogEntries(5, TRUE);

    // Ascending order.
    $ascending_ids = array();
    $log_entries = $this->logStore->load();
    foreach ($log_entries as $log_entry) {
      $ascending_ids[] = $log_entry->getId();
    }
    sort($ascending_ids);
    // This call also generates one log message, though it occurs after the results have been retrieved.
    $client->request('GET', '/logs', array('order' => 'asc'));
    $json = $this->getJsonResponse($client);
    $resources = $json->_embedded->logs;
    $resource_ids = array();
    foreach ($resources as $resource) {
      $resource_ids[] = $resource->id;
    }
    $this->assertSame($ascending_ids, $resource_ids, 'Resources not in ascending order.');

    // Descending order.
    // Add the log message generated by the last /logs request.
    $ascending_ids[] = max($ascending_ids) + 1;

    // This call also generates one log message, though it occurs after the results have been retrieved.
    $client->request('GET', '/logs', array('order' => 'desc'));

    // We must not preserve the keys when reversing the array,
    // as the resource_ids array created from the json response will also be indexed from 0.
    $descending_ids = array_reverse($ascending_ids);
    $json = $this->getJsonResponse($client);
    $resources = $json->_embedded->logs;
    $resource_ids = array();
    foreach ($resources as $resource) {
      $resource_ids[] = $resource->id;
    }
    $this->assertSame($descending_ids, $resource_ids, 'Resources not in descending order.');
  }

  /**
   * Test the possible values of the order parameter.
   */
  public function testParametersSortOrderPossibleValues() {
    $client = $this->createClient('ROLE_ADMIN');
    $this->generateLogEntries(5, TRUE);

    foreach (array('asc', 'desc') as $order) {
      $client->request('GET', '/logs', array('order' => $order));
      $response = $client->getResponse();
      $this->assertTrue($response->isOk(), 'Expected 200 status code not received.');
    }
  }

  /**
   * Test object-id parameter is properly handled.
   */
  public function testParametersObjectId() {
    $client = $this->createClient('ROLE_ADMIN');

    for ($i = 1; $i <= 5; $i++) {
      $level = array_rand(WipLogLevel::getAll());
      $this->wipLog->log($level, 'message', $i, TRUE);
    }

    $client->request('GET', '/logs', array('object-id' => 0));
    $response = $client->getResponse();
    $this->assertTrue($response->isClientError(), 'Expected 4xx status code not received.');
    for ($i = 1; $i <= 5; $i++) {
      $client->request('GET', '/logs', array('object-id' => $i));
      $response = $client->getResponse();
      $this->assertTrue($response->isOk(), 'Expected 200 status code not received.');
    }
    $client->request('GET', '/logs', array('object-id' => 6));
    $response = $client->getResponse();
    $this->assertEquals($response->getStatusCode(), 404, 'Expected 404 status code not received.');
  }

  /**
   * Test self link relation is correct on embedded resources.
   */
  public function testSelfRelationalLink() {
    $client = $this->createClient('ROLE_ADMIN');
    $this->generateLogEntries(3, TRUE);

    $client->request('GET', '/logs', array('limit' => 1));
    $json = $this->getJsonResponse($client);
    $resources = $json->_embedded->logs;
    foreach ($resources as $resource) {
      $this->assertEquals(
        $resource->_links->self->href,
        'http://localhost/logs',
        'The self link relation is incorrect.'
      );
    }
  }

  /**
   * Test paging-related link relations are correct.
   */
  public function testPagingRelationalLinks() {
    $client = $this->createClient('ROLE_ADMIN');
    $this->generateLogEntries(3, TRUE);
    $message = 'The %s link relation is incorrect.';

    // This call generates one log message, but the result does not contain that message.
    $client->request('GET', '/logs', array('limit' => 1));
    $json = $this->getJsonResponse($client);
    $this->assertEquals('http://localhost/logs?limit=1', $json->_links->self->href, sprintf($message, 'self'));
    $this->assertTrue(empty($json->_links->prev), sprintf($message, 'prev'));
    $this->assertEquals('http://localhost/logs?limit=1&page=2', $json->_links->next->href, sprintf($message, 'next'));
    $this->assertEquals('http://localhost/logs?limit=1&page=3', $json->_links->last->href, sprintf($message, 'last'));

    // This call generates one log message, but the result does not contain that message.
    $client->request('GET', '/logs', array('limit' => 1, 'page' => 2));
    $json = $this->getJsonResponse($client);
    $this->assertEquals('http://localhost/logs?limit=1&page=2', $json->_links->self->href, sprintf($message, 'self'));
    $this->assertEquals('http://localhost/logs?limit=1&page=1', $json->_links->prev->href, sprintf($message, 'prev'));
    $this->assertEquals('http://localhost/logs?limit=1&page=3', $json->_links->next->href, sprintf($message, 'next'));
    $this->assertEquals('http://localhost/logs?limit=1&page=4', $json->_links->last->href, sprintf($message, 'last'));

    // This call also generates one log message, but the result does not contain that message.
    $client->request('GET', '/logs', array('limit' => 1, 'page' => 4));
    $json = $this->getJsonResponse($client);
    $this->assertEquals('http://localhost/logs?limit=1&page=4', $json->_links->self->href, sprintf($message, 'self'));
    $this->assertEquals('http://localhost/logs?limit=1&page=3', $json->_links->prev->href, sprintf($message, 'prev'));
    $this->assertEquals('http://localhost/logs?limit=1&page=5', $json->_links->next->href, sprintf($message, 'next'));
    $this->assertEquals('http://localhost/logs?limit=1&page=5', $json->_links->last->href, sprintf($message, 'last'));
  }

  /**
   * Test count property behaves correctly.
   */
  public function testCountProperty() {
    $client = $this->createClient('ROLE_ADMIN');

    for ($i = 0; $i < 2; $i++) {
      for ($j = 1; $j <= 5; $j++) {
        $level = array_rand(WipLogLevel::getAll());
        $this->wipLog->log($level, 'message', $j, TRUE);
      }
    }

    // This call generates one log message but the response does not contain that message.
    $client->request('GET', '/logs');
    $json = $this->getJsonResponse($client);
    $this->assertEquals(10, $json->count, 'The total number of resources was incorrect.');

    for ($j = 1; $j <= 5; $j++) {
      $client->request('GET', '/logs', array('object-id' => $j));
      $json = $this->getJsonResponse($client);
      $this->assertEquals(2, $json->count, 'The total number of resources for the given object-id was incorrect.');
    }
  }

  /**
   * Test the resource entity conforms to the expected values.
   *
   * @todo Implement symfony/validator and decouple from specific formats.
   */
  public function testResourceEntity() {
    $this->generateLogEntries(1, TRUE);

    $log_entries = $this->logStore->load();
    $log_entry = new \stdClass();
    $log_entry->id = $log_entries[0]->getId();
    $log_entry->object_id = $log_entries[0]->getObjectId();
    $log_entry->level = WipLogLevel::toString($log_entries[0]->getLogLevel());
    $log_entry->timestamp = date(\DateTime::ISO8601, $log_entries[0]->getTimestamp());
    $log_entry->message = trim($log_entries[0]->getMessage());
    $log_entry->container_id = $log_entries[0]->getContainerId();
    $log_entry->user_readable = $log_entries[0]->getUserReadable();
    $log_entry->_links = new \stdClass();
    $log_entry->_links->self = new \stdClass();
    $log_entry->_links->self->href = 'http://localhost/logs';

    $client = $this->createClient('ROLE_ADMIN');
    $client->request('GET', '/logs');
    $json = $this->getJsonResponse($client);
    $resource = $json->_embedded->logs[0];

    $this->assertEquals($log_entry, $resource, 'The log resource in the response does not match the stored entry.');
  }

  /**
   * Test to ensure the date of log entries is parsable.
   */
  public function testResourceEntityDate() {
    $this->generateLogEntries(1, TRUE);

    $log_entries = $this->logStore->load();
    $timestamp = $log_entries[0]->getTimestamp();

    $client = $this->createClient('ROLE_ADMIN');
    $client->request('GET', '/logs');
    $json = $this->getJsonResponse($client);
    $date = $json->_embedded->logs[0]->timestamp;
    $this->assertEquals($timestamp, strtotime($date), 'The timestamp of the log resource is not parsable.');
  }

  /**
   * Test getting logs with user_readable flag.
   */
  public function testGetWithUserReadableFlag() {
    $client = $this->createClient('ROLE_ADMIN');

    $entry1 = new WipLogEntry(WipLogLevel::TRACE, 'false', NULL, NULL, NULL, '0', FALSE);
    $entry2 = new WipLogEntry(WipLogLevel::TRACE, 'true', NULL, NULL, NULL, '0', TRUE);
    $this->logStore->save($entry1);
    $this->logStore->save($entry2);

    // Test getting only user readable logs.
    $client->request('GET', '/logs', array('user-readable' => TRUE)); // This call also generates one log message.
    $response = $client->getResponse();
    $this->assertTrue($response->isOk(), 'Expected 200 status code not received.');

    $json = $this->getJsonResponse($client);
    $resource = $json->_embedded->logs[0];
    $this->assertEquals('true', $resource->message);

    // Test getting only non-user readable logs.
    $client->request('GET', '/logs', array('user-readable' => FALSE)); // This call also generates one log message.
    $response = $client->getResponse();
    $this->assertTrue($response->isOk(), 'Expected 200 status code not received.');

    $json = $this->getJsonResponse($client);
    $resource = $json->_embedded->logs[0];
    $this->assertEquals('false', $resource->message);

    // Test getting all logs. This call generates a log message, but is not included in the result.
    $client->request('GET', '/logs', array('user-readable' => NULL));
    $response = $client->getResponse();
    $this->assertTrue($response->isOk(), 'Expected 200 status code not received.');

    $json = $this->getJsonResponse($client);
    $this->assertCount(4, $json->_embedded->logs);
  }

  /**
   * Test OK response is generated for a request with multiple messages.
   */
  public function testPostResponseOk() {
    $client = $this->createClient('ROLE_ADMIN');

    $entries = array();
    for ($i = 0; $i < 5; $i++) {
      $level = array_rand(WipLogLevel::getAll());
      $new_entry = new WipLogEntry($level, 'message', rand(1, 5), time(), 1);
      $entries[] = $new_entry->jsonSerialize();
    }

    $client->request('POST', '/logs', array("messages" => $entries));
    $response = $client->getResponse();
    $this->assertTrue($response->isOk(), 'Expected 200 status not received.');
  }

  /**
   * Tests that an incorrectly formatted log will be logged as an error.
   */
  public function testPostInvalidFormattedLogs() {
    $invalid_log = [];
    $invalid_log['level'] = TRUE;
    $invalid_log['message'] = 'MESSAGE';
    $invalid_log['object_id'] = 'INVALID';
    $invalid_log['timestamp'] = 'INVALID';
    $invalid_log['id'] = 'INVALID';
    $invalid_log['container_id'] = 12345;

    $client = $this->createClient('ROLE_ADMIN');
    $client->request('POST', '/logs', array(
      'messages' => array($invalid_log),
    ));
    $response = $client->getResponse();
    $json = json_decode($response->getContent());

    $this->assertTrue($response->isSuccessful(), 'Expected 200 status not received.');
    $this->assertCount(1, $json->logged_ids);

    $logged_entry = $this->logStore->load()[0];
    // Make sure the message was logged as "Unable to import".
    $this->assertNotEquals($invalid_log['message'], $logged_entry->getMessage());
    $this->assertRegexp('/Unable\sto\simport/', $logged_entry->getMessage());
  }

  /**
   * Tests that integer strings are cast to integers.
   *
   * Ensures that arguments are type-corrected before being used to create new
   * WipLogEntry objects, if they are correctable.
   *
   * @dataProvider integerStringProvider
   */
  public function testIntegerStringArguments($value) {
    $log_entry = [];
    $log_entry['level'] = WipLogLevel::WARN;
    $log_entry['message'] = 'MESSAGE';
    $log_entry['object_id'] = $value;
    $log_entry['timestamp'] = $value;
    $log_entry['id'] = $value;
    $log_entry['container_id'] = '1234';
    $log_entry['user_readable'] = FALSE;

    $client = $this->createClient('ROLE_ADMIN');
    $client->request('POST', '/logs', array(
      'messages' => array($log_entry),
    ));
    $response = $client->getResponse();
    $json = json_decode($response->getContent());

    $this->assertTrue($response->isSuccessful(), 'Expected 200 status not received.');
    $this->assertCount(1, $json->logged_ids);

    $logged_entry = $this->logStore->load()[0];
    // Make sure the message was logged as-is, not as "Unable to import".
    $this->assertEquals($log_entry['message'], $logged_entry->getMessage());
  }

  /**
   * Tests that an error is logged if no messages were sent in a request.
   *
   * @dataProvider emptyValueProvider
   */
  public function testNoMessagesSent($value) {
    $client = $this->createClient('ROLE_ADMIN');
    $client->request('POST', '/logs', array(
      'messages' => $value,
    ));
    $response = $client->getResponse();
    $json = json_decode($response->getContent());

    $this->assertTrue($response->isSuccessful(), 'Expected 200 status not received.');
    $this->assertCount(0, $json->logged_ids);
    $this->assertCount(1, $this->logStore->load());
  }

  /**
   * Tests that access to post logs is only allowed by the admin role.
   */
  public function testPostLogsByUserRole() {
    $entries = array();
    for ($i = 0; $i < 5; $i++) {
      $level = array_rand(WipLogLevel::getAll());
      $new_entry = new WipLogEntry($level, 'message', rand(1, 5), time(), 1);
      $entries[] = $new_entry->jsonSerialize();
    }

    // Admin role can post logs.
    $client = $this->createClient('ROLE_ADMIN');
    $client->request('POST', '/logs', array('messages' => $entries));
    $response = $client->getResponse();
    $this->assertTrue($response->isOk(), 'Expected 200 status not received.');

    // User role cannot post logs.
    $client = $this->createClient('ROLE_USER');
    $client->request('POST', '/logs', array('messages' => $entries));
    $response = $client->getResponse();
    $this->assertTrue($response->isForbidden(), 'Expected 403 status not received.');
  }

  /**
   * Tests that the user-readable parameter is restricted to the admin role.
   */
  public function testGetLogsUserReadableParameterByUserRole() {
    $user_uuid = $this->app['security.client_users']['ROLE_USER']['username'];

    $user_task = WipPoolStoreTest::generateTask($user_uuid);
    $this->poolStore->save($user_task);
    $this->generateLogEntries(5, TRUE, $user_task->getId());
    $this->generateLogEntries(5, FALSE, $user_task->getId());

    // User role is only able to view user-readable logs that belong to them.
    $client = $this->createClient('ROLE_USER');
    $client->request('GET', '/logs');
    $json = $this->getJsonResponse($client);
    $this->assertSame(5, $json->count);
    $this->assertCount(5, $json->_embedded->logs);
    foreach ($json->_embedded->logs as $entry) {
      $this->assertSame(1, $entry->user_readable);
    }

    // User role is forbidden from using the user-readable query parameter.
    $client = $this->createClient('ROLE_USER');
    $client->request('GET', '/logs', array('user-readable' => '0'));
    $response = $client->getResponse();
    $this->assertTrue($response->isForbidden());
    $this->assertContains('Access to the user-readable parameter is restricted', $response->getContent());

    // Admin role can use the user-readable query parameter.
    $client = $this->createClient('ROLE_ADMIN');
    $client->request('GET', '/logs', array('user-readable' => '1'));
    $json = $this->getJsonResponse($client);
    $this->assertSame(5, $json->count);
    $this->assertCount(5, $json->_embedded->logs);
    foreach ($json->_embedded->logs as $entry) {
      $this->assertSame(1, $entry->user_readable);
    }

    // Admin role can view all of the logs.
    $client = $this->createClient('ROLE_ADMIN');
    $client->request('GET', '/logs');
    $json = $this->getJsonResponse($client);
    $this->assertSame(12, $json->count);
    $this->assertCount(12, $json->_embedded->logs);
  }

}
