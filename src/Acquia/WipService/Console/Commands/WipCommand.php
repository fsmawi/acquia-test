<?php

namespace Acquia\WipService\Console\Commands;

use Acquia\WipIntegrations\DoctrineORM\ConfigurationStore;
use Acquia\WipService\Console\WipConsoleCommand;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\Runtime\WipPoolInterface;
use Acquia\Wip\Storage\ConfigurationStoreInterface;
use Acquia\Wip\Storage\WipPoolStoreInterface;
use Acquia\Wip\TaskInterface;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipInterface;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipLogLevel;
use Acquia\Wip\WipTaskConfig;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Defines a console command for adding Wip tasks to the pool.
 *
 * Imports a Wip object into the local database from a file containing
 * serialized PHP. The primary use-case here is inside a container, to obtain
 * the initial Wip job from a file written to the container.
 */
class WipCommand extends WipConsoleCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $usage = <<<EOT
This is an internal-use command for adding a task to the WIP pool.  The input
argument should point to a file to load a WIP object from.  The object should be
serialized in PHP and base64 encoded.
EOT;

    $this->setName('add')
      ->setDescription('Add a WIP task.')
      ->setHelp($usage)
      ->addArgument(
        'task',
        InputArgument::REQUIRED,
        'A filename to load the WIP task from or "-" for stdin.'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $argument = $input->getArgument('task');
    $data = file_get_contents($argument === '-' ? 'php://stdin' : $argument);

    $task = unserialize($data);

    if (!$task) {
      throw new \RuntimeException('Failed to unserialize the Task.');
    }
    if ($task instanceof WipTaskConfig) {
      $this->createFromConfiguration($task);
    } elseif ($task instanceof WipInterface) {
      $this->addTask($task);
    } else {
      throw new \RuntimeException('The supplied object is not a Task.');
    }
  }

  /**
   * Creates and adds a WipTask from the specified WipTaskConfig.
   *
   * @param WipTaskConfig $config
   *   The Wip task configuration.
   */
  protected function createFromConfiguration(WipTaskConfig $config) {
    $time_offset = 0;
    try {
      $time_offset = time() - $config->getInitializeTime();
    } catch (\Exception $e) {
      // Use an offset of 0.
    }
    // Record this offset, use it when reporting log data.
    $configuration_store = $this->getConfigurationStore();
    $configuration_store->set('timeOffset', $time_offset);

    try {
      $class_name = WipFactory::getString($config->getClassId());
      if (empty($class_name)) {
        $class_name = $config->getClassId();
      }
    } catch (\Exception $e) {
      // The class ID is not in the factory configuration.  Try using the
      // class ID as the class name.
      $class_name = $config->getClassId();
    }
    /** @var WipInterface $task */
    $task = new $class_name();
    $task->setWipTaskConfig($config);
    $task->setUuid($config->getUuid());

    $wip_id = $config->getWipId();
    $configuration_store->set('externalWipId', $wip_id);

    $creation_time = NULL;
    try {
      $creation_time = $config->getCreatedTimestamp() + $time_offset;
    } catch (\Exception $e) {
      // The created timestamp was not set.
    }
    $task_metadata = $this->addTask($task, $creation_time);
    if ($time_offset !== 0) {
      $wip_log = $this->getWipLog();
      $wip_log->log(
        WipLogLevel::ERROR,
        sprintf(
          'The container time is offset from the non-container time by %d seconds.',
          $time_offset
        ),
        $task_metadata->getId()
      );
    }
  }

  /**
   * Adds the specified task to the WipPool.
   *
   * @param WipInterface $task
   *   The task to add.
   * @param int $creation_time
   *   Optional. If provided, the task creation time will be set to this value.
   *   This is used to match the creation time with that of the object that is
   *   adding the task. This can be helpful for the ContainerDelegate Wip object
   *   for example, so that SLA times can be monitored starting from the user's
   *   request time rather than container initialization time.
   *
   * @return TaskInterface
   *   The task metadata.
   */
  protected function addTask(WipInterface $task, $creation_time = NULL) {
    /** @var WipPoolStoreInterface $storage */
    $storage = $this->dependencyManager->getDependency('acquia.wip.storage.wippool');
    /** @var WipPoolInterface $wip_pool */
    $wip_pool = $this->dependencyManager->getDependency('acquia.wip.pool');
    $result = $wip_pool->addTask($task);
    if (is_int($creation_time)) {
      $result->setCreatedTimestamp($creation_time);
      $storage->save($result);
    }
    return $result;
  }

  /**
   * Gets the configuration store.
   *
   * @return ConfigurationStoreInterface
   *   The configuration store.
   */
  public function getConfigurationStore() {
    /** @var ConfigurationStoreInterface $result */
    $result = NULL;
    try {
      $result = WipFactory::getObject('acquia.wip.storage.configuration');
      if (!empty($result) || !$result instanceof ConfigurationStoreInterface) {
        throw new \DomainException('The configuration store could not be found.');
      }
    } catch (\Exception $e) {
      // No configuration store has been found.
      $result = new ConfigurationStore();
    }
    return $result;
  }

  /**
   * Gets the Wip logger.
   *
   * @return WipLogInterface
   *   The Wip logger.
   */
  public function getWipLog() {
    /** @var WipLogInterface $result */
    $result = NULL;
    try {
      $result = WipFactory::getObject('acquia.wip.wiplog');
      if (!empty($result) || !$result instanceof WipLogInterface) {
        throw new \DomainException('The Wip logger could not be found.');
      }
    } catch (\Exception $e) {
      // No logger.
      $result = new WipLog();
    }
    return $result;
  }

}
