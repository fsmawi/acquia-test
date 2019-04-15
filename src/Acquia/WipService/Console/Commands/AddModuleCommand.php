<?php

namespace Acquia\WipService\Console\Commands;

use Acquia\WipService\Console\WipConsoleCommand;
use Acquia\Wip\Objects\AddModule;
use Acquia\Wip\Runtime\WipPool;
use Acquia\Wip\TaskPriority;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogLevel;
use Acquia\Wip\WipModule;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Adds a new Wip module.
 */
class AddModuleCommand extends WipConsoleCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $usage = <<<EOT
This is an internal-use command for adding a module.
EOT;

    $this->setName('add-module')
      ->setDescription('Adds a new Wip module to the system.')
      ->setHelp($usage)
      ->addArgument(
        'name',
        InputArgument::REQUIRED,
        'The name of the Wip module to add.'
      )
      ->addArgument(
        'vcs-uri',
        InputArgument::REQUIRED,
        'The VCS URI from which the module can be cloned.'
      )
      ->addArgument(
        'vcs-path',
        InputArgument::REQUIRED,
        'The VCS tag or branch identifying which version of the module to use.'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $module_name = $input->getArgument('name');
    $uri = $input->getArgument('vcs-uri');
    $path = $input->getArgument('vcs-path');
    $module = new WipModule($module_name);
    $module->setVcsUri($uri);
    $module->setVcsPath($path);

    $add_wip = new AddModule();
    $add_wip->setModule($module);
    $add_wip->setUuid('admin');
    $add_wip->setLogLevel(WipLogLevel::TRACE);

    $wip_pool = new WipPool();
    $task = $wip_pool->addTask($add_wip, new TaskPriority(TaskPriority::HIGH), 'Module');
    $output->writeln(sprintf("Started task %d\n", $task->getId()));
  }

}
