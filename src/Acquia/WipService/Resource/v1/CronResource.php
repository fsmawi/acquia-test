<?php

namespace Acquia\WipService\Resource\v1;

use Acquia\WipService\Http\HalResponse;
use Acquia\WipService\Resource\AbstractResource;
use Acquia\Wip\Objects\Cron\CronConfig;
use Acquia\Wip\Objects\Cron\CronController;
use Acquia\Wip\Objects\ParameterDocument;
use Acquia\Wip\Runtime\WipPool;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Teapot\StatusCode;

/**
 * Defines a resource for managing tasks that represent cron jobs.
 */
class CronResource extends AbstractResource {

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array();
  }

  /**
   * Adds a cron WIP task.
   *
   * @param Request $request
   *   The current Request object.
   * @param Application $app
   *   The Application object for this application.
   *
   * @return \Acquia\WipService\Http\HalResponse
   *   A HalResponse object for use in serving the response.
   */
  public function postAction(Request $request, Application $app) {
    $uuid = $request->getUser();

    // @TODO - validation of param doc before constructing?

    // @TODO - validation of options

    // The cron interval expression would ideally be a required parameter rather
    // than an option on POST requests, but it is set as an option for
    // consistency with the PUT method, where it is optional.
    $cron_interval = $request->query->get('interval');
    if (empty($cron_interval)) {
      throw new UnprocessableEntityHttpException(
        'Cron interval setting is required when creating a new cron configuration via POST.'
      );
    }
    // @TODO - real validation of the cron expression.

    $task = $this->addCron(15, $cron_interval, $request, $uuid);

    $data = array(
      'data' => array('task' => $task),
    );
    $hal = $app['hal']($request->getUri(), $data);
    return new HalResponse($hal, StatusCode::OK);
  }

  /**
   * Replaces an existing cron WIP task.
   *
   * @param int $id
   *   An ID to use to identify the cron job.
   * @param Request $request
   *   The Request object.
   * @param Application $app
   *   The silex Application object.
   */
  public function putAction($id, Request $request, Application $app) {
    throw new BadRequestHttpException(
      'Editing an existing cron configuration is not currently supported.'
    );
  }

  /**
   * Adds a cron task, based on provided parameters.
   *
   * @param int $id
   *   An ID to use to identify the cron job.
   * @param string $cron_interval
   *   A cron interval expression string.
   * @param Request $request
   *   The Request object.
   * @param string $uuid
   *   The UUID of the user adding the cron task.
   *
   * @return \Acquia\Wip\Task
   *   A WIP Task object representing the cron configuration that was just
   *   added.
   */
  private function addCron($id, $cron_interval, Request $request, $uuid) {
    $label = $request->query->get('label', 'CRON [REST]');
    $command = $request->query->get('command', 'cron');
    $processes = $request->query->get('processes', 1.0);

    $cron_config = new CronConfig($id, $label, $cron_interval, $command, $processes);

    $parameter_document = new ParameterDocument(
      $request->getContent(),
      array('siteGroups' => 'Acquia\Wip\Objects\SiteGroup')
    );

    $wip = new CronController();
    $wip->setUuid($uuid);
    $wip->setParameterDocument($parameter_document);
    $wip->setCronConfig($cron_config);

    // @TODO - I think we'll remove this after testing.  Log levels can be set
    // in config, or could be specified as an option for any request that
    // results in a WIP object being created.
    $wip->setLogLevel(6);

    $pool = new WipPool();

    return $pool->addTask($wip);
  }

}
