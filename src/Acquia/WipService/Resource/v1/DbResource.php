<?php

namespace Acquia\WipService\Resource\v1;

use Acquia\WipService\Http\HalResponse;
use Acquia\WipService\MySql\MysqlUtilityInterface;
use Acquia\WipService\Resource\AbstractResource;
use Acquia\Wip\WipLogLevel;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides REST API endpoints for managing global application state.
 */
class DbResource extends AbstractResource {

  /**
   * An implementation of MysqlUtilityInterface.
   *
   * @var MysqlUtilityInterface
   */
  private $mysqlUtility;

  /**
   * Creates a new instance of DbResource.
   */
  public function __construct() {
    parent::__construct();
    $this->mysqlUtility = $this->dependencyManager->getDependency('acquia.wipservice.mysql.utility');
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wipservice.mysql.utility' => '\Acquia\WipService\MySql\Utility',
    );
  }

  /**
   * Backup the wip service database.
   *
   * @param Request $request
   *   The current Request object.
   * @param Application $app
   *   The Application object for this application.
   *
   * @return HalResponse
   *   A HalResponse object for use in serving the response.
   */
  public function backupAction(Request $request, Application $app) {
    $this->wipLog->log(WipLogLevel::INFO, sprintf(
      'Received db backup request from %s as user %s',
      $request->getClientIp(),
      $request->getUser()
    ));
    $app['segment']->track([
      'userId' => $request->getUser(),
      'event' => 'backup',
      'context' => [
        'ip' => $request->getClientIp(),
        'userAgent' => $request->headers->get('User-Agent'),
      ],
    ]);
    try {
      $message = $this->mysqlUtility->databaseDump();
      $status = 201;
      // We have a successful backup, delete old backups.
      $this->mysqlUtility->deleteBackups();
    } catch (\Exception $e) {
      $message = $e->getMessage();
      $status = 409;
    }
    $response = array(
      'message' => $message,
    );

    $hal_response = new HalResponse($app['hal']($request->getUri(), $response));
    $hal_response->setStatusCode($status);
    return $hal_response;
  }

}
