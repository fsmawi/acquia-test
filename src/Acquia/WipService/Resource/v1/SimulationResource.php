<?php

namespace Acquia\WipService\Resource\v1;

use Acquia\WipService\Resource\AbstractResource;
use Acquia\Wip\StateTableIteratorInterface;
use Acquia\Wip\Storage\WipPoolStoreInterface;
use Acquia\Wip\Task;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Teapot\StatusCode;

/**
 * Defines a REST API resource for handling simulation transcripts.
 */
class SimulationResource extends AbstractResource {

  /**
   * The Wip pool store instance.
   *
   * @var WipPoolStoreInterface
   */
  private $storage;

  /**
   * Creates a new instance of SimulationResource.
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
   * Gets the simulation script from a WIP object.
   */
  public function getSimulationScriptAction($id, Request $request, Application $app) {
    /** @var Task $task */
    $task = $this->storage->get($id);
    if (empty($task)) {
      throw new NotFoundHttpException('Resource not found.');
    }

    if (!$task->isCompleted()) {
      throw new BadRequestHttpException(sprintf('WIP object ID %d has not completed.', $id));
    }

    $task->loadWipIterator();
    /** @var StateTableIteratorInterface $iterator */
    $iterator = $task->getWipIterator();
    $recordings = $iterator->getRecordings();

    $simulation_scripts = [];
    $at_least_one_script_found = FALSE;

    foreach ($recordings as $name => $recording) {
      $simulation_scripts[$name] = $recording->getSimulationScript();
      if (!empty($recording->getSimulationScript())) {
        $at_least_one_script_found = TRUE;
      }
    }

    // If there were no non-empty transcripts found, throw an exception.
    if (!$at_least_one_script_found) {
      throw new NotFoundHttpException(sprintf('No recordings found for WIP object ID %d.', $id));
    }

    $response = array(
      'id' => $id,
      'simulation_scripts' => $simulation_scripts,
    );

    return new JsonResponse($response, StatusCode::OK);
  }

  /**
   * Gets the transcript from a WIP object.
   */
  public function getTranscriptAction($id, Request $request, Application $app) {
    /** @var Task $task */
    $task = $this->storage->get($id);
    if (empty($task)) {
      throw new NotFoundHttpException('Resource not found.');
    }

    if (!$task->isCompleted()) {
      throw new BadRequestHttpException(sprintf('WIP object ID %d has not completed.', $id));
    }

    $task->loadWipIterator();
    /** @var StateTableIteratorInterface $iterator */
    $iterator = $task->getWipIterator();
    $recordings = $iterator->getRecordings();

    $transcripts = [];
    $at_least_one_transcript_found = FALSE;

    foreach ($recordings as $name => $recording) {
      $transcripts[$name] = $recording->getTranscript();
      if (!empty($recording->getTranscript())) {
        $at_least_one_transcript_found = TRUE;
      }
    }

    // If there were no non-empty transcripts found, throw an exception.
    if (!$at_least_one_transcript_found) {
      throw new NotFoundHttpException(sprintf('No recordings found for WIP object ID %d.', $id));
    }

    $response = array(
      'id' => $id,
      'transcripts' => $transcripts,
    );

    return new JsonResponse($response, StatusCode::OK);
  }

}
