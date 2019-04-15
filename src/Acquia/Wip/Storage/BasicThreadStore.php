<?php

namespace Acquia\Wip\Storage;

use Acquia\Wip\Exception\NoTaskException;
use Acquia\Wip\Exception\NoThreadException;
use Acquia\Wip\Runtime\Server;
use Acquia\Wip\Runtime\Thread;
use Acquia\Wip\TaskInterface;
use Acquia\Wip\ThreadStatus;

/**
 * Provides a base class to test Thread storage.
 *
 * @copydetails ThreadStoreInterface
 */
class BasicThreadStore implements ThreadStoreInterface {

  /**
   * The threads being stored.
   *
   * @var Thread[]
   */
  private $threads = array();

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
    $this->threads = array();
    $this->id = 1;
  }

  /**
   * {@inheritdoc}
   */
  public function save(Thread $thread) {
    if (!$thread->getServerId()) {
      throw new \InvalidArgumentException('The thread is missing a valid server.');
    }
    if (!$thread->getWipId()) {
      throw new \InvalidArgumentException('The thread is missing a valid Wip ID.');
    }
    if (!$thread->getId()) {
      $thread->setId($this->id++);
    }
    $this->threads[$thread->getId()] = clone $thread;
    return $thread;
  }

  /**
   * {@inheritdoc}
   */
  public function get($id) {
    $result = NULL;
    foreach ($this->threads as $thread) {
      if ($thread->getId() == $id) {
        $result = $thread;
        break;
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveThreads(Server $server = NULL) {
    $threads = array();
    foreach ($this->threads as $thread) {
      if ($this->isInProgress($thread)) {
        if (!isset($server)) {
          $threads[] = $thread;
        } elseif ($thread->getServerId() == $server->getId()) {
          $threads[] = $thread;
        }
      }
    }
    return $threads;
  }

  /**
   * {@inheritdoc}
   */
  public function getRunningThreads($server_ids = array()) {
    $threads = array();
    foreach ($this->threads as $thread) {
      if ($thread->getStatus() === ThreadStatus::RUNNING) {
        if (!isset($server)) {
          $threads[] = $thread;
        } elseif (in_array($thread->getServerId(), $server_ids)) {
          $threads[] = $thread;
        }
      }
    }
    return $threads;
  }

  /**
   * {@inheritdoc}
   */
  public function remove(Thread $thread_to_remove) {
    foreach ($this->threads as $key => $thread) {
      if ($thread_to_remove->getId() == $thread->getId()) {
        unset($this->threads[$key]);
        break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function pruneObjects(array $object_ids) {
    foreach ($object_ids as $object_id) {
      unset($this->threads[$object_id]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getThreadByTask(TaskInterface $task) {
    if (is_null($task->getId())) {
      $message = 'Unable to locate a thread for an empty Task ID. The provided task is either empty, or was not yet saved in the database.';
      throw new NoTaskException($message);
    }

    foreach ($this->threads as $thread) {
      if ($thread->getWipId() == $task->getId() && $this->isInProgress($thread)) {
        return $thread;
      }
    }

    throw new NoThreadException(sprintf('No thread found for task %d', $task->getId()));
  }

  /**
   * Determines if a thread is actively processing.
   *
   * @param Thread $thread
   *   The thread whose status to check.
   *
   * @return bool
   *   TRUE if the passed thread is in some active status.
   */
  private function isInProgress(Thread $thread) {
    static $values = array(
      ThreadStatus::RUNNING => TRUE,
      ThreadStatus::RESERVED => TRUE,
    );
    return isset($values[$thread->getStatus()]);
  }

  /**
   * Gets the Wip Ids of running threads.
   *
   * @return array
   *   A list of Wip Ids from running threads.
   */
  public function getRunningWids() {
    $result = array();
    foreach ($this->threads as $thread) {
      if ($thread->getStatus() === ThreadStatus::RUNNING) {
        $result[] = $thread;
      }
    }
    return $result;
  }

}
