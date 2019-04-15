<?php

namespace Acquia\Wip\Storage;

use Acquia\Wip\DependencyManagerInterface;
use Acquia\Wip\Exception\ServerStoreSaveException;
use Acquia\Wip\Runtime\Server;
use Acquia\Wip\ServerStatus;
use Acquia\Wip\WipFactory;

/**
 * Provides a base class to test Server storage.
 *
 * @copydetails ServerStoreInterface
 */
class BasicServerStore implements ServerStoreInterface {

  const RESOURCE_NAME = 'acquia.wip.storage.server';

  private $servers = array();

  /**
   * Implements an "autoincrement" ID.
   *
   * @var int
   */
  private $id = 1;

  /**
   * Resets the basic implementation's storage.
   */
  public function initialize() {
    $this->servers = array();
    $this->id = 1;
  }

  /**
   * {@inheritdoc}
   */
  public function save(Server $server) {
    if (!$server->getId()) {
      $server->setId($this->id++);
    }
    foreach ($this->servers as $server_check) {
      if ($server_check->getHostname() == $server->getHostname() && $server->getId() != $server_check->getId()) {
        throw new ServerStoreSaveException('There is already a server with the specified hostname.');
      }
    }
    $this->servers[$server->getId()] = $server;
  }

  /**
   * {@inheritdoc}
   */
  public function get($id) {
    $server = FALSE;
    foreach ($this->servers as $server_check) {
      if ($id == $server_check->getId()) {
        $server = $server_check;
      }
    }
    return $server;
  }

  /**
   * {@inheritdoc}
   */
  public function getServerByHostname($hostname) {
    $server = FALSE;
    foreach ($this->servers as $server_check) {
      if ($server_check->getHostname() == $hostname) {
        $server = $server_check;
        break;
      }
    }
    return $server;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveServers() {
    $servers = array();
    foreach ($this->servers as $server) {
      if ($server->getStatus() == ServerStatus::AVAILABLE) {
        $servers[$server->getHostname()] = $server;
      }
    }
    return $servers;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllServers() {
    $servers = array();
    foreach ($this->servers as $server) {
      $servers[$server->getHostname()] = $server;
    }
    return $servers;
  }

  /**
   * {@inheritdoc}
   */
  public function remove(Server $server) {
    unset($this->servers[$server->getId()]);
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
        // Fall back to a new instance of ServerStore.
        $result = new self();
      }
    }
    return $result;
  }

}
