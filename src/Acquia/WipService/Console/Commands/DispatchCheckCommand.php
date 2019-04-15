<?php

namespace Acquia\WipService\Console\Commands;

use Acquia\WipService\Console\WipConsoleCommand;
use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\DependencyManager;
use Acquia\Wip\Storage\WipPoolStoreInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Compiles dispatch error information from the transcript command.
 */
class DispatchCheckCommand extends WipConsoleCommand implements DependencyManagedInterface {

  /**
   * The instance of the WipPoolStore.
   *
   * @var WipPoolStoreInterface
   */
  protected $wipPoolStore = NULL;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $usage = <<<EOT
The dispatch-check command reports if there are dispatch errors for the given object IDs.';

Examples:
   dispatch-check --start 16
   dispatch-check --range '{"range" : [21, 31]}'
   dispatch-check --list '{"list" : [46, 71, 141]}'
EOT;
    $this->setName('dispatch-check')
      ->setHelp($usage)
      ->setDescription('Evaluate one or more tasks for dispatch errors.')
      ->addOption(
        'start',
        NULL,
        InputOption::VALUE_REQUIRED,
        'Specify the inclusive start object ID and the command will evaluate to the end of all IDs.'
      )
      ->addOption(
        'range',
        NULL,
        InputOption::VALUE_REQUIRED,
        'Specify the inclusive start and stop IDs of objects to evaluate.'
      )
      ->addOption(
        'list',
        NULL,
        InputOption::VALUE_REQUIRED,
        'Specify a list of object IDs to evaluate.'
      );
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.storage.wippool' => 'Acquia\Wip\Storage\WipPoolStoreInterface',
    );
  }

  /**
   * Retrieves the WipPoolStore dependency.
   *
   * @return WipPoolStoreInterface
   *   The WipPoolStore instance.
   */
  private function getWipPoolStore() {
    if ($this->wipPoolStore === NULL) {
      $this->wipPoolStore = $this->dependencyManager->getDependency('acquia.wip.storage.wippool');
    }
    return $this->wipPoolStore;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->dependencyManager = new DependencyManager();
    $this->dependencyManager->addDependencies($this->getDependencies());

    $start = $input->getOption('start');
    $range = $input->getOption('range');
    $list = $input->getOption('list');

    if (!empty($range)) {
      $range = json_decode($range, TRUE)['range'];
      if ($range === NULL) {
        $message = '<error>User needs to specify range of object IDs in json format.</error>';
        $output->writeln($message);
        return 1;
      }
      $count = count($range);
      if ($count !== 2) {
        $message = '<error>User needs to specify exactly two object IDs in json format: start and stop.</error>';
        $output->writeln($message);
        return 1;
      }
      $start = $range[0];
      $stop = $range[1];

      $object_ids = $this->getWipPoolStore()->loadCompletedIdRange($start, $stop);
      $this->evaluate($object_ids, $output);
    } elseif (!empty($list)) {
      $object_ids = json_decode($list, TRUE)['list'];
      if ($object_ids === NULL) {
        $message = '<error>User needs to specify list of object IDs in json format.</error>';
        $output->writeln($message);
        return 1;
      }
      $this->evaluate($object_ids, $output);
    } else {
      // Assume start.
      $start = intval($start);
      if ($start <= 0) {
        $message = '<error>User needs to specify a positive integer object ID.</error>';
        $output->writeln($message);
        return 1;
      }

      $object_ids = $this->getWipPoolStore()->loadCompletedIdRange($start);
      $this->evaluate($object_ids, $output);
    }
    return 0;
  }

  /**
   * Calls the transcript command for each given object ID.
   *
   * @param int[] $object_ids
   *   The object IDs of the transcripts to evaluate.
   * @param OutputInterface $output
   *   The output to use to inform the user.
   */
  private function evaluate($object_ids, OutputInterface $output) {
    $no_errors = [];
    $errors = [];
    foreach ($object_ids as $object_id) {
      try {
        $transcript = new Process(
          'exec ' . $this->getAppDirectory() . '/bin/wip transcript --dispatch ' . $object_id,
          NULL,
          NULL,
          NULL,
          20
        );
        $transcript->run();
        $exit_code = $transcript->getExitCode();
        if ($exit_code != 0) {
          $errors[] = $object_id;
          $output->write('<error>.</error>');
        } else {
          $no_errors[] = $object_id;
          $output->write('<info>.</info>');
        }
      } catch (\Exception $e) {
        $output->writeln(
          sprintf(
            '<comment>Exception detected while evaluating object ID %d: %s</comment>',
            $object_id,
            $e->getMessage()
          )
        );
        continue;
      }
    }

    $output->writeln("\n\nSummary of Dispatch Evaluation:");
    $output->writeln(
      sprintf(
        "<info>Evaluation passed for object IDs [%d] %s\n</info>",
        count($no_errors),
        implode(', ', $no_errors)
      )
    );
    $output->writeln(
      sprintf(
        '<error>Evaluation failed for object IDs [%d] %s</error>',
        count($errors),
        implode(', ', $errors)
      )
    );
  }

}
