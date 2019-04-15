<?php

namespace Acquia\Wip\Storage;

use Acquia\Wip\Runtime\Server;

/**
 * The ServerStoreInterface provides CRUD interface for Server data storage.
 *
 * A server is defined by its hostname and it has a property to define how many
 * threads may run on it concurrently.
 */
interface ServerStoreInterface {

  /**
   * Saves the Server data.
   *
   * @param Server $server
   *   The server object to be saved.
   */
  public function save(Server $server);

  /**
   * Gets the server with the specified id.
   *
   * @param int $id
   *   The id of the server to be found.
   *
   * @return Server
   *   The Server object or FALSE if it's not found.
   */
  public function get($id);

  /**
   * Gets the server with the specified given hostname.
   *
   * @param string $hostname
   *   The hostname of the server to be found.
   *
   * @return Server
   *   The Server object or FALSE if it's not found.
   */
  public function getServerByHostname($hostname);

  /**
   * Returns all the active server objects known to the system.
   *
   * @return Server[]
   *   An array of server objects, keyed by id.
   */
  public function getActiveServers();

  /**
   * Returns all the server objects known to the system.
   *
   * @return Server[]
   *   An array of server objects, keyed by id.
   */
  public function getAllServers();

  /**
   * Removes the server data with the specified given hostname.
   *
   * @param Server $server
   *   The server to be removed.
   */
  public function remove(Server $server);

}
