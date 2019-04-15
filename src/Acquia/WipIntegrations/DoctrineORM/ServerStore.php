<?php

namespace Acquia\WipIntegrations\DoctrineORM;

use Acquia\WipIntegrations\DoctrineORM\Entities\ServerStoreEntry;
use Acquia\WipService\App;
use Acquia\Wip\DependencyManagerInterface;
use Acquia\Wip\Exception\ServerStoreSaveException;
use Acquia\Wip\Runtime\Server;
use Acquia\Wip\ServerStatus;
use Acquia\Wip\Storage\ServerStoreInterface;
use Acquia\Wip\WipFactory;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Provides a CRUD features for Server objects using Doctrine ORM.
 *
 * @copydetails ServerStoreInterface
 */
class ServerStore implements ServerStoreInterface {

  /**
   * The WipFactory resource name.
   */
  const RESOURCE_NAME = 'acquia.wip.storage.server';

  /**
   * The name of the entity.
   */
  const ENTITY_NAME = 'Acquia\WipIntegrations\DoctrineORM\Entities\ServerStoreEntry';

  /**
   * The entity manager.
   *
   * @var EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Creates a new instance of ServerStore.
   */
  public function __construct() {
    $this->entityManager = App::getEntityManager();
  }

  /**
   * Creates a Server object out of a ServerStoreEntry object.
   *
   * @param ServerStoreEntry $server_entry
   *   The server store entry.
   *
   * @return Server
   *   A server instance.
   */
  private function convert(ServerStoreEntry $server_entry) {
    $server = new Server($server_entry->getHostname());
    $server->setId($server_entry->getId());
    $server->setTotalThreads($server_entry->getTotalThreads());
    $server->setStatus($server_entry->getStatus());
    return $server;
  }

  /**
   * {@inheritdoc}
   */
  public function save(Server $server) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    if ($server->getId()) {
      // @index primary key
      $server_entry = $this->entityManager
        ->getRepository(self::ENTITY_NAME)
        ->find($server->getId());
    } else {
      $server_entry = new ServerStoreEntry();
    }
    $server_entry->setHostname($server->getHostname());
    $server_entry->setTotalThreads($server->getTotalThreads());
    $server_entry->setStatus($server->getStatus());

    try {
      $this->entityManager->persist($server_entry);
      $this->entityManager->flush();
    } catch (\Doctrine\DBAL\DBALException $e) {
      $server_check = $this->getServerByHostname($server->getHostname());
      if ($server_check && (!$server_entry->getId() || $server_check->getId() != $server_entry->getId())) {
        throw new ServerStoreSaveException($e->getMessage());
      } else {
        throw $e;
      }
    }

    if (!$server->getId()) {
      $server->setId($server_entry->getId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($id) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    // @index primary key
    $server_entry = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->find($id);
    return $server_entry ? $this->convert($server_entry) : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getServerByHostname($hostname) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    // @index unique key
    $server_entry = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->findOneBy(array('hostname' => $hostname));
    return $server_entry ? $this->convert($server_entry) : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveServers() {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    $servers = array();
    // @index status_idx
    $server_entries = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->findBy(array('status' => ServerStatus::AVAILABLE));
    foreach ($server_entries as $server_entry) {
      try {
        $server = $this->convert($server_entry);
        $servers[$server->getHostname()] = $server;
      } catch (\Exception $e) {
        // This will happen if there is an entry in the server_store with 0
        // threads. Ignore.
      }
    }
    return $servers;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllServers() {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    $servers = array();
    // @index none, table scan
    $server_entries = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->findBy(array());
    foreach ($server_entries as $server_entry) {
      $server = $this->convert($server_entry);
      $servers[$server->getHostname()] = $server;
    }
    return $servers;
  }

  /**
   * {@inheritdoc}
   */
  public function remove(Server $server) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    // @index primary key
    $server_entry = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->find($server->getId());
    if ($server_entry) {
      $this->entityManager->remove($server_entry);
      $this->entityManager->flush();
    }
  }

  /**
   * Gets the ServerStore instance.
   *
   * @param DependencyManagerInterface $dependency_manager
   *   Optional. The DependencyManager instance.
   *
   * @return ServerStoreInterface
   *   The ServerStoreInterface instance.
   *
   * @throws \Acquia\Wip\Exception\DependencyMissingException
   *   If a DependencyManager instance is provided but the ServerStore has not
   *   been set as a dependency.
   */
  public static function getServerStore(DependencyManagerInterface $dependency_manager = NULL) {
    $result = NULL;
    if (NULL !== $dependency_manager) {
      $result = $dependency_manager->getDependency(self::RESOURCE_NAME);
    } else {
      try {
        $result = WipFactory::getObject(self::RESOURCE_NAME);
      } catch (\Exception $e) {
        // Fall back to a new instance.
        $result = new self();
      }
    }
    return $result;
  }

}
