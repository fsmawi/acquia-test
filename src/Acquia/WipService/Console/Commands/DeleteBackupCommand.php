<?php

namespace Acquia\WipService\Console\Commands;

use Acquia\WipService\Console\WipConsoleCommand;
use Acquia\WipService\MySql\MysqlUtilityInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Facilitates the deletion of MySQL database backups.
 */
class DeleteBackupCommand extends WipConsoleCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $help = <<<EOT
Deletes mysql backups created on the wip service.

The retention policy is defined in config.backups.yml, which allows the age of backups to be defined and the number to
keep. Note this can be overridden in /mnt/files/wipservice.[env]/nobackup/config/ folder.
EOT;
    $this->setName('delete-backups')
      ->setDescription('Deletes mysql backups created on the wip service.')
      ->setHelp($help);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    /** @var MysqlUtilityInterface $mysql */
    $mysql = $this->dependencyManager->getDependency('acquia.wipservice.mysql.utility');
    $mysql->deleteBackups();
  }

}
