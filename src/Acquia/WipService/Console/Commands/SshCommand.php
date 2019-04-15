<?php

namespace Acquia\WipService\Console\Commands;

use Acquia\WipService\Console\WipConsoleCommand;
use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\Implementation\ContainerWip;
use Acquia\Wip\Runtime\WipPoolInterface;
use Acquia\Wip\Storage\WipStoreInterface;
use Acquia\Wip\WipFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Facilitates logging in to the container associated with a Wip instance.
 */
class SshCommand extends WipConsoleCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $help = <<<EOT
This command sshs into a container and provides access as the local user, and allows inspection of the container to be
performed. Note the session will terminate as soon as the container is stopped.
EOT;

    $this->setName('ssh')
      ->setDescription('Logs into the container associated with the specified Wip instance.')
      ->setHelp($help)
      ->addArgument('id', InputArgument::REQUIRED, '[wip ID]');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $id = $input->getArgument('id');
    if (empty($id)) {
      $output->writeln('No Wip ID specified.');
    }
    $id = intval($id);
    /** @var WipPoolInterface $wip_pool */
    $wip_pool = WipFactory::getObject('acquia.wip.pool');
    $task = $wip_pool->getTask($id);
    if (!empty($task)) {
      /** @var WipStoreInterface $wip_store */
      $wip_store = WipFactory::getObject('acquia.wip.storage.wip');
      $wip_iterator = $wip_store->get($id);
      if (FALSE !== $wip_iterator) {
        $wip = $wip_iterator->getWip();
        if ($wip instanceof ContainerWip) {
          $environment = $wip->getContainerEnvironment();
          if (NULL === $environment) {
            // The container is not available.
            $completed_timestamp = $task->getCompletedTimestamp();
            if (empty($completed_timestamp)) {
              throw new \Exception(sprintf('The container for wip %d has not reached the RUNNING state yet.', $id));
            } else {
              throw new \Exception(sprintf('The container for wip %d has been shut down.', $id));
            }
          }
          $command = $this->getSshCommand($environment);
          $descriptors = array(
            0 => STDIN,
            1 => STDOUT,
            2 => STDERR,
          );
          $process = proc_open($command, $descriptors, $pipes);
          proc_close($process);
        }
      }
    }
  }

  /**
   * Creates an SSH command string.
   *
   * @param EnvironmentInterface $environment
   *   The environment.
   *
   * @return string
   *   The command string.
   */
  public function getSshCommand(EnvironmentInterface $environment) {
    $user = $environment->getUser();
    if (empty($user)) {
      // Otherwise construct the hosting user from the sitegroup and
      // environment.
      $user = $environment->getSitegroup() . '.' . $environment->getEnvironmentName();
    }
    $server = $environment->getCurrentServer();
    $port = $environment->getPort();
    try {
      $ssh_key_path = $environment->getSshKeyPath();
      $result = sprintf(
        'ssh -i %s -o "IdentitiesOnly=yes" -o "StrictHostKeyChecking=no" -p %s %s@%s',
        $ssh_key_path,
        $port,
        $user,
        $server
      );
    } catch (\DomainException $e) {
      $password = $environment->getPassword();
      $result = sprintf(
        'sshpass -p%s ssh -o StrictHostKeyChecking=no %s@%s',
        $password,
        $user,
        $server
      );
    }
    return $result;
  }

}
