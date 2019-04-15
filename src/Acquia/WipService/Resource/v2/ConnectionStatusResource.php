<?php

namespace Acquia\WipService\Resource\v2;

use Acquia\WipService\Http\HalResponse;
use Acquia\WipService\Resource\AbstractResource;
use Acquia\Wip\Runtime\WipPoolControllerInterface;
use Acquia\Wip\State\Maintenance;
use Acquia\Wip\Storage\WipPoolStoreInterface;
use Acquia\Wip\TaskExitStatus;
use Acquia\Wip\TaskStatus;
use Acquia\Wip\WipLogLevel;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a resource for checking wip service's connection status.
 */
class ConnectionStatusResource extends AbstractResource {

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
   * Creates a new instance of ConnectionStatusResource.
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
   * Checks the wip service's status.
   *
   * This is a more detailed check than the Ping endpoint, potentially
   * returning data such as the number of tasks running, etc. Not much detail
   * is included for now.
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
    $this->wipLog->log(WipLogLevel::INFO, sprintf(
      'Received connection status request from %s as user %s',
      $request->getClientIp(),
      $request->getUser()
    ));
    $app['segment']->track([
      'userId' => $request->getUser(),
      'event' => 'Connection Status',
      'context' => [
        'ip' => $request->getClientIp(),
        'userAgent' => $request->headers->get('User-Agent'),
      ],
    ]);

    $response = $this->generateResponse();
    return new HalResponse($app['hal']($request->getUri(), $response));
  }

  /**
   * Generates the response.
   *
   * @return array
   *   The response body.
   */
  protected function generateResponse() {
    return array(
      'wip' => array(
        'status' => 'OK',
        'details' => $this->getStatusDetails(),
      ),
      'system' => array(
        'status' => 'OK',
        'reason' => '',
      ),
    );
  }

  /**
   * Returns details about the wip service.
   *
   * @return array
   *   The details of the wip service.
   */
  protected function getStatusDetails() {
    $one_day_ago = time() - (24 * 60 * 60);

    $num_jobs_running = $this->storage->count(TaskStatus::PROCESSING) +
      $this->storage->count(TaskStatus::WAITING);
    $num_jobs_not_started = $this->storage->count(
      NULL,
      NULL,
      NULL,
      NULL,
      NULL,
      NULL,
      TaskExitStatus::NOT_FINISHED
    );
    $num_jobs_recently_completed = $this->storage->count(
      TaskStatus::COMPLETE,
      NULL,
      NULL,
      NULL,
      NULL,
      NULL,
      NULL,
      NULL,
      $one_day_ago
    );
    $num_jobs_paused = $this->storage->count(NULL, NULL, NULL, TRUE);
    $user_error = $this->storage->count(
      NULL,
      NULL,
      NULL,
      NULL,
      NULL,
      NULL,
      TaskExitStatus::ERROR_USER
    );
    $system_error = $this->storage->count(
      NULL,
      NULL,
      NULL,
      NULL,
      NULL,
      NULL,
      TaskExitStatus::ERROR_SYSTEM
    );
    $aborted = $this->storage->count(
      NULL,
      NULL,
      NULL,
      NULL,
      NULL,
      NULL,
      TaskExitStatus::TERMINATED
    );

    $system_paused = $this->controller->isHardPausedGlobal() ||
      $this->controller->isSoftPausedGlobal();

    $current_mode = $this->stateStore->get(
      Maintenance::STATE_NAME,
      Maintenance::$defaultValue
    );
    return array(
      'Jobs running' => $num_jobs_running,
      'Jobs not started' => $num_jobs_not_started,
      'Jobs completed (last 24 hours)' => $num_jobs_recently_completed,
      'Jobs paused' => $num_jobs_paused,
      'Jobs failed due to user error' => $user_error,
      'Jobs failed due to system error' => $system_error,
      'Jobs aborted' => $aborted,
      'System paused' => $system_paused ? 'True' : 'False',
      'Maintenance mode' => $current_mode === Maintenance::OFF ? 'False' : 'True',
      'Container version' => $this->app['config.ecs']['default']['containerDefinitions'][0]['image'],
    );
  }

}
