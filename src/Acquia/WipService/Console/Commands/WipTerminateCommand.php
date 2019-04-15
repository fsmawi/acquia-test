<?php

namespace Acquia\WipService\Console\Commands;

use Acquia\WipIntegrations\DoctrineORM\SignalStore;
use Acquia\WipService\Console\WipConsoleCommand;
use Acquia\Wip\Exception\RowLockException;
use Acquia\Wip\Lock\WipPoolRowLock;
use Acquia\Wip\Signal\TaskTerminateSignal;
use Acquia\Wip\Storage\SignalStoreInterface;
use Acquia\Wip\Storage\WipPoolStoreInterface;
use Acquia\Wip\TaskExitStatus;
use Acquia\Wip\TaskInterface;
use Acquia\Wip\TaskStatus;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Defines a console command for terminating Wip tasks.
 */
class WipTerminateCommand extends WipConsoleCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $help = <<<EOT
Marks a wip task for termination. The task in the wip pool is flagged as awaiting termination and is then processed by
the wip daemon.
EOT;
    $this->setName('terminate')
      ->setDescription('Terminate a WIP task.')
      ->setHelp($help)
      ->addArgument(
        'task_id',
        InputArgument::REQUIRED,
        'The wip task id to terminate'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    // Wip worker does strict type checking so we need to cast.
    $task_id = (int) $input->getArgument('task_id');

    $row_lock = WipPoolRowLock::getWipPoolRowLock($task_id, NULL, $this->dependencyManager);
    try {
      $format = $row_lock->setTimeout(30)
        ->runAtomic($this, 'terminateAtomic', [$task_id]);
    } catch (RowLockException $e) {
      $format = '<error>Failed to lock wip_pool for task %d<error>';
    }
    $message = sprintf($format, $task_id);
    $output->writeln($message);
  }

  /**
   * Terminates the task.
   *
   * This method must be called with the associated WipPool row lock so it does
   * not conflict with other processes.
   *
   * @param int $task_id
   *   The task ID.
   *
   * @return string
   *   The resulting message that can be printed in the CLI.
   */
  public function terminateAtomic($task_id) {
    /** @var WipPoolStoreInterface $pool_store */
    $pool_store = $this->dependencyManager->getDependency('acquia.wip.storage.wippool');
    /** @var TaskInterface $task */
    $task = $pool_store->get($task_id);
    if (!$task) {
      $message = 'The task does not exist.';
    } else {
      $status = $task->getStatus();
      $exit_status = $task->getExitStatus();
      $complete_exit_status = array(
        TaskExitStatus::TERMINATED,
        TaskExitStatus::ERROR_USER,
        TaskExitStatus::COMPLETED,
        TaskExitStatus::ERROR_SYSTEM,
        TaskExitStatus::WARNING,
      );
      if ($status === TaskStatus::COMPLETE ||
        in_array($exit_status, $complete_exit_status)) {
        $message = '<error>Task %d has already finished and cannot be terminated.<error>';
      } else {
        if ($status === TaskStatus::NOT_STARTED) {
          // Bump the task along so the terminate will be processed before
          // tasks with the same work_id are completed.
          $task->setStatus(TaskStatus::WAITING);
          $pool_store->save($task);
        }
        /** @var SignalStoreInterface $signal_store */
        $signal_store = SignalStore::getSignalStore($this->dependencyManager);
        $signal = new TaskTerminateSignal($task_id);
        $signal_store->send($signal);
        $message = '<info>Task %d has been marked for termination.<info>';
      }
    }
    return $message;
  }

}
