<?php

namespace Acquia\WipService\Console\Commands;

use Acquia\WipIntegrations\DoctrineORM\WipPoolStore;
use Acquia\WipService\Console\WipConsoleCommand;
use Acquia\Wip\Runtime\WipPoolController;
use Acquia\Wip\Runtime\WipPoolControllerInterface;
use Acquia\Wip\Storage\WipPoolStoreInterface;
use Acquia\Wip\WipFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The pause command is responsible for global, group, and task pause.
 */
class PauseCommand extends WipConsoleCommand {

  /**
   * The Wip pool controller instance.
   *
   * @var WipPoolControllerInterface
   */
  private $controller;

  /**
   * The Wip pool storage instance.
   *
   * @var WipPoolStoreInterface
   */
  private $wipPoolStore;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $help = <<<EOT
This is command for pausing one or more tasks. It can be used to pause tasks from the cli.

If the hard pause is used, then a job will not be processed to completion, and an upgrade path for tasks should be
provided. If a soft pause is used then all jobs will be processed through to completed state and any new jobs added
will run on the new code base so no upgrade path is required. If using soft pause you should wait for items to drain
from the queue. Releases will use hard pause by default if soft pause is required it should be set manually on the 
server before starting a deployment.
EOT;

    $this->setName('pause')
      ->setDescription('Pause everything, pause a group, or pause a task.')
      ->setHelp($help)
      ->addOption(
        'groups',
        'g',
        InputOption::VALUE_REQUIRED,
        'Apply pause to one or more groups. Requires a comma-separated list of group names to pause.'
      )
      ->addOption(
        'tasks',
        't',
        InputOption::VALUE_REQUIRED,
        'Apply pause to one or more tasks. Requires a comma-separated list of task IDs.'
      )
      ->addOption(
        'soft',
        's',
        InputOption::VALUE_NONE,
        'Apply pause only to tasks that are not currently running.'
      )
      ->addOption(
        'block',
        'b',
        InputOption::VALUE_NONE,
        'Blocks the process until all tasks have left the "PROCESSING" state.'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->controller = WipPoolController::getWipPoolController($this->dependencyManager);
    $this->wipPoolStore = $this->dependencyManager->getDependency('acquia.wip.storage.wippool');

    $groups = $input->getOption('groups');
    $tasks = $input->getOption('tasks');
    $soft = $input->getOption('soft');
    $block = $input->getOption('block');
    // The sleep time while in blocking mode.
    $sleep_time = $block ? WipFactory::getInt('$acquia.wipctl.pause.sleeptime', 5) : 0;

    if ($groups && $tasks) {
      throw new \InvalidArgumentException('The "--groups" and "--tasks" options cannot be used together.');
    }

    // VALUE_REQUIRED unfortunately allows empty strings.
    if ($groups === '') {
      throw new \InvalidArgumentException('The "--groups" option must not be empty.');
    }
    if ($tasks === '') {
      throw new \InvalidArgumentException('The "--tasks" option must not be empty.');
    }

    // When set, the --groups and --tasks options should have a comma-separated
    // list as their value.
    $type = 'global';
    if (!empty($groups)) {
      $type = 'groups';
      $groups = explode(',', $groups);
    }
    if (!empty($tasks)) {
      $type = 'tasks';
      $tasks = explode(',', $tasks);
    }

    $exit_code = 0;
    switch ($type) {
      case 'groups':
        $failed = $this->pauseGroups($groups, $soft);
        if (empty($failed)) {
          $output->writeln(sprintf(
            '<info>Successfully applied %s to groups: %s</info>',
            $this->formatPause($soft),
            implode(', ', $groups)
          ));
        } else {
          $output->writeln(sprintf(
            '<error>Failed to %s groups: %s.</error>',
            $this->formatPause($soft),
            implode(', ', $failed)
          ));
          $exit_code = 1;
        }
        $paused_groups = $this->controller->getHardPausedGroups();
        if (!empty($paused_groups)) {
          $output->writeln(sprintf(
            '<comment>Groups currently paused: %s.</comment>',
            implode(', ', $paused_groups)
          ));
        }
        break;

      case 'tasks':
        $result = $this->pauseTasks($tasks);
        if (count($result['paused']) > 0) {
          $output->writeln(sprintf('<info>Paused successfully: %s.</info>', implode(', ', $result['paused'])));
        }
        if (count($result['failed']) > 0) {
          $output->writeln(sprintf('<error>Failed to pause: %s.</error>', implode(', ', $result['failed'])));
          $exit_code = 1;
        }
        if (count($result['not_found']) > 0) {
          $output->writeln(sprintf('<error>Not found: %s.</error>', implode(', ', $result['not_found'])));
          $exit_code = 1;
        }
        $paused_tasks = $this->wipPoolStore->load(0, PHP_INT_MAX, 'ASC', NULL, NULL, NULL, TRUE);
        if (!empty($paused_tasks)) {
          $task_ids = WipPoolStore::getTaskIds($paused_tasks);
          $output->writeln(sprintf('<comment>Tasks currently paused: %s.</comment>', implode(', ', $task_ids)));
        }
        break;

      default:
        if ($this->pauseGlobal($soft)) {
          $output->writeln(sprintf('<info>Global %s is enabled.</info>', $this->formatPause($soft)));
        } else {
          $output->writeln(sprintf('<error>Failed to apply global %s.</error>', $this->formatPause($soft)));
          $exit_code = 1;
        }
        break;
    }

    if ($block) {
      do {
        // @todo Display more details about the tasks in the PROCESSING state,
        // such as the group_name field and paused status (indicating for
        // each task whether it is individually, globally, or group-paused).
        $tasks_in_processing = $this->controller->getTasksInProcessing();

        if (!empty($tasks_in_processing)) {
          $task_ids = WipPoolStore::getTaskIds($tasks_in_processing);
          $output->writeln(
            sprintf(
              '<comment>Tasks are currently in the PROCESSING state: %s.</comment>',
              implode(', ', $task_ids)
            )
          );

          sleep($sleep_time);
        }
      } while (!empty($tasks_in_processing));
    }

    $tasks_in_progress = $this->controller->getTasksInProgress();
    if (!empty($tasks_in_progress)) {
      $task_ids = WipPoolStore::getTaskIds($tasks_in_progress);
      $output->writeln(
        sprintf('<comment>Tasks are currently in progress: %s.</comment>', implode(', ', $task_ids))
      );
    }

    return $exit_code;
  }

  /**
   * Pauses tasks.
   *
   * @param string[] $tasks
   *   An array of numeric task ID strings.
   *
   * @return array
   *   An array of tasks grouped by result.
   */
  private function pauseTasks(array $tasks) {
    $result = array(
      'paused' => array(),
      'not_found' => array(),
      'failed' => array(),
    );
    foreach ($tasks as $task_id) {
      if (ctype_digit($task_id) && $task_id > 0) {
        $task_id = intval($task_id);
        try {
          $task_paused = $this->controller->pauseTask($task_id);
          if ($task_paused) {
            $result['paused'][] = $task_id;
          } else {
            $result['failed'][] = $task_id;
          }
        } catch (\Exception $e) {
          $result['not_found'][] = $task_id;
        }
      } else {
        $result['not_found'][] = $task_id;
      }
    }
    return $result;
  }

  /**
   * Pauses groups of tasks.
   *
   * @param string[] $groups
   *   An array of group names.
   * @param bool $soft
   *   Whether to apply soft or hard pause.
   *
   * @return array
   *   An array of groups that could not be paused.
   */
  private function pauseGroups(array $groups, $soft) {
    if ($soft) {
      $this->controller->softPauseGroups($groups);
      $paused_groups = $this->controller->getSoftPausedGroups();
    } else {
      $this->controller->hardPauseGroups($groups);
      $paused_groups = $this->controller->getHardPausedGroups();
    }
    return array_diff($groups, $paused_groups);
  }

  /**
   * Pauses tasks globally.
   *
   * @param bool $soft
   *   Whether to apply soft or hard pause.
   *
   * @return bool
   *   Whether the pause operation was successful.
   */
  private function pauseGlobal($soft) {
    if ($soft) {
      $this->controller->softPauseGlobal();
      $success = $this->controller->isSoftPausedGlobal();
    } else {
      $this->controller->hardPauseGlobal();
      $success = $this->controller->isHardPausedGlobal();
    }
    return $success;
  }

  /**
   * Returns the human-readable description of the type of pause.
   *
   * @param bool $soft
   *   Whether to describe soft or hard pause.
   *
   * @return string
   *   The human-readable description of the type of pause.
   */
  private function formatPause($soft) {
    return $soft ? 'soft pause' : 'pause';
  }

}
