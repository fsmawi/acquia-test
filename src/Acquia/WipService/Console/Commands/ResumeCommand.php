<?php

namespace Acquia\WipService\Console\Commands;

use Acquia\WipIntegrations\DoctrineORM\WipPoolStore;
use Acquia\WipService\Console\WipConsoleCommand;
use Acquia\Wip\Runtime\WipPoolController;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The resume command is responsible for global, group, and task resume.
 */
class ResumeCommand extends WipConsoleCommand {

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
This is command for resuming one or more tasks. It can be used to resume tasks from the cli.
EOT;

    $this->setName('resume')
      ->setDescription('Resume everything, resume a group, or resume a task.')
      ->setHelp($help)
      ->addOption(
        'groups',
        'g',
        InputOption::VALUE_REQUIRED,
        'Resume one or more groups. Requires a comma-separated list of group names.'
      )
      ->addOption(
        'tasks',
        't',
        InputOption::VALUE_REQUIRED,
        'Resume one or more tasks. Requires a comma-separated list of task IDs.'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->wipPoolStore = $this->dependencyManager->getDependency('acquia.wip.storage.wippool');
    $this->controller = WipPoolController::getWipPoolController($this->dependencyManager);

    $groups = $input->getOption('groups');
    $tasks = $input->getOption('tasks');

    if ($groups && $tasks) {
      throw new \InvalidArgumentException('The "--groups" and "--tasks" options cannot be used together.');
    }

    // VALUE_REQUIRED unfortunately allows empty strings.
    $type = 'global';
    if ($groups === '') {
      throw new \InvalidArgumentException('The "--groups" option must not be empty.');
    }
    if ($tasks === '') {
      throw new \InvalidArgumentException('The "--tasks" option must not be empty.');
    }

    // When set, the --groups and --tasks options should have a comma-separated
    // list as their value.
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
        $resume = $this->controller->resumeGroups($groups);
        $paused_groups = $this->controller->getHardPausedGroups();
        if ($resume) {
          $output->writeln(sprintf(
            '<info>Successfully resumed groups: %s</info>',
            implode(', ', $groups)
          ));
        } else {
          $failed = array_intersect($groups, $paused_groups);
          $output->writeln(sprintf(
            '<error>Failed to resume groups: %s.</error>',
            implode(', ', $failed)
          ));
          $exit_code = 1;
        }

        if (!empty($paused_groups)) {
          $output->writeln(sprintf(
            '<comment>Groups currently paused: %s.</comment>',
            implode(', ', $paused_groups)
          ));
        }
        break;

      case 'tasks':
        $result = $this->resumeTasks($tasks);
        if (count($result['resumed']) > 0) {
          $output->writeln(
            sprintf('<info>Resumed tasks: %s.</info>', implode(', ', $result['resumed']))
          );
        }

        $paused_tasks = $this->wipPoolStore->load(0, PHP_INT_MAX, 'ASC', NULL, NULL, NULL, TRUE);
        if (!empty($paused_tasks)) {
          $task_ids = WipPoolStore::getTaskIds($paused_tasks);
          $output->writeln(sprintf('<comment>Tasks currently paused: %s.</comment>', implode(', ', $task_ids)));
        }

        if (count($result['failed']) > 0) {
          $output->writeln(sprintf('<error>Failed to resume: %s.</error>', implode(', ', $result['failed'])));
          $exit_code = 1;
        }
        if (count($result['not_found']) > 0) {
          $output->writeln(sprintf("<error>Not found: %s.</error>", implode(', ', $result['not_found'])));
          $exit_code = 1;
        }
        break;

      default:
        // The default is a global pause.
        if ($this->controller->resumeGlobal()) {
          $output->writeln('<info>Global pause is disabled.</info>');
        } else {
          $output->writeln('<error>Global resume failed.</error>');
          $exit_code = 1;
        }
        break;
    }

    // @todo Display more details about the tasks in progress such as the
    //   group_name field and paused status (indicating for each task whether it
    //   is individually, globally, or group-paused).
    $tasks_in_progress = $this->controller->getTasksInProgress();
    if (count($tasks_in_progress) > 0) {
      $task_ids = WipPoolStore::getTaskIds($tasks_in_progress);
      $output->writeln(
        sprintf('<comment>Tasks are currently in progress: %s.</comment>', implode(', ', $task_ids))
      );
    }

    return $exit_code;
  }

  /**
   * Resumes tasks.
   *
   * @param string[] $tasks
   *   An array of numeric task ID strings.
   *
   * @return array
   *   An array of tasks grouped by result.
   */
  private function resumeTasks(array $tasks) {
    $result = array(
      'resumed' => array(),
      'not_found' => array(),
      'failed' => array(),
    );
    foreach ($tasks as $task_id) {
      if (ctype_digit($task_id) && $task_id > 0) {
        $task_id = intval($task_id);
        try {
          $resumed = $this->controller->resumeTask($task_id);
          if ($resumed) {
            $result['resumed'][] = $task_id;
          } else {
            $result['failed'][] = $task_id;
          };
        } catch (\Exception $e) {
          $result['not_found'][] = $task_id;
        }
      } else {
        $result['not_found'][] = $task_id;
      }
    }
    return $result;
  }

}
