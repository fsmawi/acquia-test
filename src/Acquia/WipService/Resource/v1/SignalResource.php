<?php

namespace Acquia\WipService\Resource\v1;

use Acquia\WipService\Http\HalResponse;
use Acquia\WipService\Resource\AbstractResource;
use Acquia\WipService\Utility\SignalCallbackHttpTransport;
use Acquia\Wip\Runtime\InternalDispatchCleanup;
use Acquia\Wip\Signal\SignalFactory;
use Acquia\Wip\Signal\SshCompleteSignal;
use Acquia\Wip\Signal\WipCompleteSignal;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogLevel;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Teapot\StatusCode;

/**
 * Defines a REST API resource for handling signal callbacks.
 */
class SignalResource extends AbstractResource {

  /**
   * The signal storage instance.
   *
   * @var \Acquia\Wip\Storage\SignalStoreInterface
   */
  private $storage;

  /**
   * Creates a new instance of SignalResource.
   */
  public function __construct() {
    parent::__construct();
    $this->storage = $this->dependencyManager->getDependency('acquia.wip.storage.signal');
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.storage.signal' => '\Acquia\Wip\Storage\SignalStoreInterface',
    );
  }

  /**
   * Receives signals.
   *
   * @param string $id
   *   The unique signal identifier.
   * @param Request $request
   *   An instance of Request representing the incoming HTTP request.
   * @param Application $app
   *   The application instance.
   *
   * @return HalResponse
   *   The HalResponse instance.
   */
  public function postAction($id, Request $request, Application $app) {
    // Note: in future, we might choose to accept a second parameter that sets
    // the signal type, but for now we only create signals of type 'complete'.
    try {
      $handler = new SignalCallbackHttpTransport();
      $signal = $handler->resolveSignal($id);
      if ($data = json_decode($request->getContent())) {
        $signal->setData($data);
      }

      // Sent time needs to be an integer already here because
      // Signal::initializeFromSignal will call setSentTime, which requires an
      // integer.
      $signal->setSentTime(time());
      if (isset($signal->getData()->classId)) {
        $signal = SignalFactory::getDomainSpecificSignal($signal);
      }
      if ($signal->getObjectId() == 0) {
        // This signal holds the results of an internal thread dispatch.
        if ($this->willSimulateFailure()) {
          $this->wipLog->log(
            WipLogLevel::ALERT,
            "Simulating signal failure"
          );
          $hal = $app['hal']($request->getUri(), []);
          return new HalResponse($hal, StatusCode::OK);
        }
        if ($signal instanceof SshCompleteSignal) {
          $cleanup = new InternalDispatchCleanup();
          $cleanup->handleSystemSignal($signal);
        }
      } else {
        $this->storage->send($signal);
        if ($signal instanceof SshCompleteSignal || $signal instanceof WipCompleteSignal) {
          $handler->releaseCallback($id);
        }
        $this->wipLog->log(
          WipLogLevel::TRACE,
          sprintf(
            'Received signal callback %s; Sent signal of type %s to WIP %d',
            $id,
            get_class($signal),
            $signal->getObjectId()
          ),
          $signal->getObjectId()
        );
      }

      $data = array(
        'id' => $signal->getObjectId(),
      );
      $hal = $app['hal']($request->getUri(), $data);
      return new HalResponse($hal, StatusCode::OK);
    } catch (\Exception $e) {
      $this->wipLog->log(
        WipLogLevel::FATAL,
        sprintf('Error when processing signal: %s', $e->getMessage())
      );
      $this->wipLog->log(
        WipLogLevel::DEBUG,
        sprintf("Signal error stacktrace:\n%s", $e->getTraceAsString())
      );
      if (!empty($data)) {
        $this->wipLog->log(
          WipLogLevel::FATAL,
          sprintf('Signal data: %s', print_r($data, TRUE))
        );
      }
      $hal = $app['hal']($request->getUri(), []);
      return new HalResponse($hal, StatusCode::INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * Determines if a failure should be simulated now.
   *
   * @return bool
   *   TRUE if a failure will be simulated; FALSE otherwise.
   */
  private function willSimulateFailure() {
    $result = FALSE;
    $debug = WipFactory::getBool('$acquia.wip.secure.debug', FALSE);
    $percent_failure = WipFactory::getInt('$acquia.wip.signal.simulate.failure', 0);
    if ($debug && $percent_failure > 0) {
      $value = floatval(mt_rand(0, 100));
      if ($value <= $percent_failure) {
        $result = TRUE;
      }
    }
    return $result;
  }

}
