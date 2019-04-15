<?php

namespace Acquia\WipService\Console\Commands;

use Acquia\WipService\Console\WipConsoleCommand;
use Acquia\Wip\WipLogLevel;
use DateTime;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Defines a console command for displaying log messages.
 */
class LogCommand extends WipConsoleCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $all_levels = WipLogLevel::getAll();
    $levels = '';
    foreach ($all_levels as $level => $label) {
      $levels .= sprintf("\n  %d - %s", $level, $label);
    }
    $help = <<<EOT
The log command retrieves log entries from the database layer.

<comment>Log levels:</comment>
$levels

<comment>Example commands:</comment>

  # View the latest 10 log entries.
  <info>wip log</info>

  # View log entries above Error level for a specific object ID.
  <info>wip log --object-id=123 --limit=none --minimum-level=2</info>

  # View all log entries for a specific object ID.
  <info>wip log --object-id=123 --limit=none</info>
EOT;

    $this->setName('log')
      ->setHelp($help)
      ->setDescription('View the logs.')
      ->addOption(
        'object-id',
        NULL,
        InputOption::VALUE_REQUIRED,
        'A specific object ID by which to filter the displayed log entries.'
      )
      ->addOption(
        'limit',
        NULL,
        InputOption::VALUE_REQUIRED,
        'The number of log entries to display. Defaults to 10.',
        10
      )
      ->addOption(
        'offset',
        NULL,
        InputOption::VALUE_REQUIRED,
        'The starting offset position to fetch logs from.',
        0
      )
      ->addOption(
        'order',
        NULL,
        InputOption::VALUE_REQUIRED,
        'The order of the returned log entries. Defaults to DESC.',
        'DESC'
      )
      ->addOption(
        'minimum-level',
        NULL,
        InputOption::VALUE_REQUIRED,
        'The minimum log level.',
        WipLogLevel::TRACE
      )
      ->addOption(
        'maximum-level',
        NULL,
        InputOption::VALUE_REQUIRED,
        'The maximum log level.',
        WipLogLevel::FATAL
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $object_id = $input->getOption('object-id');
    $limit = $input->getOption('limit');
    $offset = $input->getOption('offset');
    $sort_order = strtoupper($input->getOption('order'));
    $minimum_level = $input->getOption('minimum-level');
    $maximum_level = $input->getOption('maximum-level');

    // Numeric values passed on the command line are strings.
    if (is_numeric($minimum_level)) {
      $minimum_level = (int) $minimum_level;
    }
    if (is_numeric($maximum_level)) {
      $maximum_level = (int) $maximum_level;
    }

    // Translate string log levels to log level integers.
    if (is_string($minimum_level)) {
      $minimum_level = WipLogLevel::toInt($minimum_level);
    }
    if (is_string($maximum_level)) {
      $maximum_level = WipLogLevel::toInt($maximum_level);
    }

    if ($limit === 'none') {
      $limit = PHP_INT_MAX;
    }

    // Parameter validation.
    if (!empty($object_id) && !is_numeric($object_id)) {
      throw new \RuntimeException('The --object-id option must be an integer.');
    }
    if (!empty($limit) && !is_numeric($limit)) {
      throw new \RuntimeException('The --limit option must be an integer.');
    }
    if (!is_numeric($offset)) {
      throw new \RuntimeException('The --offset option must be an integer.');
    }
    if (!in_array($sort_order, array('ASC', 'DESC'))) {
      throw new \RuntimeException('The --order option must be one of ASC or DESC.');
    }
    // Checking whether the levels are integers is taken care of in WipLogLevel.
    if (!WipLogLevel::isValid($minimum_level)) {
      throw new \RuntimeException('The --minimum-level option must be a valid log level.');
    }
    if (!WipLogLevel::isValid($maximum_level)) {
      throw new \RuntimeException('The --maximum-level option must be a valid log level.');
    }

    if (!empty($object_id)) {
      $object_id = (int) $object_id;
    }

    $wip_log = $this->dependencyManager->getDependency('acquia.wip.wiplogstore');
    $results = $wip_log->load(
      $object_id,
      $offset,
      $limit,
      $sort_order,
      $minimum_level,
      $maximum_level
    );

    if (empty($results)) {
      $output->writeln('No results found for query.');
    } else {
      $table = new Table($output);
      $header = array(
        'ID',
        'Object ID',
        'Level',
        'Time',
        'Message',
      );
      $table->setHeaders($header);
      $rows = array();
      foreach ($results as $log_entry) {
        $rows[] = array(
          $log_entry->getId(),
          $log_entry->getObjectId(),
          WipLogLevel::toString($log_entry->getLogLevel()),
          date(DateTime::ISO8601, $log_entry->getTimestamp()),
          trim($log_entry->getMessage()),
        );
      }
      $table->setRows($rows);
      $table->render();
    }
  }

}
