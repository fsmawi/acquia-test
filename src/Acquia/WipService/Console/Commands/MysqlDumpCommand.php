<?php

namespace Acquia\WipService\Console\Commands;

use Acquia\WipService\Console\WipConsoleCommand;
use Acquia\WipService\MySql\MysqlUtilityInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Facilitates dumping of the wip MySQL database.
 */
class MysqlDumpCommand extends WipConsoleCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $help = <<<EOT
Performs a mysql dump.

Files are output too /mnt/files/wipservice.[env]/backups/on-demand.
EOT;
    $this->setName('sqldump')
      ->setDescription('Performs a mysql dump.')
      ->setHelp($help);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    /** @var MysqlUtilityInterface $mysql */
    $mysql = $this->dependencyManager->getDependency('acquia.wipservice.mysql.utility');

    try {
      $message = $mysql->databaseDump();
      $output->writeln(
        sprintf('<comment>%s.</comment>', $message)
      );
    } catch (\Exception $e) {
      $message = $e->getMessage();
      $output->writeln(
        sprintf('<error>%s.</error>', $message)
      );
    }
  }

}
