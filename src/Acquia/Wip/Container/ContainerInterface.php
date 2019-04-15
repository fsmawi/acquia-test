<?php

namespace Acquia\Wip\Container;

use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\WipTaskConfig;

/**
 * Defines a common interface for interacting with containers.
 */
interface ContainerInterface {

  /**
   * The external port for SSH.
   */
  const PORT_TYPE_SSH = 22;

  /**
   * The external port for HTTP.
   */
  const PORT_TYPE_HTTP = 8007;

  /**
   * The external port for HTTPS.
   */
  const PORT_TYPE_HTTPS = 443;

  /**
   * Container status that indicates the container is running.
   */
  const RUNNING = 'RUNNING';

  /**
   * Container status that indicates the container has stopped.
   */
  const STOPPED = 'STOPPED';

  /**
   * Container status that indicates the container is moving to a new status.
   */
  const PENDING = 'PENDING';

  /**
   * Gets the process ID of the container.
   *
   * @return string
   *   The process ID.
   */
  public function getPid();

  /**
   * Sets the container's process ID.
   *
   * @param string $pid
   *   The unique identifier of the container.
   */
  public function setPid($pid);

  /**
   * Sets the Wip ID associated with this container.
   *
   * @param int $id
   *   The Wip object ID that created this container.
   */
  public function setWipId($id);

  /**
   * Gets the Wip ID associated with this container.
   *
   * @return int
   *   The Wip object ID that created this container.
   */
  public function getWipId();

  /**
   * Gets the task group name that runs in the associated container.
   *
   * @return string
   *   The WIP task group name.
   */
  public function getGroupName();

  /**
   * Sets the task group name that runs in the associated container.
   *
   * @param string $group_name
   *   The WIP task group name.
   */
  public function setGroupName($group_name);

  /**
   * Returns the Environment instance associated with this instance.
   *
   * @return EnvironmentInterface
   *   The Environment instance.
   */
  public function getEnvironment();

  /**
   * Sets the Environment instance associated with this instance.
   *
   * @param EnvironmentInterface $environment
   *   The Environment instance.
   *
   * @throws \InvalidArgumentException
   *   If the hosting sitegroup has not been set or if the hosting environment
   *   name has not been set.
   */
  public function setEnvironment(EnvironmentInterface $environment);

  /**
   * Adds the specified override.
   *
   * @param string $key
   *   The override key name.
   * @param string $value
   *   The value.
   * @param bool $secure
   *   Optional. If TRUE, the override will be treated as a secure variable and
   *   will be prevented from being used for anything except launching a task.
   */
  public function addContainerOverride($key, $value, $secure = FALSE);

  /**
   * Starts this container instance.
   *
   * @return ContainerProcessInterface
   *   The process object that represents the container.
   *
   * @throws \RuntimeException
   *   If the container has already been executed.
   */
  public function run();

  /**
   * Indicates whether the container has started.
   *
   * This method should return TRUE only when the container is ready to
   * configure and assign work to.
   *
   * @return bool
   *   TRUE if the container has been started; FALSE otherwise.
   */
  public function hasStarted();

  /**
   * Indicates whether the container has stopped.
   *
   * @return bool
   *   TRUE if the container has stopped; FALSE otherwise.
   */
  public function hasStopped();

  /**
   * Checks whether the container has been configured.
   *
   * @return bool
   *   TRUE if the container has been configured; FALSE otherwise.
   */
  public function isConfigured();

  /**
   * Indicates whether the container launch has failed.
   *
   * @return bool
   *   TRUE if the container launch has failed; FALSE otherwise.
   */
  public function launchFailed();

  /**
   * Sets whether the container has been configured.
   *
   * @param bool $configured
   *   Whether the container has been configured.
   */
  public function setConfigured($configured);

  /**
   * Performs actions required to configure and delegate work to the container.
   *
   * Implementations should pass off any required configurations to the
   * container in addition to the container's workload.
   *
   * @param ContainerProcessInterface $process
   *   The process the container is being initialized for.
   * @param WipTaskConfig $configuration
   *   The task configuration.
   */
  public function initializeContainer(
    ContainerProcessInterface $process,
    WipTaskConfig $configuration
  );

  /**
   * Kills the container instance managed by this process.
   */
  public function kill();

  /**
   * Gets the hostname where the container is running.
   *
   * @return string
   *   The hostname.
   */
  public function getHost();

  /**
   * Sets the hostname of this container.
   *
   * @param string $host
   *   A fully-qualified host name or an IP address.
   */
  public function setHost($host);

  /**
   * Gets the container's port number for the specified type.
   *
   * @param int $port_type_id
   *   The port type ID that indicates what kind of port is being requested.
   *
   * @return int
   *   The port number for the specified port type.
   *
   * @throws \InvalidArgumentException
   *   If the specified port type is not available.
   */
  public function getPort($port_type_id);

  /**
   * Gets all the mapped container ports.
   *
   * @return array
   *   List of mapped ports.
   *
   * @throws \RuntimeException
   *   If the container is not available.
   */
  public function getPortMappings();

  /**
   * Gets the container public key.
   *
   * If the container key does not yet exist, it will be created.
   *
   * @return string
   *   The public key.
   *
   * @throws \RuntimeException
   *   If the key does not exist and could not be created.
   */
  public function getPublicKey();

  /**
   * Gets the path to the public SSH key used to access the container.
   *
   * @return string
   *   The path to the public SSH key.
   */
  public function getPublicKeyPath();

  /**
   * Gets the current container status.
   *
   * @param bool $force_load
   *   Optional. If TRUE the container status will be reloaded. Otherwise the
   *   cached value will be used.
   *
   * @return string
   *   Indicates the task status. "PENDING" indicates the container is
   *   currently working toward another state, either "RUNNING" or "STOPPED".
   *   "RUNNING" indicates the container launch has completed and the container
   *   is running. "STOPPED" indicates the container is not running.
   */
  public function getContainerStatus($force_load = FALSE);

  /**
   * Gets the container next status.
   *
   * This is helpful in the case that the container's current status is
   * "PENDING". Looking at the next status it is possible to tell if the status
   * is going to the "STOPPED" state or the "RUNNING" state.
   *
   * Once the container has successfully been started this is the status that
   * should be considered because it indicates the next lifecycle state of the
   * container. For example the container may be running but has been stopped
   * in the AWS user interface. The next status will indicate the container has
   * been stopped before the current status will. This can move the Wip object
   * to completion more efficiently than waiting for the full container
   * shutdown.
   *
   * @param bool $force_load
   *   Optional. If TRUE the container status will be reloaded. Otherwise the
   *   cached value will be used.
   *
   * @return string
   *   Indicates the next task status. "RUNNING" indicates the container is
   *   either already running or working to move from the "PENDING" state to
   *   the "RUNNING" state. "STOPPED" indicates the container is already
   *   stopped or is working to move from the "RUNNING" or "PENDING"' state to
   *   the "STOPPED" state or is already in the "STOPPED" state.
   */
  public function getContainerNextStatus($force_load = FALSE);

  /**
   * Marks the specified key as a secure key.
   *
   * This is used to secure data stored in the database, helping prevent issues
   * in which passwords or SSH keys are written to the database in clear text.
   *
   * @param string $key
   *   The key containing sensitive information.
   */
  public function addSecureOverrideKey($key);

  /**
   * Indicates whether the value associated with the specified key is secure.
   *
   * @param string $key
   *   The key name.
   *
   * @return bool
   *   TRUE if the value is secure; FALSE otherwise.
   */
  public function isSecureOverrideKey($key);

}
