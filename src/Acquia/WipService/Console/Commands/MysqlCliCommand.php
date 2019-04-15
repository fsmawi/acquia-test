<?php

namespace Acquia\WipService\Console\Commands;

use Acquia\WipService\App;
use Acquia\WipService\Console\WipConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Facilitates logging in to the interactive MySQL console.
 */
class MysqlCliCommand extends WipConsoleCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('sqlc')
      ->setDescription('Logs into the interactive MySQL console.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $db_config = App::getApp()['db.options'];
    $user = escapeshellarg($db_config['user']);
    $pass = escapeshellarg($db_config['password']);
    $name = escapeshellarg($db_config['dbname']);
    $host = escapeshellarg($db_config['host']);

    if (empty($db_config['password'])) {
      $command = sprintf('mysql --user=%s --database=%s --host=%s', $user, $name, $host);
    } else {
      $command = sprintf('mysql --user=%s --password=%s --database=%s --host=%s', $user, $pass, $name, $host);
    }

    $descriptors = array(
      0 => STDIN,
      1 => STDOUT,
      2 => STDERR,
    );
    $process = proc_open($command, $descriptors, $pipes);
    proc_close($process);
  }

}
