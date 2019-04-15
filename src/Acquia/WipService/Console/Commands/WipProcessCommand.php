<?php

namespace Acquia\WipService\Console\Commands;

use Acquia\WipService\Console\WipConsoleCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class WipProcessCommand.
 */
class WipProcessCommand extends WipConsoleCommand {

  const PROC_COUNT_DEFAULT = 5;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $help = <<<EOT
This is an internal-use command for starting the wip process daemon.
EOT;
    $this->setName('process')
      ->setDescription('Start the Wip process daemon.')
      ->setHelp($help)
      ->addOption(
        'procs',
        NULL,
        InputOption::VALUE_REQUIRED,
        'Enter the number of processes to spawn'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $proc_count = self::PROC_COUNT_DEFAULT;

    if ($proc_input = $input->getOption('procs')) {
      $proc_count = is_numeric($proc_input) ? $proc_input : self::PROC_COUNT_DEFAULT;
    }

    $output->writeln(sprintf('Wip daemon started with %s processes.', $proc_count));
  }

}
