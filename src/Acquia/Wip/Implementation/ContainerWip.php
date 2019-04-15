<?php

namespace Acquia\Wip\Implementation;

use Acquia\Wip\Container\ContainerInterface;
use Acquia\Wip\Container\ContainerProcessInterface;
use Acquia\Wip\Container\ContainerResultInterface;
use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\Exception\ContainerInfoUnavailableException;
use Acquia\Wip\ExitMessage;
use Acquia\Wip\IteratorStatus;
use Acquia\Wip\Notification\NotificationSeverity;
use Acquia\Wip\Signal\ContainerCompleteSignal;
use Acquia\Wip\Signal\ContainerDataSignalInterface;
use Acquia\Wip\Signal\ContainerTerminatedSignalInterface;
use Acquia\Wip\Signal\Signal;
use Acquia\Wip\Signal\SignalCallbackHttpTransportInterface;
use Acquia\Wip\Signal\SignalInterface;
use Acquia\Wip\Signal\SignalType;
use Acquia\Wip\Signal\UriCallback;
use Acquia\Wip\TaskExitStatus;
use Acquia\Wip\WipContextInterface;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogLevel;

/**
 * The ContainerWip is the superclass for objects that leverage containers.
 */
class ContainerWip extends BasicWip implements DependencyManagedInterface {

  /**
   * The version associated with this Wip class.
   */
  const CLASS_VERSION = 1;
  /**
   * The state used to invoke the container.
   */
  const CONTAINER_INVOKE = 'containerWipInvoke';

  /**
   * The amount of disk space the container is allowed to use by default.
   *
   * This value is measured in GB.
   */
  const CONTAINER_MAX_DISK = 10.0;

  /**
   * The number of seconds the container is allowed to run by default.
   */
  const CONTAINER_MAX_TIME = 3600;

  /**
   * Indicates whether the container should be released upon completion.
   *
   * @var bool
   */
  private $releaseContainer = NULL;

  /**
   * Indicates whether the container should be terminated upon completion.
   *
   * @var bool
   */
  private $terminateContainer = NULL;

  /**
   * Indicates whether the code should wait for the container to stop upon async terminate.
   *
   * @var bool
   */
  private $waitForTerminate = NULL;

  /**
   * Indicates whether the container has been launched.
   *
   * This will only be set to TRUE if the container gets into the RUNNING
   * state.
   *
   * @var bool
   */
  private $containerLaunched = FALSE;

  /**
   * The environment used to SSH to the container.
   *
   * @var EnvironmentInterface
   */
  private $containerEnvironment;

  /**
   * Indicates whether the container signal has been received.
   *
   * This signal is sent in the container's entry-point script at a point when
   * the container's status should have been set to the RUNNING state.
   *
   * @var bool
   */
  private $containerSignalReceived;

  /**
   * Indicates that we are waiting for resources.
   *
   * @var bool
   */
  private $waitingForResources = FALSE;

  /**
   * The maximum amount of disk space permissible in the container.
   *
   * This value is expressed in gigabytes.
   *
   * @var float
   */
  private $maxDisk = self::CONTAINER_MAX_DISK;

  /**
   * The maximum amount of time the container is allowed to run.
   *
   * This value is expressed in seconds.
   *
   * @var int
   */
  private $maxTime = self::CONTAINER_MAX_TIME;

  /**
   * Sets whether the container will be released upon completion.
   *
   * If set to FALSE the container will continue to run even after its work is
   * completed.  This is useful for debugging.
   *
   * @param bool $release
   *   TRUE if the container should be released upon task completion; FALSE if
   *   the container should continue to run.
   */
  public function releaseContainerUponCompletion($release = TRUE) {
    if (!is_bool($release)) {
      throw new \InvalidArgumentException('The "release" parameter must be a boolean value.');
    }
    $this->releaseContainer = $release;
  }

  /**
   * Indicates whether the container will be released upon completion.
   *
   * @return bool
   *   TRUE if the container will be released; FALSE otherwise.
   */
  public function getReleaseContainerUponCompletion() {
    $result = $this->releaseContainer;
    if (NULL === $result) {
      $result = WipFactory::getBool('$acquia.container.release_on_exit', TRUE);
    }
    return $result;
  }

  /**
   * Sets whether the container should be terminated on completion.
   *
   * This is different than simply releasing the container. On termination none
   * of the cleanup would be done.
   *
   * @param bool $terminate
   *   If TRUE, the container will be terminated upon completion of this Wip
   *   object.
   */
  public function terminateContainerUponCompletion($terminate = TRUE) {
    if (!is_bool($terminate)) {
      throw new \InvalidArgumentException('The "terminate" parameter must be a boolean value.');
    }
    $this->terminateContainer = $terminate;
  }

  /**
   * Indicates whether this object is configured to terminate its container.
   *
   * @return bool
   *   Returns TRUE if the container will be terminated; FALSE otherwise.
   */
  public function getTerminateContainerUponCompletion() {
    $result = $this->terminateContainer;
    if (NULL === $result) {
      $result = WipFactory::getBool('$acquia.container.terminate_on_exit', FALSE);
    }
    return $result;
  }

  /**
   * Sets whether this task should wait for container completion.
   *
   * @param bool $wait
   *   TRUE if the task should wait; FALSE otherwise.
   */
  public function waitForTerminate($wait = TRUE) {
    if (!is_bool($wait)) {
      throw new \InvalidArgumentException('The "wait" parameter must be a boolean value.');
    }
    $this->waitForTerminate = $wait;
  }

  /**
   * Indicates whether task completion must wait for the container to complete.
   *
   * @return bool
   *   TRUE if the task must wait; FALSE otherwise.
   */
  public function getWaitForTerminate() {
    $result = $this->waitForTerminate;
    if (NULL === $result) {
      $result = WipFactory::getBool('$acquia.container.waitforstop', TRUE);
    }
    return $result;
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
   * Returns a state table block responsible for starting the container.
   *
   * @param string $success
   *   The state to go to upon success.
   * @param string $failure
   *   The state to go to upon failure.
   * @param string $container_terminated
   *   The state to go to upon container termination.
   *
   * @return string
   *   The block of text that can be inserted into a state table.
   */
  protected function getContainerStartTable($success, $failure, $container_terminated) {
    $start_state = $this->getContainerStartState();

    return <<<EOT
# Initializes in preparation for the container to be started.
$start_state [container] {
  *                  containerWipInvoke
  !                  $failure
}

# Starts the container.
containerWipInvoke:containerWipWaitForLaunch [container] {
  success            containerWipContainerLaunched
  wait               containerWipInvoke wait=60 exec=false
  spin               containerWipInvoke wait=3 exec=false
  nowait             containerWipInvoke wait=3 exec=false
  uninitialized      containerWipInvoke wait=30 max=3
  no_resources       containerWipInvoke wait=300 max=12
  fail               containerWipInvoke wait=30 max=3
  !                  $failure
}

# At this point the container has been launched.
containerWipContainerLaunched [container] {
  *                  containerWipSetupEnvironment
  !                  $failure
}

# Sets up an environment suitable for invoking SSH on the new container.
containerWipSetupEnvironment:containerWipEnvironmentReady [container] {
  yes                containerWipCheckSsh
  no                 containerWipSetupEnvironment wait=10 max=5
  !                  $failure
}

# Wait for the SSH daemon process to start.
containerWipCheckSsh:checkContainerResultStatus [container] {
  success            containerWipCheckResources
  wait               containerWipCheckSsh wait=10 exec=false
  *                  containerWipCheckSsh wait=30 max=3
  no_information     containerWipCheckSsh wait=30 max=3
  uninitialized      containerWipCheckSsh wait=10 max=30
  terminated         $container_terminated
  fail               containerWipCheckSsh wait=5 max=5

# If this fails we have a running container that must be shut down. The failure
# would likely be due to the SSH daemon not being started though it is also
# possible that the container was stopped.
  !                  $failure
}

# Check the container resources before starting.
containerWipCheckResources:checkContainerResources [container] {
  success            $success
  no_resources       containerWipCheckResources wait=300 max=12
  fail               $failure
  *                  $failure
  !                  $failure
}

EOT;
  }

  /**
   * Returns the name of the state that starts the container.
   *
   * @return string
   *   The state name.
   */
  protected function getContainerStartState() {
    return 'containerWipStart';
  }

  /**
   * Returns a state table block responsible for stopping the container.
   *
   * @param string $success
   *   The state to go to upon success.
   * @param string $failure
   *   The state to go to upon failure.
   *
   * @return string
   *   The block of text that can be inserted into a state table.
   */
  protected function getContainerStopTable($success, $failure) {
    $stop_state = $this->getContainerStopState();
    return <<<EOT
# Make sure that no failures after this point go back to the failure state
# because that would probably set up an infinite loop.
$stop_state [container] {
  *                  containerWipFinish
  !                  containerWipFinish
}

# Determine whether the container should be released when the Wip object
# completes. This behavior can be set using the releaseContainerUponCompletion
# method.
containerWipFinish:containerWipReleaseOnComplete [container] {
  yes                containerWipRelease
  no                 $success
  force              containerWipTerminate
  !                  $failure
}

# Wait for the container to be stopped.
# Note that this transition method looks specifically for SSH results because
# this state shares its context with other states.
containerWipRelease:checkSshStatus [container] {
  success            containerWipWaitForRelease
  wait               containerWipRelease wait=5 exec=false
  # We Don't have the ability to ssh in so we force the container to terminate.
  uninitialized      containerWipTerminate
  *                  containerWipRelease wait=10 max=3
  !                  $failure
}

# Wait for the container to be stopped.
containerWipTerminate:containerWipIsReleased [container] {
  success            $success wait=30
  nowait             $success
  wait               containerWipTerminate wait=5 exec=false
  fail               containerWipTerminate wait=30 exec=true max=3
  !                  $failure
}

# Waits for the ContainerDataSignal to come in, which indicates the container
# has exited and all container signals have been received.
containerWipWaitForRelease:containerWipDataSignalReceived [container] {
  success            $success
  wait               containerWipWaitForRelease wait=15 max=10 exec=false
  !                  $success
}

EOT;
  }

  /**
   * Returns the name of the state that stops the container.
   *
   * @return string
   *   The state name.
   */
  protected function getContainerStopState() {
    return 'containerWipStop';
  }

  /**
   * Initializes in preparation for starting a container.
   */
  public function containerWipStart() {
    $this->containerLaunched = FALSE;
    $this->containerEnvironment = NULL;
    $this->containerSignalReceived = FALSE;

    // Make all of the states that interact with the container use the same
    // context.
    $iterator = $this->getIterator();
    foreach (array(
      'containerWipSetupEnvironment',
      'containerWipRelease',
      'containerWipTerminate',
    ) as $state) {
      $context = $iterator->getWipContext($state, FALSE);
      $context->linkContext(self::CONTAINER_INVOKE);
    }
  }

  /**
   * Starts a new container instance.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function containerWipInvoke(WipContextInterface $wip_context) {

    // Now launch the container, using the callback command as an override.
    /** @var ContainerInterface $container */
    $container = $this->createContainer();
    $this->waitingForResources = FALSE;

    $this->addContainerOverrides($container);

    try {
      $process = $container->run();
    } catch (\Exception $e) {
      $this->log(WipLogLevel::ERROR, sprintf('Failed to start the container: %s', $e->getMessage()));
      $this->getMetricsUtility()->sendMetric('count', 'wip.system.job_status.failed_to_start_container', 1);
      $this->waitingForResources = TRUE;
      $notifier = $this->getNotifier();
      $severity = NotificationSeverity::ERROR;
      $metadata = array('task_id' => $this->getId());
      $notifier->notifyException($e, $severity, $metadata);
      return;
    }

    $api = $this->getContainerApi();
    $api->setContainerProcess($process, $wip_context, $this->getWipLog());
  }

  /**
   * Called when the container is successfully launched.
   */
  public function containerWipContainerLaunched() {
    $this->containerLaunched = TRUE;
  }

  /**
   * Configures the container environment.
   *
   * This environment will be used whenever an SSH call needs to be done inside
   * the container.
   *
   * This method is responsible for looking at the container metadata and
   * populating an Environment instance with the appropriate host and port.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function containerWipSetupEnvironment(WipContextInterface $wip_context) {
    $process = $this->getContainerProcess($wip_context);
    try {
      $process_environment = $process->getEnvironment();
      if (empty($process_environment->getCurrentServer())) {
        // Get the environment from the container, as it should now be populated
        // with the host and port.
        $container_environment = $process->getContainer()->getEnvironment();
        $servers = $container_environment->getServers();
        if (empty($servers)) {
          $this->log(WipLogLevel::ERROR, sprintf("Could not get the servers from the container."));
        }
        $process_environment->setServers($servers);
        $process_environment->selectNextServer();
        $process_environment->setPort($container_environment->getPort());
        $this->setContainerEnvironment($process_environment);
      }
    } catch (\Exception $e) {
      // This is already logged.
    }
  }

  /**
   * Indicates whether the container environment is ready.
   *
   * @return string
   *   'yes' - The container environment is ready.
   *   'no'  - The container environment is not ready.
   */
  public function containerWipEnvironmentReady() {
    $result = 'no';
    $environment = $this->getContainerEnvironment();
    if (!empty($environment)) {
      $result = 'yes';
    }
    return $result;
  }

  /**
   * Verifies we can connect to the ssh daemon running in the container.
   *
   * This method uses a simple SSH call to verify that the SSH daemon process
   * has started and the container is ready to perform work. Due to the time it
   * takes to start the necessary services within the container it is not
   * uncommon for this to fail a few times before moving to the next state.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function containerWipCheckSsh(WipContextInterface $wip_context) {
    try {
      $ssh = $this->getSsh('Verify the SSH connection.', $this->getContainerEnvironment());
      $result = $ssh->execCommand('id');
      $this->getSshApi()->setSshResult($result, $wip_context, $this->getWipLog());
      if ($result->isSuccess()) {
        $container = $this->getContainer();
        $container->setConfigured(TRUE);
      }
    } catch (\Exception $e) {
      // An exception is thrown if the daemon process has not started or if the
      // login process failed.
      $this->log(WipLogLevel::ALERT, 'The SSH service within the container is not yet ready.');
      $this->log(
        WipLogLevel::DEBUG,
        sprintf('The SSH call failed. Reason: %s', $e->getMessage())
      );
    }
  }

  /**
   * Checks whether the container has enough resources to proceed.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function containerWipCheckResources(WipContextInterface $wip_context) {
    $disk_usage = 100;
    $ssh = $this->getSsh('Check container resources.', $this->getContainerEnvironment());
    $result = $ssh->execCommand("df / | awk '{ print $5 }' | tail -n1 | cut -d '%' -f 1");
    if ($result->isSuccess()) {
      $disk_usage = (int) $result->getStdout();
    }
    /** @var object $wip_context */
    $wip_context->container_disk_usage = $disk_usage;
  }

  /**
   * Checks the container to ensure we have enough resources to start work.
   *
   * Compares the container disk usage with the configured max.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   *
   * @return string
   *   'no_resources' - The container is waiting for resources.
   *   'success' - The container is ok to start work.
   *   'fail' - The container disk usage is not set.
   */
  public function checkContainerResources(WipContextInterface $wip_context) {
    if (isset($wip_context->container_disk_usage)) {
      $this->log(
        WipLogLevel::INFO,
        sprintf('The container host is using %s%% disk.', $wip_context->container_disk_usage)
      );
      $max_disk = WipFactory::getInt('$acquia.container.maxdiskpercent', 75);
      if ($wip_context->container_disk_usage < $max_disk) {
        return 'success';
      } else {
        $this->log(WipLogLevel::ERROR, 'Waiting for build resources.', TRUE);

        $notifier = $this->getNotifier();
        $severity = NotificationSeverity::ERROR;
        $metadata = array(
          'task_id' => $this->getId(),
          'disk_usage' => $wip_context->container_disk_usage,
        );
        $notifier->notifyError(
          'ContainerResources',
          'Container disk space is insufficient to start a build.',
          $severity,
          $metadata
        );
      }

      return 'no_resources';
    } else {
      $this->log(
        WipLogLevel::FATAL,
        'The container disk usage is not set.'
      );

      return 'fail';
    }
  }

  /**
   * Initializes in preparation for stopping the container.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function containerWipStop(WipContextInterface $wip_context) {
  }

  /**
   * {@inheritdoc}
   */
  public function containerWipFinish() {
    $result = $this->getContainerResult();
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
   * Releases the container, allowing it to do cleanup if needed.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function containerWipRelease(WipContextInterface $wip_context) {
    $environment = $this->getContainerEnvironment();
    if (NULL !== $environment) {
      $ssh_api = $this->getSshApi();
      if ('STOPPED' === $this->getContainer()->getContainerNextStatus()) {
        $this->containerLaunched = FALSE;
        $this->containerEnvironment = NULL;
        $ssh_api->clearSshResults($wip_context, $this->getWipLog());
      } else {
        $ssh = $this->getSsh('Release the container.', $this->getContainerEnvironment());
        $result = $ssh->execCommand('touch /tmp/shutdownContainer');
        $ssh_api->setSshResult($result, $wip_context, $this->getWipLog());
        if ($result->isSuccess()) {
          $this->containerLaunched = FALSE;
          $this->containerEnvironment = NULL;
        }
      }
    }
  }

  /**
   * Terminates the container.
   *
   * @param bool $force
   *   Force a termination of the container.
   */
  public function containerWipTerminate($force = FALSE) {
    $container = $this->getContainer();
    if (!empty($container) && ($force || !$container->hasStopped())) {
      try {
        $container->kill();
      } catch (\Exception $e) {
        $this->log(WipLogLevel::ERROR, sprintf('Failed to release container: %s', $e->getMessage()));
      }
    }
    $this->containerLaunched = FALSE;
    $this->containerEnvironment = NULL;
  }

  /**
   * Checks whether the container has launched and is ready for initialization.
   *
   * @return string
   *   'no_resources' - The container is waiting for resources.
   *   'success' - The container has launched.
   *   'wait' - The container has not launched yet.
   *   'spin' - Signal received but the container is not in the RUNNING state.
   *   'nowait' - Do not wait for the container to launch.
   *   'uninitialized' - No container process was found in the context.
   *   'fail' - An error occurred when looking at the container status.
   */
  public function containerWipWaitForLaunch() {
    $result = 'uninitialized';

    // If the launch failed because there were no resources, there will be no
    // container to query, so bail out immediately.
    if ($this->waitingForResources) {
      return 'no_resources';
    }

    try {
      $container = $this->getContainer();
      if (!empty($container)) {
        if ($container->hasStarted()) {
          $result = 'success';
        } elseif ($container->launchFailed()) {
          $result = 'fail';
        } elseif ($this->containerSignalReceived()) {
          // At this point the container signal was received so stop using the
          // fail-safe timeout and spin instead.
          $result = 'spin';
        } elseif (!WipFactory::getBool('$acquia.container.waitforstartsignal', TRUE)) {
          // Don't wait for the container to start. This may be useful for
          // using a long-running container for local development.
          $result = 'nowait';
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
   * Indicates whether the container's ready signal has been received.
   *
   * @return bool
   *   TRUE if the signal was received; FALSE otherwise.
   */
  private function containerSignalReceived() {
    return $this->containerSignalReceived;
  }

  /**
   * {@inheritdoc}
   */
  public function onSignal(SignalInterface $signal) {
    if ($signal instanceof ContainerCompleteSignal) {
      $this->containerSignalReceived = TRUE;
    }
  }

  /**
   * Indicates whether the container should be released upon task completion.
   *
   * @return string
   *   'yes' - Release the container before exiting.
   *   'no' - Leave the container running.
   *   'force' - Force quit the container without doing any cleanup.
   */
  public function containerWipReleaseOnComplete() {
    $result = 'yes';
    if ($this->getReleaseContainerUponCompletion() === FALSE) {
      $result = 'no';
    }
    if ($this->getTerminateContainerUponCompletion() === TRUE) {
      $result = 'force';
    }
    return $result;
  }

  /**
   * Indicates whether the container is still running.
   *
   * @return string
   *   'success' - The container is no longer running.
   *   'wait' - The container is still running.
   *   'nowait' - Do not wait for the container to be released.
   *   'fail' - Failed to release the container.
   */
  public function containerWipIsReleased() {
    $wait_for_stop = WipFactory::getBool('$acquia.container.waitforstop', FALSE);
    $wait_for_stop |= $this->getWaitForTerminate();
    if (!$wait_for_stop) {
      $result = 'nowait';
    } else {
      $container = $this->getContainer();
      if ($container->hasStopped()) {
        $result = 'success';
      } else {
        $result = 'wait';
      }
    }
    return $result;
  }

  /**
   * Waits for the container.
   */
  public function containerWipWaitForRelease() {
  }

  /**
   * Indicates whether the ContainerDataSignal has been received yet.
   *
   * @return string
   *   'success' - The container has received the ContainerDataSignal.
   *   'wait' - The container has not received the ContainerDataSignal.
   */
  public function containerWipDataSignalReceived() {
    $data_signal = $this->getContainerDataSignal();
    if (NULL === $data_signal) {
      $result = 'wait';
    } else {
      $result = 'success';
    }
    return $result;
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
    $readable = !$this->containerLaunched;
    if ($readable) {
      // Log a more detailed, non-user-readable message for the developer.
      $this->log(WipLogLevel::FATAL, 'Failed to launch a container for the task.', FALSE);
    }
    $this->log(WipLogLevel::FATAL, 'The task has failed.', $readable);

    $this->cleanUp();
  }

  /**
   * Releases the container if necessary.
   */
  public function onTerminate() {
    if ($this->containerLaunched && $this->getReleaseContainerUponCompletion()) {
      $this->containerWipTerminate(TRUE);
      $container = $this->getContainer();
      if ($container->hasStopped()) {
        $this->log(WipLogLevel::INFO, 'The container has been stopped.', TRUE);
      } else {
        $this->log(WipLogLevel::ERROR, 'Failed to stop the container.', TRUE);
      }
    }
    parent::onTerminate();
  }

  /**
   * Gets the environment for the associated container.
   *
   * @return EnvironmentInterface
   *   The environment used for interacting with the container.
   */
  public function getContainerEnvironment() {
    return $this->containerEnvironment;
  }

  /**
   * Sets the environment that will be used for the container.
   *
   * @param EnvironmentInterface $environment
   *   The environment that will be used to SSH to the container.
   */
  public function setContainerEnvironment(EnvironmentInterface $environment) {
    $this->containerEnvironment = $environment;
  }

  /**
   * Gets the container.
   *
   * @param WipContextInterface $wip_context
   *   Optional. The WipContextInterface is the interface through which a Wip
   *   object interacts with its runtime environment and provides a means of
   *   sharing data between a state method and a transition method.
   *
   * @return ContainerInterface
   *   The container, if available.
   */
  protected function getContainer(WipContextInterface $wip_context = NULL) {
    $result = NULL;
    if (NULL === $wip_context) {
      // By default use the WipContext instance associated with the state that
      // started the container.
      $wip_context = $this->getIterator()->getWipContext('containerWipInvoke');
    }
    $process = $this->getContainerProcess($wip_context);
    if (!empty($process)) {
      $result = $process->getContainer();
    } else {
      $container_result = $this->getContainerResult($wip_context);
      if (!empty($container_result)) {
        $result = $container_result->getContainer();
      }
    }
    return $result;
  }

  /**
   * Gets the container process from the specified WipContext instance.
   *
   * @param WipContextInterface $wip_context
   *   Optional. The WipContextInterface is the interface through which a Wip
   *   object interacts with its runtime environment and provides a means of
   *   sharing data between a state method and a transition method.
   *
   * @return ContainerProcessInterface
   *   The process.
   */
  private function getContainerProcess(WipContextInterface $wip_context = NULL) {
    $result = NULL;
    if (NULL === $wip_context) {
      $wip_context = $this->getIterator()->getWipContext(self::CONTAINER_INVOKE);
    }
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
   *   Optional. The WipContextInterface is the interface through which a Wip
   *   object interacts with its runtime environment and provides a means of
   *   sharing data between a state method and a transition method.
   *
   * @return ContainerResultInterface
   *   The result, if available.
   */
  private function getContainerResult(WipContextInterface $wip_context = NULL) {
    $result = NULL;
    if (NULL === $wip_context) {
      $wip_context = $this->getIterator()->getWipContext(self::CONTAINER_INVOKE);
    }
    $container_api = $this->getContainerApi();
    $results = $container_api->getContainerResults($wip_context);
    if (!empty($results) && is_array($results)) {
      $result = array_shift($results);
    }
    return $result;
  }

  /**
   * Returns the results of container processes associated with the context.
   *
   * This can be used to process any combination of processes and results from
   * the SSH service, the Wip Task service, the Acquia Cloud service, and the
   * Container service. If the container status has changed this will be
   * reflected in the result.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface instance where the results and/or processes are
   *   stored.
   *
   * @return string
   *   'success' - All tasks were completed successfully.
   *   'wait' - One or more processes are still running.
   *   'uninitialized' - No results or processes have been added.
   *   'fail' - At least one task failed.
   *   'ssh_fail' - An Ssh command failed to connect.
   *   'ready' - The container is up and ready to receive the task.
   *   'running' - The container process is still running.
   *   'no_progress' - An call is still running but no progress detected.
   *   'no_information' - Could not retrieve information about the running container.
   *   'terminated' - The container has terminated.
   */
  public function checkContainerResultStatus(WipContextInterface $wip_context) {
    try {
      $container = $this->getContainer();
      $container_status = $container->getContainerNextStatus();
      // Do this check first so that any signal associated with this context can
      // be consumed.
      $result = parent::checkResultStatus($wip_context);
      if ($container_status === ContainerInterface::STOPPED) {
        $result = 'terminated';
      }
      return $result;
    } catch (ContainerInfoUnavailableException $e) {
      $this->log(WipLogLevel::ERROR, 'Could not retrieve container information: ' . $e->getMessage());
      return 'no_information';
    }
  }

  /**
   * Handles the failure state.
   *
   * @param WipContextInterface $wip_context
   *   The current WIP context.
   * @param \Exception|null $exception
   *   The received exception.
   */
  public function failure(WipContextInterface $wip_context, \Exception $exception = NULL) {
    // Did the failure occur due to a terminated container?
    $exit_message = $this->getExitMessage();
    if (empty($exit_message) &&
      $this->getContainer()->getContainerNextStatus() === ContainerInterface::STOPPED) {
      $this->setExitMessage(new ExitMessage('The container terminated unexpectedly.', WipLogLevel::FATAL));
    }
    if ($this->getExitCode() === IteratorStatus::OK) {
      $this->setExitCode(IteratorStatus::ERROR_SYSTEM);
    }

    parent::failure($wip_context, $exception);
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions($options) {
    parent::setOptions($options);
    if (!empty($options->containerMaxDisk) && is_numeric($options->containerMaxDisk)) {
      $this->setMaxDisk(floatval($options->containerMaxDisk));
    }
    if (!empty($options->containerMaxTime) && is_numeric($options->containerMaxTime)) {
      $this->setMaxTime(intval($options->containerMaxTime));
    }
  }

  /**
   * Sets the maximum amount of disk space the container is allowed to use.
   *
   * @param float $max_disk
   *   The maximum amount of disk space available, expressed in gigabytes.
   */
  public function setMaxDisk($max_disk) {
    if (!is_float($max_disk) || $max_disk < 0) {
      throw new \InvalidArgumentException('The "max_disk" parameter must be a positive float value.');
    }
    $this->maxDisk = $max_disk;
  }

  /**
   * Gets the maximum amount of disk space the container is allowed to use.
   *
   * @return float
   *   The maximum amount of disk space available, in gigabytes.
   */
  public function getMaxDisk() {
    return $this->maxDisk;
  }

  /**
   * Sets the maximum duration the container is allowed to run.
   *
   * @param int $max_time
   *   The maximum duration the container is allowed to run, expressed in
   *   seconds.
   */
  public function setMaxTime($max_time) {
    if (!is_int($max_time) || $max_time < 0) {
      throw new \InvalidArgumentException('The "max_time" parameter must be a positive integer value.');
    }
    $this->maxTime = $max_time;
  }

  /**
   * Gets the maximum duration the container is allowed to run.
   *
   * This method takes into account the maximum container execution time
   * configured for this Wip service deployment, which is used as an absolute
   * maximum no matter what the client requests.
   *
   * @return int
   *   The maximum duration the container is allowed to run, expressed in
   *   seconds.
   */
  public function getMaxTime() {
    $result = $this->maxTime;

    // This is the maximum duration allowed by the configuration.
    $container_max_time = WipFactory::getInt('$acquia.container.maxruntime', 3600);
    if ($this->maxTime > $container_max_time) {
      // Don't allow the client to exceed the configuration maximum.
      $result = $container_max_time;
    }
    return $result;
  }

  /**
   * Gets the first unprocessed ContainerTerminate signal, if any.
   *
   * @return ContainerTerminatedSignalInterface|null
   *   The signal. If there is no such signal, NULL is returned instead.
   */
  protected function getContainerTerminatedSignal() {
    $container_api = $this->getContainerApi();
    return $container_api->getContainerTerminatedSignal($this->getId());
  }

  /**
   * Gets the first unprocessed ContainerData signal, if any.
   *
   * @return ContainerDataSignalInterface|null
   *   The signal. If there is no such signal, NULL is returned instead.
   */
  protected function getContainerDataSignal() {
    $container_api = $this->getContainerApi();
    return $container_api->getContainerDataSignal($this->getId());
  }

  /**
   * Adds container overrides.
   *
   * @param ContainerInterface $container
   *   The container.
   */
  protected function addContainerOverrides(ContainerInterface $container) {
    // Create a callback command the container can invoke when the SSH daemon
    // has been launched. That is the point that the container becomes useful.
    // This callback saves unnecessary polling.
    /** @var SignalCallbackHttpTransportInterface $callback_handler */
    $callback_handler = $this->dependencyManager->getDependency('acquia.wip.handler.signal');
    $url = $callback_handler->getCallbackUrl($this->getId());
    $callback = new UriCallback($url);
    $callback_data = new \stdClass();

    // The pid can not be set because the curl command has to be generated
    // before the container is launched. Substitute the Wip object ID.
    $callback_data->pid = $this->getId();
    $callback_data->startTime = time();
    $callback_data->classId = '$acquia.wip.signal.container.complete';
    $callback->setData($callback_data);
    try {
      $authentication = WipFactory::getObject('acquia.wip.uri.authentication');
      $callback->setAuthentication($authentication);
    } catch (\Exception $e) {
      // No authentication is being used.
    }

    // Provide a command that can be used to send a signal back to this Wip
    // instance that indicates when the container is ready. This saves a lot of
    // polling.
    $signal = new Signal();
    $signal->setData($callback_data);
    $signal->setType(SignalType::COMPLETE);
    $container->addContainerOverride('SIGNAL_USER', escapeshellarg($callback->getAuth()), TRUE);
    $container->addContainerOverride('SIGNAL_URL', escapeshellarg($url));
    $container->addContainerOverride('SIGNAL_BODY', escapeshellarg($callback->getSignalBodyJson($signal)));
    $container->addContainerOverride('WORKLOAD_MAX_TIME', escapeshellarg($this->getMaxTime()));
    $container->addContainerOverride('WORKLOAD_MAX_DISK', escapeshellarg($this->getMaxDisk()));

    // Provide a means of overriding the number of seconds between the start of
    // the entry-point script and the time that the container changes to the
    // RUNNING state.
    $entry_point_delay = WipFactory::getInt('$acquia.container.entrypoint.delay', 5);
    $container->addContainerOverride('ENTRY_POINT_DELAY', (string) $entry_point_delay);


    // Indicate to the entry-point script that we will be using a simple
    // container (one that is only used for isolation).
    $container->addContainerOverride('CONTAINER_MODE', 'simple');

    // Provide a public key that will be used to log into the container.
    $container_public_key = $container->getPublicKey();
    $container->addContainerOverride('CONTAINER_SSH_KEY', $container_public_key, TRUE);

    // Provide a means for developers to use local keys to log into the
    // container. If provided, the developer's public key will be added to the
    // authorized_keys file.
    $container_dev_key_path = WipFactory::getString('$acquia.wip.container.dev_public_key_file', NULL);
    if ($container_dev_key_path !== NULL && file_exists($container_dev_key_path)) {
      $dev_public_key = file_get_contents($container_dev_key_path);
      $container->addContainerOverride('DEVELOPER_SSH_KEY', $dev_public_key, TRUE);
    }
  }

}
