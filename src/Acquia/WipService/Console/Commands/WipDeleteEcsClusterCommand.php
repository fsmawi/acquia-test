<?php

namespace Acquia\WipService\Console\Commands;

use Acquia\WipInterface\EcsClusterStoreInterface;
use Acquia\WipService\Console\WipConsoleCommand;
use Acquia\Wip\Storage\StateStoreInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Deletes an ecs cluster configuration.
 */
class WipDeleteEcsClusterCommand extends WipConsoleCommand {

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
   * List of configurations that can not be deleted.
   *
   * @var array
   */
  protected $noDelete = [
    'default' => TRUE,
  ];

  /**
   * WipDeleteEcsClusterCommand constructor.
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
Delete an ecs configuration if the cluster is not currently active, and provides a warning if it is active:
<comment>wipctl delete-ecs-cluster <name> </comment>
EOT;

    $this->setName('delete-ecs-cluster')
      ->setDescription('Deletes an ecs cluster configuration.')
      ->setHelp($help)
      ->addArgument(
        'name',
        InputArgument::REQUIRED,
        'The name of the configuration.'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $cluster_name = strtolower($input->getArgument('name'));

    if (array_key_exists($cluster_name, $this->noDelete)) {
      throw new \RuntimeException('The default cluster can not be deleted.');
    }

    $configuration = $this->stateStorage->get('acquia.wip.ecs_cluster.name', 'default');
    if ($configuration == $cluster_name) {
      throw new \RuntimeException(sprintf('The cluster called [%s] is active and can not be deleted.', $cluster_name));
    }
    $this->noDelete[$configuration] = TRUE;

    $exists = $this->clusterStorage->load($cluster_name);
    if (empty($exists)) {
      $names = [];
      foreach ($this->clusterStorage->loadAll() as $cluster) {
        $name = $cluster->getName();
        if (!array_key_exists($name, $this->noDelete)) {
          $names[] = $name;
        }
      }
      if (empty($names)) {
        $names = 'No clusters exist that can be deleted.';
      } else {
        $names = sprintf('The following clusters %s can be deleted.', implode(', ', $names));
      }
      $message = 'The cluster called [%s] does not exist. %s';
      throw new \RuntimeException(sprintf($message, $cluster_name, $names));
    }

    $this->clusterStorage->delete($cluster_name);

    $output->writeln(sprintf('The cluster [%s] has been deleted.', $cluster_name));
  }

}
