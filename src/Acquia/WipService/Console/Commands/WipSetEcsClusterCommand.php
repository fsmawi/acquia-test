<?php

namespace Acquia\WipService\Console\Commands;

use Acquia\WipInterface\EcsClusterStoreInterface;
use Acquia\WipService\Console\WipConsoleCommand;
use Acquia\Wip\Storage\StateStoreInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Sets an ecs cluster configuration to active.
 */
class WipSetEcsClusterCommand extends WipConsoleCommand {

  /**
   * Cluster storage.
   *
   * @var EcsClusterStoreInterface
   */
  protected $clusterStorage;

  /**
   * State storage.
   *
   * @var StateStoreInterface
   */
  protected $stateStorage;

  /**
   * WipSetEcsClusterCommand constructor.
   */
  public function __construct() {
    parent::__construct();
    $this->clusterStorage = $this->dependencyManager->getDependency('acquia.wip.storage.ecs_cluster');
    $this->stateStorage = $this->dependencyManager->getDependency('acquia.wip.storage.state');
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $help = <<<EOT
Sets the ecs cluster that will be used to process wip tasks.

Use the default configuration:
<comment>wipctl set-active-cluster</comment>

Use a non-default configuration:
<comment>wipctl set-active-cluster --name=myConfig</comment>
EOT;

    $this->setName('set-active-cluster')
      ->setDescription('Sets an ecs cluster configuration to active.')
      ->setHelp($help)
      ->addOption(
        'name',
        NULL,
        InputOption::VALUE_OPTIONAL,
        'The name of configuration to use.',
        'default'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $cluster_name = strtolower($input->getOption('name'));

    $exists = $this->clusterStorage->load($cluster_name);
    if (empty($exists)) {
      $names = [];
      foreach ($this->clusterStorage->loadAll() as $cluster) {
        $names[] = $cluster->getName();
      }
      if (empty($names)) {
        $names = 'No clusters exist that can be made active.';
      } else {
        $names = sprintf('Available clusters %s.', implode(', ', $names));
      }
      throw new \RuntimeException(sprintf('The cluster called [%s] does not exist. %s', $cluster_name, $names));
    }

    if ($cluster_name == 'default') {
      $this->stateStorage->delete('acquia.wip.ecs_cluster.name');
    } else {
      $this->stateStorage->set('acquia.wip.ecs_cluster.name', $cluster_name);
    }

    $output->writeln(sprintf('The active cluster has been set to [%s]', $cluster_name));
  }

}
