<?php

namespace Acquia\WipService\Console\Commands;

use Acquia\Wip\Implementation\WipClassDetailGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class WipVersionDifferenceCommand.
 */
class WipVersionDifferenceCommand extends Command {

  /**
   * The path to the fingerprint directory.
   */
  const FINGERPRINT_DIR = 'wipversions/fingerprints';

  /**
   * The path to the detail directory.
   */
  const DETAIL_DIR = 'wipversions/details';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $usage = <<<EOT
The diff command checks if the fingerprint hash for a Wip class has changed. 
Original fingerprints and details are found in the wipversions directory.

<comment>Example commands:</comment>

  # Get the fingerprint hash difference for the 'BuildSteps' Wip class.
  <info>diff 'Acquia\Wip\Modules\NativeModule\BuildSteps'</info>
EOT;

    $this->setName('diff')
      ->setHelp($usage)
      ->setDescription('Detects the difference in fingerprint hash values.')
      ->addArgument(
        'class',
        InputArgument::REQUIRED,
        'The name of a Wip class.'
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
    $new_hash = hash('md5', $data);

    // Find the current version hash. If the file doesn't exist, or if its
    // content differs from the new hash, alert the user.
    $old_hash = file_get_contents(sprintf("%s/%s", self::FINGERPRINT_DIR, $short_class_name));
    if (($old_hash === FALSE) || strcmp(trim($old_hash), trim($new_hash)) !== 0) {
      $output->writeln(
        '<error>The fingerprint hash values do not match.</error>'
      );
      $output->writeln(sprintf("New fingerprint hash value: %s", $new_hash));
      $output->writeln(sprintf("Old fingerprint hash value: %s", $old_hash === FALSE ? "N/A" : $old_hash));

      $diff = $this->getDiffDetails($short_class_name, $data);
      $output->writeln($diff);
    } else {
      $output->writeln(
        '<info>The fingerprint hash values match.</info>'
      );
    }
  }

  /**
   * Prints out a diff of old and new class details.
   *
   * @param string $class_name
   *   The name of the class.
   * @param string $new_details
   *   The new details.
   *
   * @return string
   *   The output of the diff.
   */
  private function getDiffDetails($class_name, $new_details) {
    $details_path = sprintf("%s/%s", self::DETAIL_DIR, $class_name);
    $old_details = '';

    if (is_readable($details_path)) {
      $old_details = file_get_contents($details_path);
    }

    $command = sprintf(
      "bash -c 'diff <(echo \"%s\") <(echo \"%s\")'",
      str_replace("'", "'\''", $old_details),
      str_replace("'", "'\''", $new_details)
    );

    return shell_exec($command);
  }

}
