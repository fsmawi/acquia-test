<?php

namespace Acquia\WipService\Resource;

use Acquia\WipService\App;
use Acquia\WipService\Exception\ValidationErrorException;
use Acquia\WipService\Silex\ConfigValidator\ServiceDescriptionConfigValidator;
use Acquia\WipService\Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Acquia\WipService\Validator\Constraints;
use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\DependencyManager;
use Acquia\Wip\Environment;
use Acquia\Wip\Metrics\MetricsRelayInterface;
use Acquia\Wip\State\Maintenance;
use Acquia\Wip\Storage\StateStoreInterface;
use Acquia\Wip\WipLogLevel;
use Silex\Application;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Validator\Constraint;
use Teapot\StatusCode;

/**
 * Defines an abstract resource to encapsulate behavior common to all resources.
 */
abstract class AbstractResource implements DependencyManagedInterface {

  const MAINTENANCE_WINDOW_MINUTES = 5;

  const METRIC_PREFIX = 'wip.system.outages.maintenance.';

  /**
   * The application instance.
   *
   * @var App
   */
  protected $app;

  /**
   * The incoming request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  private $request;

  /**
   * The validator service.
   *
   * @var \Symfony\Component\Validator\Validator
   */
  private $validator;

  /**
   * The route currently being requested.
   *
   * @var array
   *
   * @see config/service-description.json
   */
  private $route;

  /**
   * An array containing any violations that occurred during the request.
   *
   * @var array
   */
  private $violations = array();

  /**
   * Dependency manager.
   *
   * @var DependencyManager
   */
  protected $dependencyManager;

  /**
   * The Wip log instance.
   *
   * @var WipLogInterface
   */
  protected $wipLog;

  /**
   * The state storage instance.
   *
   * @var StateStoreInterface
   */
  protected $stateStore;

  /**
   * The interface to send the timing metrics to.
   *
   * @var MetricsRelayInterface
   */
  private $relay;

  /**
   * Provides superclass functionality for resource implementations.
   */
  public function __construct() {
    $this->dependencyManager = new DependencyManager();
    $dependencies = $this->getCommonDependencies();
    $dependencies = array_merge($dependencies, $this->getDependencies());
    $this->dependencyManager->addDependencies($dependencies);
    $this->wipLog = $this->dependencyManager->getDependency('acquia.wip.wiplog');
    $this->stateStore = $this->dependencyManager->getDependency('acquia.wip.storage.state');
    $this->relay = $this->dependencyManager->getDependency('acquia.wip.metrics.relay');

    $this->app = App::getApp();
    $this->validator = $this->app['validator'];
    $this->request = $this->app['request'];

    $api_version = $this->getApiVersion();
    $route_name = $this->getRouteName();
    $this->route = $this->app['api.versions'][$api_version]['operations'][$route_name];

    $this->authorizeUser();
    $this->checkIfAllowedDuringMaintenance();
    $this->validateRequestParameters();
  }

  /**
   * Get dependencies that are common for all resources.
   *
   * @return array
   *   The common dependencies.
   */
  protected function getCommonDependencies() {
    return array(
      'acquia.wip.wiplog' => '\Acquia\Wip\Implementation\WipLog',
      'acquia.wip.storage.state' => 'Acquia\Wip\Storage\StateStoreInterface',
      'acquia.wip.metrics.relay' => 'Acquia\Wip\Metrics\MetricsRelayInterface',
    );
  }

  /**
   * {@inheritdoc}
   */
  abstract public function getDependencies();

  /**
   * Gets the name of the route.
   *
   * This method returns the name of the operation being performed by the client
   * from the service description document.
   *
   * @return string
   *   The name of the route.
   */
  public function getRouteName() {
    return preg_replace('~^Legacy~', '', $this->request->attributes->get('_route'));
  }

  /**
   * Gets the requested API version.
   *
   * @return string
   *   The API version e.g. "v1".
   */
  public function getApiVersion() {
    return $this->app['api.version'];
  }

  /**
   * Returns whether the current user is an admin.
   *
   * @return bool
   *   Whether the current user is in the admin role.
   */
  public function isAdminUser() {
    /** @var AuthorizationChecker $authorization */
    $authorization = $this->app['security.authorization_checker'];
    return $authorization->isGranted('ROLE_ADMIN');
  }

  /**
   * Sends an access denied response.
   *
   * @param string $reason
   *   (optional) The reason why access was denied.
   *
   * @throws AccessDeniedHttpException
   *   Always thrown when this method is called.
   */
  public function accessDenied($reason = NULL) {
    if ($reason === NULL) {
      $reason = 'Access to this resource is restricted.';
    }
    throw new AccessDeniedHttpException($reason);
  }

  /**
   * Validates a value against a constraint.
   *
   * @param Constraint $constraint
   *   An constraint to validate the value against.
   * @param mixed $value
   *   The value being validated.
   *
   * @throws BadRequestHttpException
   *   If a violation occurs in validating the value.
   */
  protected function validate(Constraint $constraint, $value) {
    $violations = $this->validator->validateValue($value, $constraint);
    if (count($violations) > 0) {
      $this->violations[] = $violations;
    }
  }

  /**
   * Throws an exception if any violations have accumulated.
   *
   * @param int $code
   *   Optional. The HTTP error code.
   *
   * @throws ValidationErrorException
   *   If the request body is malformed.
   */
  public function checkViolations($code = 400) {
    if (count($this->violations) > 0) {
      throw new ValidationErrorException(
        'An error occurred during validation.',
        $this->violations,
        $code
      );
    }
  }

  /**
   * Validates request parameters against the service description.
   *
   * @see config/service-description.json
   */
  private function validateRequestParameters() {
    $route_parameters = array();
    if (!empty($this->route['parameters'])) {
      $route_parameters = $this->route['parameters'];
    }

    // Validate allowed parameters.
    $allowed_constraint = new Constraints\AllowedParameters(array(
      'routeParameters' => $route_parameters,
    ));
    $this->validate($allowed_constraint, $this->request);

    // Validate required parameters.
    $required_constraint = new Constraints\RequiredParameters(array(
      'routeParameters' => $route_parameters,
    ));
    $this->validate($required_constraint, $this->request);
  }

  /**
   * Authorizes the user based on role and route access configuration.
   *
   * @see config/service-description.json
   */
  private function authorizeUser() {
    // If access is set to true in the service description, allow access.
    if ($this->route['access'] === TRUE) {
      return;
    }

    // If access is defined as a list of roles and the user has one of the
    // enumerated roles, allow access.
    if (is_array($this->route['access'])) {
      $access_roles = $this->route['access'];
      $user_roles = $this->app['user']->getRoles();
      if (!empty(array_intersect($access_roles, $user_roles))) {
        return;
      }
    }

    // For all other cases, deny access.
    $this->accessDenied();
  }

  /**
   * Checks if the route is allowed in the current maintenance mode.
   *
   * @throws ServiceUnavailableHttpException
   *   If the route is not allowed to operate in the current maintenance mode.
   */
  private function checkIfAllowedDuringMaintenance() {
    $current_mode = $this->stateStore->get(
      Maintenance::STATE_NAME,
      Maintenance::$defaultValue
    );

    // When maintenance mode is disabled, allow all routes to operate as normal.
    if ($current_mode === Maintenance::OFF) {
      return;
    }

    // To allow a route to operate when maintenance mode is enabled, the route
    // must be configured as such in the service description document. Here, we
    // check the route to see if it should be allowed in the current mode.
    $key = ServiceDescriptionConfigValidator::ALLOWED_DURING_MAINTENANCE;
    if (!empty($this->route[$key]) && is_array($this->route[$key])) {
      $allowed = $this->route[$key];
      if (in_array($current_mode, $allowed, TRUE)) {
        return;
      }
    }

    // Construct a namespace for the metric, replacing all slashes in the URI
    // with dots, then add a count to it.
    $uri = str_replace('/', '.', $this->route['uri']);
    $method = strtolower($this->route['httpMethod']);
    $namespace = self::METRIC_PREFIX . $method . $uri;
    $this->relay->count($namespace, 1);

    // Log 503 in access.log if it exists.
    $sitegroup = Environment::getRuntimeSitegroup();
    $name = Environment::getRuntimeEnvironmentName();

    $path = sprintf('/var/log/sites/%s.%s/logs', $sitegroup, $name);

    if (is_readable($path)) {
      $server = shell_exec(sprintf('ls %s', $path));
      $server = trim($server);
      $log = sprintf('%s/%s/access.log', $path, $server);
      if (is_readable($log)) {
        $message = sprintf(
          "[%s] Returning 503 for request %s %s\n",
          date("d/M/Y:H:i:s O"),
          $this->route['httpMethod'],
          $this->route['uri']
        );
        error_log($message, 3, $log);
      }
    }

    $headers = array(
      'X-Maintenance-Mode' => $current_mode,
    );
    $this->serviceUnavailable('This service is in maintenance mode.', $headers);
  }

  /**
   * Sends a service unavailable response.
   *
   * @param string $reason
   *   The reason why access was denied.
   * @param array $headers
   *   An array of HTTP headers to add to the response.
   *
   * @throws ServiceUnavailableHttpException
   *   Always thrown when this method is called.
   */
  public function serviceUnavailable($reason = NULL, $headers = array()) {
    if ($reason === NULL) {
      $reason = 'This service is currently unavailable.';
    }
    $headers['Retry-After'] = self::MAINTENANCE_WINDOW_MINUTES * 60;
    // Send metric to stasd.
    throw new ServiceUnavailableHttpException($headers, $reason);
  }

  /**
   * Logs a user action.
   *
   * @param string $message
   *   The message describing the action.
   */
  public function logUserAction($message) {
    $this->wipLog->log(
      WipLogLevel::INFO,
      sprintf(
        '%s on behalf of user %s from %s',
        $message,
        $this->request->getUser(),
        $this->request->getClientIp()
      )
    );
  }

}
