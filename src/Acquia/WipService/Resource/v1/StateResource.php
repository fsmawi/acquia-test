<?php

namespace Acquia\WipService\Resource\v1;

use Acquia\WipService\Http\HalResponse;
use Acquia\WipService\Resource\AbstractResource;
use Acquia\WipService\Validator\Constraints\EntityFieldType;
use Acquia\WipService\Validator\Constraints\GlobalPauseMode;
use Acquia\WipService\Validator\Constraints\JsonDecode;
use Acquia\WipService\Validator\Constraints\MaintenanceMode;
use Acquia\WipService\Validator\Constraints\MonitorDaemonPauseMode;
use Acquia\Wip\Metrics\MetricsRelayInterface;
use Acquia\Wip\Runtime\WipPoolControllerInterface;
use Acquia\Wip\State\GlobalPause;
use Acquia\Wip\State\Maintenance;
use Acquia\Wip\State\MonitorDaemonPause;
use Acquia\Wip\WipLogLevel;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Teapot\StatusCode;

/**
 * Provides REST API endpoints for managing global application state.
 */
class StateResource extends AbstractResource {

  /**
   * The timer name pattern for metrics.
   */
  const TIMER_PATTERN = 'wip.system.outages.%s';

  /**
   * The Wip pool controller.
   *
   * @var WipPoolControllerInterface
   */
  private $controller;

  /**
   * A list of supported state names.
   *
   * @var string[]
   */
  private $allowedStates = array(
    Maintenance::STATE_NAME,
    GlobalPause::STATE_NAME,
    MonitorDaemonPause::STATE_NAME,
  );

  /**
   * An associative array of state names and their default values.
   *
   * @var array
   */
  private $defaultValues = array();

  /**
   * The interface to send the timing metrics to.
   *
   * @var MetricsRelayInterface
   */
  private $relay;

  /**
   * Creates a new instance of StateResource.
   */
  public function __construct() {
    parent::__construct();
    $this->controller = $this->dependencyManager->getDependency('acquia.wip.pool.controller');
    $this->defaultValues = array(
      Maintenance::STATE_NAME => Maintenance::$defaultValue,
      GlobalPause::STATE_NAME => GlobalPause::$defaultValue,
      MonitorDaemonPause::STATE_NAME => MonitorDaemonPause::$defaultValue,
    );

    $this->relay = $this->dependencyManager->getDependency('acquia.wip.metrics.relay');
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.pool.controller' => 'Acquia\Wip\Runtime\WipPoolControllerInterface',
      'acquia.wip.metrics.relay' => 'Acquia\Wip\Metrics\MetricsRelayInterface',
    );
  }

  /**
   * Gets the current value of the given state name.
   *
   * @param string $name
   *   The name of the state.
   * @param Request $request
   *   The Request object.
   * @param Application $app
   *   The silex Application object.
   *
   * @return HalResponse
   *   The Hypertext Application Language response.
   *
   * @throws NotFoundHttpException
   *   If the state resource does not exist.
   * @throws BadRequestHttpException
   *   If the state could not be fetched from the storage layer.
   */
  public function getAction($name, Request $request, Application $app) {
    $this->validateStateName($name);

    try {
      $response = $this->getStateResponse($name, $request, $app);
    } catch (\InvalidArgumentException $e) {
      throw new BadRequestHttpException(
        sprintf('Unable to get state. Reason: %s', $e->getMessage())
      );
    }
    return $response;
  }

  /**
   * Modifies the state of the given state name.
   *
   * @param string $name
   *   The state name to set.
   * @param Request $request
   *   The Request object.
   * @param Application $app
   *   The silex Application object.
   *
   * @return HalResponse
   *   The Hypertext Application Language response.
   *
   * @throws NotFoundHttpException
   *   If the state name is not supported.
   * @throws ValidationErrorException
   *   If the request body is malformed.
   * @throws \Exception
   *   If an unexpected error occurs.
   */
  public function putAction($name, Request $request, Application $app) {
    $this->validateStateName($name);

    $json_body = $request->getContent();
    $this->validate(new JsonDecode(
      'Malformed request entity. The message payload was empty or could not be decoded.'
    ), $json_body);

    $body = json_decode($json_body, TRUE);
    $this->validate(new EntityFieldType('value'), $body);
    // Check for any violations early to avoid trying to use the value field if
    // it doesn't exist on the request entity.
    $this->checkViolations(422);
    $value = $body['value'];

    switch ($name) {
      case Maintenance::STATE_NAME:
        $this->validate(new MaintenanceMode('value'), $value);
        break;

      case GlobalPause::STATE_NAME:
        $this->validate(new GlobalPauseMode('value'), $value);
        break;

      case MonitorDaemonPause::STATE_NAME:
        $this->validate(new MonitorDaemonPauseMode('value'), $value);
        break;
    }
    $this->checkViolations(422);

    try {
      switch ($name) {
        case Maintenance::STATE_NAME:
          $this->setMaintenanceMode($value);
          break;

        case GlobalPause::STATE_NAME:
          $this->setGlobalPauseMode($value);
          break;

        case MonitorDaemonPause::STATE_NAME:
          $this->setMonitorDaemonPauseMode($value);
          break;
      }
    } catch (\Exception $e) {
      $message = sprintf('Error updating state %s to %s: %s', $name, $value, $e->getMessage());
      $this->wipLog->log(WipLogLevel::FATAL, $message);
      throw $e;
    }

    $response = $this->getStateResponse($name, $request, $app);
    return $response;
  }

  /**
   * Sets maintenance mode.
   *
   * @param string $value
   *   The maintenance mode value.
   */
  private function setMaintenanceMode($value) {
    if ($value === Maintenance::OFF) {
      // For the case in which OFF is being set, we actually delete the state.
      $this->deleteState(Maintenance::STATE_NAME);
    } else {
      $this->setState(Maintenance::STATE_NAME, $value);
    }
  }

  /**
   * Sets monitor daemon pause mode.
   *
   * @param string $value
   *   The monitor daemon pause mode value.
   */
  private function setMonitorDaemonPauseMode($value) {
    if ($value === MonitorDaemonPause::OFF) {
      // For the case in which OFF is being set, we actually delete the state.
      $this->deleteState(MonitorDaemonPause::STATE_NAME);
    } else {
      $this->setState(MonitorDaemonPause::STATE_NAME, $value);
    }
  }

  /**
   * Sets pause mode.
   *
   * @param string $value
   *   The pause mode value.
   */
  protected function setGlobalPauseMode($value) {
    switch ($value) {
      case GlobalPause::OFF:
        // For the case in which OFF is being set, we actually delete the state.
        $this->deleteState(GlobalPause::STATE_NAME);
        break;

      case GlobalPause::SOFT_PAUSE:
        $this->controller->softPauseGlobal();
        $this->logUserAction('Enabled global soft-pause');
        break;

      case GlobalPause::HARD_PAUSE:
        $this->controller->hardPauseGlobal();
        $this->logUserAction('Enabled global hard-pause');
        break;
    }
  }

  /**
   * Deletes the current value of the given state name.
   *
   * @param string $name
   *   The state name to delete.
   * @param Request $request
   *   The Request object.
   * @param Application $app
   *   The silex Application object.
   *
   * @return HalResponse
   *   The Hypertext Application Language response.
   *
   * @throws NotFoundHttpException
   *   If given state name is not supported.
   * @throws \Exception
   *   If an unexpected error occurs.
   */
  public function deleteAction($name, Request $request, Application $app) {
    $this->validateStateName($name);

    try {
      $this->deleteState($name);
      $response = $this->getStateResponse($name, $request, $app);
    } catch (\Exception $e) {
      $message = sprintf('Error deleting state %s: %s', $name, $e->getMessage());
      $this->wipLog->log(WipLogLevel::FATAL, $message);
      throw $e;
    }

    return $response;
  }

  /**
   * Ensures that the state name is supported.
   *
   * @param mixed $name
   *   The name of a state.
   *
   * @throws NotFoundHttpException
   *   If the given state name is not supported.
   */
  private function validateStateName($name) {
    if ($name === NULL || !is_string($name) || !in_array($name, $this->allowedStates)) {
      throw new NotFoundHttpException('Resource not found.');
    }
  }

  /**
   * Gets the state response.
   *
   * @param string $state_name
   *   The string representation of the state.
   * @param Request $request
   *   The Request object.
   * @param Application $app
   *   The silex Application object.
   *
   * @return HalResponse
   *   The HalResponse instance.
   */
  private function getStateResponse($state_name, Request $request, Application $app) {
    $state_value = $this->stateStore->get(
      $state_name,
      $this->defaultValues[$state_name]
    );
    $tasks = $this->controller->getTasksInProgress();

    $response = array(
      'key' => $state_name,
      'value' => $state_value,
      'tasks_in_progress' => TaskCollectionResource::toPartials($tasks),
    );

    $hal = $app['hal']($request->getUri(), $response);
    return new HalResponse($hal);
  }

  /**
   * Sets the state of a given key to the specified value.
   *
   * @param string $name
   *   The name of the state.
   * @param mixed $value
   *   The value of the state.
   */
  private function setState($name, $value) {
    $this->stateStore->set($name, $value);
    $this->logUserAction(sprintf('Set state %s to %s', $name, var_export($value, TRUE)));
  }

  /**
   * Deletes a state by name.
   *
   * @param string $name
   *   The name of the state.
   */
  private function deleteState($name) {
    // Get the timestamp when the pause was put into effect.
    $timestamp = $this->stateStore->getChangedTime($name);

    $this->stateStore->delete($name);
    $current_time = time();
    $elapsed_time = $current_time - $timestamp;

    // Relay the correct time.
    $timer_name = sprintf(self::TIMER_PATTERN, $name);
    $this->relay->timing($timer_name, $elapsed_time);
    $this->logUserAction(sprintf('Deleted state %s', $name));
  }

}
