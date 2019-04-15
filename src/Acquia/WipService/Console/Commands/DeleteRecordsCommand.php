<?php

namespace Acquia\WipService\Console\Commands;

use Acquia\WipIntegrations\DoctrineORM\SignalCallbackStore;
use Acquia\WipService\App;
use Acquia\WipService\Console\WipConsoleCommand;
use Acquia\WipService\MySql\MysqlUtilityInterface;
use Acquia\Wip\Storage\SignalStoreInterface;
use Acquia\Wip\Storage\ThreadStoreInterface;
use Acquia\Wip\Storage\WipLogStoreInterface;
use Acquia\Wip\Storage\WipPoolStoreInterface;
use Acquia\Wip\Storage\WipStoreInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Cleans up old entries in the database.
 */
class DeleteRecordsCommand extends WipConsoleCommand {

  /**
   * The wip log.
   *
   * @var WipLogStoreInterface
   */
  protected $wipLog;

  /**
   * The thread store.
   *
   * @var ThreadStoreInterface
   */
  protected $threadStore;

  /**
   * The wip pool.
   *
   * @var WipPoolStoreInterface
   */
  protected $wipPool;

  /**
   * The wip store.
   *
   * @var WipStoreInterface
   */
  protected $wipStore;

  /**
   * The signal store.
   *
   * @var SignalStoreInterface
   */
  protected $signalStore;

  /**
   * List of groups and associated retention time as a string.
   *
   * @var array
   */
  protected $groupList;

  /**
   * Log retention time.
   *
   * @var string
   */
  protected $logRetention;

  /**
   * Mysql utility class.
   *
   * @var MysqlUtilityInterface
   */
  protected $mysql;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    parent::__construct();
    $this->wipPool = $this->dependencyManager->getDependency('acquia.wip.storage.wippool');
    $this->mysql = $this->dependencyManager->getDependency('acquia.wipservice.mysql.utility');
    $this->wipLog = $this->dependencyManager->getDependency('acquia.wip.wiplogstore');
    $this->wipStore = $this->dependencyManager->getDependency('acquia.wip.storage.wip');
    $this->signalStore = $this->dependencyManager->getDependency('acquia.wip.storage.signal');
    $this->threadStore = $this->dependencyManager->getDependency('acquia.wip.storage.thread');
    $orm = App::getApp()['config.orm_options'];
    $this->groupList = isset($orm['group_retention']) ? $orm['group_retention'] : [];
    $this->logRetention = isset($orm['system_log_retention']) ? $orm['system_log_retention'] : '7 days';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $help = <<<EOT
Deletes entries in the database over a certain age.

The retention policy is defined in config.orm.yml under group_retention. Canary and wip tasks can be kept for different
time periods. Note this can be overridden in /mnt/files/wipservice.[env]/nobackup/config/ folder.
EOT;
    $this->setName('dbcleanup')
      ->setDescription('Deletes entries in the database over a certain age')
      ->setHelp($help);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    try {
      $current_time = time();
      $callback_store = new SignalCallbackStore();
      // Move to config along with retention limit.
      foreach ($this->groupList as $group => $retention_period) {
        $count = 0;
        $cleanup_before = $current_time - strtotime($retention_period, 0);
        while ($ids = $this->wipPool->getCompletedIds($group, $cleanup_before)) {
          $count += count($ids);
          $this->wipLog->pruneObjectsNoResults($ids, PHP_INT_MAX);
          $this->wipStore->pruneObjects($ids);
          $uuids = $this->signalStore->getUuids($ids);
          $callback_store->pruneObjects($uuids);
          $this->signalStore->pruneObjects($ids);
          $this->threadStore->pruneObjects($ids);
          $this->wipPool->pruneObjects($ids);
        }
        $output->writeln(
          sprintf('<comment>Record clean up complete: %d %s wip object(s) deleted.</comment>', $count, $group)
        );
      }
      // Clean up system logs.
      $cleanup_before = $current_time - strtotime($this->logRetention, 0);
      // System logs build up quickly so delete in batches to ensure no memory
      // issues.
      $this->wipLog->pruneObjectsNoResults([0], $cleanup_before);
      $output->writeln('<comment>System log clean up complete.</comment>');
    } catch (\Exception $e) {
      $message = $e->getMessage();
      $output->writeln(
        sprintf('<error>%s.</error>', $message)
      );
    }
  }

}
