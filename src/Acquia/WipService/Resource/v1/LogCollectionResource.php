<?php

namespace Acquia\WipService\Resource\v1;

use Acquia\WipIntegrations\DoctrineORM\WipLogStore;
use Acquia\WipService\Http\HalResponse;
use Acquia\WipService\Resource\AbstractResource;
use Acquia\WipService\Validator\Constraints\ChoiceParameter;
use Acquia\WipService\Validator\Constraints\RangeParameter;
use Acquia\WipService\Validator\Constraints\WipLogLevelParameter;
use Acquia\Wip\Implementation\WipLogEntry;
use Acquia\Wip\WipLogLevel;
use DateTime;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints\Uuid;
use Teapot\StatusCode;

/**
 * Provides a REST API resource representing log message collections.
 */
class LogCollectionResource extends AbstractResource {

  /**
   * The Wip log storage instance.
   *
   * @var WipLogStore
   */
  private $storage;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    parent::__construct();
    $this->storage = $this->dependencyManager->getDependency('acquia.wip.wiplogstore');
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.wiplogstore' => '\Acquia\Wip\Storage\WipLogStoreInterface',
    );
  }

  /**
   * Gets collections of log messages.
   *
   * @param Request $request
   *   An instance of Request representing the incoming HTTP request.
   * @param Application $app
   *   The application instance.
   *
   * @return HalResponse
   *   The Hal response instance.
   */
  public function getAction(Request $request, Application $app) {
    $object_id = $request->query->get('object-id');
    $limit = $request->query->get('limit', 20);
    $page = $request->query->get('page', 1);
    $sort_order = strtoupper($request->query->get('order', 'asc'));
    $min = $request->query->get('minimum-level', WipLogLevel::TRACE);
    $max = $request->query->get('maximum-level', WipLogLevel::FATAL);
    $uuid = $request->query->get('uuid');
    $user_readable = $request->query->get('user-readable');
    if (!$this->isAdminUser()) {
      // Disallow the user-readable parameter to non-admins.
      if ($user_readable !== NULL) {
        $this->accessDenied('Access to the user-readable parameter is restricted to admin users.');
      }
      // Regular users should only ever see user-readable log messages.
      $user_readable = TRUE;
    }

    // Parameter validation.
    if (isset($object_id)) {
      $this->validate(new RangeParameter(array(
        'name' => 'object-id',
        'min' => 1,
        'max' => PHP_INT_MAX,
      )), $object_id);
    }
    $this->validate(new RangeParameter(array(
      'name' => 'limit',
      'min' => 1,
      'max' => 100,
    )), $limit);
    $this->validate(new RangeParameter(array(
      'name' => 'page',
      'min' => 1,
      'max' => PHP_INT_MAX,
    )), $page);
    $this->validate(new ChoiceParameter(array(
      'name' => 'order',
      'choices' => array('ASC', 'DESC'),
    )), $sort_order);
    if (isset($min)) {
      $this->validate(new WipLogLevelParameter('minimum-level'), $min);
    }
    if (isset($max)) {
      $this->validate(new WipLogLevelParameter('maximum-level'), $max);
    }
    if (isset($user_readable)) {
      $this->validate(new ChoiceParameter(array(
        'name' => 'user-readable',
        'choices' => array('0', '1'),
      )), $user_readable);
    }
    if ($uuid !== NULL) {
      $this->validate(new Uuid(), $uuid);
    }
    $this->checkViolations();

    // Convert values passed in GET parameters to the required types.
    if (is_numeric($min)) {
      $min = (int) $min;
    }
    if (is_numeric($max)) {
      $max = (int) $max;
    }
    if (is_numeric($object_id)) {
      $object_id = (int) $object_id;
    }
    if (is_string($min)) {
      $min = WipLogLevel::toInt($min);
    }
    if (is_string($max)) {
      $max = WipLogLevel::toInt($max);
    }
    if (is_string($user_readable)) {
      $user_readable = boolval($user_readable);
    }

    // Filter tasks by the current user unless they're an admin.
    if (!$this->isAdminUser()) {
      $uuid = $request->getUser();
    }

    // Calculate offset from page and limit parameters.
    $offset = $page * $limit - $limit;
    $results = $this->storage->load(
      $object_id,
      $offset,
      $limit,
      $sort_order,
      $min,
      $max,
      $user_readable,
      $uuid
    );
    if (empty($results)) {
      throw new NotFoundHttpException('No records found for query.');
    }
    $count = $this->storage->count(
      $object_id,
      $min,
      $max,
      $user_readable,
      $uuid
    );
    $app['segment']->track([
      'userId' => $request->getUser(),
      'event' => 'Get log messages',
      'context' => [
        'ip' => $request->getClientIp(),
        'userAgent' => $request->headers->get('User-Agent'),
      ],
      'properties' => [
        'objectId' => $object_id,
        'offset' => $offset,
        'limit' => $limit,
        'sort_order' => $sort_order,
        'min' => $min,
        'max' => $max,
        'userReadable' => $user_readable,
        'uuid' => $uuid,
      ],
    ]);

    $resources = array();
    foreach ($results as $log_entry) {
      $resources[] = array(
        'id' => $log_entry->getId(),
        'object_id' => $log_entry->getObjectId(),
        'level' => WipLogLevel::toString($log_entry->getLogLevel()),
        'timestamp' => date(DateTime::ISO8601, $log_entry->getTimestamp()),
        'message' => trim($log_entry->getMessage()),
        'container_id' => $log_entry->getContainerId(),
        'user_readable' => $log_entry->getUserReadable(),
      );
    }

    $entity = array(
      'count' => $count,
    );
    $hal = $app['hal']($request->getUri(), $entity);
    // Embed the log entry resources.
    $uri = $request->getSchemeAndHttpHost() . $request->getBaseUrl() . $request->getPathInfo();
    foreach ($resources as $resource) {
      $hal->addResource('logs', $app['hal']($uri, $resource));
    }
    $response = new HalResponse($hal, StatusCode::OK);
    $response->addPagingLinks($page, $limit, $count);

    return $response;
  }

  /**
   * Accepts batches of log messages from delegate wip objects.
   *
   * @param Request $request
   *   The incoming request.
   * @param Application $app
   *   The application.
   *
   * @return Response
   *   The response indicating if the logs have been successfully received and
   *   saved to the log store.
   *
   * @throws \Exception
   *   Exception potentially thrown by errors saving messages to the log.
   */
  public function postAction(Request $request, Application $app) {
    $response = new HalResponse();
    $messages = $request->request->get('messages');
    $logged_entries = array();

    if (!empty($messages)) {
      try {
        foreach ($messages as $log_entry) {
          $new_entry = $this->createLogEntry($log_entry);
          $this->storage->save($new_entry);

          array_push($logged_entries, $new_entry->getId());
        }
      } catch (\Exception $e) {
        $response->setContent(json_encode(array(
          'success' => FALSE,
          'message' => 'Exception caught: ' . $e->getMessage(),
          'logged_ids' => $logged_entries,
        )));
        // Request was successful, logging wasn't. Leaving status code to
        // be 200. We may want to change this in the future if we decide
        // to return an error status code.
        $response->setStatusCode(200);
      }
    } else {
      // No messages are received. Log an error and return a 200 status
      // indicating that all (zero) received messages have been logged.
      $entry_string = "No messages received in POST request from container.";
      $new_entry = new WipLogEntry(WipLogLevel::ERROR, $entry_string);

      $this->storage->save($new_entry);
    }

    if (empty($response->getContent())) {
      $response->setContent(json_encode(array(
        'success' => TRUE,
        'message' => NULL,
        'logged_ids' => $logged_entries,
      )));
    }

    return $response;
  }

  /**
   * Creates a new WipLogEntry from the JSON data.
   *
   * If integer strings are passed in for fields that require integer values,
   * cast them to integers. If any fields are still invalid, create an entry
   * with all known fields in the message and log it as an error.
   *
   * @param array $log_entry
   *   The data to be used to create a new log entry.
   *
   * @return WipLogEntry
   *   The newly created log entry.
   */
  private function createLogEntry($log_entry) {
    if (!empty($log_entry['object_id']) && (is_int($log_entry['object_id']) || ctype_digit($log_entry['object_id']))) {
      $log_entry['object_id'] = (int) $log_entry['object_id'];
    }
    if (!empty($log_entry['timestamp']) && (is_int($log_entry['timestamp']) || ctype_digit($log_entry['timestamp']))) {
      $log_entry['timestamp'] = (int) $log_entry['timestamp'];
    }
    if (!empty($log_entry['id']) && (is_int($log_entry['id']) || ctype_digit($log_entry['id']))) {
      $log_entry['id'] = (int) $log_entry['id'];
    }

    try {
      return new WipLogEntry(
        $log_entry['level'],
        $log_entry['message'],
        $log_entry['object_id'],
        $log_entry['timestamp'],
        $log_entry['id'],
        $log_entry['container_id'],
        $log_entry['user_readable']
      );
    } catch (\Exception $e) {
      $error_message = sprintf(
        'Unable to import log entry due to invalid input data: %s',
        var_export($log_entry, TRUE)
      );

      return new WipLogEntry(WipLogLevel::ERROR, $error_message);
    }
  }

}
