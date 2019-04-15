<?php

namespace Acquia\WipService\Test;

use Acquia\WipService\App;

/**
 * This trait contains common methods used for initializing tests.
 */
trait CommonTestTrait {

  /**
   * Missing summary.
   *
   * @var \Doctrine\ORM\EntityManager
   */
  protected $entityManager;

  /**
   * Missing summary.
   *
   * @var array
   */
  protected $entities = array(
    'Acquia\WipIntegrations\DoctrineORM\Entities\EcsClusterStoreEntry',
    'Acquia\WipIntegrations\DoctrineORM\Entities\ConfigurationStoreEntry',
    'Acquia\WipIntegrations\DoctrineORM\Entities\ServerStoreEntry',
    'Acquia\WipIntegrations\DoctrineORM\Entities\SignalCallbackStoreEntry',
    'Acquia\WipIntegrations\DoctrineORM\Entities\SignalStoreEntry',
    'Acquia\WipIntegrations\DoctrineORM\Entities\StateStoreEntry',
    'Acquia\WipIntegrations\DoctrineORM\Entities\TaskDefinitionEntry',
    'Acquia\WipIntegrations\DoctrineORM\Entities\ThreadStoreEntry',
    'Acquia\WipIntegrations\DoctrineORM\Entities\WipApplicationStoreEntry',
    'Acquia\WipIntegrations\DoctrineORM\Entities\WipGroupConcurrencyEntry',
    'Acquia\WipIntegrations\DoctrineORM\Entities\WipLogStoreEntry',
    'Acquia\WipIntegrations\DoctrineORM\Entities\WipPoolStoreEntry',
    'Acquia\WipIntegrations\DoctrineORM\Entities\WipStoreEntry',
  );

  /**
   * PHPUnit setUp for setting up the application.
   *
   * Note: Child classes that define a setUp method must call
   * parent::setUp().
   */
  public function setUp() {
    $this->app = $this->createApplication();

    $this->entityManager = App::getEntityManager();
    $this->ensureEmptyDatabase();
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $this->truncateEntities();
    $this->entityManager->getConnection()->close();
  }

  /**
   * {@inheritdoc}
   */
  public function createApplication() {
    $app = require __DIR__ . '/../../../../../app/app.php';
    $app['debug'] = TRUE;
    $app['exception_handler']->disable();
    return $app;
  }

  /**
   * Safeguard to ensure that tests are running against an empty database.
   */
  private function ensureEmptyDatabase() {
    $found = array();
    foreach ($this->entities as $entity_name) {
      $records = $this->entityManager->getRepository($entity_name)->findOneBy(array());
      if (!empty($records)) {
        $found[$entity_name] = sprintf('Found %d records.', count($records));
      }
    }

    if (!empty($found)) {
      $found_list = print_r($found, TRUE);
      $message = <<<EOT
The tests should be executed against an empty database but the following
entities where found:

$found_list

Import the scratch/CLEAR_DB.sql file to truncate all database tables.

EOT;
      die($message);
    }
  }

  /**
   * Truncate entity tables at the end of each test to ensure a clean slate.
   */
  private function truncateEntities() {
    foreach ($this->entities as $entity_name) {
      $metadata = $this->entityManager->getClassMetadata($entity_name);
      $connection = $this->entityManager->getConnection();
      $platform = $connection->getDatabasePlatform();
      $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 0;');
      $query = $platform->getTruncateTableSql($metadata->getTableName());
      $connection->executeUpdate($query);
      $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 1;');
    }
  }

}
