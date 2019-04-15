<?php

namespace Acquia\WipService\Console\Commands;

use Acquia\WipInterface\EcsClusterStoreInterface;
use Acquia\WipService\Console\WipConsoleCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Saves an ecs cluster configuration.
 */
class WipSaveEcsClusterCommand extends WipConsoleCommand {

  /**
   * Cluster storage.
   *
   * @var EcsClusterStoreInterface
   */
  protected $clusterStorage;

  /**
   * WipSaveEcsClusterCommand constructor.
   */
  public function __construct() {
    parent::__construct();
    $this->clusterStorage = $this->dependencyManager->getDependency('acquia.wip.storage.ecs_cluster');
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $usage = <<<EOT
Adds or updates an ecs cluster configuration:
<comment>wipctl save-ecs-cluster <name> <cluster> <key> <secret> --region=clusterRegion</comment>
EOT;

    $this->setName('save-ecs-cluster')
      ->setDescription('Adds or updates an ecs cluster configuration.')
      ->setHelp($usage)
      ->addArgument(
        'name',
        InputArgument::REQUIRED,
        'The unique name of the configuration.'
      )
      ->addArgument(
        'cluster',
        InputArgument::REQUIRED,
        'The ECS cluster to point at.'
      )
      ->addArgument(
        'key',
        InputArgument::REQUIRED,
        'The aws access key.'
      )
      ->addArgument(
        'secret',
        InputArgument::REQUIRED,
        'The aws secret.'
      )
      ->addOption(
        'region',
        NULL,
        InputOption::VALUE_OPTIONAL,
        'The region of the cluster.',
        'us-east-1'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $cluster_name = strtolower($input->getArgument('name'));
    $cluster = $input->getArgument('cluster');
    $key = $input->getArgument('key');
    $secret = $input->getArgument('secret');
    $region = $input->getOption('region');

    $exists = $this->clusterStorage->load($cluster_name);
    if (!empty($exists)) {
      $message = sprintf('Cluster configuration [%s] updated', $cluster_name);
    } else {
      $message = sprintf('New cluster configuration [%s] added', $cluster_name);
    }

    $this->clusterStorage->save($cluster_name, $key, $secret, $region, $cluster);

    $output->writeln($message);
  }

}
