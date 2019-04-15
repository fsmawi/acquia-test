<?php

namespace Acquia\Wip\Implementation;

use Acquia\Wip\Container\ContainerProcessInterface;
use Acquia\Wip\Container\ContainerResult;
use Acquia\Wip\Container\ContainerResultInterface;
use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\DependencyManager;
use Acquia\Wip\DependencyManagerInterface;
use Acquia\Wip\Exception\DependencyMissingException;
use Acquia\Wip\Exception\DependencyTypeException;
use Acquia\Wip\ServiceApi;
use Acquia\Wip\Signal\ContainerCompleteSignal;
use Acquia\Wip\Signal\ContainerDataSignalInterface;
use Acquia\Wip\Signal\ContainerSignalInterface;
use Acquia\Wip\Signal\ContainerTerminatedSignalInterface;
use Acquia\Wip\Signal\SignalType;
use Acquia\Wip\Storage\SignalStoreInterface;
use Acquia\Wip\WipContainerInterface;
use Acquia\Wip\WipContextInterface;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipLogLevel;

/**
 * Represents the integration of containers into Wip tasks.
 */
class ContainerApi extends ServiceApi implements WipContainerInterface, DependencyManagedInterface {

  /**
   * The associated resource name.
   */
  const RESOURCE_NAME = 'acquia.wip.containers';

  /**
   * The DependencyManager instance.
   *
   * @var DependencyManager
   */
  public $dependencyManager;

  /**
   * The ID of the Wip object associated with this instance.
   *
   * @var int
   */
  private $wipId;

  /**
   * Initializes this instance of ContainerApi.
   *
   * @throws DependencyTypeException
   *   If dependencies are not satisfied.
   */
  public function __construct() {
    $this->dependencyManager = new DependencyManager();
    $dependencies = $this->getDependencies();
    if (!empty($dependencies)) {
      $this->dependencyManager->addDependencies($dependencies);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.storage.signal' => 'Acquia\Wip\Storage\SignalStoreInterface',
    );
  }

  /**
   * Gets the ID of the Wip object associated with this instance.
   *
   * @return int
   *   The Wip ID.
   */
  public function getWipId() {
    return $this->wipId;
  }

  /**
   * Sets the ID of the Wip object associated with this instance.
   *
   * @param int $wip_id
   *   The Wip ID.
   */
  public function setWipId($wip_id) {
    if (!is_int($wip_id) || $wip_id <= 0) {
      throw new \InvalidArgumentException('The wip_id argument must be a positive integer.');
    }
    $this->wipId = $wip_id;
  }

  /**
   * {@inheritdoc}
   */
  public function clearContainerResults(WipContextInterface $context, WipLogInterface $logger) {
    $this->clearContainerProcesses($context, $logger);
    if (isset($context->container)) {
      unset($context->container);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setContainerResult(
    ContainerResultInterface $result,
    WipContextInterface $context,
    WipLogInterface $logger
  ) {
    $this->clearContainerResults($context, $logger);
    $this->addContainerResult($result, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function addContainerResult(ContainerResultInterface $result, WipContextInterface $context) {
    if (!isset($context->container)) {
      $context->container = new \stdClass();
    }
    if (!isset($context->container->results) || !is_array($context->container->results)) {
      $context->container->results = array();
    }
    $unique_id = $result->getUniqueId();
    if (!array_key_exists($unique_id, $context->container->results)) {
      $context->container->results[$unique_id] = $result;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getContainerResults(WipContextInterface $context) {
    $result = array();
    if (isset($context->container) && isset($context->container->results) && is_array($context->container->results)) {
      $result = $context->container->results;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getContainerResult($id, WipContextInterface $context) {
    $result = NULL;
    if (isset($context->container) && isset($context->container->results) && is_array($context->container->results)) {
      if (array_key_exists($id, $context->container->results)) {
        $result = $context->container->results[$id];
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function removeContainerResult(ContainerResultInterface $result, WipContextInterface $context) {
    if (isset($context->container) && isset($context->container->results) && is_array($context->container->results)) {
      $unique_id = $result->getUniqueId();
      if (array_key_exists($unique_id, $context->container->results)) {
        unset($context->container->results[$unique_id]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clearContainerProcesses(WipContextInterface $context, WipLogInterface $logger) {
    if (isset($context->container) && isset($context->container->processes)) {
      if (is_array($context->container->processes)) {
        foreach ($context->container->processes as $process) {
          $this->releaseServerSideResources($process, $logger);
        }
      }
    }
    if (isset($context->container)) {
      unset($context->container);
    }
  }

  /**
   * Releases server side resources held by the specified process.
   *
   * @param ContainerProcessInterface $process
   *   The process.
   * @param WipLogInterface $logger
   *   The logger.
   */
  private function releaseServerSideResources(ContainerProcessInterface $process, WipLogInterface $logger) {
    if (!$process->hasCompleted($logger)) {
      $process->kill($logger);
    } else {
      $process->release($logger);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setContainerProcess(
    ContainerProcessInterface $process,
    WipContextInterface $context,
    WipLogInterface $logger
  ) {
    $this->clearContainerProcesses($context, $logger);
    $this->addContainerProcess($process, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function addContainerProcess(ContainerProcessInterface $process, WipContextInterface $context) {
    if (!isset($context->container)) {
      $context->container = new \stdClass();
    }
    if (!isset($context->container->processes) || !is_array($context->container->processes)) {
      $context->container->processes = array();
    }
    $unique_id = $process->getUniqueId();
    if (!array_key_exists($unique_id, $context->container->processes)) {
      $context->container->processes[$unique_id] = $process;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function removeContainerProcess(
    ContainerProcessInterface $process,
    WipContextInterface $context,
    WipLogInterface $logger
  ) {
    if (isset($context->container) &&
      isset($context->container->processes) &&
      is_array($context->container->processes)
    ) {
      $unique_id = $process->getUniqueId();
      if (array_key_exists($unique_id, $context->container->processes)) {
        try {
          $this->releaseServerSideResources($process, $logger);
        } catch (\Exception $e) {
          // Ignore any exceptions.
        }
        // Always clear the process so subsequent calls to check process status
        // will succeed despite any cleanup issues.
        unset($context->container->processes[$unique_id]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getContainerProcesses(WipContextInterface $context) {
    $result = array();
    if (isset($context->container) &&
      isset($context->container->processes) &&
      is_array($context->container->processes)
    ) {
      $result = $context->container->processes;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getContainerProcess($id, WipContextInterface $context) {
    $result = NULL;
    if (isset($context->container) &&
      isset($context->container->processes) &&
      is_array($context->container->processes)
    ) {
      if (array_key_exists($id, $context->container->processes)) {
        $result = $context->container->processes[$id];
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getContainerStatus(WipContextInterface $context, WipLogInterface $logger) {
    $result = 'uninitialized';
    // Processing signals will automatically convert any completed process
    // objects into result objects.
    $context->processSignals();
    // Verify all processes have completed.
    $processes = $this->getContainerProcesses($context);
    foreach ($processes as $id => $process) {
      /** @var ContainerProcessInterface $process */
      if ($process instanceof ContainerProcessInterface) {
        // If the container has not yet started, wait for it to start.
        if (!$process->hasStarted($logger)) {
          return 'wait';
        }
        // The container is up, but might not be configured.
        if (!$process->isConfigured()) {
          // The state method handles configuring the container and setting
          // the isConfigured flag. This path should only be reached once per
          // process.
          return 'ready';
        }
        if ($process->launchFailed()) {
          $process->forceFail('The container failed to launch.', $logger);
        }
        // The process is either running or has finished by this point.
        if (!$process->hasCompleted($logger)) {
          if ($this->runningTooLong($process, $logger)) {
            $process->forceFail('The container has taken too long.', $logger);
          } else {
            // @todo Remove debugging log call or make it more useful.
            $logger->log(WipLogLevel::DEBUG, 'Container is running...', $context->getObjectId());
            return 'running';
          }
        }
        /** @var ContainerResultInterface $container_result */
        $container_result = $process->getResult($logger);
        // @todo Also collect stats for failed processes.
        if ($container_result->isSuccess()) {
          // Record the run length of all successful tasks.
          $environment = $container_result->getEnvironment();
          if (!empty($environment)) {
            $run_time = $container_result->getRuntime();
            $this->recordProcessRuntime('container', $environment->getSitegroup(), $run_time);
          }
        }
        // This process completed; convert it to a result.
        $this->addContainerResult($container_result, $context);
        $this->removeContainerProcess($process, $context, $logger);
        $log_level = WipLogLevel::WARN;
        if ($container_result->isSuccess()) {
          $log_level = $container_result->getLogLevel();
        }
        $logger->multiLog(
          $context->getObjectId(),
          $log_level,
          sprintf(
            'Requested the result of asynchronous container process - %s completed in %s seconds',
            $process->getDescription(),
            $container_result->getRuntime()
          ),
          WipLogLevel::DEBUG,
          sprintf(
            ' - exit: %s; exit message: %s server: %s',
            $container_result->getExitCode(),
            $container_result->getExitMessage(),
            $process->getEnvironment()->getCurrentServer()
          )
        );
      }
    }
    // Have all of the processes completed?
    $processes = $this->getContainerProcesses($context);
    if (count($processes) === 0) {
      // Inspect all results. Note this only happens if there are no processes
      // still running.
      $container_results = $this->getContainerResults($context);
      if (count($container_results) > 0) {
        $result = 'success';
        foreach ($container_results as $id => $container_result) {
          if ($container_result instanceof ContainerResultInterface) {
            if (!$container_result->isSuccess()) {
              $result = 'fail';
              break;
            }
          }
        }
      }
    }
    return $result;
  }

  /**
   * Gets the signal storage instance to use.
   *
   * @return SignalStoreInterface
   *   An instance of SignalStoreInterface.
   *
   * @throws DependencyMissingException
   *   If the SignalStoreInterface implementation could not be found.
   */
  private function getSignalStore() {
    return $this->dependencyManager->getDependency('acquia.wip.storage.signal');
  }

  /**
   * Processes the given container signal.
   *
   * @param ContainerSignalInterface $signal
   *   An instance of ContainerSignalInterface representing the signal.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the container results and/or
   *   processes are stored.
   * @param WipLogInterface $logger
   *   The logger.
   *
   * @return int
   *   0 if the specified signal was not processed; 1 otherwise.
   */
  public function processSignal(
    ContainerSignalInterface $signal,
    WipContextInterface $context,
    WipLogInterface $logger
  ) {
    $result = 0;
    if ($signal instanceof ContainerCompleteSignal && $signal->getType() === SignalType::COMPLETE) {
      $logger->log(WipLogLevel::DEBUG, sprintf('Received ContainerCompleteSignal.'), $context->getObjectId());
      $result += $this->processCompletionSignal($signal, $context, $logger);
    } else {
      $logger->log(WipLogLevel::DEBUG, sprintf('Received signal of type %s.', get_class($signal)));
    }
    return $result;
  }

  /**
   * Processes the specified ContainerCompleteSignal instance.
   *
   * @param ContainerCompleteSignal $signal
   *   An instance of ContainerCompleteSignal representing the completion
   *   signal.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the container results and/or
   *   processes are stored.
   * @param WipLogInterface $logger
   *   The logger.
   *
   * @return int
   *   0 if the specified signal was not processed; 1 otherwise.
   */
  private function processCompletionSignal(
    ContainerCompleteSignal $signal,
    WipContextInterface $context,
    WipLogInterface $logger
  ) {
    $result = 0;
    $pid = $signal->getPid();
    $start_time = $signal->getStartTime();
    if (empty($pid) || empty($start_time)) {
      $logger->log(
        WipLogLevel::DEBUG,
        sprintf(
          'The pid and startTime are required in the signal data to be able to process the completion signal. Received signal data: %s',
          var_export($signal->convertFieldsToObject(), TRUE)
        ),
        $context->getObjectId()
      );
    } else {
      $logger->log(
        WipLogLevel::TRACE,
        sprintf('Received signal with data %s', var_export($signal->convertFieldsToObject(), TRUE)),
        $context->getObjectId()
      );
      $unique_id = ContainerResult::createUniqueId($pid, $start_time);
      $process = $this->getContainerProcess($unique_id, $context);
      if ($process instanceof ContainerProcessInterface) {
        $logger->log(
          WipLogLevel::TRACE,
          sprintf('Received signal %s', print_r($signal, TRUE)),
          $context->getObjectId()
        );
        // This process completed; convert it to a result.
        $container_result = $process->getResultFromSignal($signal, $logger);

        $extra_data = $signal->getExtraData();
        if (NULL !== $extra_data && isset($extra_data->recordings) && is_array($extra_data->recordings)) {
          foreach ($extra_data->recordings as $name => $recording) {
            $context->getIterator()->addRecording($name, $recording);
          }
        }

        $this->addContainerResult($container_result, $context);
        $this->removeContainerProcess($process, $context, $logger);
        $result = 1;
        // Check for timer data.
        $timer = $signal->getTimer();
        if (NULL !== $timer) {
          $context->getIterator()->blendTimerData($timer);
        }
        $log_level = WipLogLevel::WARN;
        if ($container_result->isSuccess()) {
          $log_level = WipLogLevel::INFO;
        }
        $logger->multiLog(
          $context->getObjectId(),
          $log_level,
          sprintf(
            'Signaled result of asynchronous container process - %d - %s completed in %s seconds',
            $process->getPid(),
            $process->getDescription(),
            $container_result->getRuntime()
          ),
          WipLogLevel::DEBUG,
          sprintf(' - exit: %s', $container_result->getExitCode())
        );
      }
    }
    $signal_store = $this->getSignalStore();
    $signal_store->consume($signal);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getContainerTerminatedSignal($wip_id) {
    $result = NULL;
    $signals = $this->getSignalStore()->loadAllActive($wip_id);
    foreach ($signals as $signal) {
      if ($signal instanceof ContainerTerminatedSignalInterface) {
        $result = $signal;
        break;
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getContainerDataSignal($wip_id) {
    $result = NULL;
    $signals = $this->getSignalStore()->loadAllActive($wip_id);
    foreach ($signals as $signal) {
      if ($signal instanceof ContainerDataSignalInterface) {
        $result = $signal;
        break;
      }
    }
    return $result;
  }

  /**
   * Determines whether the specified process has been running too long.
   *
   * @param ContainerProcessInterface $process
   *   The process.
   * @param WipLogInterface $logger
   *   The logger.
   *
   * @return bool
   *   TRUE if the process has been running too long; FALSE otherwise.
   */
  private function runningTooLong(ContainerProcessInterface $process, WipLogInterface $logger) {
    // @todo For now don't kill container processes due to runtime.
    $result = FALSE;
    return $result;
  }

  /**
   * Gets the ContainerApi instance.
   *
   * @param DependencyManagerInterface $dependency_manager
   *   Optional. The DependencyManager instance.
   *
   * @return WipContainerInterface
   *   The ContainerApi instance.
   *
   * @throws \Acquia\Wip\Exception\DependencyMissingException
   *   If a DependencyManager instance is provided but the ContainerApi has not
   *   been set as a dependency.
   */
  public static function getContainerApi(DependencyManagerInterface $dependency_manager = NULL) {
    $result = NULL;
    if (NULL !== $dependency_manager) {
      $result = $dependency_manager->getDependency(self::RESOURCE_NAME);
    } else {
      try {
        $result = WipFactory::getObject(self::RESOURCE_NAME);
      } catch (\Exception $e) {
        // Fall back to a new instance of ContainerApi.
        $result = new self();
      }
    }
    return $result;
  }

}
