<?php

namespace Acquia\Wip\Storage;

use Acquia\Wip\Exception\NoTaskException;
use Acquia\Wip\Exception\NoThreadException;
use Acquia\Wip\Runtime\Server;
use Acquia\Wip\Runtime\Thread;
use Acquia\Wip\TaskInterface;

/**
 * The ThreadStoreInterface provides CRUD interface for Thread data storage.
 *
 * The thread storage holds what threads are running on which servers allowing
 * the system to tell where to route the next task for processing.
 */
interface ThreadStoreInterface {

  /**
   * Saves thread data.
   *
   * @param Thread $thread
   *   The thread to be stored.
   */
  public function save(Thread $thread);

  /**
   * Returns a Thread corresponding to the given ID.
   *
   * @param int $id
   *   The ID to retrieve the Thread for.
   *
   * @return Thread
   *   The Thread with the given id.
   */
  public function get($id);

  /**
   * Fetches the running and reserved threads on a server.
   *
   * @param Server $server
   *   (Optional) A server object.  If provided, the list of threads will be
   *   filtered by only retaining threads on the given server.
   *
   * @return Thread[]
   *   Array of Thread objects.
   */
  public function getActiveThreads(Server $server = NULL);

  /**
   * Fetches the running threads on the given server IDs.
   *
   * @param int[] $server_ids
   *   The IDs of servers to include in the query.
   *
   * @return Thread[]
   *   Array of Thread objects.
   */
  public function getRunningThreads($server_ids = array());

  /**
   * Gets the Wip Ids of running threads.
   *
   * @return array
   *   A list of Wip Ids from running threads.
   */
  public function getRunningWids();

  /**
   * Removes a thread.
   *
   * @param Thread $thread
   *   The thread to be removed.
   */
  public function remove(Thread $thread);

  /**
   * Returns the Thread that is associated with processing a given Task.
   *
   * @param TaskInterface $task
   *   The given Task.
   *
   * @return Thread
   *   The active thread that corresponds to the given Task.
   *
   * @throws NoTaskException
   *   When the given task has no ID.
   * @throws NoThreadException
   *   When there is no thread in the store associated with the given task.
   */
  public function getThreadByTask(TaskInterface $task);

  /**
   * Deletes threads associated with specific objects.
   *
   * @param int[] $object_ids
   *   List of object ids.
   */
  public function pruneObjects(array $object_ids);

}
