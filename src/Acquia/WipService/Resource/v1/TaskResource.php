<?php

namespace Acquia\WipService\Resource\v1;

use Acquia\WipIntegrations\DoctrineORM\SignalStore;
use Acquia\WipIntegrations\DoctrineORM\WipModuleStore;
use Acquia\WipIntegrations\DoctrineORM\WipPoolStore;
use Acquia\WipService\Exception\InternalServerErrorException;
use Acquia\WipService\Exception\ValidationErrorException;
use Acquia\WipService\Http\HalResponse;
use Acquia\WipService\Resource\AbstractResource;
use Acquia\WipService\Validator\Constraints\EntityFieldType;
use Acquia\WipService\Validator\Constraints\FuzzyIntegerParameter;
use Acquia\WipService\Validator\Constraints\JsonDecode;
use Acquia\Wip\Environment;
use Acquia\Wip\Exception\NoTaskException;
use Acquia\Wip\Exception\RowLockException;
use Acquia\Wip\Lock\WipPoolRowLock;
use Acquia\Wip\NativeWipModule;
use Acquia\Wip\Runtime\WipPoolControllerInterface;
use Acquia\Wip\Runtime\WipPoolInterface;
use Acquia\Wip\Signal\TaskTerminateSignal;
use Acquia\Wip\Ssh\SshKeys;
use Acquia\Wip\Storage\SignalStoreInterface;
use Acquia\Wip\Storage\WipPoolStoreInterface;
use Acquia\Wip\TaskExitStatus;
use Acquia\Wip\TaskInterface;
use Acquia\Wip\TaskPriority;
use Acquia\Wip\TaskStatus;
use Acquia\Wip\WipInterface;
use Acquia\Wip\WipModuleInterface;
use Acquia\Wip\WipModuleTaskInterface;
use Acquia\Wip\WipTaskConfig;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Teapot\StatusCode;

/**
 * Defines a REST API controller for interacting with task resources.
 */
class TaskResource extends AbstractResource {

  /**
   * The mutex lock's name for the Build Steps encoding key pair generation.
   */
  const LOCK_NAME = 'buildsteps.keygen';

  /**
   * The name of the encoding key.
   */
  const ENCODING_KEY_NAME = 'buildsteps.key';

  /**
   * The comment for the encoding key.
   */
  const ENCODING_KEY_COMMENT = 'BuildSteps Encoding Key';

  /**
   * An implementation of WipPoolInterface.
   *
   * @var WipPoolInterface
   */
  private $wipPool;

  /**
   * The Wip pool storage instance.
   *
   * @var WipPoolStore
   */
  private $storage;

  /**
   * The wip pool controller.
   *
   * @var WipPoolControllerInterface
   */
  private $controller;

  /**
   * Number of seconds to keep the lock beyond the process lifetime.
   *
   * This should be a very small number of seconds that we'll continue to hold
   * the lock beyond the amount of time we expect (the expectation is the
   * lifetime of this process), just for safety so that we don't lose the lock
   * whilst still processing.  We need to allow just a small amount of time in
   * case we had just checked the lock, and proceeded to process as the lock was
   * about to expire.  This is the amount of time that it might take to complete
   * one loop.  It is also ok to add more time here, as we will explicitly
   * release the lock when this process finishes.
   */
  const LOCK_TIMEOUT_BUFFER = 5;

  /**
   * Creates a new instance of TaskResource.
   */
  public function __construct() {
    parent::__construct();
    $this->storage = $this->dependencyManager->getDependency('acquia.wip.storage.wippool');
    $this->wipPool = $this->dependencyManager->getDependency('acquia.wip.pool');
    $this->controller = $this->dependencyManager->getDependency('acquia.wip.pool.controller');
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.lock.global' => '\Acquia\Wip\LockInterface',
      'acquia.wip.storage.wippool' => '\Acquia\Wip\Storage\WipPoolStoreInterface',
      'acquia.wip.pool' => '\Acquia\Wip\Runtime\WipPoolInterface',
      'acquia.wip.pool.controller' => '\Acquia\Wip\Runtime\WipPoolControllerInterface',
      'acquia.wip.storage.module' => '\Acquia\Wip\Storage\WipModuleStoreInterface',
      'acquia.wip.storage.module_task' => '\Acquia\Wip\Storage\WipModuleTaskStoreInterface',
      'acquia.wip.storage.signal' => 'Acquia\Wip\Storage\SignalStoreInterface',
    );
  }

  /**
   * Get an individual task.
   *
   * @param int $id
   *   The ID of the task.
   * @param Request $request
   *   The incoming request.
   * @param Application $app
   *   The application instance.
   *
   * @return HalResponse
   *   The Hypertext Application Language response.
   *
   * @throws NotFoundHttpException
   *   If the task ID is invalid.
   */
  public function getAction($id, Request $request, Application $app) {
    $this->validate(new FuzzyIntegerParameter(array(
      'name' => 'id',
      'nonZero' => TRUE,
      'nonNegative' => TRUE,
    )), $id);
    $this->checkViolations();

    $uuid = NULL;
    // The current user must be the owner of the task unless they're an admin.
    if (!$this->isAdminUser()) {
      $uuid = $request->getUser();
    }
    $task = $this->storage->get($id, $uuid);
    if (empty($task)) {
      throw new NotFoundHttpException('Resource not found.');
    }
    $response = $this->toTaskArray($task);
    $hal = $app['hal']($request->getUri(), $response);
    return new HalResponse($hal, StatusCode::OK);
  }

  /**
   * Converts a task object to an array representation.
   *
   * @param TaskInterface $task
   *   The task to convert.
   *
   * @return array
   *   An array representing the task.
   */
  private function toTaskArray(TaskInterface $task) {
    return array(
      'id' => $task->getId(),
      'claimed_time' => $task->getClaimedTimestamp(),
      'completed_time' => $task->getCompletedTimestamp(),
      'created_time' => $task->getCreatedTimestamp(),
      'delegated' => $task->isDelegated(),
      'exit_message' => $task->getExitMessage(),
      'exit_status' => $task->getExitStatus(),
      'group_name' => $task->getGroupName(),
      'lease_time' => $task->getLeaseTime(),
      'name' => $task->getName(),
      'parent' => $task->getParentId(),
      'paused' => $task->isPaused(),
      'resource_id' => $task->getResourceId(),
      'start_time' => $task->getStartTimestamp(),
      'timeout' => $task->getTimeout(),
      'uuid' => $task->getUuid(),
      'wake_time' => $task->getWakeTimestamp(),
      'class' => $task->getWipClassName(),
      'status' => $task->getStatus(),
      'priority' => $task->getPriority(),
      'is_prioritized' => $task->isPrioritized(),
      'client_job_id' => $task->getClientJobId(),
    );
  }

  /**
   * Adds a task.
   *
   * Right now, BuildSteps and Canary tasks are accepted, as well as any task
   * that has been defined by an enabled module. Any tasks with CRITICAL
   * priority will need to be run by an admin. Canary jobs should always have
   * CRITICAL priority.
   *
   * @param Request $request
   *   An instance of Request representing the HTTP request.
   * @param Application $app
   *   An instance of Application representing the application.
   *
   * @return HalResponse
   *   An instance of HalResponse representing the HTTP response.
   */
  public function postAction(Request $request, Application $app) {
    $uuid = $request->getUser();

    $json_body = $request->getContent();
    $this->validate(new JsonDecode(
      'Malformed request entity. The message payload was empty or could not be decoded.'
    ), $json_body);

    $request_body = json_decode($json_body);
    $this->validate(new EntityFieldType(array(
      'name' => 'options',
      'type' => 'object',
    )), $request_body);
    // Check for any violations early to avoid trying to use the options field
    // if it doesn't exist or is of the wrong type.
    $this->checkViolations(422);
    $options = $request_body->options;
    $app['segment']->track([
      'userId' => $request->getUser(),
      'event' => 'Add task',
      'context' => [
        'ip' => $request->getClientIp(),
        'userAgent' => $request->headers->get('User-Agent'),
      ],
      'properties' => [
        'uuid' => $uuid,
        'options' => json_decode(json_encode($options), TRUE),
      ],
    ]);
    try {
      /** @var WipModuleInterface $module */
      /** @var WipModuleTaskInterface $task */
      list($module, $task) = $this->getWipModuleFromRequest($options);

      // User is allowed to reduce the default task priority, but not increase it.
      // The lowest priority number is the highest, most critical priority.
      if (isset($options->priority)) {
        $override_priority = intval($options->priority);
        $default_priority = $task->getPriority();
        if ($default_priority < $override_priority) {
          $task->setPriority($options->priority);
        }
        if ($default_priority > $options->priority) {
          $this->logUserAction(
            sprintf(
              "User tried to set priority to %d when the most critical allowed is %d on a %s task, rejecting override",
              $options->priority,
              $default_priority,
              $task->getName()
            )
          );
        }
      }

      // Only admins can request canary jobs and jobs with high priority, but
      // priority cannot be passed in for now.
      $user_roles = $app['user']->getRoles();
      if ($task->getPriority() === TaskPriority::CRITICAL && !in_array('ROLE_ADMIN', $user_roles)) {
        throw new AccessDeniedException();
      }
      $pool_task = $this->createGenericWip($module, $task, $options, $uuid);

      $response = array(
        'task_id' => $pool_task->getId(),
      );
      $hal = $app['hal']($request->getUri(), $response);
      return new HalResponse($hal);
    } catch (AccessDeniedException $ae) {
      throw new AccessDeniedException(
        sprintf("Unable to start the task. Error message: %s", $ae->getMessage())
      );
    } catch (\Exception $e) {
      throw new UnprocessableEntityHttpException(
        sprintf("Unable to start the task. Error message: %s", $e->getMessage())
      );
    }
  }

  /**
   * Gets the WipModule instance from a request.
   *
   * @param object $options
   *   The options information from the request.
   *
   * @return array(WipModuleInterface, WipModuleTaskInterface)
   *   The module and module task instances.
   */
  private function getWipModuleFromRequest($options) {
    $wip_task = NULL;
    $module = NULL;

    if (isset($options->taskType)) {
      try {
        $module = WipModuleStore::getWipModuleStore($this->dependencyManager)
          ->getByTaskName($options->taskType);
        if (NULL === $module) {
          throw new \Exception('Not using an installed module.');
        }
        $wip_task = $module->getTask($options->taskType);
      } catch (\Exception $e) {
      }
    }
    if ($wip_task === NULL) {
      $task_type = $this->getNativeTaskName($options);
      // Look in the NativeModule for the corresponding task.
      $module = new NativeWipModule();
      $wip_task = $module->getTask($task_type);
    }
    if (empty($wip_task)) {
      throw new \RuntimeException(
        sprintf("Task type (%s) is unrecognized or has no configured module.", $options->taskType)
      );
    }

    return array($module, $wip_task);
  }

  /**
   * Gets the task name from the options.
   *
   * @param object $options
   *   The options information from the request.
   *
   * @return string
   *   The task name.
   */
  private function getNativeTaskName($options) {
    if (isset($options->taskType)) {
      $result = $options->taskType;
    } else {
      $result = 'NativeModule/BuildSteps';
    }

    if (empty($result) || 'buildsteps' === strtolower($result)) {
      $result = 'NativeModule/BuildSteps';
    } elseif ('canary' === strtolower($result)) {
      $result = 'NativeModule/CanaryWip';
    }
    return $result;
  }

  /**
   * Creates a Wip with a task type as specified in the options.
   *
   * @param WipModuleInterface $module
   *   The module instance.
   * @param WipModuleTaskInterface $task
   *   The task instance.
   * @param object $options
   *   The options object can include values for parameterDocument, vcsPath,
   *   acquiaCloudCredentials, and site. There should be enough information for
   *   WipTaskConfig to construct a ParameterDocument, so either the
   *   parameterDocument or both acquiaCloudCredentials and site need to be
   *   provided.
   * @param string $uuid
   *   The UUID of the user who started the task.
   *
   * @return TaskInterface
   *   The Task instance containing meta data about the task.
   *
   * @throws \RuntimeException
   *   If the module for this task type is not enabled.
   */
  private function createGenericWip(WipModuleInterface $module, WipModuleTaskInterface $task, $options, $uuid) {
    $task_type = $options->taskType;
    if (!$module->isEnabled()) {
      throw new \RuntimeException(
        sprintf("Unable to start the %s task because its module is not enabled.", $task_type)
      );
    }
    if (!$module->isReady()) {
      throw new \RuntimeException(
        sprintf("Unable to start the %s task because its module is not ready.", $task_type)
      );
    }

    $wip_config = new WipTaskConfig();
    $wip_config->setClassId($task->getClassName());
    $wip_config->setGroupName($task->getGroupName());

    if ($private_key = $this->getPrivateKey()) {
      $options->privateKey = $private_key;
    }
    $options->privateKeyName = self::ENCODING_KEY_NAME;

    $wip_config->setOptions($options);
    $module->requireIncludes();

    /** @var WipInterface $wip */
    $class_name = $task->getClassName();
    if (!class_exists($class_name)) {
      throw new \DomainException(sprintf('The "%s" class cannot be found.', $class_name));
    }
    $wip = new $class_name();

    // Add all of the include files. These files are loaded before the Wip
    // instance is deserialized.
    $includes = $module->getIncludes();
    $docroot = $module->getAbsolutePath($module->getDirectory());
    if (!empty($includes)) {
      foreach ($includes as $include) {
        $wip->addInclude($docroot, $include);
      }
    }

    $wip->setUuid($uuid);
    $wip->setWipTaskConfig($wip_config);
    $wip->setLogLevel($task->getLogLevel());

    // Optionally set the client job ID.
    $client_job_id = isset($options->clientJobId) ? $options->clientJobId : '';

    // Add the new task delegate.
    return $this->wipPool->addTask(
      $wip,
      new TaskPriority($task->getPriority()),
      $wip_config->getGroupName(),
      NULL,
      $client_job_id
    );
  }

  /**
   * Gets the private key used to decode encrypted values in the build document.
   *
   * @return null|string
   *   The private key for decoding encrypted values in the build document if it
   *   exists, or NULL if it has not been generated yet.
   */
  private function getPrivateKey() {
    $keys = new SshKeys();
    $keys->setRelativeKeyPath(self::ENCODING_KEY_NAME);
    $environment = Environment::getRuntimeEnvironment();
    $result = NULL;
    if ($keys->hasKey($environment)) {
      $result = file_get_contents($keys->getPrivateKeyPath($environment));
    }
    return $result;
  }

  /**
   * Pauses a single task with the given ID.
   *
   * @param int $id
   *   A task ID.
   * @param Request $request
   *   An instance of Request representing the HTTP request.
   * @param Application $app
   *   An instance of Application representing the application.
   *
   * @return HalResponse
   *   An instance of HalResponse representing the HTTP response.
   */
  public function pauseAction($id, Request $request, Application $app) {
    return $this->modifyPause('pause', $id, $request, $app);
  }

  /**
   * Terminates a single task with the given ID.
   *
   * @param int $id
   *   A task ID.
   * @param Request $request
   *   An instance of Request representing the HTTP request.
   * @param Application $app
   *   An instance of Application representing the application.
   *
   * @return HalResponse
   *   An instance of HalResponse representing the HTTP response.
   *
   * @throws NotFoundHttpException
   *   Resource not found.
   */
  public function terminateAction($id, Request $request, Application $app) {
    try {
      $id = (int) $id;
      $row_lock = WipPoolRowLock::getWipPoolRowLock($id, NULL, $this->dependencyManager);
      list($message, $status) = $row_lock->setTimeout(30)
        ->runAtomic($this, 'terminateAtomic', [$id]);
      $response = array(
        'message' => sprintf($message, $id),
      );
      $hal_response = new HalResponse($app['hal']($request->getUri(), $response));
      $hal_response->setStatusCode($status);
      return $hal_response;
    } catch (NoTaskException $e) {
      $response = array(
        'message' => $e->getMessage(),
      );
      $hal_response = new HalResponse($app['hal']($request->getUri(), $response));
      $hal_response->setStatusCode(404);
      return $hal_response;
    } catch (\RuntimeException $e) {
      $response = array(
        'message' => $e->getMessage(),
      );
      $hal_response = new HalResponse($app['hal']($request->getUri(), $response));
      $hal_response->setStatusCode(400);
      return $hal_response;
    } catch (RowLockException $e) {
      $response = array(
        'message' => sprintf('Failed to terminate task %d.', $id),
      );
      $hal_response = new HalResponse($app['hal']($request->getUri(), $response));
      $hal_response->setStatusCode(500);
      return $hal_response;
    } catch (\Exception $e) {
      throw new NotFoundHttpException($e->getMessage());
    }
  }

  /**
   * Processes the termination request.
   *
   * This is done with the associated WipPool row lock to prevent conflicts
   * with other processes.
   *
   * @param int $task_id
   *   The task ID.
   *
   * @return array
   *   The response message and status.
   */
  public function terminateAtomic($task_id) {
    /** @var WipPoolStoreInterface $pool_store */
    $pool_store = $this->dependencyManager->getDependency('acquia.wip.storage.wippool');
    /** @var TaskInterface $task */
    $task = $pool_store->get($task_id);
    if (!$task) {
      $message = 'Task %d not found.';
      $status = 404;
    } else {
      $status = $task->getStatus();
      $exit_status = $task->getExitStatus();
      $complete_exit_status = array(
        TaskExitStatus::TERMINATED,
        TaskExitStatus::ERROR_USER,
        TaskExitStatus::COMPLETED,
        TaskExitStatus::ERROR_SYSTEM,
        TaskExitStatus::WARNING,
      );
      if ($status === TaskStatus::COMPLETE ||
      in_array($exit_status, $complete_exit_status)) {
        $message = 'Task %d has already finished and cannot be terminated.';
        $status = 400;
      } else {
        if ($status === TaskStatus::NOT_STARTED) {
          // Bump the task along so the terminate will be processed before
          // tasks with the same work_id are completed.
          $task->setStatus(TaskStatus::WAITING);
          $pool_store->save($task);
        }
        /** @var SignalStoreInterface $signal_store */
        $signal_store = SignalStore::getSignalStore($this->dependencyManager);
        $signal = new TaskTerminateSignal($task_id);
        $signal_store->send($signal);
        $message = 'Task %d has been marked for termination.';
        $status = 200;
      }
    }

    return [$message, $status];
  }

  /**
   * Resumes the task with the given ID.
   *
   * @param int $id
   *   A task ID.
   * @param Request $request
   *   An instance of Request representing the HTTP request.
   * @param Application $app
   *   An instance of Application representing the application.
   *
   * @return HalResponse
   *   An instance of HalResponse representing the HTTP response.
   */
  public function resumeAction($id, Request $request, Application $app) {
    return $this->modifyPause('resume', $id, $request, $app);
  }

  /**
   * Modifies the paused state of a task.
   *
   * @param string $action
   *   The state modification action to perform. Either "pause" or "resume".
   * @param int $id
   *   A task ID.
   * @param Request $request
   *   An instance of Request representing the HTTP request.
   * @param Application $app
   *   An instance of Application representing the application.
   *
   * @return HalResponse
   *   An instance of HalResponse representing the HTTP response.
   *
   * @throws ValidationErrorException
   *   If validation of request parameters fails.
   * @throws NotFoundHttpException
   *   If the task resource is not found.
   * @throws InternalServerErrorException
   *   If the task could not have its paused state modified, or if an exception
   *   occurred during the modification operation itself.
   */
  private function modifyPause($action, $id, Request $request, Application $app) {
    $this->validate(new FuzzyIntegerParameter(array(
      'name' => 'id',
      'nonZero' => TRUE,
      'nonNegative' => TRUE,
    )), $id);
    $this->checkViolations();

    $task = $this->storage->get($id);
    if (empty($task)) {
      throw new NotFoundHttpException('Resource not found.');
    }

    // It is unlikely that pausing or resuming a task will fail as modifying
    // paused state is an idempotent operation. Regardless, an unexpected
    // exception could occur so we must protect against that here. It is more
    // likely by this point to be a server error, rather than something the
    // client has control over.
    $success = FALSE;
    $reason = NULL;
    try {
      if ($action === 'pause') {
        $success = $this->controller->pauseTask($id);
      } else {
        $success = $this->controller->resumeTask($id);
      }
    } catch (\Exception $e) {
      $reason = $e->getMessage();
    }
    if (!$success) {
      $error = sprintf('Unable to %s task %d', $action, $id);
      if ($reason !== NULL) {
        $error .= sprintf(': %s', $reason);
      }
      throw new InternalServerErrorException($error);
    }
    $this->logUserAction(sprintf(
      '%s task %d',
      $action === 'pause' ? 'Paused' : 'Resumed',
      $id
    ));

    // PUT requests should return the complete request entity where possible and
    // the location of the resource must be that of the entity itself, not the
    // pause action endpoint.
    $updated_task = $this->storage->get($id);
    $resource_location = sprintf(
      '%s%s/tasks/%d',
      $request->getSchemeAndHttpHost(),
      $request->getBaseUrl(),
      $id
    );
    $hal = $app['hal']($resource_location, $this->toTaskArray($updated_task));
    return new HalResponse($hal);
  }

}
