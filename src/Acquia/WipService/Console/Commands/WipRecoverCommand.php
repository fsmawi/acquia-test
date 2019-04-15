<?php

namespace Acquia\WipService\Console\Commands;

use Acquia\WipService\Console\WipConsoleCommand;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\Runtime\WipRecovery;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogLevel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Defines a console command for recovering from thread and task inconsistencies in the database.
 */
class WipRecoverCommand extends WipConsoleCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $help = <<<EOT
This command is used to recover from inconsistencies in the database after a webnode restart.

It is run automatically as part of the task processing process. It is also possible to run from the cli
in this case it should be run with the --report-only option and this will allow you to see all tasks in flight.
EOT;

    $this->setName('recover')
      ->setDescription('Recovers from unexpected process-tasks exit.')
      ->setHelp($help)
      ->addOption(
        'report-only',
        'r',
        InputOption::VALUE_NONE,
        'Only report if the database is in an inconsistent state, do not fix'
      )
      ->addOption(
        'server-ids',
        's',
        InputOption::VALUE_REQUIRED,
        'A json string of server ids'
      )
      ->addOption(
        'format',
        'f',
        InputOption::VALUE_REQUIRED,
        'Output format for recovery report (text, json)',
        'text'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $start = microtime(TRUE);
    $json = $input->getOption('server-ids');
    $server_ids = array();
    if (!empty($json)) {
      $decoded = json_decode($json);
      if (NULL !== $decoded && is_array($decoded)) {
        $server_ids = $decoded;
      }
    }
    $format = $input->getOption('format');
    $recovery = new WipRecovery($server_ids, $format);
    $recovery->evaluate();
    $message = $recovery->report();
    $output->writeln($message);

    $report_only = $input->getOption('report-only');
    if ($report_only) {
      return;
    }

    $recovery->fix();
    if (WipFactory::getBool('$acquia.command.duration.logs', FALSE)) {
      global $argv;
      $duration = microtime(TRUE) - $start;
      $command_line = implode(' ', $argv);
      WipLog::getWipLog()->log(
        WipLogLevel::DEBUG,
        sprintf('WipRecoverCommand took %0.3f seconds: %s', $duration, $command_line)
      );
    }
  }

}
