<?php

namespace Acquia\Wip\Objects;

use Acquia\WipIntegrations\DoctrineORM\WipModuleStore;
use Acquia\WipIntegrations\DoctrineORM\WipModuleTaskStore;
use Acquia\Wip\Environment;
use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\ExitMessage;
use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\Objects\Modules\WipModuleConfigReader;
use Acquia\Wip\Runtime\WipPool;
use Acquia\Wip\Signal\SignalCallbackHttpTransportInterface;
use Acquia\Wip\Signal\UriCallback;
use Acquia\Wip\Signal\WipCompleteSignal;
use Acquia\Wip\Storage\BasicServerStore;
use Acquia\Wip\Storage\ServerStoreInterface;
use Acquia\Wip\TaskPriority;
use Acquia\Wip\WipContextInterface;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogLevel;
use Acquia\Wip\WipModuleInterface;
use Acquia\Wip\WipTaskProcess;

/**
 * Adds a Wip module.
 */
class AddModule extends BasicWip {

  /**
   * The state table that will be executed by this Wip object.
   *
   * @var string
   */
  protected $stateTable = <<<EOT
start:verifyConfiguration {
  success             discoverWebs
  fail                failure
}

discoverWebs:verifyWebnodes {
  success             addModuleToWebs
  fail                discoverWebs wait=30 max=3

  # Unexpected transition.
  *                   failure
  !                   failure
}

addModuleToWebs:checkResultStatus {
  success             updateDatabase
  wait                addModuleToWebs wait=30 exec=false
  uninitialized       addModuleToWebs wait=5 max=3
  fail                failure

  # Unexpected transition.
  *                   failure
  !                   failure
}

updateDatabase:checkResultStatus {
  success             finish
  wait                updateDatabase wait=30 exec=false
  uninitialized       finish
  fail                updateDatabase wait=30 max=3

  # Unexpected transition.
  *                   failure
  !                   failure
}

failure {
  *                   finish
}
EOT;

  /**
   * The set of webnodes the module must be deployed to.
   *
   * @var string[]
   */
  private $webNodes;

  /**
   * The module to add.
   *
   * @var WipModuleInterface
   */
  private $module;

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    $name = 'Unknown';
    $module = $this->getModule();
    if (!empty($module)) {
      $name = $module->getName();
    }
    return (sprintf('Add module %s', $name));
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    $dependencies = parent::getDependencies();
    $dependencies['acquia.wip.storage.module'] = '\Acquia\Wip\Storage\WipModuleStoreInterface';
    $dependencies['acquia.wip.storage.module_task'] = '\Acquia\Wip\Storage\WipModuleTaskStoreInterface';
    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function start(WipContextInterface $wip_context) {
    parent::start($wip_context);

    // The updateDatabase state will need the result from the
    // addModuleToWebs state.
    $iterator = $this->getIterator();
    $context = $iterator->getWipContext('updateDatabase');
    $context->linkContext('addModuleToWebs');
  }

  /**
   * Verifies the configuration has been set properly.
   *
   * @return string
   *   'success' - The configuration is complete.
   *   'fail' - The configuration is not complete.
   */
  public function verifyConfiguration() {
    $result = 'fail';
    $module = $this->getModule();
    if (!empty($module)) {
      $result = 'success';
    }
    return $result;
  }

  /**
   * Identifies the webnodes the module must be added to.
   */
  public function discoverWebs() {
    /** @var ServerStoreInterface $server_store */
    $server_store = BasicServerStore::getServerStore($this->dependencyManager);
    $active = $server_store->getActiveServers();
    $result = array();
    foreach ($active as $web) {
      $result[] = $web->getHostname();
    }
    $this->webNodes = $result;
  }

  /**
   * Indicates whether the webnodes have been discovered.
   *
   * @return string
   *   'success' - The webnodes have been discovered.
   *   'fail' - A problem was encountered when trying to discover the webnodes.
   */
  public function verifyWebnodes() {
    $result = 'fail';
    if (count($this->webNodes) > 0) {
      $result = 'success';
    }
    return $result;
  }

  /**
   * Creates Wip instances for each webnode that provisions the module.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function addModuleToWebs(WipContextInterface $wip_context) {
    $wip_api = $this->getWipApi();
    $logger = $this->getWipLog();

    // Clear data out of the wip_context.
    $wip_api->clearWipTaskProcesses($wip_context, $logger);
    $wip_api->clearWipTaskResults($wip_context, $logger);

    // Add a task for each webnode.
    foreach ($this->webNodes as $server_name) {
      $callback = $this->createCallback('$acquia.wip.signal.wip.complete');
      $add_wip = $this->createChildTask($server_name);
      $add_wip->addCallback($callback);
      $wip_pool = new WipPool();
      $parent_id = $this->getId();
      $child_task = $wip_pool->addTask($add_wip, new TaskPriority(TaskPriority::HIGH), 'Module', $parent_id);
      $process = new WipTaskProcess($child_task);
      $wip_api->addWipTaskProcess($process, $wip_context);
    }
  }

  /**
   * Creates a callback used to signal this instance when a child completes.
   *
   * @param string $callback_class_id
   *   The identifier that indicates what type of a signal to instantiate when
   *   the callback is invoked.
   *
   * @return UriCallback
   *   The callback instance.
   *
   * @throws \Acquia\Wip\Exception\DependencyMissingException
   *   If any dependencies are missing.
   */
  private function createCallback($callback_class_id) {
    /** @var SignalCallbackHttpTransportInterface $callback_handler */
    $callback_handler = $this->dependencyManager->getDependency('acquia.wip.handler.signal');
    $url = $callback_handler->getCallbackUrl($this->getId());
    $callback = new UriCallback($url);
    $callback_data = new \stdClass();

    // The pid can not be set because the curl command has to be generated
    // before the container is launched. Substitute the Wip object ID.
    $callback_data->pid = $this->getId();
    $callback_data->startTime = time();
    $callback_data->classId = $callback_class_id;
    $callback->setData($callback_data);
    try {
      $authentication = WipFactory::getObject('acquia.wip.uri.authentication');
      $callback->setAuthentication($authentication);
    } catch (\Exception $e) {
      // No authentication is being used.
    }
    return $callback;
  }

  /**
   * Creates a child Wip that installs the module on the specified webnode.
   *
   * @param string $server_name
   *   The webnode.
   *
   * @return AddModuleToWebnode
   *   The resulting child Wip instance.
   */
  private function createChildTask($server_name) {
    $add_wip = new AddModuleToWebnode();
    $add_wip->setModule($this->getModule());
    $add_wip->setUuid('admin');
    $add_wip->setWebnode($server_name);
    $add_wip->setLogLevel($this->getLogLevel());
    return $add_wip;
  }

  /**
   * Gets the signal data from the specified signal.
   *
   * @param WipCompleteSignal $signal
   *   The signal.
   *
   * @return string
   *   The module configuration.
   *
   * @throws \DomainException
   *   If the specified signal does not contain module configuration data.
   */
  private function extractModuleConfigFromSignal(WipCompleteSignal $signal = NULL) {
    if (NULL !== $signal) {
      $data = $signal->getData();
      if (isset($data->wipData) && isset($data->wipData->moduleConfig)) {
        return $data->wipData->moduleConfig;
      }
    }
    throw new \DomainException('No module configuration found in signal.');
  }

  /**
   * Updates the module and task tables with the module configuration.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function updateDatabase(WipContextInterface $wip_context) {
    // The module configuration can be found in the signal the child Wip
    // objects used to indicate they had completed.
    try {
      $wip_result = $this->getWipResult($wip_context);
      $module = $this->getModule();
      $module_configuration = $this->extractModuleConfigFromSignal($wip_result->getSignal());
      $this->deleteModule($module);
      WipModuleConfigReader::populateModule($module, $module_configuration);
      $module->setReady(TRUE);
      $this->saveModule($module);
    } catch (\Exception $e) {
      $module_name = $this->getModule()->getName();
      $summary = sprintf('Deployment of the module "%s" failed.', $module_name);
      $log_message = sprintf(
        'Deployment of the module "%s" failed: %s',
        $module_name,
        $e->getMessage()
      );
      $this->setExitMessage(new ExitMessage($summary, WipLogLevel::FATAL, $log_message));
      if (!empty($wip_result)) {
        $wip_result->forceFail($log_message);
      }
    }
  }

  /**
   * Gets a suitable WipTaskResult instance from the specified context.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   *
   * @return \Acquia\Wip\WipTaskResultInterface
   *   The WipTaskResult instance.
   *
   * @throws \DomainException
   *   If a suitable WipTaskResult instance cannot be found in the specified
   *   wip_context.
   */
  private function getWipResult(WipContextInterface $wip_context) {
    // The module configuration can be found in the signal the child Wip
    // objects used to indicate they had completed.
    $result = NULL;
    $wip_api = $this->getWipApi();
    $wip_results = $wip_api->getWipTaskResults($wip_context);
    foreach ($wip_results as $wip_result) {
      $signal = $wip_result->getSignal();
      try {
        $this->extractModuleConfigFromSignal($signal);
        return $wip_result;
      } catch (\Exception $e) {
        continue;
      }
    }
    throw new \DomainException('Failed to find a valid WipTaskResult instance.');
  }

  /**
   * {@inheritdoc}
   */
  public function failure(WipContextInterface $wip_context, \Exception $exception = NULL) {
    $exit_message = $this->getExitMessage();
    if (empty($exit_message)) {
      $module_name = $this->getModule()->getName();
      $this->setExitMessage(
        new ExitMessage(
          sprintf('Failed to deploy module "%s".', $module_name),
          WipLogLevel::FATAL
        )
      );
    }
    parent::failure($wip_context, $exception);
  }

  /**
   * Saves the module changes to the database.
   *
   * @param WipModuleInterface $module
   *   The module to save.
   *
   * @throws \DomainException
   *   If the module has no tasks.
   */
  private function saveModule(WipModuleInterface $module) {
    $tasks = $module->getTasks();
    if (empty($tasks)) {
      throw new \DomainException(sprintf('The module %s has no tasks.', $module->getName()));
    }
    WipModuleStore::getWipModuleStore($this->getDependencyManager())
      ->save($module);
    $task_store = WipModuleTaskStore::getWipModuleTaskStore($this->getDependencyManager());
    foreach ($tasks as $task) {
      $task_store->save($task);
    }
  }

  /**
   * Deletes the module from the database.
   *
   * @param WipModuleInterface $module
   *   The module to delete.
   */
  private function deleteModule(WipModuleInterface $module) {
    $task_store = WipModuleTaskStore::getWipModuleTaskStore($this->getDependencyManager());
    foreach ($module->getTasks() as $task) {
      $task_store->delete($task->getName());
    }
    WipModuleStore::getWipModuleStore($this->getDependencyManager())
      ->delete($module->getName());
  }

  /**
   * Gets the environment of the Wip runtime.
   *
   * @return EnvironmentInterface
   *   The runtime environment.
   */
  public function getRuntimeEnvironment() {
    return Environment::getRuntimeEnvironment();
  }

  /**
   * Sets the module to be added.
   *
   * @param WipModuleInterface $module
   *   The module to be added.
   */
  public function setModule(WipModuleInterface $module) {
    $this->module = $module;
  }

  /**
   * Gets the module.
   *
   * @return WipModuleInterface
   *   The module.
   */
  public function getModule() {
    return $this->module;
  }

}
