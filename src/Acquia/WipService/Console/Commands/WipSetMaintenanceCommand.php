<?php

namespace Acquia\WipService\Console\Commands;

use Acquia\WipService\Console\WipConsoleCommand;
use Acquia\WipService\Resource\v1\StateResource;
use Acquia\Wip\Metrics\MetricsRelayInterface;
use Acquia\Wip\State\Maintenance;
use Acquia\Wip\Storage\StateStoreInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Defines a console command for setting maintenance mode.
 */
class WipSetMaintenanceCommand extends WipConsoleCommand {

  /**
   * The state storage instance.
   *
   * @var StateStoreInterface
   */
  protected $stateStore;

  /**
   * The interface to send the timing metrics to.
   *
   * @var MetricsRelayInterface
   */
  private $relay;

  /**
   * WipSetMaintenanceCommand constructor.
   */
  public function __construct() {
    parent::__construct();
    $this->stateStore = $this->dependencyManager->getDependency('acquia.wip.storage.state');
    $this->relay = $this->dependencyManager->getDependency('acquia.wip.metrics.relay');
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $help = <<<EOT
Puts the wip service into maintenance mode. This enables the service to continue to queue new and allow access to other
endpoints marked with "allowedDuringMaintenance": ["full"], in their service description. All other endpoints will
reject requests.
    
To enable maintenance mode
<comment>wipctl set-maintenance --enable</comment>
To disable maintenance mode
<comment>wipctl set-maintenance</comment>
EOT;
    $this->setName('set-maintenance')
      ->setDescription('Set maintenance mode.')
      ->setHelp($help)
      ->addOption(
        'enable',
        NULL,
        InputOption::VALUE_NONE,
        'Enable maintenance mode. Running without this option will disable maintenance.'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $enabled = $input->getOption('enable');
    $name = Maintenance::STATE_NAME;
    $current_state = $this->stateStore->get($name);
    if (empty($enabled)) {
      if ($current_state === Maintenance::FULL) {
        $elapsed_time = time() - $this->stateStore->getChangedTime($name);

        // For the case in which OFF is being set, we actually delete the state.
        $this->stateStore->delete($name);
        // Relay the timing values.
        $this->relay->timing(sprintf(StateResource::TIMER_PATTERN, $name), $elapsed_time);
        $output->writeln('<comment>Maintenance mode has been disabled.</comment>');
      } else {
        $output->writeln('<comment>Maintenance mode is currently disabled.</comment>');
      }
    } else {
      if ($current_state === Maintenance::FULL) {
        $output->writeln('<comment>Maintenance mode is currently enabled.</comment>');
      } else {
        $this->stateStore->set($name, Maintenance::FULL);
        $output->writeln('<comment>Maintenance mode has been enabled.</comment>');
      }
    }
  }

}
