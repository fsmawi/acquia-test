<?php

namespace Acquia\WipIntegrations\Container;

use Acquia\WipIntegrations\DoctrineORM\Entities\EcsClusterStoreEntry;
use Acquia\WipIntegrations\DoctrineORM\TaskDefinitionStore;
use Acquia\WipInterface\EcsClusterStoreInterface;
use Acquia\WipService\App;
use Acquia\Wip\Container\AbstractContainer;
use Acquia\Wip\Container\ContainerInterface;
use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\Environment;
use Acquia\Wip\Exception\ContainerInfoUnavailableException;
use Acquia\Wip\Exception\ContainerResourcesException;
use Acquia\Wip\Implementation\ContainerApi;
use Acquia\Wip\Security\EncryptTrait;
use Acquia\Wip\Storage\StateStoreInterface;
use Acquia\Wip\Utility\ArrayUtility;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogLevel;
use Aws\Ec2\Ec2Client;
use Aws\Ecs\EcsClient;
use Aws\Ecs\Exception\EcsException;
use GuzzleHttp\Exception\ClientException;
use Guzzle\Service\Resource\Model;

/**
 * An Amazon ECS implementation of a Container handler.
 */
class EcsContainer extends AbstractContainer implements ContainerInterface, DependencyManagedInterface {

  use EncryptTrait;

  /**
   * The "family" name prefix to use for all containerized tasks that we run.
   *
   * @var string
   */
  const ECS_TASK_FAMILY_PREFIX = 'WIP';

  /**
   * The number of seconds task info is considered fresh.
   *
   * Beyond this time, the task info will be reloaded.
   */
  const TASK_INFO_TTL = 5;

  /**
   * An ECS client that can be used for testing.
   *
   * @var EcsClient
   */
  private static $testEcsClient;

  /**
   * An AWS EC2 client object to be used in testing.
   *
   * @var Ec2Client
   */
  private static $testEc2Client;

  /**
   * The last-fetched list of tasks from AWS API.
   *
   * In order to not fetch the task list from AWS when we only need to know
   * something about the last status, we cache the last known task list here.
   * This cached list should only be used by callers needing data that is not
   * time-sensitive.  Any callers needing current data should refresh the list
   * from the API.
   *
   * @var array
   */
  private $lastTaskList = array();

  /**
   * The ECS cluster used to run the task.
   *
   * @var EcsClusterStoreInterface
   */
  private $ecsCluster;

  /**
   * Customized overrides.
   *
   * @var array
   */
  private $overrides = array();

  /**
   * Creates a new instance of EcsContainer.
   */
  public function __construct() {
    parent::__construct();
    $this->setDebug(App::getApp()['config.global']['debug']);
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    $parent_dependencies = parent::getDependencies();
    return array(
      'acquia.wip.storage.task_definition' => 'Acquia\WipInterface\TaskDefinitionStoreInterface',
      'acquia.wip.storage.ecs_cluster'     => 'Acquia\WipInterface\EcsClusterStoreInterface',
      'acquia.wip.containers'              => 'Acquia\Wip\WipContainerInterface',
      'acquia.wip.storage.state'           => 'Acquia\Wip\Storage\StateStoreInterface',
    ) + $parent_dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function addContainerOverride($key, $value, $secure = FALSE) {
    if (!is_string($key) || empty($key)) {
      throw new \InvalidArgumentException('The "key" parameter must be a non-empty string.');
    }
    if (!is_string($value) || empty($value)) {
      throw new \InvalidArgumentException('The "value" parameter must be a non-empty string.');
    }
    if ($secure) {
      $value = $this->encrypt($value);
      $this->addSecureOverrideKey($key);
    }
    $this->overrides[$key] = $value;
  }

  /**
   * Returns container overrides to apply when running the task.
   *
   * @see http://docs.aws.amazon.com/aws-sdk-php/v2/api/class-Aws.Ecs.EcsClient.html#_runTask
   *
   * @return array
   *   An associative array containing container overrides.
   */
  private function getContainerOverrides() {
    $overrides = array(
      'name' => $this->getEcsTaskName(),
      'environment' => array(
        array(
          'name' => 'ACQUIA_WIP_WIPFLUSHINGLOGSTORE_ENDPOINT',
          'value' => WipFactory::getString('$acquia.wip.wipflushinglogstore.endpoint'),
        ),
        array(
          'name' => 'SEGMENT_PROJECT_KEY',
          'value' => App::getApp()['segment.options']['project_key'],
          'secure' => TRUE,
        ),
        array(
          'name' => 'BUGSNAG_API_KEY',
          'value' => App::getApp()['bugsnag.options']['apiKey'],
          'secure' => TRUE,
        ),
        array(
          'name' => 'BUGSNAG_STAGE',
          'value' => App::getApp()['bugsnag_stage'],
        ),
        array(
          'name' => 'WIP_SERVICE_USERNAME',
          'value' => App::getApp()['security.client_users']['ROLE_ADMIN']['username'],
          'secure' => TRUE,
        ),
        array(
          'name' => 'WIP_SERVICE_PASSWORD',
          'value' => App::getApp()['security.client_users']['ROLE_ADMIN']['password'],
          'secure' => TRUE,
        ),
      ),
    );

    // Mark all secure overrides so they will not appear in the database.
    foreach ($overrides['environment'] as $variable) {
      if (is_array($variable) && !empty($variable['secure']) && !empty($variable['name'])) {
        $this->addSecureOverrideKey($variable['name']);
        unset($variable['secure']);
      }
    }

    if (!empty($this->overrides)) {
      foreach ($this->overrides as $key => $value) {
        if ($this->isSecureOverrideKey($key)) {
          $value = $this->decrypt($value);
        }
        $new_override = array('name' => $key, 'value' => $value);
        $overrides['environment'][] = $new_override;
      }
    }
    return array('containerOverrides' => array($overrides));
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    /** @var TaskDefinitionStore $storage */
    $storage = $this->dependencyManager->getDependency('acquia.wip.storage.task_definition');
    $stored_definition = $storage->get($this->getEcsTaskFamilyName(), $this->getEcsRegion());
    $placementStrategy = [
      'field' => 'instanceId',
      'type' => 'spread',
    ];

    $ecs_client = $this->getEcsClient();
    $cluster_object = $this->getEcsCluster();
    $cluster = $cluster_object->getCluster();

    if ($this->taskDefinitionChanged($stored_definition)) {
      $stored_definition = $this->registerTask();
    }

    $task_definition_name = $this->getEcsTaskFamilyName() . ':' . $stored_definition['revision'];

    // Run a container for the wip task.
    try {
      // @see http://docs.aws.amazon.com/aws-sdk-php/latest/class-Aws.Ecs.EcsClient.html#_runTask
      /** @var Model $response */
      $response = $ecs_client->runTask(array(
        // Required.
        'taskDefinition' => $task_definition_name,
        'cluster' => $cluster,
        'overrides' => $this->getContainerOverrides(),
        'placementStrategy' => [$placementStrategy],
      ));
    } catch (\Exception $e) {
      if ($e instanceof EcsException || $e instanceof ClientException) {
        $this->getWipLog()->log(
          WipLogLevel::ERROR,
          sprintf(
            'Unable to start the container. Registering the task definition and retrying. response: %s',
            $e->getMessage()
          ),
          $this->getWipId()
        );

        // @todo - we can query the API here to find out if a task was registered,
        // but the result is not 100% normalized with our own, so would need to be
        // careful when comparing.
        // This exception *might* mean that we did not yet register the task
        // definition for this task. We can do that now instead.
        $this->registerTask();
        // Try again, now that we've registered the task definition.
        $response = $ecs_client->runTask(array(
          // Required.
          'taskDefinition' => $task_definition_name,
          'cluster' => $cluster,
          'overrides' => $this->getContainerOverrides(),
          'placementStrategy' => $placementStrategy,
        ));
      } else {
        throw $e;
      }
    }

    $job_data = $response->get('tasks');
    $this->secureEcsTaskData($job_data);
    $this->getWipLog()->log(
      WipLogLevel::TRACE,
      sprintf(
        'ECS runTask response: %s',
        var_export($job_data, TRUE)
      ),
      $this->getWipId()
    );

    // Check for ECS failures, these will mostly happen because we are out of
    // memory or proc on the cluster instances.
    if (!empty($response['failures'])) {
      $reasons = [];
      foreach ($response['failures'] as $failure) {
        $reasons[] = $failure['reason'];
      }
      $failure_reasons = implode(',', $reasons);
      if (FALSE !== strpos($failure_reasons, 'RESOURCE:')) {
        throw new ContainerResourcesException($failure_reasons);
      } else {
        throw new \Exception(sprintf('ECS task failed to start, reason(s): %s', $failure_reasons));
      }
    }

    // We assume that since we run only one task, there is only one returned.
    $tasks = $response['tasks'];
    $pid = reset($tasks)['taskArn'];
    $message = 'Unable to fetch ECS task ARN. Make sure you have access to the ECS runTask operation. Check for any failures in the ECS runTask response.';
    if (empty($pid)) {
      $this->getWipLog()->log(
        WipLogLevel::DEBUG,
        $message
      );
    }

    $this->setPid($pid);
    $this->debug(sprintf('Started ECS task %s', $this->getPid()));
    return parent::run();
  }

  /**
   * {@inheritdoc}
   */
  public function launchFailed() {
    $result = parent::launchFailed();
    if (!$result && $this->getContainerNextStatus() === 'STOPPED') {
      $this->setLaunchFailed();
      $result = TRUE;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function loadHostAndPorts() {
    if (!$this->running) {
      throw new \RuntimeException(
        'Unable to obtain host and port for a container that is not running.'
      );
    }
    $ec2_client = $this->getEc2Client();
    $ecs_client = $this->getEcsClient();

    $cluster = $this->getEcsCluster()->getCluster();

    $container_instance_data = $ecs_client->describeContainerInstances(array(
      'containerInstances' => array(
        reset($this->lastTaskList)['containerInstanceArn'],
      ),
      'cluster' => $cluster,
    ));
    $container_instances = $container_instance_data['containerInstances'];
    $container_instance = $ec2_client->describeInstances(array(
      'InstanceIds' => array(
        reset($container_instances)['ec2InstanceId'],
      ),
    ));

    $reservations = $container_instance['Reservations'];
    $reservation = reset($reservations);
    $instance = reset($reservation['Instances']);
    $this->getWipLog()->log(
      WipLogLevel::TRACE,
      sprintf(
        'ECS instance: %s',
        print_r($instance, TRUE)
      ),
      $this->getWipId()
    );
    $this->setHost($instance['PublicDnsName']);
    $container = reset(reset($this->lastTaskList)['containers']);

    $this->getWipLog()->log(
      WipLogLevel::TRACE,
      sprintf(
        'Bindings instance: %s',
        print_r(json_encode($container['networkBindings']), TRUE)
      ),
      $this->getWipId()
    );
    foreach ($container['networkBindings'] as $binding) {
      $this->ports[$binding['containerPort']] = $binding['hostPort'];
    }

    if (empty($this->ports[ContainerInterface::PORT_TYPE_SSH])) {
      throw new \RuntimeException(
        'Unable to determine SSH port of ECS container.'
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasStopped() {
    try {
      $status = $this->getContainerNextStatus();
      $result = ($status !== ContainerInterface::STOPPED);
    } catch (\Exception $e) {
      // Failed to get the task status.
      $result = TRUE;
    }
    return $result;
  }

  /**
   * Checks whether this container has entered the RUNNING state.
   *
   * @return bool
   *   Whether the container is in the RUNNING state.
   */
  protected function checkRunning() {
    $result = FALSE;
    $status = $this->getContainerStatus();
    $next_status = $this->getContainerNextStatus();
    $this->getWipLog()->log(
      WipLogLevel::DEBUG,
      sprintf(
        'EcsContainer::checkRunning task status is "%s".',
        $status
      ),
      $this->getWipId()
    );
    if ($status == ContainerInterface::RUNNING && $next_status == ContainerInterface::RUNNING) {
      $result = TRUE;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function kill() {
    if (!$this->getPid()) {
      throw new \RuntimeException(
        'Unable to kill process: no ECS task has been started yet.'
      );
    }

    $ecs_client = $this->getEcsClient();

    $cluster = $this->getEcsCluster()->getCluster();
    $ecs_client->stopTask(array(
      'cluster' => $cluster,
      'task' => $this->getPid(),
    ));
  }

  /**
   * Determines whether a given task definition changed since registration.
   *
   * @param array $stored_definition
   *   The task definition that we currently have stored for the task.
   *
   * @return bool
   *   TRUE if the task changed from the locally stored registration data,
   *   otherwise FALSE.
   */
  public function taskDefinitionChanged($stored_definition) {
    if (empty($stored_definition)) {
      // Also register a changed definition if there was no previous task
      // definition registered.
      return TRUE;
    }

    // Remove the revision member that is added by storage before comparing the
    // arrays (the revision number for this task on ECS).
    if (isset($stored_definition['revision'])) {
      unset($stored_definition['revision']);
    }

    $current_definition = $this->getTaskDefinition();
    if (ArrayUtility::arraysDiffer($stored_definition, $current_definition)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Registers a task definition with the ECS service.
   *
   * A task definition must be registered before it can be run.
   *
   * @return array
   *   The task definition parameters as an array.
   */
  public function registerTask() {
    $client = $this->getEcsClient();

    $definition = $this->getTaskDefinition();

    // @see http://docs.aws.amazon.com/aws-sdk-php/latest/class-Aws.Ecs.EcsClient.html#_registerTaskDefinition
    /** @var Model $response */
    $response = $client->registerTaskDefinition($definition);

    // Save the latest definition for this task/region.
    /** @var TaskDefinitionStore $storage */
    $storage = $this->dependencyManager->getDependency('acquia.wip.storage.task_definition');
    $storage->save(
      $this->getEcsTaskFamilyName(),
      $this->getEcsRegion(),
      $definition,
      $response['taskDefinition']['revision']
    );
    $definition['revision'] = $response['taskDefinition']['revision'];
    return $definition;
  }

  /**
   * Obtains an ECS task definition for a given task.
   *
   * This will take a default definition from configuration, and apply
   * task-specific overrides to it.
   *
   * @return array
   *   An ECS task definition that can be used for the given WIP task. @see
   *   http://docs.aws.amazon.com/aws-sdk-php/latest/class-Aws.Ecs.EcsClient.html#_registerTaskDefinition
   *   for an example of how this structure should look.
   */
  private function getTaskDefinition() {
    // Take the default configuration from config.ecs.yml file ...
    $definition = App::getApp()['config.ecs']['default'];
    $task_family = $this->getEcsTaskFamilyName();
    if (!empty(App::getApp()['config.ecs']['overrides'][$task_family])) {
      $overrides = App::getApp()['config.ecs']['overrides'][$task_family];
    }

    // And if there is an override defined for the specific task type, use that
    // to overwrite properties of the default.
    if (!empty($overrides)) {
      $definition = array_replace_recursive($definition, $overrides);
    }

    $definition['family'] = $task_family;
    $definition['containerDefinitions'][0]['name'] = $this->getEcsTaskName();

    return $definition;
  }

  /**
   * Obtains an appropriate ECS client for a given task.
   *
   * The ECS client returned will be customized by region to the passed task.
   *
   * @return EcsClient
   *   The ECS client that can be used for the passed task.
   */
  private function getEcsClient() {
    if (isset(self::$testEcsClient)) {
      return self::$testEcsClient;
    }

    $cluster = $this->getEcsCluster();
    return new EcsClient(array(
      'credentials' => array(
        'key' => $cluster->getAwsAccessKeyId(),
        'secret' => $cluster->getAwsSecretAccessKey(),
      ),
      'region' => $this->getEcsRegion(),
      'version' => '2014-11-13',
    ));
  }

  /**
   * Obtains an appropriate EC2 client for this process.
   *
   * The EC2 client returned will be customized by region to task corresponding
   * to this process object.
   *
   * @return Ec2Client
   *   The EC2 client that can be used for the process.
   */
  private function getEc2Client() {
    if (isset(self::$testEc2Client)) {
      return self::$testEc2Client;
    }

    $cluster = $this->getEcsCluster();
    return new Ec2Client(array(
      'credentials' => array(
        'key' => $cluster->getAwsAccessKeyId(),
        'secret' => $cluster->getAwsSecretAccessKey(),
      ),
      'region' => $this->getEcsRegion(),
      'version' => '2015-04-15',
    ));
  }

  /**
   * Sets an object to use as an ECS client during testing.
   *
   * @param EcsClient $client
   *   An ECS client object.
   */
  public static function setTestEcsClient(EcsClient $client) {
    self::$testEcsClient = $client;
  }

  /**
   * Sets an AWS EC2 client for testing.
   *
   * @param Ec2Client $client
   *   The EC2 client to use.
   */
  public static function setTestEc2Client(Ec2Client $client) {
    self::$testEc2Client = $client;
  }

  /**
   * Returns the region that a given task should be run in.
   *
   * @return string
   *   A valid AWS region name that can be used to run the WIP task.
   */
  private function getEcsRegion() {
    $cluster = $this->getEcsCluster();

    return $cluster->getRegion();
  }

  /**
   * Gets the name of the current configuration that is being used.
   *
   * @return string
   *   The name of the configuration being used.
   */
  private function getConfigurationName() {
    /** @var StateStoreInterface $state_storage */
    $state_storage = $this->dependencyManager->getDependency('acquia.wip.storage.state');
    // Load the current cluster that in is usage.
    return $state_storage->get('acquia.wip.ecs_cluster.name', 'default');
  }

  /**
   * Gets an appropriate ECS cluster to run a given WIP task.
   *
   * @return EcsClusterStoreEntry
   *   The appropriate ECS cluster object for the passed WIP Task.
   */
  private function getEcsCluster() {
    if (empty($this->ecsCluster)) {
      /** @var EcsClusterStoreInterface $cluster_storage */
      $cluster_storage = $this->dependencyManager->getDependency('acquia.wip.storage.ecs_cluster');
      $this->ecsCluster = $cluster_storage->load($this->getConfigurationName());
    }

    return $this->ecsCluster;
  }

  /**
   * Returns the ECS task definition name used for the associated task.
   *
   * @return string
   *   The ECS task definition name.
   */
  private function getEcsTaskName() {
    // @todo - using only the group name may be a little naive.
    return preg_replace('/[^a-zA-Z0-9]/', '_', $this->getGroupName());
  }

  /**
   * Gets the registered task definition name to be used in AWS API calls.
   *
   * This name is used as the "family" property in the task definition, and is
   * then used to determine what kind of ECS task (effectively the container
   * itself) gets started for a given WIP task.
   */
  private function getEcsTaskFamilyName() {
    // @todo - actual mapping from the task

    // Name-spacing the task family by customer ID allows us to maintain
    // separate task configurations per customer, including things like REST
    // API keys.
    return sprintf(
      '%s_%s_%s_%s_%s_%s',
      self::ECS_TASK_FAMILY_PREFIX,
      $this->getInstallNamespace(),
      $this->getShortCustomerName(),
      $this->getEcsTaskName(),
      $this->getEcsRegion(),
      $this->getEcsCluster()->getCluster()
    );
  }

  /**
   * Gets a short unique customer name that can be used for name-spacing.
   *
   * The primary use case here is to have a short identifier that is unique to a
   * "customer" so that we can namespace their task definitions.
   *
   * @return string
   *   A short unique name representing the customer that started the WIP task.
   */
  private function getShortCustomerName() {
    // @todo - part of the multi-tenant epic: actually get a customer ID from
    // the task.
    $result = 'DEFAULT';
    return $result;
  }

  /**
   * Returns a namespace for this WIP service installation.
   *
   * This namespace can be used to differentiate task definitions created by
   * specific installs, so that they do not conflict.
   *
   * @return string
   *   The namespace name, if configured in config.global.yml, otherwise the
   *   empty string.
   */
  private function getInstallNamespace() {
    $global_config = App::getApp()['config.global'];

    if (!empty($global_config['namespace'])) {
      return $global_config['namespace'];
    }
    return sprintf(
      '%s_%s',
      Environment::getRuntimeSitegroup(),
      Environment::getRuntimeEnvironmentName()
    );
  }

  /**
   * Gets task information for the container.
   *
   * @param bool $force_load
   *   Optional.  If true, any caching is ignored the task information will
   *   be refreshed.  The default behavior is to cache the result of the task
   *   information.
   *
   * @return object
   *   The task information.
   */
  protected function getContainerTaskInfo($force_load = FALSE) {
    static $cached_value = NULL;
    static $cache_expire = 0;

    try {
      $result = $cached_value;
      if ($force_load || !empty($cached_value) || time() >= $cache_expire) {
        $ecs_client = $this->getEcsClient();
        $cluster = $this->getEcsCluster()->getCluster();
        $container_task_data = $ecs_client->describeTasks(array(
          'tasks' => array($this->getPid()),
          'cluster' => $cluster,
        ));
        // The list of tasks last checked here is cached locally to save an API
        // call.  We know that the list of tasks can only contain one member as we
        // specified the task ARN to retrieve.
        $task_data = $container_task_data['tasks'];
        $this->secureEcsTaskData($task_data);
        $this->lastTaskList = $task_data;
        $result = $cached_value = reset($this->lastTaskList);
        $cache_expire = time() + self::TASK_INFO_TTL;
      }
      return $result;
    } catch (\Exception $e) {
      throw new ContainerInfoUnavailableException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * Secures the task data provided by ECS.
   *
   * This replaces all secure environment variables in the task data with a
   * value that indicates the data is secure.
   *
   * Note: This modifies the contents of the "tasks" parameter.
   *
   * @param array $tasks
   *   The task data.
   * @param string $value
   *   Optional. The value that will replace the sensitive data.
   */
  private function secureEcsTaskData(&$tasks, $value = '[secure]') {
    // The sensitive data is in the 'overrides/containerOverrides/environment' section.
    foreach ($tasks as &$task) {
      if (!isset($task['overrides']) || !isset($task['overrides']['containerOverrides'])) {
        continue;
      }
      foreach ($task['overrides']['containerOverrides'] as &$containerOverride) {
        if (!isset($containerOverride['environment'])) {
          continue;
        }
        $environment_overrides = &$containerOverride['environment'];
        foreach ($environment_overrides as &$environment_override) {
          if (isset($environment_override['name']) &&
            $this->isSecureOverrideKey($environment_override['name'])
          ) {
            $environment_override['value'] = $value;
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getContainerStatus($force_load = FALSE) {
    $container_task_info = $this->getContainerTaskInfo($force_load);
    return $container_task_info['lastStatus'];
  }

  /**
   * {@inheritdoc}
   */
  public function getContainerNextStatus($force_load = FALSE) {
    $container_task_info = $this->getContainerTaskInfo($force_load);
    $result = $container_task_info['desiredStatus'];
    if ($result !== 'STOPPED') {
      // A termination signal may have been received. If so, indicate the
      // container's next status is stopped. This saves an additional
      // fail-safe timeout in the state table caused by receiving the
      // termination signal before the container has actually terminated.
      $container_api = ContainerApi::getContainerApi();
      $signal = $container_api->getContainerTerminatedSignal($this->getWipId());
      if (NULL !== $signal) {
        $result = 'STOPPED';
      }
    }
    return $result;
  }

}
