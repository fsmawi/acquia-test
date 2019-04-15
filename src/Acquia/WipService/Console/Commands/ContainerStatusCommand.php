<?php

namespace Acquia\WipService\Console\Commands;

use Acquia\WipService\Console\WipConsoleCommand;
use Acquia\Wip\Storage\WipPoolStoreInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Returns status information about the main task running in the container.
 *
 * This is used for failsafe long-polling of the container to see if the task
 * has completed yet in case we didn't receive the completion signal. It should
 * contain the same information as we receive from the signal data.
 *
 * In the container, task ID 1 is the main task being executed. The exit of this
 * task will signal completion to the controller and the container will be
 * killed. This means that the parent wip object (task ID 1) in the container
 * needs to wait for completion of all other sub-tasks.
 */
class ContainerStatusCommand extends WipConsoleCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $description = <<<EOT
This is an internal-use command. Returns status information about the main task running in the container.
EOT;
    $this->setName('container-status')
      ->setDescription($description);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    if (empty(getenv('WIP_CONTAINERIZED'))) {
      $output->writeln('<error>This command is only for use inside a container.</error>');
      return 1;
    }

    /** @var WipPoolStoreInterface $storage */
    $storage = $this->dependencyManager->getDependency('acquia.wip.storage.wippool');
    $task = $storage->get(1);
    if (empty($task)) {
      $output->writeln('<info>Task not found.</info>');
      return 1;
    }

    $result = new \stdClass();
    $result->claimed_timestamp = $task->getClaimedTimestamp();
    $result->class_name = $task->getWipClassName();
    $result->completed_timestamp = $task->getCompletedTimestamp();
    $result->created_timestamp = $task->getCreatedTimestamp();
    $result->exit_message = $task->getExitMessage();
    $result->exit_status = $task->getExitStatus();
    $result->group_name = $task->getGroupName();
    $result->id = $task->getId();
    $result->name = $task->getName();
    $result->priority = $task->getPriority();
    $result->run_status = $task->getStatus();
    $result->start_timestamp = $task->getStartTimestamp();
    $result->timeout = $task->getTimeout();
    $result->wake_timestamp = $task->getWakeTimestamp();

    $output->write(json_encode($result), TRUE);
  }

}
