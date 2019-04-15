<?php

namespace Acquia\WipService\Console\Command;

use Acquia\WipIntegrations\DoctrineORM\WipLogStore;
use Acquia\WipService\Console\AbstractWipToolTest;
use Acquia\WipService\Console\Commands\LogCommand;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\WipLogLevel;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Missing summary.
 */
class LogCommandTest extends AbstractWipToolTest {

  /**
   * Missing summary.
   *
   * @var \Acquia\Wip\Storage\WipLogStoreInterface
   */
  private $storage;

  /**
   * Missing summary.
   *
   * @var \Acquia\Wip\Storage\WipLogInterface
   */
  private $wipLog;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->storage = new WipLogStore($this->app);
    $this->wipLog = new WipLog($this->storage);
  }

  /**
   * Missing summary.
   */
  protected function getConsoleApp() {
    $application = $this->app['console'];
    $application->add(new LogCommand());
    return $application;
  }

  /**
   * Generate random log entries.
   *
   * @param int $count
   *   The number of log entries to generate.
   */
  private function generateLogEntries($count) {
    if (!is_int($count) || $count < 1) {
      throw new \RuntimeException('The count parameter to the generateLogEntries must be a positive integer.');
    }
    for ($i = 0; $i < $count; $i++) {
      $level = array_rand(WipLogLevel::getAll());
      $this->wipLog->log($level, 'message', rand(1, 5));
    }
  }

  /**
   * Missing summary.
   */
  private function renderTable(
    $object_id = NULL,
    $offset = 0,
    $count = 10,
    $sort_order = 'desc',
    $minimum_log_level = WipLogLevel::TRACE,
    $maximum_log_level = WipLogLevel::FATAL
  ) {
    $output = new BufferedOutput();
    $results = $this->storage->load($object_id, $offset, $count, $sort_order, $minimum_log_level, $maximum_log_level);

    $formatter = new OutputFormatter();
    $formatter->setDecorated = FALSE;
    $output->setFormatter($formatter);

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
          date(\DateTime::ISO8601, $log_entry->getTimestamp()),
          trim($log_entry->getMessage()),
        );
      }
      $table->setRows($rows);
      $table->render();
    }

    return $output->fetch();
  }

  /**
   * Missing summary.
   */
  public function testNoResults() {
    $rendered = $this->renderTable();
    $stdout = $this->execute();

    $this->assertEquals($rendered, $stdout);
  }

  /**
   * Missing summary.
   */
  public function testParametersNone() {
    $this->generateLogEntries(10);
    $rendered = $this->renderTable();
    $stdout = $this->execute();

    $this->assertEquals($rendered, $stdout);
  }

  /**
   * Missing summary.
   */
  private function execute($arguments = array()) {
    $application = $this->getConsoleApp();
    $command = $application->find('log');
    $command_tester = new CommandTester($command);
    $default_arguments = array(
      'command' => $command->getName(),
    );
    $command_tester->execute(array_merge($default_arguments, $arguments));
    return $command_tester->getDisplay();
  }

  /**
   * Missing summary.
   */
  public function testParametersLimitNone() {
    $this->generateLogEntries(25);
    $rendered = $this->renderTable(
      $object_id = NULL,
      $offset = 0,
      $limit = NULL
    );
    $stdout = $this->execute(array(
      '--limit' => 'none',
    ));

    $this->assertEquals($rendered, $stdout);
  }

  /**
   * Missing summary.
   */
  public function testParametersLimitValue() {
    $this->generateLogEntries(10);

    $rendered = $this->renderTable(
      $object_id = NULL,
      $offset = 0,
      $limit = 5
    );
    $stdout = $this->execute(array(
      '--limit' => 5,
    ));

    $this->assertEquals($rendered, $stdout);
  }

  /**
   * Missing summary.
   */
  public function testParametersLimitOffset() {
    $this->generateLogEntries(10);

    $rendered = $this->renderTable(
      $object_id = NULL,
      $offset = 5,
      $limit = 5
    );
    $stdout = $this->execute(array(
      '--limit' => 5,
      '--offset' => 5,
    ));

    $this->assertEquals($rendered, $stdout);
  }

  /**
   * Missing summary.
   */
  public function testParametersOrder() {
    $this->generateLogEntries(5);

    foreach (array('asc', 'desc') as $order) {
      $rendered = $this->renderTable(
        $object_id = NULL,
        $offset = 0,
        $limit = NULL,
        $sort_order = strtoupper($order)
      );
      $stdout = $this->execute(array(
        '--order' => $order,
      ));

      $this->assertEquals($rendered, $stdout);
    }
  }

  /**
   * Missing summary.
   *
   * @expectedException \RuntimeException
   *
   * @todo Use a specific exception for invalid parameters.
   */
  public function testParametersOrderErroneous() {
    foreach (array(0, 1, 'foo') as $erroneous_order) {
      $this->execute(array(
        '--order' => $erroneous_order,
      ));
    }
  }

  /**
   * Missing summary.
   */
  public function testWipLogLevels() {
    $this->generateLogEntries(100);

    foreach (WipLogLevel::getAll() as $level => $label) {
      // Minimum log level integer.
      $rendered = $this->renderTable(
        $object_id = NULL,
        $offset = 0,
        $limit = 10,
        $sort_order = 'DESC',
        $minimum_log_level = $level
      );
      $stdout = $this->execute(array(
        '--minimum-level' => $level,
      ));
      $this->assertEquals($rendered, $stdout);
      // Maximum log level integer.
      $rendered = $this->renderTable(
        $object_id = NULL,
        $offset = 0,
        $limit = 10,
        $sort_order = 'DESC',
        $minimum_log_level = WipLogLevel::TRACE,
        $maximum_log_level = $level
      );
      $stdout = $this->execute(array(
        '--maximum-level' => $level,
      ));
      $this->assertEquals($rendered, $stdout);
      // Minimum log level integer.
      $rendered = $this->renderTable(
        $object_id = NULL,
        $offset = 0,
        $limit = 10,
        $sort_order = 'DESC',
        $minimum_log_level = $level
      );
      $stdout = $this->execute(array(
        '--minimum-level' => $label,
      ));
      $this->assertEquals($rendered, $stdout);
      // Maximum log level integer.
      $rendered = $this->renderTable(
        $object_id = NULL,
        $offset = 0,
        $limit = 10,
        $sort_order = 'DESC',
        $minimum_log_level = WipLogLevel::TRACE,
        $maximum_log_level = $level
      );
      $stdout = $this->execute(array(
        '--maximum-level' => $label,
      ));
      $this->assertEquals($rendered, $stdout);
    }
  }

  /**
   * Missing summary.
   *
   * @expectedException \RuntimeException
   */
  public function testWipLogLevelsOutOfBoundLower() {
    $this->generateLogEntries(10);

    $min = min(array_flip(WipLogLevel::getAll()));
    $this->execute(array(
      '--minimum-level' => $min - 1,
    ));
  }

  /**
   * Missing summary.
   *
   * @expectedException \RuntimeException
   */
  public function testWipLogLevelsOutOfBoundUpper() {
    $this->generateLogEntries(10);

    $max = max(array_flip(WipLogLevel::getAll()));
    $this->execute(array(
      '--maximum-level' => $max + 1,
    ));
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testWipLogLevelsInvalidMin() {
    $this->generateLogEntries(10);

    $this->execute(array(
      '--minimum-level' => 'foo',
    ));
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testWipLogLevelsInvalidMax() {
    $this->generateLogEntries(10);

    $this->execute(array(
      '--maximum-level' => 'foo',
    ));
  }

}
