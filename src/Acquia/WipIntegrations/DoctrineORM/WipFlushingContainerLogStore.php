<?php

namespace Acquia\WipIntegrations\DoctrineORM;

use Acquia\Wip\Implementation\WipLogEntry;
use Acquia\Wip\Security\AuthenticationInterface;
use Acquia\Wip\Storage\ConfigurationStoreInterface;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogEntryInterface;
use Acquia\Wip\WipLogLevel;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

/**
 * Enables batches of log messages to be sent to the parent Wip deployment.
 */
class WipFlushingContainerLogStore extends WipLogStore {

  /**
   * The entity manager.
   *
   * @var EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The number of unsent logs currently in the buffer.
   *
   * @var int
   */
  private $logsInBuffer = 0;

  /**
   * The Guzzle client used to send requests.
   *
   * @var Client
   */
  private $client = NULL;

  /**
   * The number of logs stored before flushing.
   *
   * @var int
   */
  private $bufferSize = NULL;

  /**
   * The maximum number of logs to send in one request.
   *
   * @var int
   */
  private $maxBufferSize = NULL;

  /**
   * The log level (inclusive) of log entries reached before flushing.
   *
   * @var string
   */
  private $logLevel = NULL;

  /**
   * The REST endpoint of the parent Wip deployment for making API requests to.
   *
   * @var string
   */
  private $endpoint = NULL;

  /**
   * The container ID.
   *
   * @var string
   */
  private $containerId = '0';

  /**
   * The authentication needed for the REST calls.
   *
   * @var AuthenticationInterface
   */
  private $authentication = NULL;

  /**
   * The configuration that stores the external Wip ID, among other data.
   *
   * @var ConfigurationStoreInterface
   */
  private $configuration = NULL;

  /**
   * The set of messages that should be non-user readable.
   *
   * Messages are generally logged from a Wip object in a manner that makes
   * sense assuming the Wip object is not running in a container. When executed
   * within a container there is a ContainerDelegate instance that is also
   * logging user-readable messages. The conflicting user-readable messages need
   * to be resolved to prevent confusing log messages.
   *
   * In order to prevent user-readable log messages within the container from
   * being logged outside of the container as user-readable, add a regular
   * expression to this array that identifies the message. When the log messages
   * are flushed outside of the container, any matching log messages will first
   * be set to not be user-readable.
   *
   * @var string[]
   */
  private $filterUserReadableMessages = array(
    // The message for the "onAdd" event.
    'The task has been added.',
    // The message for the "onRestart" event.
    'The task has been restarted.',
    // The message for the "onTerminate" event.
    'The task was manually terminated.',
  );

  /**
   * Creates a new instance of WipFlushingContainerLogStore.
   */
  public function __construct() {
    parent::__construct();

    $this->endpoint = $this->getEndpoint();
    $this->bufferSize = WipFactory::getInt('$acquia.wip.wipflushinglogstore.buffer_size', 20);
    $this->logLevel = WipFactory::getInt('$acquia.wip.wipflushinglogstore.log_level', WipLogLevel::ERROR);
    $this->maxBufferSize = WipFactory::getInt('$acquia.wip.wipflushinglogstore.max_buffer_size', 100);
    $this->authentication = WipFactory::getObject('acquia.wip.uri.authentication');
    $this->configuration = WipFactory::getObject('acquia.wip.storage.configuration');

    $client_options = array(
      'headers' => ['User-Agent' => 'WipClient'],
    );
    $this->client = new Client($client_options);
  }

  /**
   * Sets the buffer size of the log store.
   *
   * @param int $size
   *   The size of the buffer. Must be a positive integer.
   */
  public function setBufferSize($size) {
    if (is_null($size) || !is_numeric($size) || (is_numeric($size) && $size < 0)) {
      throw new \InvalidArgumentException('The size must be a positive integer.');
    }

    $this->bufferSize = $size;
  }

  /**
   * Returns the buffer size of the log.
   *
   * @return int
   *   The buffer size.
   */
  public function getBufferSize() {
    return $this->bufferSize;
  }

  /**
   * Sets the maximum number of logs to be sent in one request.
   *
   * @param int $size
   *   The maximum size. Must be a positive integer.
   */
  public function setMaxBufferSize($size) {
    if (is_null($size) || !is_numeric($size) || (is_numeric($size) && $size < 0)) {
      throw new \InvalidArgumentException("The number must be a positive integer.");
    }

    $this->maxBufferSize = $size;
  }

  /**
   * Returns the maximum number of logs to be sent in one request.
   *
   * @return int
   *   The maximum size.
   */
  public function getMaxBufferSize() {
    return $this->maxBufferSize;
  }

  /**
   * Sets the endpoint for the POST request.
   *
   * @param string $endpoint
   *   The endpoint for the POST request.
   *
   * @throws \InvalidArgumentException
   *   When the specified endpoint is invalid.
   */
  public function setEndpoint($endpoint) {
    if (empty($endpoint) || !is_string($endpoint)) {
      throw new \InvalidArgumentException('The endpoint must be a non-empty string.');
    }
    $this->endpoint = $endpoint;
  }

  /**
   * Returns the endpoint for the POST request.
   *
   * @return string
   *   The endpoint.
   *
   * @throws \InvalidArgumentException
   *   When the endpoint is not found.
   */
  public function getEndpoint() {
    if (!empty($this->endpoint)) {
      return $this->endpoint;
    }

    $result = getenv('ACQUIA_WIP_WIPFLUSHINGLOGSTORE_ENDPOINT');
    if (empty($result)) {
      $result = WipFactory::getString('$acquia.wip.wipflushinglogstore.endpoint');
    }
    if (empty($result)) {
      throw new \InvalidArgumentException('The REST endpoint must be set for the flushing log store.');
    }
    return $result;
  }

  /**
   * Sets the lowest severity of logs to cause a flush.
   *
   * @param int $level
   *   The level. Must be a valid WipLogLevel value.
   */
  public function setLogLevel($level) {
    if (!WipLogLevel::isValid($level)) {
      throw new \InvalidArgumentException("The level must be a valid WipLogLevel value.");
    }

    $this->logLevel = $level;
  }

  /**
   * Returns the lowest severity of logs to cause a flush.
   *
   * @return int
   *   The log level.
   */
  public function getLogLevel() {
    return $this->logLevel;
  }

  /**
   * Sets the container ID associated with the logs.
   *
   * @param string $container_id
   *   The container ID. Must be a string.
   */
  public function setContainerId($container_id) {
    if (empty($container_id) || !is_string($container_id)) {
      throw new \InvalidArgumentException("The container ID must be a string.");
    }

    $this->containerId = $container_id;
  }

  /**
   * Returns the container ID associated with the logs.
   *
   * @return string
   *   The container ID.
   */
  public function getContainerId() {
    return $this->containerId;
  }

  /**
   * Returns the HTTP client.
   *
   * @return Client
   *   The HTTP client.
   */
  public function getClient() {
    return $this->client;
  }

  /**
   * Sets the HTTP client.
   *
   * @param Client $client
   *   The HTTP client.
   */
  public function setClient(Client $client) {
    if (is_null($client)) {
      throw new \InvalidArgumentException("The client must not be null.");
    }

    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public function save(WipLogEntryInterface $log_entry) {
    // @todo The container ID needs to be set before saving.
    $entry_id = parent::save($log_entry);
    $this->logsInBuffer++;
    $buffer_full = ($this->logsInBuffer >= $this->bufferSize);
    $user_readable = $log_entry->getUserReadable();

    $level_reached = FALSE;
    // Allows NULL to disable level override flushing.
    if (!is_null($this->logLevel)) {
      $level_reached = $log_entry->getLogLevel() <= $this->logLevel;
    }

    if ($buffer_full || $level_reached || $user_readable) {
      $this->flushLog($this->maxBufferSize);
    }

    return $entry_id;
  }

  /**
   * Flushes all logs asynchronously in anticipation of exiting.
   *
   * @return bool
   *   TRUE if completed successfully, indicated by a non-null promise object
   *   being created.
   */
  public function cleanUp() {
    $return = $this->flushLog(PHP_INT_MAX, FALSE);
    return !is_null($return);
  }

  /**
   * Flushes the log by sending a POST request with a number of logs.
   *
   * If the request is synchronous, wait for the response and delete logs that
   * were successfully logged remotely. If the request is asynchronous, return
   * immediately.
   *
   * The log entries' objectIds are changed to their corresponding external WIP
   * IDs before being sent.
   *
   * @param int $max_logs
   *   Max number of logs to send in one request.
   * @param bool $async
   *   Boolean indicating whether the request should be asynchronous.
   *
   * @return bool|\GuzzleHttp\Promise\PromiseInterface
   *   A boolean indicating whether a synchronous request was successfully sent,
   *   or a PromiseInterface returned by an asynchronous request.
   */
  private function flushLog($max_logs, $async = FALSE) {
    $entries_to_flush = parent::load(NULL, 0, $max_logs);
    $external_wip_id = $this->configuration->get('externalWipId');
    if (!empty($external_wip_id) && is_numeric($external_wip_id)) {
      // This is the object ID of the Wip object running outside the container.
      $id = intval($external_wip_id);
      $entries_to_filter = $entries_to_flush;
      $entries_to_flush = array();
      foreach ($entries_to_filter as $entry) {
        $entry = $this->flushFilter($id, $entry);
        if (!empty($entry)) {
          $entries_to_flush[] = $entry;
        }
      }
    }

    $body = array(
      'messages' => $entries_to_flush,
    );

    $verify = WipFactory::getBool('$acquia.wip.ssl.verifyCertificate', TRUE);
    $options = array(
      'verify' => $verify,
      'auth' => [
        $this->authentication->getAccountId(),
        $this->authentication->getSecret(),
      ],
      'json' => $body,
    );

    $request = new Request('POST', $this->endpoint, array(), json_encode($body));

    if (!$async) {
      try {
        $response = $this->client->send($request, $options);
      } catch (RequestException $e) {
        // Log the error. This is the class all other http errors extend.
        if (!is_null($e->getResponse())) {
          $message = sprintf(
            'Failed to send log messages back to the controller. Response code: %d; Reason: %s',
            $e->getResponse()->getStatusCode(),
            $e->getResponse()->getReasonPhrase()
          );
        } else {
          $message_pattern = 'Failed to send log messages back to the controller. Check that the log endpoint is set correctly. Endpoint: %s; Verify: %s';
          $message = sprintf(
            $message_pattern,
            $this->endpoint,
            var_export($verify, TRUE)
          );
        }

        $this->save(new WipLogEntry(WipLogLevel::WARN, $message));
      }
      if (!empty($response)) {
        $response_body = json_decode((string) $response->getBody());

        if (!empty($response_body->logged_ids)) {
          $logged_ids = $response_body->logged_ids;

          if (!empty($logged_ids)) {
            foreach ($logged_ids as $id) {
              if (parent::deleteById($id) !== NULL) {
                $this->logsInBuffer--;
              }
            }
          }

          return TRUE;
        }
      }
    } else {
      try {
        return $this->client->sendAsync($request, $options);
      } catch (RequestException $e) {
        // Log the error. This is the class all other http errors extend.
        if (!is_null($e->getResponse())) {
          $message = sprintf(
            'Failed to send log messages back to the controller. Response code: %d; Reason: %s',
            $e->getResponse()->getStatusCode(),
            $e->getResponse()->getReasonPhrase()
          );
        } else {
          $message_pattern = 'Failed to send log messages back to the controller. Check that the log endpoint is set correctly. Endpoint: %s; Verify: %s';
          $message = sprintf(
            $message_pattern,
            $this->endpoint,
            var_export($verify, TRUE)
          );
        }

        $this->save(new WipLogEntry(WipLogLevel::WARN, $message));
      }
    }
  }

  /**
   * Filters a log entry that is to be flushed.
   *
   * This filter is responsible for detecting and modifying messages before they
   * are flushed. This filter can also be used to remove messages from the
   * flushed stream if needed.
   *
   * @param int $object_id
   *   The external object ID the log entry will be flushed to.
   * @param WipLogEntryInterface $entry
   *   The log entry.
   *
   * @return WipLogEntryInterface
   *   The entry to flush.
   */
  private function flushFilter($object_id, WipLogEntryInterface $entry) {
    // If the message is from onAdd, onRestart, or onTerminate, unset the
    // user-readable flag. These messages are valid, but when run in a
    // container, the ContainerDelegate object's messages will have a more
    // useful timestamp.
    $message = $entry->getMessage();
    $user_readable = $entry->getUserReadable();

    // Filter any user-readable messages.
    $filter_keys = array_keys($this->filterUserReadableMessages);
    $key_count = count($filter_keys);
    for ($i = 0; $user_readable && $i < $key_count; $i++) {
      $filter_message = $this->filterUserReadableMessages[$filter_keys[$i]];
      if (preg_match("/{$filter_message}/", $message)) {
        $user_readable = FALSE;
      }
    }
    $id = $entry->getObjectId();
    if ($id > 0) {
      $id = $object_id;
    }
    $entry = new WipLogEntry(
      $entry->getLogLevel(),
      $message,
      $id,
      $entry->getTimestamp() - $this->getTimeOffset(),
      $entry->getId(),
      $entry->getContainerId(),
      $user_readable
    );
    return $entry;
  }

  /**
   * Fetches the time offset.
   *
   * @return int
   *   The time difference between container time and non-container time
   *   measured in seconds.
   */
  private function getTimeOffset() {
    $configuration_store = $this->getConfigurationStore();
    return $configuration_store->get('timeOffset', 0);
  }

  /**
   * Gets the configuration store.
   *
   * @return ConfigurationStoreInterface
   *   The configuration store.
   */
  private function getConfigurationStore() {
    /** @var ConfigurationStoreInterface $result */
    $result = NULL;
    try {
      $result = WipFactory::getObject('acquia.wip.storage.configuration');
      if (!empty($result) || !$result instanceof ConfigurationStoreInterface) {
        throw new \DomainException('The configuration store could not be found.');
      }
    } catch (\Exception $e) {
      // No configuration store has been found.
      $result = new ConfigurationStore();
    }
    return $result;
  }

}
