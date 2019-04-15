<?php

namespace Acquia\WipService\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * Class WipVersionCheckAllCommand.
 */
class WipVersionCheckAllCommand extends Command {

  /**
   * The path to the default file containing a list of all Wip classes.
   */
  const INPUT_FILE = 'wipversions/all_wips.txt';

  /**
   * The path to the default file containing the output of the command.
   */
  const OUTPUT_FILE = 'wipversions/all_wips.output';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $usage = <<<EOT
Check the details and fingerprints of all the Wip classes given in a file. The 
input file's location defaults to wipversions/all_wips.txt. The output will be
printed in a file, which defaults to wipversions/all_wips.output.

<comment>Example commands:</comment>

  # Check the details and fingerprints of all Wip classes.
  <info>check-all </info>
  # Check the details and fingerprints of all Wip classes listed in /sample/path/wips.txt.
  <info>check-all --input='/sample/path/wips.txt'</info>
  # Check the details and fingerprints using custom.output for the output.
  <info>check-all --output='custom.output'</info>
EOT;

    $this->setName('check-all')
      ->setHelp($usage)
      ->setDescription(
        'Checks the details and fingerprints of all given Wip classes.'
      )
      ->addOption(
        'input',
        NULL,
        InputOption::VALUE_REQUIRED,
        'The path to a file containing a list of Wip class names.'
      )
      ->addOption(
        'output',
        NULL,
        InputOption::VALUE_REQUIRED,
        'The path to a file for the output.'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $input_file_path = $input->getOption('input');
    if (empty($input_file_path)) {
      $output->writeln(
        sprintf("<info>Using default input file %s...</info>", self::INPUT_FILE)
      );
      $input_file_path = self::INPUT_FILE;
    }
    $output_file_path = $input->getOption('output');
    if (empty($output_file_path)) {
      $output->writeln(
        sprintf("<info>Using default output file %s...</info>", self::OUTPUT_FILE)
      );
      $output_file_path = self::OUTPUT_FILE;
    }

    if (is_readable($input_file_path)) {
      $content = file_get_contents($input_file_path);
      $classes = explode("\n", $content);
      // Remove the commented lines from the input file.
      foreach ($classes as $key => $value) {
        if (strpos($value, ';') === 0) {
          unset($classes[$key]);
        }
      }
      if (empty($classes) || (count($classes)) === 1 && empty($classes[0])) {
        $error_message = sprintf('No classes were provided in file: %s', $input_file_path);
        throw new \RuntimeException($error_message);
      } else {
        $application = $this->getApplication();
        /** @var WipVersionDifferenceCommand $diff_command */
        $diff_command = $application->find('diff');

        $handle = NULL;
        try {
          // Clear the current output file.
          $handle = fopen($output_file_path, 'w');
          fclose($handle);

          // Run a diff on each file.
          foreach ($classes as $class) {
            $class = trim($class);
            if (!empty($class)) {
              if (strcmp(substr($class, 0, 1), ";") === 0) {
                continue;
              }
              $diff_input = new ArrayInput(['class' => $class]);
              $diff_output = new StreamOutput(fopen($output_file_path, 'a'));
              $diff_output->write(sprintf("%s: ", $class));

              $diff_command->run($diff_input, $diff_output);
            }
          }
        } catch (\Exception $e) {
          throw $e;
        } finally {
          if (is_resource($handle)) {
            fclose($handle);
          }
        }

        $output_contents = file_get_contents($output_file_path);
        if (empty($output_contents)) {
          $error_message = sprintf(
            'No output was recorded. Please check to make sure that classes were provided in file: %s',
            $input_file_path
          );
          throw new \RuntimeException($error_message);
        } elseif (strpos($output_contents, "The fingerprint hash values do not match") !== FALSE) {
          $error_message = 'Some fingerprints of Wip object(s) have changed. Please make sure that any version changes necessary are implemented.';
          $error_message = sprintf("%s\nOutput:\n%s\n", $error_message, $output_contents);
          throw new \RuntimeException($error_message);
        } else {
          $output->writeln(
            "<info>All Wip object checks have passed. No version changes should be needed.</info>"
          );
        }
      }
    } else {
      throw new \RuntimeException(
        sprintf('Unable to read input file: %s', $input_file_path)
      );
    }
  }

}
