<?php

namespace Acquia\WipService\Resource\v1;

use Acquia\WipService\Resource\AbstractResource;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Teapot\StatusCode;

/**
 * Defines a REST API resource for fetching serialized Wip objects.
 */
class SerializedObjectResource extends AbstractResource {

  /**
   * The Wip pool storage instance.
   *
   * @var WipPoolStoreInterface
   */
  private $storage;

  /**
   * Creates a new instance of SerializedObjectResource.
   */
  public function __construct() {
    parent::__construct();
    $this->storage = $this->dependencyManager->getDependency('acquia.wip.storage.wippool');
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.storage.wippool' => '\Acquia\Wip\Storage\WipPoolStoreInterface',
    );
  }

  /**
   * Get a serialized WIP object.
   *
   * @param int $id
   *   The Wip task ID.
   * @param Request $request
   *   An instance of Request representing the incoming HTTP request.
   * @param Application $app
   *   The application instance.
   *
   * @return JsonResponse
   *   The JsonResponse instance.
   */
  public function getAction($id, Request $request, Application $app) {
    $task = $this->storage->get($id);
    if (empty($task)) {
      throw new NotFoundHttpException(sprintf(
        'No WIP object found for ID %d.',
        $id
      ));
    }
    $task->loadWipIterator();
    $task->setStatus(0);
    $task->setExitStatus(0);
    $response = array(
      'id' => $id,
      'task' => base64_encode(serialize($task)),
    );
    return new JsonResponse($response, StatusCode::OK);
  }

}
