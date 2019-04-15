<?php

use Acquia\WipService\App;
use Acquia\WipService\Silex\ApiVersionServiceProvider;
use Acquia\WipService\Silex\ConfigValidator\ServiceDescriptionConfigValidator;
use Acquia\WipService\Silex\ServiceDescriptionServiceProvider;
use Acquia\Wip\WipFactory;
use Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider;
use Igorw\Silex\ConfigServiceProvider;
use Nocarrier\Hal;
use Silex\Application;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Teapot\StatusCode;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Application();
$app['root_dir'] = dirname(__DIR__);

$app->register(new ConfigServiceProvider($app['root_dir'] . '/config/config.global.yml'));
$app->register(new ConfigServiceProvider($app['root_dir'] . '/config/config.backups.yml'));
$app->register(new ConfigServiceProvider($app['root_dir'] . '/config/config.security.yml'));
$app->register(new ConfigServiceProvider($app['root_dir'] . '/config/config.services.yml'));
$app->register(new ConfigServiceProvider($app['root_dir'] . '/config/config.containers.yml'));
$app->register(new ConfigServiceProvider($app['root_dir'] . '/config/config.db.yml'));
$app->register(new ConfigServiceProvider($app['root_dir'] . '/config/config.orm.yml'));
$app->register(new ConfigServiceProvider($app['root_dir'] . '/config/config.ecs.yml'));
$app->register(new ServiceDescriptionServiceProvider(
  $app['root_dir'] . '/config/service-description-v1.json',
  new ServiceDescriptionConfigValidator()
));
$app->register(new ServiceDescriptionServiceProvider(
  $app['root_dir'] . '/config/service-description-v2.json',
  new ServiceDescriptionConfigValidator()
));
$app->register(new ApiVersionServiceProvider());

date_default_timezone_set('UTC');

WipFactory::setConfigPath(sprintf('%s/config/config.factory.cfg', $app['root_dir']));


$app->register(new Silex\Provider\TwigServiceProvider(), array(
  'twig.path' => __DIR__ . '/../views',
));

// For local development, allow files in ~/.wip-service/config to override
// configuration.
$config_overrides_dir = getenv('HOME') . '/.wip-service/config';
// For containers, allow files in /wip/config to override configuration.
if (getenv('WIP_CONTAINERIZED')) {
  $config_overrides_dir = '/wip/config';
}

$ah_site_group = getenv('AH_SITE_GROUP');
$ah_site_env = getenv('AH_SITE_ENVIRONMENT');
$ah_database_config_file = sprintf('/var/www/site-php/%s.%s/config.json', $ah_site_group, $ah_site_env);
$is_acquia_hosting = file_exists($ah_database_config_file);
if ($is_acquia_hosting) {
  $ah_database_config = json_decode(file_get_contents($ah_database_config_file), TRUE);
  $ah_database_info = $ah_database_config['databases'][$ah_site_group];
  $ah_db_options = array(
    'dbname' => $ah_database_info['name'],
    'user' => $ah_database_info['user'],
    'password' => $ah_database_info['pass'],
    'host' => key($ah_database_info['db_url_ha']),
    'port' => $ah_database_info['port'],
  );

  // If we're on Acquia Hosting, allow files in nobackup/config to override any
  // application configuration files.
  $config_overrides_dir = sprintf('/mnt/files/%s.%s/nobackup/config', $ah_site_group, $ah_site_env);
}

// Check for any configuration override files and register them.
if (is_dir($config_overrides_dir)) {
  $finder = new Finder();
  $finder->files()->name('*.yml')->in($config_overrides_dir);
  foreach ($finder as $file) {
    // Override any keys that were set in the default /config directory with
    // those keys that are set in the override file.
    $app->register(new ConfigServiceProvider($file->getRealPath()));
  }
}

$app['debug'] = !empty($app['config.global']['debug']);

$app->register(new Silex\Provider\SecurityServiceProvider(), array(
  'security.firewalls' => array(
    'default' => array(
      'pattern' => '^.*$',
      'http' => TRUE,
      'users' => $app['security.users'],
    ),
  ),
  'security.role_hierarchy' => array(
    'ROLE_ADMIN' => array('ROLE_USER'),
  ),
  'security.access_rules' => array(
    array('^.*$', 'ROLE_USER'),
  ),
));

// Allow the database name to be overridden using an environment variable. This
// allows the tests to use a different database to the runtime application. See
// tests/bootstrap.php to see how this value is automatically set when executing
// the tests.
$database_name_override = getenv('WIP_SERVICE_DATABASE');
if (!empty($database_name_override)) {
  // We cannot indirectly modify the dbname member in the service container; we
  // have to replace the whole config.db service.
  $db_config = $app['config.db'];
  $db_config['dbname'] = $database_name_override;
  $app['config.db'] = $db_config;
}
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
  'db.options' => !empty($ah_db_options) ? $ah_db_options : $app['config.db'],
));

// The config file has the relative path to the ORM entity definition, but we'd
// need the absolute path.
foreach ($app['config.orm_options']['orm.em.options']['mappings'] as $key => $mapping) {
  if (!isset($mapping['path'])) {
    $path = __DIR__ . '/../' . ltrim($mapping['path'], '/');
    $app['config.orm_options']['orm.em.options']['mappings'][$key]['path'] = $path;
  }
}
$app->register(new DoctrineOrmServiceProvider(), $app['config.orm_options']);
$app->register(new Silex\Provider\ValidatorServiceProvider());
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

// The Bugsnag API key is passed into the wip task container during launch as an
// environment variable.
$bugsnag_api_key = getenv('BUGSNAG_API_KEY');
if (empty($bugsnag_api_key)) {
  $bugsnag_api_key = $app['services.bugsnag']['api_key'];
}
// The Bugsnag stage could either come from environment variables or from the
// application configuration.
$bugsnag_stage = getenv('BUGSNAG_STAGE');
if (empty($bugsnag_stage)) {
  $bugsnag_stage = $app['services.bugsnag']['stage'];
  if (empty($bugsnag_stage)) {
    $bugsnag_stage = getenv('AH_SITE_ENVIRONMENT');
  }
}
$app['bugsnag_stage'] = $bugsnag_stage;
$app->register(new Bugsnag\Silex\Provider\BugsnagServiceProvider(), array(
  'bugsnag.options' => array(
    'apiKey' => $bugsnag_api_key,
  ),
));
/** @var Bugsnag_Client $bugsnag */
$bugsnag = $app['bugsnag'];
$bugsnag->setReleaseStage($bugsnag_stage);
$bugsnag->setNotifyReleaseStages($app['services.bugsnag']['notify_stages']);

if (!empty($app['services.cromwell']['host'])) {
  $app->register(new Acquia\WipService\Metrics\CromwellProvider(), array(
    'cromwell.options' => array(
      'product' => $app['services.cromwell']['product'],
      'environment' => getenv('AH_SITE_ENVIRONMENT'),
      'host' => $app['services.cromwell']['host'],
      'port' => $app['services.cromwell']['port'],
    ),
  ));
}

if (!empty($app['services.segment'])) {
  $segment_options = $app['services.segment'];
  $segment_options['environment'] = getenv('AH_SITE_ENVIRONMENT');
  $app->register(new Acquia\WipService\Metrics\SegmentProvider(), array('segment.options' => $segment_options));
}

// Set the base_url for generated URLs etc.
if (!empty($app['config.global']['base_url'])) {
  $app['request_context']->setBaseUrl($app['config.global']['base_url']);
}

$app['http.allowed_methods'] = array(
  'GET',
  'POST',
  'PUT',
  'PATCH',
  'DELETE',
  'OPTIONS',
  'HEAD',
);
$app->before(function (Request $request) use ($app) {
  if ($request->getMethod() === 'OPTIONS') {
    $response = new Response();
    $response->setStatusCode(StatusCode::NO_CONTENT);
    return $response->send();
  }
}, Application::EARLY_EVENT);

// Generate routes based on the service description.
foreach ($app['api.versions'] as $api_version => $description) {
  foreach ($description['operations'] as $route_name => $route) {
    // For the operations defined in each API version, declare a canonical URL
    // containing the API version, bind the name of the operation in the service
    // description document as the route name (e.g. GetTaskV1), and set the HTTP
    // method and controller class.
    $app->match($route['uri'], $route['controller'])
      ->bind($route_name)
      ->method($route['httpMethod']);

    if ($api_version === 'v1') {
      // To avoid breaking backwards compatibility for existing clients, make v1
      // the default when no version is specified in the URL. Before the version
      // was introduced into the URL, bare paths were used to locate resources
      // (e.g. GET /tasks) and changing this would break the contract with
      // existing clients. Bind these duplicate routes to a different name
      // (by prepending "Legacy") so that they may receive special treatment
      // later on.
      $legacy_uri = preg_replace('~^/v1~', '', $route['uri']);
      $app->match($legacy_uri, $route['controller'])
        ->bind(sprintf('Legacy%s', $route_name))
        ->method($route['httpMethod']);
    }
  }
}

$app->before(function (Request $request) use ($app) {
  // If we have no trusted proxies at all, then we are unable to determine
  // whether the request is secure (see Request::isSecure() - at least one
  // trusted proxy must be set). This should be an array of the internal IPs
  // of the balancers.
  if (!empty($app['config.global']['trusted-proxies'])) {
    $request->setTrustedProxies($app['config.global']['trusted-proxies']);
  }

  if (!empty($app['config.global']['tls_require']) && !$request->isSecure() && !$app['debug']) {
    $app->abort(StatusCode::FORBIDDEN, 'TLS is required to interact with the WIP API.');
  }
});

$app->before(function (Request $request) {
  if (strpos($request->headers->get('Content-Type'), 'application/json') === 0) {
    $data = json_decode($request->getContent(), TRUE);
    $request->request->replace(is_array($data) ? $data : array());
  }
});

$app['hal'] = $app->protect(
  function ($link = NULL, array $data = array()) use ($app) {
    return new Hal($link, $data);
  }
);

$app->after(function (Request $request, Response $response) use ($app) {
  $json_response = $response instanceof JsonResponse;
  if ($json_response && !in_array($request->getMethod(), array('OPTIONS', 'HEAD'))) {
    // Always return an object to avoid JSON/JavaScript Hijacking.
    // @codingStandardsIgnoreStart
    // @see https://www.owasp.org/index.php/OWASP_AJAX_Security_Guidelines#Always_return_JSON_with_an_Object_on_the_outside
    // @codingStandardsIgnoreEnd
    $content = $response->getContent();
    if (strpos($content, '<!doctype html>') !== 0) {
      $response_content = (object) json_decode($content);
      $response->setContent(json_encode($response_content));
    }
  }
  $response->headers->set('Accept', 'application/json; version=1');

  $allowed_methods = implode(',', $app['http.allowed_methods']);
  $response->headers->set('Access-Control-Allow-Origin', '*');
  $response->headers->set('Access-Control-Allow-Methods', $allowed_methods);
  $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
});

// This is intended to be comprehensive exception handling to provide a
// standardized response body. We should probably use Api-Problem or vnd.error.
$app->error(function (\Exception $e, $code) use ($app) {
  $log_file = getenv("ACQUIA_HOSTING_DRUPAL_LOG");
  if (!empty($log_file)) {
    error_log(sprintf('%d - Process failed.', getmypid()), 3, $log_file);
  }
  $message = $e->getMessage();
  if (!empty($message)) {
    // Strip quotes from exception messages.
    $message = str_replace(array("'", '"'), '', $message);
  }
  $status = Response::$statusTexts[$code];
  $response_body = array(
    'code' => $code,
    'status' => !empty($status) ? $status : Response::$statusTexts[StatusCode::INTERNAL_SERVER_ERROR],
    'message' => !empty($message) ? $message : 'We are sorry, but something went terribly wrong.',
    'time' => date(DateTime::ISO8601),
  );
  if (method_exists($e, 'getViolations')) {
    $response_body['violations'] = $e->getViolations();
  }
  if ($app['debug']) {
    $response_body['stacktrace'] = $e->getTraceAsString();
  }
  $headers = array();
  if ($e instanceof HttpExceptionInterface) {
    $headers = $e->getHeaders();
  }

  return new JsonResponse($response_body, $code, $headers);
});

// Set the application on the global container.
App::setApp($app);
return $app;
