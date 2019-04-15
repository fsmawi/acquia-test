<?php

namespace Acquia\WipService\Resource\v1;

use Acquia\WipIntegrations\DoctrineORM\Entities\WipPoolStoreEntry;
use Acquia\WipService\Exception\InternalServerErrorException;
use Acquia\WipService\Http\HalResponse;
use Acquia\WipService\Resource\AbstractResource;
use Acquia\WipService\Validator\Constraints\ChoiceParameter;
use Acquia\WipService\Validator\Constraints\FuzzyBooleanParameter;
use Acquia\WipService\Validator\Constraints\FuzzyBooleanParameterValidator;
use Acquia\WipService\Validator\Constraints\FuzzyIntegerParameter;
use Acquia\WipService\Validator\Constraints\RangeParameter;
use Acquia\WipService\Validator\Constraints\TaskPriorityParameter;
use Acquia\WipService\Validator\Constraints\TaskStatusParameter;
use Acquia\Wip\Runtime\WipPoolControllerInterface;
use Acquia\Wip\State\GroupPause;
use Acquia\Wip\Storage\WipPoolStoreInterface;
use Acquia\Wip\TaskStatus;
use Nocarrier\Hal;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\Constraints\Uuid;
use Teapot\StatusCode;

/**
 * Defines a REST API resource for interacting with collections of tasks.
 */
class TaskCollectionResource extends AbstractResource {

  /**
   * The Wip pool storage instance.
   *
   * @var WipPoolStoreInterface
   */
  private $storage;

  /**
   * The wip pool controller.
   *
   * @var WipPoolControllerInterface
   */
  private $controller;

  /**
   * The default limit.
   */
  const DEFAULT_LIMIT = 20;

  /**
   * The default page.
   */
  const DEFAULT_PAGE = 1;

  /**
   * Creates a new instance of TaskCollectionResource.
   */
  public function __construct() {
    parent::__construct();
    $this->storage = $this->dependencyManager->getDependency('acquia.wip.storage.wippool');
    $this->controller = $this->dependencyManager->getDependency('acquia.wip.pool.controller');
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.storage.wippool' => '\Acquia\Wip\Storage\WipPoolStoreInterface',
      'acquia.wip.pool.controller' => '\Acquia\Wip\Runtime\WipPoolControllerInterface',
    );
  }

  /**
   * Get a collection of tasks.
   *
   * @param Request $request
   *   An instance of Request representing the incoming HTTP request.
   * @param Application $app
   *   The application instance.
   *
   * @return HalResponse
   *   The Hypertext Application Language response.
   *
   * @throws ValidationErrorException
   *   If validation of the request parameters fails.
   * @throws NotFoundHttpException
   *   If no resources are found matching the specified parameters.
   */
  public function getAction(Request $request, Application $app) {
    $limit = $request->query->get('limit', self::DEFAULT_LIMIT);
    $page = $request->query->get('page', self::DEFAULT_PAGE);
    $sort_order = strtoupper($request->query->get('order', 'asc'));
    $status = $request->query->get('status');
    $parent = $request->query->get('parent');
    $group_name = $request->query->get('group_name');
    $paused = $request->query->get('paused');
    $priority = $request->query->get('priority');
    $uuid = $request->query->get('uuid');
    $client_job_id = $request->query->get('client_job_id');

    // @todo Add the ability to fetch tasks where the group_name starts with a
    // specified string.
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
    if ($status !== NULL) {
      $this->validate(new TaskStatusParameter('status'), $status);
    }
    if ($parent !== NULL) {
      $this->validate(new FuzzyIntegerParameter('parent'), $parent);
    }
    if ($paused !== NULL) {
      $this->validate(new FuzzyBooleanParameter('paused'), $paused);
    }
    if ($priority !== NULL) {
      $this->validate(new TaskPriorityParameter('priority'), $priority);
    }
    if ($uuid !== NULL) {
      $this->validate(new Uuid(), $uuid);
    }
    if ($client_job_id !== NULL) {
      $this->validate(new Uuid(), $client_job_id);
    }
    $this->checkViolations();

    if ($limit !== NULL) {
      $limit = (int) $limit;
    }
    if ($page !== NULL) {
      $page = (int) $page;
    }
    if ($status !== NULL) {
      $status = (int) $status;
    }
    if ($parent !== NULL) {
      $parent = (int) $parent;
    }
    if ($paused !== NULL) {
      $paused = FuzzyBooleanParameterValidator::coerce($paused);
    }
    if ($priority !== NULL) {
      $priority = (int) $priority;
    }

    // Calculate offset from page and limit parameters.
    $offset = $page * $limit - $limit;

    // Filter tasks by the current user unless they're an admin.
    if (!$this->isAdminUser()) {
      $uuid = $request->getUser();
    }

    // Load pool entries and count using specified filters.
    $tasks = $this->storage->load(
      $offset,
      $limit,
      $sort_order,
      $status,
      $parent,
      $group_name,
      $paused,
      $priority,
      $uuid,
      NULL,
      NULL,
      $client_job_id
    );
    $count = $this->storage->count(
      $status,
      $parent,
      $group_name,
      $paused,
      $priority,
      $uuid,
      NULL,
      NULL,
      NULL,
      NULL,
      $client_job_id
    );

    // Tee up a new HAL response and add the count field to it.
    /** @var Hal $hal */
    $hal = $app['hal']($request->getUri(), array('count' => $count));
    // Base URL is needed to compose the URL of each individual task resource.
    $base_url = $request->getSchemeAndHttpHost() . $request->getBaseUrl();
    /** @var WipPoolStoreEntry $entry */
    foreach ($tasks as $task) {
      $task_fields = array(
        'id' => $task->getId(),
        'parent' => $task->getParentId(),
        'name' => $task->getName(),
        'group_name' => $task->getGroupName(),
        'priority' => $task->getPriority(),
        'status' => $task->getStatus(),
        'exit_status' => $task->getExitStatus(),
        'exit_message' => $task->getExitMessage(),
        'wake_time' => $task->getWakeTimestamp(),
        'created_time' => $task->getCreatedTimestamp(),
        'start_time' => $task->getStartTimestamp(),
        'completed_time' => $task->getCompletedTimestamp(),
        'claimed_time' => $task->getClaimedTimestamp(),
        'lease_time' => $task->getLeaseTime(),
        'max_run_time' => $task->getLeaseTime(),
        'paused' => $task->isPaused(),
        'resource_id' => $task->getResourceId(),
        'uuid' => $task->getUuid(),
        'class' => $task->getWipClassName(),
        'client_job_id' => $task->getClientJobId(),
      );
      $uri = $base_url . '/tasks/' . $task_fields['id'];
      $hal->addResource('tasks', $app['hal']($uri, $task_fields));
    }
    $response = new HalResponse($hal, StatusCode::OK);
    $response->addPagingLinks($page, $limit, $count);

    return $response;
  }

  /**
   * Get a summary of task statuses.
   *
   * @param Request $request
   *   The incoming request.
   * @param Application $app
   *   The application instance.
   *
   * @return HalResponse
   *   The Hypertext Application Language response.
   */
  public function getTaskSummary(Request $request, Application $app) {
    $statuses = TaskStatus::getValues();
    $counts = array();
    foreach ($statuses as $status) {
      $label = TaskStatus::getLabel($status);
      $counts[$label] = $this->storage->count($status);
    }
    $hal = $app['hal']($request->getUri(), array('counts' => (object) $counts));
    $response = new HalResponse($hal, StatusCode::OK);
    return $response;
  }

  /**
   * Pauses a group of tasks.
   *
   * @param Request $request
   *   The incoming request.
   * @param Application $app
   *   The application instance.
   *
   * @return HalResponse
   *   The Hypertext Application Language response.
   */
  public function pauseAction(Request $request, Application $app) {
    $groups = $this->getGroupsParameter($request);

    $paused_groups = array();
    $tasks_in_progress = array();
    $reason = NULL;
    try {
      $type = $request->get('type', 'hard');
      switch ($type) {
        case 'soft':
          $tasks_in_progress = $this->controller->softPauseGroups($groups);
          $paused_groups = $this->controller->getSoftPausedGroups();
          $success = array_intersect($groups, $paused_groups) === $groups;
          $this->logUserAction(sprintf(
            'Soft-paused tasks in groups %s',
            implode(', ', $groups)
          ));
          break;

        case 'hard':
          $tasks_in_progress = $this->controller->hardPauseGroups($groups);
          $paused_groups = $this->controller->getHardPausedGroups();
          $success = array_intersect($groups, $paused_groups) === $groups;
          $this->logUserAction(sprintf(
            'Paused tasks in groups %s',
            implode(', ', $groups)
          ));
          break;

        default:
          throw new BadRequestHttpException(sprintf(
            'Unrecognised "type" query parameter value "%s". Expected either "soft" or "hard".',
            $type
          ));
      }
      $this->logUserAction(sprintf(
        'There are %d tasks in progress after issuing the %s-pause request',
        count($tasks_in_progress),
        $type
      ));
    } catch (HttpException $e) {
      throw $e;
    } catch (\Exception $e) {
      $reason = $e->getMessage();
    }
    if (!$success) {
      $error = sprintf(
        'Unable to pause tasks in groups: %s.',
        implode(', ', array_diff($groups, $paused_groups))
      );
      if ($reason !== NULL) {
        $error .= sprintf(' Reason: %s', $reason);
      }
      throw new InternalServerErrorException($error);
    }

    return $this->generateEmbeddedEntityResponse(
      $tasks_in_progress,
      $paused_groups,
      $request,
      $app
    );
  }

  /**
   * Resumes a group of tasks.
   *
   * @param Request $request
   *   An instance of Request representing the HTTP request.
   * @param Application $app
   *   An instance of Application representing the application.
   *
   * @return HalResponse
   *   An instance of HalResponse representing the HTTP response.
   */
  public function resumeAction(Request $request, Application $app) {
    $groups = $this->getGroupsParameter($request);

    $paused_groups = array();
    $success = FALSE;
    $reason = NULL;
    try {
      $success = $this->controller->resumeGroups($groups);
      $paused_groups = $this->controller->getHardPausedGroups();
      if ($success) {
        $this->logUserAction(
          sprintf('Resumed tasks in groups: %s', implode(', ', $groups))
        );
      } else {
        $this->logUserAction(
          sprintf('Failed to resumed tasks in groups: %s', implode(', ', $groups))
        );
      }
    } catch (\Exception $e) {
      $reason = $e->getMessage();
    }
    if (!$success) {
      $error = sprintf(
        'Unable to resume tasks in groups: %s.',
        implode(', ', array_diff($groups, $paused_groups))
      );
      if ($reason !== NULL) {
        $error .= sprintf(' Reason: %s', $reason);
      }
      throw new InternalServerErrorException($error);
    }

    $tasks_in_progress = $this->controller->getTasksInProgress($groups);
    return $this->generateEmbeddedEntityResponse(
      $tasks_in_progress,
      $paused_groups,
      $request,
      $app
    );
  }

  /**
   * Generates an HTTP response for group pause and resume requests.
   *
   * @param Task[] $tasks
   *   An array of tasks that are currently in progress.
   * @param string[] $paused_groups
   *   The whole set of paused groups.
   * @param Request $request
   *   An instance of Request representing the HTTP request.
   * @param Application $app
   *   An instance of Application representing the application.
   *
   * @return HalResponse
   *   An instance of HalResponse representing the HTTP response.
   */
  private function generateEmbeddedEntityResponse($tasks, $paused_groups, Request $request, Application $app) {
    $count = count($tasks);
    // Generate partial task entities which will be embedded in the response.
    $resources = $this->toPartials($tasks);
    $response = array(
      'count' => $count,
      'paused_groups' => $paused_groups,
    );
    $hal = $app['hal']($request->getUri(), $response);
    // Embed the task entity resources.
    foreach ($resources as $resource) {
      $uri = sprintf(
        '%s%s/tasks/%d',
        $request->getSchemeAndHttpHost(),
        $request->getBaseUrl(),
        $resource['id']
      );
      $hal->addResource('tasks_in_progress', $app['hal']($uri, $resource));
    }
    return new HalResponse($hal);
  }

  /**
   * Converts an array of task entities into partial representations.
   *
   * The purpose of this method is to provide a standardized partial task
   * representation. Whenever a partial task entity is required, this format
   * should be used.
   *
   * @param Task[] $tasks
   *   An array of task entities.
   *
   * @return array
   *   An array of partial task representations.
   */
  public static function toPartials($tasks) {
    $result = array();
    foreach ($tasks as $task) {
      $result[] = array(
        'id' => $task->getId(),
        'group_name' => $task->getGroupName(),
        'status' => $task->getStatus(),
      );
    }
    return $result;
  }

  /**
   * Gets and validates the groups query parameter.
   *
   * @param Request $request
   *   An instance of Request representing the HTTP request.
   *
   * @return string[]|null
   *   An array of group names, or NULL if none were provided or the groups
   *   parameter was malformed and could not be parsed.
   *
   * @throws UnprocessableEntityHttpException
   *   If the groups parameter is empty after attempting to parse the
   *   comma-separated list.
   */
  private function getGroupsParameter(Request $request) {
    $groups = $request->get('groups');
    if (!GroupPause::isValidValue($groups)) {
      throw new UnprocessableEntityHttpException(
        'Missing required "groups" query parameter. Expected a comma-separated list of group_name values.'
      );
    }
    return explode(',', $groups);
  }

  /**
   * Gets all tasks that are currently in the PROCESSING state.
   *
   * @param Request $request
   *   An instance of Request representing the HTTP request.
   * @param Application $app
   *   An instance of Application representing the application.
   *
   * @return HalResponse
   *   An instance of HalResponse representing the HTTP response.
   */
  public function getInProcessingAction(Request $request, Application $app) {
    $tasks_in_processing = $this->controller->getTasksInProcessing();
    $count = count($tasks_in_processing);

    $hal = $app['hal']($request->getUri(), array('count' => $count));
    // Base URL is needed to compose the URL of each individual task resource.
    $base_url = $request->getSchemeAndHttpHost() . $request->getBaseUrl();
    /** @var WipPoolStoreEntry $entry */
    foreach ($tasks_in_processing as $task) {
      $task_fields = array(
        'id'             => $task->getId(),
        'parent'         => $task->getParentId(),
        'name'           => $task->getName(),
        'group_name'     => $task->getGroupName(),
        'priority'       => $task->getPriority(),
        'status'         => $task->getStatus(),
        'exit_status'    => $task->getExitStatus(),
        'exit_message'   => $task->getExitMessage(),
        'wake_time'      => $task->getWakeTimestamp(),
        'created_time'   => $task->getCreatedTimestamp(),
        'start_time'     => $task->getStartTimestamp(),
        'completed_time' => $task->getCompletedTimestamp(),
        'claimed_time'   => $task->getClaimedTimestamp(),
        'lease_time'     => $task->getLeaseTime(),
        'max_run_time'   => $task->getLeaseTime(),
        'paused'         => $task->isPaused(),
        'resource_id'    => $task->getResourceId(),
        'uuid'           => $task->getUuid(),
        'class'          => $task->getWipClassName(),
      );
      $uri = $base_url . '/tasks/' . $task_fields['id'];
      $hal->addResource('tasks', $app['hal']($uri, $task_fields));
    }
    $response = new HalResponse($hal, StatusCode::OK);
    $response->addPagingLinks(self::DEFAULT_PAGE, self::DEFAULT_LIMIT, $count);

    return $response;
  }

}
