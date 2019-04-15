<?php

namespace Acquia\WipService\Console\Commands;

use Acquia\Wip\Implementation\WipClassDetailGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class WipVersionCommand.
 */
class WipVersionDetailCommand extends Command {

  /**
   * The path to the detail directory.
   */
  const DETAIL_DIR = 'wipversions/details';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $usage = <<<EOT
The detail command calculates version information for a Wip class based on 
its state table and class variables.

<comment>Example commands:</comment>

  # Get the version information for the 'BuildSteps' Wip class.
  <info>wipversion detail 'Acquia\Wip\Modules\NativeModule\BuildSteps'</info>
EOT;

    $this->setName('detail')
      ->setHelp($usage)
      ->setDescription('Calculates the version details.')
      ->addArgument(
        'class',
        InputArgument::REQUIRED,
        'The name of a Wip class.'
      )
      ->addOption(
        'save',
        NULL,
        InputOption::VALUE_NONE,
        'Whether the details should be saved, overwriting any existing file.'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $class_name = $input->getArgument('class');
    $class_name_parts = explode('\\', $class_name);
    $short_class_name = end($class_name_parts);

    $detail_generator = new WipClassDetailGenerator($class_name);
    $data = implode("\n", $detail_generator->generate());
    $output->writeln($data);

    if ($input->getOption('save')) {
      file_put_contents(sprintf("%s/%s", self::DETAIL_DIR, $short_class_name), $data);
      $output->writeln('<info>The details have been saved.</info>');
    }
  }

}
