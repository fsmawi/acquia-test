<?php

namespace Acquia\Wip\Objects\BuildSteps;

use Acquia\Hmac\Key;
use Acquia\WipService\App;
use Acquia\Wip\Container\ContainerInterface;
use Acquia\Wip\Implementation\ContainerWip;
use Acquia\Wip\TaskExitStatus;
use Acquia\Wip\TaskInterface;
use Acquia\Wip\TaskStatus;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogLevel;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * This class provides a mechanism to communicate back to the Pipeline service.
 */
abstract class AbstractPipelineWip extends ContainerWip {

  /**
   * The version associated with this Wip class.
   */
  const CLASS_VERSION = 1;

  /**
   * The ID associated with the pipeline this build is part of.
   *
   * @var string
   */
  private $pipelineId;

  /**
   * The Acquia application id associated with a job.
   *
   * @var string
   */
  private $pipelineApplicationId;

  /**
   * The ID associated with the pipeline job this build is responsible for.
   *
   * @var string
   */
  private $pipelineJobId;

  /**
   * The endpoint used to communicate with the pipeline service.
   *
   * @var string
   */
  private $pipelineEndpoint = 'https://pipeline-api-production.pipeline.services.acquia.io';

  /**
   * The API key used to communicate with the pipeline service.
   *
   * @var string
   */
  private $securePipelineApiKey;

  /**
   * The API secret used to communicate with the pipeline service.
   *
   * @var string
   */
  private $securePipelineApiSecret;

  /**
   * The ref to use when merging a GitHub pull request.
   *
   * @var string
   */
  private $githubMergeRef = '';

  /**
   * Indicates whether to verify the pipeline service security certificate.
   *
   * @var bool
   */
  private $pipelineVerify = FALSE;

  /**
   * Indicates the user that invoked the pipeline.
   *
   * @var string
   */
  private $pipelineUser = '<unknown>';

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, $user_readable = FALSE) {
    // There are certain messages we do not need to send.
    $ignore_message = FALSE;
    $ignore_messages = [
      '@^Task \d+ has been added.$@',
    ];
    foreach ($ignore_messages as $pattern) {
      if (preg_match($pattern, $message) === 1) {
        $ignore_message = TRUE;
        break;
      }
    }

    if (!$ignore_message && $user_readable) {
      try {
        // Send this log message to the Pipeline service.
        $relative_url = sprintf('/api/v1/ci/jobs/%s/logs', $this->getPipelineJobId());
        $log_entry = new \stdClass();
        $log_entry->applications = [$this->getPipelineApplicationId()];
        $log_entry->timestamp = time();
        // Use upper case names for legacy reasons in Pipeline API. This is
        // technically not necessary as Pipeline API does no further validation
        // than checking whether the value is a string.
        $log_entry->level = strtoupper(WipLogLevel::toString($level));
        $log_entry->message = $message;
        $log_entry->auth_token = $this->getPipelineAuthToken();

        $this->pipelineRequest('POST', $relative_url, $log_entry);
      } catch (\Exception $e) {
        $error_message = sprintf('Pipeline -> Failed to log to the pipeline service: %s.', $e->getMessage());
        try {
          $job_id = $this->getPipelineJobId();
        } catch (\Exception $e) {
          $job_id = 'unknown';
        }
        $error_message .= sprintf("\nJob ID: %s\n", $job_id);
        parent::log(
          WipLogLevel::ERROR,
          $error_message
        );
      }
    }
    parent::log($level, $message, $user_readable);
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions($options) {
    parent::setOptions($options);
    if (isset($options->pipelineId)) {
      $this->setPipelineId($options->pipelineId);
    }
    if (!empty($options->applications) && is_array($options->applications)) {
      $this->setPipelineApplicationId(reset($options->applications));
    }
    if (isset($options->pipelineJobId)) {
      $this->setPipelineJobId($options->pipelineJobId);
    }
    if (isset($options->pipelineEndpoint)) {
      $this->setPipelineEndpoint($options->pipelineEndpoint);
    }
    if (isset($options->pipelineApiKey)) {
      $this->setPipelineApiKey($options->pipelineApiKey);
      unset($options->pipelineApiKey);
    }
    if (isset($options->pipelineApiSecret)) {
      $this->setPipelineApiSecret($options->pipelineApiSecret);
      unset($options->pipelineApiSecret);
    }
    if (isset($options->pipelineVerify)) {
      $this->setPipelineVerify(boolval($options->pipelineVerify));
    }
    if (isset($options->pipelineUser)) {
      $this->setPipelineUser($options->pipelineUser);
    }
    if (isset($options->authToken)) {
      $this->setPipelineAuthToken($options->authToken);
    }
    if (isset($options->mergeRef)) {
      $this->setGithubMergeRef($options->mergeRef);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onStatusChange(TaskInterface $task) {
    try {
      // Send this status change to the Pipeline service.
      $relative_url = sprintf('/api/v1/ci/jobs/%s', $this->getPipelineJobId());
      $status = new \stdClass();

      $status->applications = [$this->getPipelineApplicationId()];
      $status->status = $this->getPipelineStatus($task);
      $status->started_at = $task->getStartTimestamp();
      $status->finished_at = $task->getCompletedTimestamp();
      $status->exit_message = $task->getExitMessage();
      $status->auth_token = $this->getPipelineAuthToken();
      $result = $this->pipelineRequest('PUT', $relative_url, json_encode($status));
      $status_code = $result->getStatusCode();
      if ($status_code < 200 || $status_code >= 300) {
        throw new \RuntimeException(sprintf('Bad result code: %d', $status_code));
      }
    } catch (\Exception $e) {
      $message = sprintf(
        'Pipeline -> Failed to notify the pipeline service of status change to "%s". Exit status: "%s", exit message: "%s", exception message: "%s".',
        TaskStatus::getLabel($task->getStatus()),
        TaskExitStatus::getLabel($task->getExitStatus()),
        $task->getExitMessage(),
        $e->getMessage()
      );
      try {
        $job_id = $this->getPipelineJobId();
      } catch (\Exception $e) {
        $job_id = 'unknown';
      }
      $message .= sprintf("\nJob ID: %s\n", $job_id);
      parent::log(
        WipLogLevel::ERROR,
        $message
      );
    }
  }

  /**
   * Returns the appropriate Pipeline status string for the specified task.
   *
   * Legal Pipeline status values are:
   *   "succeeded" - The task has completed successfully.
   *   "terminated" - The task has been terminated.
   *   "failed_by_user" - The task has failed due to user error.
   *   "failed_by_system" - The task has failed due to an internal error.
   *   "queued" - The task has not yet started.
   *   "running" - The task is currently being executed.
   *   "paused" - The task itself is paused.
   *
   * @param TaskInterface $task
   *   The task.
   *
   * @return string
   *   The Pipeline status.
   */
  private function getPipelineStatus(TaskInterface $task) {
    $result = NULL;
    $run_status_values = array(
      TaskStatus::NOT_READY => 'queued',
      TaskStatus::NOT_STARTED => 'queued',
      TaskStatus::RESTARTED => 'queued',
      TaskStatus::PROCESSING => 'running',
      TaskStatus::WAITING => 'running',
    );
    $exit_status_values = array(
      TaskExitStatus::COMPLETED => 'succeeded',
      TaskExitStatus::WARNING => 'succeeded',
      TaskExitStatus::ERROR_SYSTEM => 'failed_by_system',
      TaskExitStatus::ERROR_USER => 'failed_by_user',
      TaskExitStatus::TERMINATED => 'terminated',
    );

    if ($task->isPaused()) {
      $result = 'paused';
    } elseif ($task->getStatus() !== TaskStatus::COMPLETE) {
      $task_status = $task->getStatus();
      if (!empty($run_status_values[$task_status])) {
        $result = $run_status_values[$task_status];
      } else {
        $this->log(
          WipLogLevel::ERROR,
          sprintf(
            'Task status value %d is missing in the run_status_values in %s::%s.',
            $task_status,
            __FILE__,
            __FUNCTION__
          )
        );
        $result = $run_status_values[TaskExitStatus::ERROR_SYSTEM];
      }
    } else {
      $exit_status = $task->getExitStatus();
      if (!empty($exit_status_values[$exit_status])) {
        $result = $exit_status_values[$exit_status];
      } else {
        $this->log(
          WipLogLevel::ERROR,
          sprintf(
            'Task status value %d is missing in the exit_status_values in %s::%s.',
            $exit_status,
            __FILE__,
            __FUNCTION__
          )
        );
        $result = $run_status_values[TaskExitStatus::ERROR_SYSTEM];
      }
    }
    return $result;
  }

  /**
   * Sets the pipeline ID.
   *
   * The pipeline ID indicates which pipeline is associated with this build.
   *
   * @param string $pipeline_id
   *   The pipeline ID.
   *
   * @throws \InvalidArgumentException
   *   If the specified pipeline ID is not a string.
   */
  protected function setPipelineId($pipeline_id) {
    if (!is_string($pipeline_id)) {
      throw new \InvalidArgumentException('The "pipeline_id" parameter must be a string.');
    }
    $this->pipelineId = $pipeline_id;
  }

  /**
   * Gets the pipeline ID.
   *
   * @return string
   *   The pipeline ID.
   *
   * @throws \DomainException
   *   If the pipeline ID has not been set.
   */
  protected function getPipelineId() {
    if (empty($this->pipelineId)) {
      throw new \DomainException('The Pipeline ID has not been set.');
    }
    return $this->pipelineId;
  }

  /**
   * Sets the pipeline application ID.
   *
   * The pipeline application is the Acquia application id associated with
   * the build.
   *
   * @param string $pipeline_application
   *   The pipeline application id.
   *
   * @throws \InvalidArgumentException
   *   If the specified pipeline application is not a string.
   */
  protected function setPipelineApplicationId($pipeline_application) {
    if (!is_string($pipeline_application)) {
      throw new \InvalidArgumentException('The "pipeline_application" parameter must be a string.');
    }
    $this->pipelineApplicationId = $pipeline_application;
  }

  /**
   * Gets the pipeline Application ID.
   *
   * @return string
   *   The pipeline application ID
   *
   * @throws \DomainException
   *   If the pipeline application has not been set.
   */
  protected function getPipelineApplicationId() {
    if (empty($this->pipelineApplicationId)) {
      throw new \DomainException('The Pipeline application id has not been set.');
    }
    return $this->pipelineApplicationId;
  }

  /**
   * Sets the pipeline job ID associated with this build.
   *
   * @param string $pipeline_job_id
   *   The job ID.
   *
   * @throws \InvalidArgumentException
   *   If the pipeline job ID is not a string.
   */
  protected function setPipelineJobId($pipeline_job_id) {
    if (!is_string($pipeline_job_id)) {
      throw new \InvalidArgumentException('The "pipeline_job_id" parameter must be a string.');
    }
    $this->pipelineJobId = $pipeline_job_id;
  }

  /**
   * Gets the pipeline job ID.
   *
   * @return string
   *   The job ID.
   *
   * @throws \DomainException
   *   If the job ID has not been set.
   */
  public function getPipelineJobId() {
    if (empty($this->pipelineJobId)) {
      throw new \DomainException('The Pipeline Job ID has not been set.');
    }
    return $this->pipelineJobId;
  }

  /**
   * Sets the pipeline endpoint.
   *
   * @param string $pipeline_endpoint
   *   The base URI used to communicate with the Pipeline service.
   *
   * @throws \InvalidArgumentException
   *   If the specified pipeline_endpoint is not a string.
   */
  protected function setPipelineEndpoint($pipeline_endpoint) {
    if (!is_string($pipeline_endpoint)) {
      throw new \InvalidArgumentException('The "pipeline_endpoint" parameter must be a string.');
    }
    $this->pipelineEndpoint = $pipeline_endpoint;
  }

  /**
   * Gets the pipeline endpoint.
   *
   * @return string
   *   The pipeline endpoint.
   *
   * @throws \DomainException
   *   If the pipeline endpoint has not been set.
   */
  public function getPipelineEndpoint() {
    if (empty($this->pipelineEndpoint)) {
      throw new \DomainException('The Pipeline endpoint as not been set.');
    }
    return $this->pipelineEndpoint;
  }

  /**
   * Sets the pipeline API key.
   *
   * @param string $pipeline_api_key
   *   The API key.
   *
   * @throws \InvalidArgumentException
   *   If the specified key is not a string.
   */
  protected function setPipelineApiKey($pipeline_api_key) {
    if (!is_string($pipeline_api_key)) {
      throw new \InvalidArgumentException('The "pipeline_api_key" parameter must be a string.');
    }
    $this->securePipelineApiKey = $this->encrypt($pipeline_api_key);
  }

  /**
   * Gets the pipeline API key.
   *
   * @return string
   *   The API key.
   *
   * @throws \DomainException
   *   If the API key has not been set.
   */
  public function getPipelineApiKey() {
    if (empty($this->securePipelineApiKey)) {
      throw new \DomainException('The Pipeline API key has not been set.');
    }
    return $this->decrypt($this->securePipelineApiKey);
  }

  /**
   * Sets the pipeline API secret.
   *
   * @param string $pipeline_api_secret
   *   The secret.
   *
   * @throws \InvalidArgumentException
   *   If the secret is not a string.
   */
  protected function setPipelineApiSecret($pipeline_api_secret) {
    if (!is_string($pipeline_api_secret)) {
      throw new \InvalidArgumentException('The "pipeline_api_secret" parameter must be a string.');
    }
    $this->securePipelineApiSecret = $this->encrypt($pipeline_api_secret);
  }

  /**
   * Gets the pipeline API secret.
   *
   * @return string
   *   The API secret.
   *
   * @throws \DomainException
   *   If the secret has not been set.
   */
  public function getPipelineApiSecret() {
    if (empty($this->securePipelineApiSecret)) {
      throw new \DomainException('The Pipeline API secret has not been set.');
    }
    return $this->decrypt($this->securePipelineApiSecret);
  }

  /**
   * Sets whether the Pipeline certificate should be verified.
   *
   * The certificate of a production pipeline instance should be verified, but
   * those used in development and testing use a self-signed certificate. This
   * mechanism allows the same code to do both.
   *
   * @param bool $verify
   *   TRUE if the pipeline certificate should be verified; FALSE otherwise.
   *
   * @throws \InvalidArgumentException
   *   If the "verify" parameter is not a boolean value.
   */
  protected function setPipelineVerify($verify) {
    if (!is_bool($verify)) {
      throw new \InvalidArgumentException('The "verify" parameter must be a boolean value.');
    }
    $this->pipelineVerify = $verify;
  }

  /**
   * Indicates whether the Pipeline certificate should be verified.
   *
   * @return bool
   *   TRUE if the pipeline certificate should be verified; FALSE otherwise.
   */
  public function getPipelineVerify() {
    return $this->pipelineVerify;
  }

  /**
   * Sends a request to Pipeline.
   *
   * This method can be used to feed information back to Pipeline. For example
   * this can be used to send log data to pipeline as they are logged in Wip.
   *
   * @param string $method
   *   The HTTP method to use for the request.
   * @param string $uri
   *   The relative URI to send the request to.
   * @param mixed $request_data
   *   The payload of the request.
   * @param string $auth_token
   *   Optional. The temporary auth token associated with a job.
   *
   * @return ResponseInterface
   *   The response.
   */
  protected function pipelineRequest($method, $uri, $request_data, $auth_token = NULL) {
    $result = NULL;
    if (empty($request_data)) {
      $request_data = new \stdClass();
    }
    $options = [
      'headers' => [
        'Content-Type' => 'application/json',
      ],
    ];
    if (is_string($request_data)) {
      $options['body'] = $request_data;
    } else {
      $request_data->job_id = $this->getPipelineJobId();
      $options['body'] = json_encode($request_data);
    }

    $client = $this->getPipelineClient($auth_token);
    if (!empty($client)) {
      $request_start = microtime(TRUE);
      $result = $client->request($method, $uri, $options);
      $elapsed_time = microtime(TRUE) - $request_start;
      $body = $options['body'];
      $result_code = $result->getStatusCode();
      $result_body = $result->getBody();
      $message = <<<EOT
Sent pipeline request to $uri with body
$body
Result code: $result_code
Result body: $result_body
Elapsed time: $elapsed_time seconds
EOT;
      if ($result_code >= 200 && $result_code < 300) {
        $log_level = WipLogLevel::TRACE;
        if ($elapsed_time > 0.25) {
          $log_level = WipLogLevel::INFO;
        }
        if ($elapsed_time > 1) {
          $log_level = WipLogLevel::WARN;
        }
      } else {
        $log_level = WipLogLevel::ERROR;
      }
      $this->log($log_level, $message);
    }
    return $result;
  }

  /**
   * Adds a retry handler to the guzzle stack.
   *
   * @param HandlerStack $stack
   *   The handler stack.
   */
  public function addRetryHandler(HandlerStack $stack) {
    $logger = $this->getWipLog();
    $id = $this->getId();

    // Determine if we want to retry a request.
    $retry_limit = WipFactory::getInt('$acquia.pipeline.client.retires', 5);
    $retries = function (
      $retries,
      RequestInterface $request,
      ResponseInterface $response = NULL,
      $exception = NULL
    ) use (
      $logger,
      $retry_limit,
      $id
    ) {
      if ($retries >= $retry_limit) {
        $message = sprintf(
          'Request to %s [%s] failed. Retry limit reached.',
          $request->getUri(),
          $request->getMethod()
        );
        $logger->log(WipLogLevel::ERROR, $message, $id);
        return FALSE;
      }

      if ($exception instanceof ConnectException) {
        $message = sprintf(
          'Request to %s [%s] failed with %s. Number of retries %s. [Connection error]',
          $request->getUri(),
          $request->getMethod(),
          $exception->getMessage(),
          $retries
        );
        $logger->log(WipLogLevel::INFO, $message, $id);
        return TRUE;
      }

      if ($response) {
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
          return FALSE;
        }
        $message = sprintf(
          'Request to %s [%s] failed with %s. Number of retries %s. [HTTP Error]',
          $request->getUri(),
          $request->getMethod(),
          $response->getStatusCode(),
          $retries
        );
      } else {
        $message = sprintf(
          'Request to %s [%s] failed. Number of retries %s. [Unknown Error]',
          $request->getUri(),
          $request->getMethod(),
          $retries
        );
      }

      $logger->log(WipLogLevel::INFO, $message, $id);
      return TRUE;
    };

    // Set the delay for retries.
    $retry_delay = WipFactory::getInt('$acquia.pipeline.client.retry_delay', 0);
    $delay = function ($retries, ResponseInterface $response = NULL) use ($retry_delay) {
      // Delay in Milliseconds.
      return $retry_delay;
    };

    $stack->push(Middleware::retry($retries, $delay), 'PipelineRetries');
  }

  /**
   * Gets a Client instance appropriate for calling Pipeline endpoints.
   *
   * @param string $auth_token
   *   Optional. The temporary auth token associated with a job.
   *
   * @return Client
   *   The client.
   *
   * @throws \DomainException
   *   If this instance does not have enough information to communicate with
   *   the Pipeline service.
   */
  public function getPipelineClient($auth_token = NULL) {
    $result = NULL;
    $endpoint = $api_key = $api_secret = $job_id = NULL;
    try {
      $endpoint = $this->getPipelineEndpoint();
      $api_key = $this->getPipelineApiKey();
      $api_secret = $this->getPipelineApiSecret();
      $job_id = $this->getPipelineJobId();
    } catch (\Exception $e) {
    }
    if (!empty($endpoint)
      && !empty($api_key)
      && !empty($api_secret)
      && !empty($job_id)
    ) {
      $stack = HandlerStack::create();
      $this->addRetryHandler($stack);
      if ($auth_token === NULL) {
        $key = new Key($api_key, base64_encode($api_secret));
        $middleware = new HmacAuthMiddleware($key, 'CIStore');
        $stack->push($middleware, 'AcquiaHmacMiddleware');
      }
      $client_options = array(
        'base_uri' => $endpoint,
        'verify' => $this->getPipelineVerify(),
        'headers' => ['User-Agent' => 'WipClient/@package_version@'],
        'handler' => $stack,
      );
      $result = new Client($client_options);
    }
    return $result;
  }

  /**
   * Sets the user that invoked the pipeline.
   *
   * @param string $user
   *   The user.
   */
  private function setPipelineUser($user) {
    if (!is_string($user)) {
      throw new \InvalidArgumentException('The "user" parameter must be a string.');
    }
    if (!empty($user)) {
      $this->pipelineUser = $user;
    }
  }

  /**
   * Gets the user that invoked the pipeline.
   *
   * @return string
   *   The user.
   */
  public function getPipelineUser() {
    return $this->pipelineUser;
  }

  /**
   * Sets the temporary auth token.
   *
   * @param string $token
   *   The token.
   *
   * @throws \InvalidArgumentException
   *   If the specified auth token is not a string.
   */
  protected function setPipelineAuthToken($token) {
    if (!is_string($token)) {
      throw new \InvalidArgumentException('The "authToken" parameter must be a string.');
    }
    if (!empty($token)) {
      $this->pipelineAuthToken = $token;
    }
  }

  /**
   * Gets the temporary auth token.
   *
   * @return string
   *   The token.
   */
  public function getPipelineAuthToken() {
    if (!empty($this->pipelineAuthToken)) {
      return $this->pipelineAuthToken;
    }
    return NULL;
  }

  /**
   * Sets the GitHub merge ref.
   *
   * @param string $ref
   *   The merge ref.
   *
   * @throws \InvalidArgumentException
   *   If the specified auth token is not a string.
   */
  public function setGithubMergeRef($ref) {
    if (!is_string($ref)) {
      throw new \InvalidArgumentException('The "githubMergeRef" parameter must be a string.');
    }
    if (!empty($ref)) {
      $this->githubMergeRef = $ref;
    }
  }

  /**
   * Gets the GitHub merge ref.
   *
   * @return string
   *   The request ref.
   */
  public function getGithubMergeRef() {
    return $this->githubMergeRef;
  }

  /**
   * {@inheritdoc}
   */
  protected function addContainerOverrides(ContainerInterface $container) {
    parent::addContainerOverrides($container);

    // Pipelines environment variables needed outside of the build.
    $container->addContainerOverride('PIPELINES_PIPELINE_ID', $this->getPipelineId());
    $container->addContainerOverride('PIPELINES_APPLICATION_ID', $this->getPipelineApplicationId());
    $container->addContainerOverride('PIPELINES_JOB_ID', $this->getPipelineJobId());
    $container->addContainerOverride('PIPELINES_JOB_AUTH_TOKEN', $this->getPipelineAuthToken());
    $container->addContainerOverride('PIPELINES_API_ENDPOINT', $this->getPipelineEndpoint());
    $container->addContainerOverride('PIPELINES_VERIFY', $this->getPipelineVerify() ? 'TRUE' : 'FALSE');
    $stats_key = App::getApp()['services.metrics']['api_key'];
    $container->addContainerOverride('PIPELINES_STATS_KEY', $stats_key, TRUE);
  }

}
