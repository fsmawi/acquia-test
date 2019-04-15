<?php

namespace Acquia\Wip\Objects\ContainerDelegate;

use Acquia\Wip\Container\ContainerInterface;
use Acquia\Wip\Container\ContainerProcessInterface;
use Acquia\Wip\Container\ContainerResultInterface;
use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\DependencyManager;
use Acquia\Wip\Environment;
use Acquia\Wip\ExitMessage;
use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\IteratorStatus;
use Acquia\Wip\Objects\Resource\SshKeyRemove;
use Acquia\Wip\Signal\CleanupSignal;
use Acquia\Wip\Signal\SignalCallbackHttpTransportInterface;
use Acquia\Wip\Signal\SignalFactory;
use Acquia\Wip\TaskExitStatus;
use Acquia\Wip\WipContextInterface;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipInterface;
use Acquia\Wip\WipLogLevel;
use Acquia\Wip\WipTaskConfig;
use Acquia\Wip\WipTaskProcess;

/**
 * The ContainerDelegate is used to delegate work to a container.
 *
 * This work will be performed in a Wip instance running inside the container.
 * This instance will monitor the container and complete when the container has
 * completed its work.  Its exit code, exit message, logs, etc will come from
 * the Wip object doing the actual work.
 */
class ContainerDelegate extends BasicWip implements DependencyManagedInterface {

  /**
   * The version associated with this Wip class.
   */
  const CLASS_VERSION = 1;

  /**
   * The DependencyManager instance.
   *
   * @var DependencyManager
   */
  protected $dependencyManager;

  /**
   * The task configuration that will be passed to the actual task.
   *
   * @var WipTaskConfig
   */
  protected $configuration;

  /**
   * Indicates whether the container should be killed upon completion.
   *
   * @var bool
   */
  private $killContainer = TRUE;

  /**
   * The environment.
   *
   * @var Environment
   */
  private $environment = NULL;

  /**
   * Indicates whether the container has successfully run.
   *
   * @var bool
   */
  private $containerSuccessfullyRun = FALSE;

  /**
   * Resources that should be removed before this Wip object completes.
   *
   * @var array[]
   */
  private $releaseResources = array();

  /**
   * Always return the work ID of the delegated work.
   *
   * This class must help ensure that concurrency rules are applied
   * appropriately for the Wip object this delegate object represents.
   *
   * @return string
   *   The value that uniquely identifies the actual workload.
   */
  public function generateWorkId() {
    $wip_task_config = $this->configuration;
    if (empty($wip_task_config)) {
      throw new \DomainException('The WipTaskConfig must be set before the work ID can be generated.');
    }
    $class_name = $wip_task_config->getClassId();
    /** @var WipInterface $wip_object */
    $wip_object = new $class_name();
    $wip_object->setWipTaskConfig($wip_task_config);
    return $wip_object->generateWorkId();
  }

  /**
   * Sets whether the container will be killed upon completion.
   *
   * If set to FALSE the container will continue to run even after its work is
   * completed.  This is useful for debugging.
   *
   * @param bool $kill
   *   TRUE if the container should be killed upon task completion; FALSE if
   *   the container should continue to run.
   */
  public function killContainerUponCompletion($kill = TRUE) {
    if (!is_bool($kill)) {
      throw new \InvalidArgumentException('The kill parameter must be a boolean value.');
    }
    $this->killContainer = $kill;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.handler.containers' => 'Acquia\Wip\Container\ContainerInterface',
      'acquia.wip.containers' => 'Acquia\Wip\WipContainerInterface',
      'acquia.wip.handler.signal' => 'Acquia\Wip\Signal\SignalCallbackHttpTransportInterface',
      'acquia.wip.storage.signal' => 'Acquia\Wip\Storage\SignalStoreInterface',
    ) + parent::getDependencies();
  }

  /**
   * {@inheritdoc}
   */
  public function setGroup($group_name) {
    if (!is_string($group_name)) {
      throw new \InvalidArgumentException('The group_name parameter must be a string.');
    }
    if (empty($group_name)) {
      throw new \InvalidArgumentException('The group_name parameter can not be empty.');
    }
    parent::setGroup($group_name);
  }

  /**
   * The state table that will be executed by this Wip object.
   *
   * @var string
   */
  protected $stateTable = <<<EOT

start {
  * verifyConfiguration
}

verifyConfiguration:checkConfiguration {
  success invokeContainer
  fail failure
}

invokeContainer:waitForContainerLaunch [container] {
  success initializeContainer
  wait invokeContainer wait=5 exec=false
  uninitialized invokeContainer wait=30 max=3
  fail invokeContainer wait=30 max=3
}

initializeContainer:verifyContainerInitialization [container] {
  success waitForContainer
  fail initializeContainer wait=5 max=5
}

# The container has its own timers that will be blended once the container has
# completed. The time spent during this state should not be considered in the
# timing summary because it would be double counted.
waitForContainer:checkContainerStatus [none] {
  success finishContainer
  running waitForContainer wait=300 exec=false
  ready waitForContainer wait=300 exec=false
  wait waitForContainer wait=300 exec=false
  uninitialized failure
  fail failure
}

# Make sure that no failures after this point go back to the failure state
# because that would set up an infinite loop.
failure {
  * finishContainer
  ! finishContainer
}

finishContainer:checkShouldKillContainer {
  yes killContainer
  no  processRemainingSignals
  !   processRemainingSignals
}

killContainer:checkContainerIsKilled [container] {
  success processRemainingSignals
  wait killContainer wait=30 exec=false
  fail killContainer wait=30 exec=true max=3
  ! alertContainerStillRunning
}

alertContainerStillRunning [container] {
  * processRemainingSignals
  ! processRemainingSignals
}

processRemainingSignals {
  * releaseResources
  ! releaseResources
}

releaseResources {
  * finish
  ! finish
}

EOT;

  /**
   * {@inheritdoc}
   */
  public function start(WipContextInterface $wip_context) {
    parent::start($wip_context);

    // Make all of the states that interact with the container use the same
    // context.
    $iterator = $this->getIterator();
    foreach (array('initializeContainer', 'waitForContainer', 'killContainer') as $state) {
      $context = $iterator->getWipContext($state);
      $context->linkContext('invokeContainer');
    }
  }

  /**
   * Verifies the configuration is complete for container execution.
   */
  public function verifyConfiguration() {
    // Get the environment from the parameter document.
    $parameter_document = $this->getParameterDocument();
    $this->environment = $this->extractEnvironment($parameter_document);
    $this->log(
      WipLogLevel::DEBUG,
      sprintf('Retrieved the environment from parameter document: %s', print_r($this->environment, TRUE))
    );
  }

  /**
   * Starts the container.
   *
   * @todo - There should be some sort of invocation data that is passed along
   * so we know what sort of container to invoke.
   *
   * Note that the container will start running but wait for instructions to
   * be passed to it before it does any of the configured workload.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function invokeContainer(WipContextInterface $wip_context) {
    /** @var ContainerInterface $container */
    $container = $this->createContainer();
    $process = $container->run();
    $api = $this->getContainerApi();
    $api->setContainerProcess($process, $wip_context, $this->getWipLog());
  }

  /**
   * Initializes the container with a workload.
   *
   * This is how the container knows what to work on.  It has to pass the type
   * of Wip object to execute and any configuration that Wip object requires
   * to do its job.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function initializeContainer(WipContextInterface $wip_context) {
    try {
      $process = $this->getContainerProcess($wip_context);
      $container = $process->getContainer();
      $this->configuration->setWipId($this->getId());
      try {
        $wip_pool_store = $this->getIterator()->getWipPoolStore();
        $task = $wip_pool_store->get($this->getId());
        $added_time = $task->getCreatedTimestamp();
        $this->configuration->setCreatedTimestamp($added_time);
      } catch (\Exception $e) {
        $this->log(WipLogLevel::ERROR, 'Unable to set the creation timestamp.');
      }
      $this->configuration->setUuid($this->getUuid());
      $options = $this->configuration->getOptions();

      // Make sure a resource cleanup callback is provided to prevent resource
      // leaks should the container be killed.
      if (empty($options->cleanupResourceCallback)) {
        /** @var SignalCallbackHttpTransportInterface $callback_handler */
        $callback_handler = $this->dependencyManager->getDependency('acquia.wip.handler.signal');
        $url = $callback_handler->getCallbackUrl($this->getId());
        $options->cleanupResourceCallback = $url;
      }

      // @todo - Note that the container implementations do not have an
      // initializeContainer method yet even though this initialization is
      // currently highly implementation-specific.  Going forward this
      // initialization should be performed in a common way using REST.
      $this->configuration->setInitializeTime(time());
      $container->initializeContainer($process, $this->configuration);
    } catch (\Exception $e) {
      // There is a delay between the point when the container changes to
      // 'RUNNING' status and the time it is ready to accept initialization
      // data.
      $this->log(WipLogLevel::ERROR, sprintf('Failed to initialize the container: %s', $e->getMessage()));
    }
  }

  /**
   * Marks the container as running, and lets the transition do the rest.
   *
   * Other than setting the successfully run flag, this method does nothing.
   * Its transition does all of the work.
   */
  public function waitForContainer() {
    $this->containerSuccessfullyRun = TRUE;
  }

  /**
   * Ensures the container does not continue to run.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function killContainer(WipContextInterface $wip_context) {
    $container = $this->getContainer($wip_context);
    if (!empty($container)) {
      try {
        // @todo - need a way to figure out if the container is still running.
        $container->kill();
      } catch (\Exception $e) {
        $this->log(WipLogLevel::ERROR, sprintf('Failed to kill container: %s', $e->getMessage()));
      }
    }
  }

  /**
   * Called after the container has been unsuccessfully killed.
   *
   * This may warrant an alert because we don't want a container leak.
   */
  public function alertContainerStillRunning() {
    $this->notifyFailure();
  }

  /**
   * Checks that this object has been configured successfully.
   *
   * @return string
   *   'success' - This object was configured successfully.
   *   'fail' - The object configuration failed.
   */
  public function checkConfiguration() {
    $result = 'success';
    return $result;
  }

  /**
   * Checks whether the container has launched and is ready for initialization.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   *
   * @return string
   *   'success' - The container has launched.
   *   'wait' - The container has not launched yet.
   *   'uninitialized' - No container process was found in the context.
   *   'fail' - An error occurred when looking at the container status.
   */
  public function waitForContainerLaunch(WipContextInterface $wip_context) {
    $result = 'uninitialized';
    try {
      $container = $this->getContainer($wip_context);
      if (!empty($container)) {
        if ($container->hasStarted()) {
          $result = 'success';
        } elseif ($container->launchFailed()) {
          $result = 'fail';
        } else {
          $result = 'wait';
        }
      }
    } catch (\Exception $e) {
      $this->log(WipLogLevel::ERROR, sprintf('Failed to fetch container status: %s.', $e->getMessage()));
      $result = 'fail';
    }
    return $result;
  }

  /**
   * Verifies that the container was initialized successfully.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   *
   * @return string
   *   'success' - The container was initialized successfully.
   *   'fail' - The container initialization failed.
   */
  public function verifyContainerInitialization(WipContextInterface $wip_context) {
    $result = 'fail';
    $container = $this->getContainer($wip_context);
    if (!empty($container) && $container->isConfigured()) {
      $result = 'success';
    }
    return $result;
  }

  /**
   * Indicates whether the container is still running.
   *
   * @return string
   *   'success' - The container is no longer running.
   *   'wait' - The container is still running.
   *   'fail' - Failed to kill the container.
   */
  public function checkContainerIsKilled() {
    // @todo - Do something with the container to determine if it is still
    // running.  Probably the ContainerInterface should have such a method and
    // possibly ContainerProcess and ContainerResult.
    $result = 'success';
    return $result;
  }

  /**
   * Empty state method; transition method does the actual work.
   */
  public function finishContainer() {
  }

  /**
   * Indicates whether the container should be killed upon task completion.
   *
   * @return string
   *   'yes' - Kill the container before exiting.
   *   'no' - Leave the container running.
   */
  public function checkShouldKillContainer() {
    $result = 'yes';
    $kill_container = WipFactory::getBool('$acquia.container.release_on_exit', TRUE);
    if ($kill_container === FALSE || $this->killContainer === FALSE) {
      $result = 'no';
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function finish(WipContextInterface $wip_context) {
    $iterator = $wip_context->getIterator();
    $invoke_context = $iterator->getWipContext('invokeContainer');
    $result = $this->getContainerResult($invoke_context);
    $log_level = WipLogLevel::INFO;
    if (!empty($result)) {
      try {
        $exit_code = $result->getExitCode();
        $this->setExitCode($result->getExitCode());
        if (TaskExitStatus::isError($exit_code) || TaskExitStatus::isTerminated($exit_code)) {
          $log_level = WipLogLevel::FATAL;
        } elseif ($exit_code === TaskExitStatus::WARNING) {
          $log_level = WipLogLevel::WARN;
        }
      } catch (\Exception $e) {
      }
      try {
        $this->setExitMessage(new ExitMessage($result->getExitMessage(), $log_level));
      } catch (\Exception $e) {
      }
    }
  }

  /**
   * The default failure state in the FSM.
   *
   * If the container did not successfully run, set the exit code and message to
   * indicate that. Otherwise, set the ContainerDelegate object's exit code and
   * message to be the same as those of the containerized wip object.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   * @param \Exception $exception
   *   The exception that caused the failure (assuming the failure was caused
   *   by an exception).
   */
  public function failure(WipContextInterface $wip_context, \Exception $exception = NULL) {
    if (!$this->containerSuccessfullyRun) {
      $this->setExitCode(IteratorStatus::ERROR_SYSTEM);
      $this->setExitMessage(new ExitMessage('Failed to launch a container for the task.', WipLogLevel::FATAL));
    } else {
      $context = $this->getIterator()->getWipContext('waitForContainer');
      $container_results = $this->getContainerApi()->getContainerResults($context);
      if (!empty($container_results)) {
        // @todo Verify there can only be one result in the results array.
        $result = reset($container_results);
        $exit_code = $result->getExitCode();
        if (!IteratorStatus::isValid($exit_code)) {
          $exit_code = IteratorStatus::ERROR_SYSTEM;
        }
        $this->setExitCode($exit_code);
        $this->setExitMessage(new ExitMessage($result->getExitMessage(), WipLogLevel::FATAL));
      }
    }

    parent::failure($wip_context, $exception);
  }

  /**
   * Creates a container of the type specified in the configuration file.
   *
   * @return ContainerInterface
   *   The container.
   */
  private function createContainer() {
    /** @var ContainerInterface $result */
    $result = $this->dependencyManager->getDependency('acquia.wip.handler.containers');
    $result->setWipId($this->getId());
    $result->setGroupName($this->getGroup());

    return $result;
  }

  /**
   * Gets the container process from the specified WipContext instance.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   *
   * @return ContainerProcessInterface
   *   The process.
   */
  private function getContainerProcess(WipContextInterface $wip_context) {
    $result = NULL;
    $container_api = $this->getContainerApi();
    $processes = $container_api->getContainerProcesses($wip_context);
    if (!empty($processes) && is_array($processes)) {
      $result = array_shift($processes);
    }
    return $result;
  }

  /**
   * Gets the container result from the specified WipContext instance.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   *
   * @return ContainerResultInterface
   *   The result, if available.
   */
  private function getContainerResult(WipContextInterface $wip_context) {
    $result = NULL;
    $container_api = $this->getContainerApi();
    $results = $container_api->getContainerResults($wip_context);
    if (!empty($results) && is_array($results)) {
      $result = array_shift($results);
    }
    return $result;
  }

  /**
   * Gets the container.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   *
   * @return ContainerInterface
   *   The container, if available.
   */
  private function getContainer(WipContextInterface $wip_context) {
    $container = NULL;
    $process = $this->getContainerProcess($wip_context);
    if (!empty($process)) {
      $container = $process->getContainer();
    } else {
      $result = $this->getContainerResult($wip_context);
      if (!empty($result)) {
        $container = $result->getContainer();
      }
    }
    return $container;
  }

  /**
   * {@inheritdoc}
   */
  public function setWipTaskConfig(WipTaskConfig $configuration) {
    parent::setWipTaskConfig($configuration);
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function onStart() {
    // The actual start time should be recorded when the container has been
    // initialized and the delegated Wip object begins.  Log an internal
    // (non user-readable) message so the end user doesn't get confused.
    $message = 'The ContainerDelegate task has started.';
    $this->log(WipLogLevel::INFO, $message, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function onFinish() {
    // The actual finish time should be recorded when the delegated Wip object
    // completes.  Log an internal message so the end user doesn't get confused.
    $message = 'The ContainerDelegate task has finished.';
    $this->log(WipLogLevel::INFO, $message, FALSE);
    $this->cleanUp();
  }

  /**
   * {@inheritdoc}
   */
  public function onFail() {
    // If a container was successfully launched, the actual failure timestamp
    // should be recorded when the delegated Wip object fails.  In this case,
    // log an internal message so the end user doesn't get confused.  If there
    // was an error launching or initializing the container, a delegate Wip
    // object would never have been created. In this case, log a user-readable
    // message indicating the failure and log a detailed non-user-readable log
    // for the developer.
    $readable = !$this->containerSuccessfullyRun;
    if ($readable) {
      // Log a more detailed, non-user-readable message for the developer.
      $this->log(WipLogLevel::FATAL, 'Failed to launch a container for the task.', FALSE);
    }
    $this->log(WipLogLevel::FATAL, 'The task has failed.', $readable);

    $this->cleanUp();
  }

  /**
   * Processes signals that were sent from within the container.
   *
   * The container's completion signal will have already been processed.  These
   * signals will generally be data signals, particularly ones describing
   * persistent resources created within the container that have not yet been
   * released.
   */
  public function processRemainingSignals() {
    $signals = $this->getIterator()->getSignals();
    if (!empty($signals)) {
      foreach ($signals as $signal) {
        try {
          $signal = SignalFactory::getDomainSpecificSignal($signal);
        } catch (\Exception $e) {
          $this->log(
            WipLogLevel::ERROR,
            sprintf('Error processing signal: "%s" for signal %s', $e->getMessage(), print_r($signal, TRUE))
          );
        }
        if ($signal instanceof CleanupSignal) {
          switch ($signal->getAction()) {
            case CleanupSignal::ACTION_REQUEST:
              $this->addCleanupRequest(
                $signal->getResourceType(),
                $signal->getResourceId(),
                $signal->getResourceName()
              );
              break;

            case CleanupSignal::ACTION_CANCEL:
              $this->removeCleanupRequest(
                $signal->getResourceType(),
                $signal->getResourceId(),
                $signal->getResourceName()
              );
              break;

            default:
              $this->log(WipLogLevel::ERROR, sprintf('Bad cleanup action: %d.', $signal->getAction()));
          }
          $signal_store = $this->getSignalStore();
          $signal_store->consume($signal);
        }
      }
    }
  }

  /**
   * Adds the specified resource to be released upon completion.
   *
   * @param string $type
   *   The resource type.
   * @param string $id
   *   Optional. The resource ID.
   * @param string $name
   *   Optional. The resource name.
   */
  public function addCleanupRequest($type, $id = NULL, $name = NULL) {
    if (empty($id) && empty($name)) {
      // Nothing to remove.
      $message = 'Requested resource cleanup for type %s but the caller failed to provide any resource identification.';
      $this->log(
        WipLogLevel::ERROR,
        sprintf($message, $type)
      );
    } else {
      if (!isset($this->releaseResources[$type])) {
        $this->releaseResources[$type] = array();
      }
      $resource_exists = FALSE;
      // Do not allow multiple cleanup requests for the same resource to be
      // added.
      foreach ($this->releaseResources[$type] as $resources) {
        if ((!empty($resources->id) && $resources->id === $id) ||
          (!empty($resources->name) && $resources->name === $name)) {
          $resource_exists = TRUE;
          break;
        }
      }
      $description = new \stdClass();
      if (!empty($id)) {
        $description->id = $id;
      }
      if (!empty($name)) {
        $description->name = $name;
      }

      if (!$resource_exists) {
        $this->releaseResources[$type][] = $description;
        $this->log(WipLogLevel::TRACE, sprintf('Adding cleanup request for %s', print_r($description, TRUE)));
      } else {
        $this->log(
          WipLogLevel::TRACE,
          sprintf('The cleanup request for %s has already been processed.', print_r($description, TRUE))
        );
      }
    }
  }

  /**
   * Removes the specified resource from the list that will be released.
   *
   * @param string $type
   *   The resource type.
   * @param string $id
   *   Optional. The resource ID.
   * @param string $name
   *   Optional. The resource name.
   */
  public function removeCleanupRequest($type, $id = NULL, $name = NULL) {
    if (empty($id) && empty($name)) {
      // Nothing to remove.
      $message = 'Requested to cancel cleanup for resource type %s but the caller failed to provide any resource identification.';
      $this->log(
        WipLogLevel::ERROR,
        sprintf($message, $type)
      );
    } elseif (isset($this->releaseResources[$type])) {
      foreach ($this->releaseResources[$type] as $key => $description) {
        if (!empty($description->id) && $description->id !== $id) {
          continue;
        }
        if (!empty($description->name) && $description->name !== $name) {
          continue;
        }
        $this->log(WipLogLevel::TRACE, sprintf('Removing cleanup request for %s', print_r($description, TRUE)));
        unset($this->releaseResources[$type][$key]);
        // We only expect one instance of a cleanup request in which the type
        // and either ID or name matches.
        break;
      }
      if (empty($this->releaseResources[$type])) {
        unset($this->releaseResources[$type]);
      }
    }
  }

  /**
   * Releases any resources that remain after the container has completed.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function releaseResources(WipContextInterface $wip_context) {
    $wip_pool = $this->getWipPool();
    foreach ($this->releaseResources as $type => $descriptions) {
      foreach ($descriptions as $description) {
        switch ($type) {
          case 'ssh-key':
            $name = NULL;
            $id = NULL;
            if (!empty($description->name)) {
              $name = $description->name;
            }
            if (!empty($description->id)) {
              $id = $description->id;
            }
            $obj = $this->createDeleteObject($name, $id);
            try {
              // We could make this a high priority task, but I'm not sure that
              // is warranted because keeping the key for a few minutes will not
              // impact the user's perception of how long it took to complete
              // this task.
              $task = $wip_pool->addTask($obj);
              $child_process = new WipTaskProcess($task);
              $api = $this->getWipApi();
              $api->setWipTaskProcess($child_process, $wip_context, $this->getWipLog());
              if (!empty($name)) {
                $message = 'The associated Wip object failed to release SSH key "%s". This will occur in a separate task (id: %d)';
                $this->log(
                  WipLogLevel::ALERT,
                  sprintf($message, $name, $task->getId())
                );
              } elseif (!empty($id)) {
                $message = 'The associated Wip object failed to release SSH key %d. This will occur in a separate task (id: %d)';
                $this->log(
                  WipLogLevel::ALERT,
                  sprintf($message, $id, $task->getId())
                );
              }
            } catch (\Exception $e) {
              $this->log(WipLogLevel::ERROR, sprintf('Failed to release SSH key "%s".', $id));
            }
            break;

          default:
            $this->log(WipLogLevel::ERROR, sprintf('Request to release unknown resource type "%s".', $type));
        }
      }
    }
  }

  /**
   * Creates a Wip object that will delete the specified SSH key.
   *
   * @param string $name
   *   Optional.  The SSH key nickname.
   * @param int $id
   *   Optional. The Hosting ID of the SSH key.
   *
   * @return SshKeyRemove
   *   The Wip object that will delete an SSH key.
   */
  private function createDeleteObject($name, $id) {
    $result = new SshKeyRemove();
    $result->setLogLevel($this->getLogLevel());
    $options = new \stdClass();
    if (!empty($name)) {
      $options->keyName = $name;
    }
    if (!empty($id)) {
      $options->keyId = $id;
    }
    $options->parameterDocument = $this->getParameterDocument();
    $result->setOptions($options);
    return $result;
  }

}
