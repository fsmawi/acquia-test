<?php

namespace Acquia\WipService\Console\Commands;

use Acquia\Wip\Implementation\WipClassDetailGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class WipVersionGetHashCommand.
 */
class WipVersionGetHashCommand extends Command {


  /**
   * The path to the fingerprint directory.
   */
  const FINGERPRINT_DIR = 'wipversions/fingerprints';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $usage = <<<EOT
The hash command generates a hash value ("fingerprint") based on the version 
details of a Wip class.

<comment>Example commands:</comment>

  # Get the fingerprint hash string for the 'BuildSteps' Wip class.
  <info>hash 'Acquia\Wip\Modules\NativeModule\BuildSteps'</info>
EOT;

    $this->setName('hash')
      ->setHelp($usage)
      ->setDescription('Calculate the fingerprint hash.')
      ->addArgument(
        'class',
        InputArgument::REQUIRED,
        'The name of a Wip class.'
      )
      ->addOption(
        'save',
        NULL,
        InputOption::VALUE_NONE,
        'Whether the fingerprint should be saved, overwriting any existing file.'
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
    $data = $detail_generator->generate();
    $hash_value = hash('md5', implode("\n", $data));
    $output->writeln(sprintf("Fingerprint hash value: %s\n", $hash_value));

    if ($input->getOption('save')) {
      file_put_contents(sprintf("%s/%s", self::FINGERPRINT_DIR, $short_class_name), $hash_value);
      $output->writeln('<info>The fingerprint has been saved.</info>');
    }
  }

}
