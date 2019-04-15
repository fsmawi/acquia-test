<?php

namespace Acquia\WipService\Resource\v1;

use Acquia\WipIntegrations\DoctrineORM\WipModuleStore;
use Acquia\WipIntegrations\DoctrineORM\WipModuleTaskStore;
use Acquia\WipService\Http\HalResponse;
use Acquia\WipService\Resource\AbstractResource;
use Acquia\WipService\Validator\Constraints\ChoiceParameter;
use Acquia\WipService\Validator\Constraints\EntityFieldType;
use Acquia\WipService\Validator\Constraints\JsonDecode;
use Acquia\Wip\Objects\AddModule;
use Acquia\Wip\Runtime\WipPool;
use Acquia\Wip\TaskPriority;
use Acquia\Wip\WipModule;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Teapot\StatusCode;

/**
 * Defines a REST API controller for interacting with module resources.
 */
class ModuleResource extends AbstractResource {

  /**
   * The Wip module storage instance.
   *
   * @var WipModuleStore
   */
  private $moduleStorage;

  /**
   * Creates a new instance of ModuleResource.
   */
  public function __construct() {
    parent::__construct();
    $this->moduleStorage = $this->dependencyManager->getDependency('acquia.wip.storage.module');
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.storage.module' => '\Acquia\Wip\Storage\WipModuleStoreInterface',
    );
  }

  /**
   * Adds a module.
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
      'name' => 'name',
      'type' => 'string',
    )), $request_body->name);
    $this->validate(new EntityFieldType(array(
      'name' => 'vcs-uri',
      'type' => 'string',
    )), $request_body->{'vcs-uri'});
    $this->validate(new EntityFieldType(array(
      'name' => 'commit-tag',
      'type' => 'string',
    )), $request_body->{'commit-tag'});
    $this->validate(new ChoiceParameter(array(
      'name' => 'enabled',
      'choices' => array('0', '1'),
    )), $request_body->enabled);

    $this->checkViolations(422);
    $options = $request_body->options;
    $app['segment']->track([
      'userId' => $request->getUser(),
      'event' => 'Add module',
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
      $module = new WipModule(
        $request_body->name,
        $request_body->{'vcs-uri'},
        $request_body->{'commit-tag'},
        $request_body->enabled
      );

      // Add the module to all of the webnodes.
      $add_wip = new AddModule();
      $add_wip->setModule($module);
      $add_wip->setUuid('admin');
      $wip_pool = new WipPool();

      $task = $wip_pool->addTask($add_wip, new TaskPriority(TaskPriority::HIGH));

      $response = array(
        'successful' => TRUE,
        'task_id' => $task->getId(),
      );
      $hal = $app['hal']($request->getUri(), $response);
      return new HalResponse($hal);
    } catch (\Exception $e) {
      throw new UnprocessableEntityHttpException(
        sprintf("Unable to add the module. Error message: %s", $e->getMessage())
      );
    }
  }

}
