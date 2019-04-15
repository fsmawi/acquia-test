<?php

namespace Acquia\Wip\Runtime;

use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\DependencyManager;
use Acquia\Wip\Exception\NoTaskException;
use Acquia\Wip\Exception\RowLockException;
use Acquia\Wip\Implementation\IteratorResult;
use Acquia\Wip\IteratorResultInterface;
use Acquia\Wip\IteratorStatus;
use Acquia\Wip\Lock\WipPoolRowLock;
use Acquia\Wip\StateTableIteratorInterface;
use Acquia\Wip\Storage\WipPoolStoreInterface;
use Acquia\Wip\Storage\WipStoreInterface;
use Acquia\Wip\Task;
use Acquia\Wip\TaskExitStatus;
use Acquia\Wip\TaskInterface;
use Acquia\Wip\TaskStatus;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipInterface;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipLogLevel;

/**
 * A remote worker process wrapper that can run some steps of a given WIP.
 */
class WipWorker implements DependencyManagedInterface {

  /**
   * The dependency manager.
   *
   * @var DependencyManager
   */
  private $dependencyManager;

  /**
   * The WipLog.
   *
   * @var WipLogInterface
   */
  private $wipLog = NULL;

  /**
   * The task ID associated with this worker.
   *
   * @var int
   */
  private $taskId;

  /**
   * The state table iterator.
   *
   * @var StateTableIteratorInterface
   */
  private $iterator;

  /**
   * Instantiates a new WipWorker.
   */
  public function __construct() {
    $this->dependencyManager = new DependencyManager();
    $this->dependencyManager->addDependencies($this->getDependencies());
  }

  /**
   * Implements DependencyManagedInterface::getDependencies().
   */
  public function getDependencies() {
    return array(
      'acquia.wip.storage.thread' => 'Acquia\Wip\Storage\ThreadStoreInterface',
      'acquia.wip.storage.wippool' => 'Acquia\Wip\Storage\WipPoolStoreInterface',
      'acquia.wip.storage.wip' => 'Acquia\Wip\Storage\WipStoreInterface',
      'acquia.wip.wiplog' => 'Acquia\Wip\WipLogInterface',
      'acquia.wip.pool' => 'Acquia\Wip\Runtime\WipPool',
      'acquia.wip.lock.rowlock.wippool' => 'Acquia\Wip\Lock\RowLockInterface',
    );
  }

  /**
   * Gets the dependency manager instance used by this WipWorker.
   *
   * @return DependencyManager
   *   The DependencyManager instance.
   */
  public function getDependencyManager() {
    return $this->dependencyManager;
  }

  /**
   * Sets the task ID this worker will execute.
   *
   * @param int $task_id
   *   The task ID.
   */
  public function setTaskId($task_id) {
    if (!is_int($task_id)) {
      throw new \InvalidArgumentException('The "task_id" parameter must be an integer.');
    }
    $this->taskId = $task_id;
  }

  /**
   * Gets the ID of the task associated with this worker.
   *
   * @return int
   *   The task ID.
   */
  public function getTaskId() {
    return $this->taskId;
  }

  /**
   * Sets the iterator.
   *
   * @param StateTableIteratorInterface $iterator
   *   The iterator.
   */
  private function setIterator(StateTableIteratorInterface $iterator) {
    $this->iterator = $iterator;
  }

  /**
   * Gets the iterator.
   *
   * @return StateTableIteratorInterface
   *   The iterator.
   */
  private function getIterator() {
    return $this->iterator;
  }

  /**
   * Gets the task associated with this worker.
   *
   * @return Task
   *   The Wip task.
   *
   * @throws NoTaskException
   *   If the task does not exist.
   * @throws \Acquia\Wip\Exception\DependencyMissingException
   *   If there are missing dependencies.
   * @throws \Acquia\Wip\Exception\NoObjectException
   *   If there is no corresponding object in the WipStore.
   * @throws \Acquia\Wip\Exception\TaskOverwriteException
   */
  public function getTask() {
    /** @var WipPoolStoreInterface $pool_store */
    $pool_store = $this->dependencyManager->getDependency('acquia.wip.storage.wippool');

    $result = $pool_store->get($this->getTaskId());
    if (empty($result)) {
      throw new NoTaskException('The task does not exist.');
    }
    $iterator = $this->getIterator();
    if (empty($iterator)) {
      $result->loadWipIterator();
      $this->setIterator($result->getWipIterator());
    } else {
      $result->setWipIterator($iterator);
    }
    return $result;
  }

  /**
   * Executes one or more steps of a WIP state table.
   *
   * Usually this will be time-limited, so this method will execute a number of
   * state methods up to the given time limit. The time limit is part of the WIP
   * task's configuration, so need not be passed in here.
   *
   * @TODO Break this method into several smaller methods.
   */
  public function process() {
    $task_id = $this->getTaskId();
    if ($task_id === NULL) {
      throw new NoTaskException('The task ID has not been set.');
    }
    if ($this->getTask()->getStatus() === TaskStatus::COMPLETE) {
      $message = sprintf("Trying to process task %d though it is already complete\n", $task_id);
      $this->getWipLog()->log(WipLogLevel::FATAL, $message);
      return NULL;
    }
    /** @var TaskInterface $task */
    // It is ok to throw an exception here if the lock cannot be acquired.
    $task = WipPoolRowLock::getWipPoolRowLock($task_id, NULL, $this->getDependencyManager())
      ->setTimeout(30)
      ->runAtomic($this, 'updateProcessingInformation');
    if (NULL === $task) {
      throw new \Exception(sprintf('Failed to execute "updateProcessingInformation" on task %d.', $this->getTaskId()));
    }
    $task->getWipIterator()->setWipLog($this->getWipLog());
    $iterator = $task->getWipIterator();
    $wip = $iterator->getWip();

    $wip->onDeserialize();
    if ($task->isTerminating()) {
      WipPoolRowLock::getWipPoolRowLock($task_id, NULL, $this->getDependencyManager())
        ->setTimeout(30)
        ->runAtomic($this, 'terminateAtomic');
      // Fake the iterator status.
      $result = new IteratorResult(0, FALSE, new IteratorStatus(), IteratorStatus::TERMINATED);
      return $result;
    }
    if ($iterator->needsUpdate()) {
      try {
        $this->getWipLog()->log(WipLogLevel::ALERT, 'Wip update required.', $this->getTaskId());
        $iterator->update();
        $this->getWipLog()->log(WipLogLevel::ALERT, 'Wip update completed.', $this->getTaskId());
        // Serialize the update.
        $result = new IteratorResult(0, FALSE, new IteratorStatus(), $task->getExitMessage());
        $arguments = array($result, $wip, $iterator);
        WipPoolRowLock::getWipPoolRowLock($task_id, NULL, $this->getDependencyManager())
          ->setTimeout(30)
          ->runAtomic($this, 'processNotComplete', $arguments);
      } catch (\Exception $e) {
        // The update failed. We have to fail out the Wip object.
        $internal_message = sprintf("Wip update failed: %s\n%s", $e->getMessage(), $e->getTraceAsString());
        $external_message = sprintf("Failed to complete task due to a system error.");
        $this->getWipLog()->log(WipLogLevel::FATAL, $internal_message);
        $result = new IteratorResult(0, TRUE, new IteratorStatus(IteratorStatus::ERROR_SYSTEM), $external_message);
        $arguments = array($result, $wip, $iterator);
        WipPoolRowLock::getWipPoolRowLock($task_id, NULL, $this->getDependencyManager())
          ->setTimeout(30)
          ->runAtomic($this, 'processComplete', $arguments);
      }
      return $result;
    }

    // Indicate the task is in_process.
    if (!empty($wip)) {
      $wip->onProcess();
    }

    $timeout = $task->getTimeout();

    $start = time();
    // @todo - gardener implementation also considers pauses, so that needs adding.
    // We no longer need to consider that the daemon was killed, as the proc will die before long anyway.
    do {
      $result = $iterator->moveToNextState();

      $wait = $result->getWaitTime();
      $remaining_seconds = $timeout - (time() - $start);
      if ($wait > 0) {
        // Allow other tasks to execute while this one waits for an
        // asynchronous workload to complete.
        break;
      }
    } while (!$result->isComplete() && $remaining_seconds > 0);

    // Get a lock and process the task.
    $method = $result->isComplete() ? 'processComplete' : 'processNotComplete';
    $arguments = array($result, $wip, $iterator);
    WipPoolRowLock::getWipPoolRowLock($task_id, NULL, $this->getDependencyManager())
      ->setTimeout(30)
      ->runAtomic($this, $method, $arguments);
    return $result;
  }

  /**
   * Terminate a task.
   *
   * @return TaskInterface
   *   The task.
   *
   * @throws NoTaskException
   *   If the task ID has not been set.
   * @throws RowLockException
   *   If the row lock could not be acquired.
   */
  public function terminate() {
    $task_id = $this->getTaskId();
    if ($task_id === NULL) {
      throw new NoTaskException('The task ID has not been set.');
    }

    if ($this->getTask()->getExitStatus() != TaskExitStatus::NOT_FINISHED) {
      throw new \RuntimeException(sprintf('Task id %d already completed and cannot be terminated.', $task_id));
    }

    $logger = $this->getWipLog();
    $logger->log(
      WipLogLevel::TRACE,
      sprintf('Received request to terminate task %s.', $task_id)
    );

    try {
      $task = WipPoolRowLock::getWipPoolRowLock($task_id, NULL, $this->getDependencyManager())
        ->setTimeout(30)
        ->runAtomic($this, 'terminateHelper', [$task_id]);
    } catch (RowLockException $e) {
      $logger->log(
        WipLogLevel::ERROR,
        sprintf('Failed to terminate task %s.\n%s', $task_id, $e->getMessage())
      );
      throw $e;
    }

    return $task;
  }

  /**
   * Marks a task as terminating and saves it.
   *
   * @param int $task_id
   *   The task ID to terminate.
   *
   * @return TaskInterface
   *   The task.
   *
   * @throws \Acquia\Wip\Exception\DependencyMissingException
   */
  public function terminateHelper($task_id) {
    /** @var TaskInterface $task */
    $task = NULL;
    $row_lock = WipPoolRowLock::getWipPoolRowLock($task_id, NULL, $this->getDependencyManager());
    if ($row_lock->hasLock()) {
      /** @var WipPoolStoreInterface $pool_store */
      $pool_store = $this->dependencyManager->getDependency('acquia.wip.storage.wippool');
      $task = $pool_store->get($task_id);
      $task->setIsTerminating(TRUE);
      $pool_store->save($task);
      $this->getWipLog()->log(
        WipLogLevel::ALERT,
        sprintf('Task %s has been marked to be terminated.', $task_id)
      );
    } else {
      $message = sprintf('Failed to lock wip_pool: %s before calling %s', $task_id, __METHOD__);
      $this->getWipLog()->log(WipLogLevel::FATAL, $message, 0);
    }

    return $task;
  }

  /**
   * Does the necessary operations to terminate a task.
   *
   * This is called via runAtomic and should not be called directly.
   *
   * @return Task
   *   The task object.
   */
  public function terminateAtomic() {
    $task = $this->getTask();
    // The task is not complete so terminate it.
    if ($task->getStatus() !== TaskStatus::COMPLETE) {
      $task->getWipIterator()->setWipLog($this->getWipLog());
      $iterator = $task->getWipIterator();
      $wip = $iterator->getWip();
      // We want to move the task to terminated so fake the result.
      $result = new IteratorResult(0, TRUE, new IteratorStatus(IteratorStatus::TERMINATED));
      // Terminations can be treated as if processing is complete as callCompleteLifecycleMethod
      // handles the transitions for us given the correct IteratorResult object.
      $this->processComplete($result, $wip, $iterator);
      // Reload the task after processing is complete.
      $task = $this->getTask();
      return $task;
    } else {
      return $task;
    }
  }

  /**
   * Does the necessary operations when starting to process a task.
   *
   * This is called via runAtomic and should not be called directly.
   *
   * @return TaskInterface $task
   *   The task instance.
   */
  public function updateProcessingInformation() {
    $task = $this->getTask();
    $row_lock = WipPoolRowLock::getWipPoolRowLock($task->getId(), NULL, $this->getDependencyManager());
    if ($row_lock->hasLock()) {
      // If this task has not yet been started, start it now.
      if ($task->getStartTimestamp() == 0) {
        $task->setStartTimestamp(time());
        $iterator = $task->getWipIterator();
        $wip = $iterator->getWip();
        if (!empty($wip)) {
          $iterator->compileStateTable();
          $wip->onStart();
          $wip->onStatusChange($task);
        }
      }

      // Update the task status in the pool and sync back to storage.
      $task->setStatus(TaskStatus::PROCESSING);
      $task->setWakeTimestamp(0);
      /** @var WipPoolStoreInterface $pool_store */
      $pool_store = $this->dependencyManager->getDependency('acquia.wip.storage.wippool');
      $pool_store->save($task);
      return $task;
    } else {
      $message = sprintf('Failed to lock wip_pool: %s before calling %s', $task->getId(), __METHOD__);
      $this->getWipLog()->log(WipLogLevel::FATAL, $message, 0);
      return NULL;
    }
  }

  /**
   * Update the task data in the WIP pool.
   *
   * @param TaskInterface $task
   *   The task.
   * @param StateTableIteratorInterface $iterator
   *   The iterator.
   */
  protected function updateData(TaskInterface $task, StateTableIteratorInterface $iterator) {
    $row_lock = WipPoolRowLock::getWipPoolRowLock($task->getId(), NULL, $this->getDependencyManager());
    if ($row_lock->hasLock()) {
      /** @var WipStoreInterface $object_store */
      $object_store = $this->dependencyManager->getDependency('acquia.wip.storage.wip');
      $object_store->save($this->getTaskId(), $iterator);
      // Store the task in the WIP pool.
      /** @var WipPoolStoreInterface $pool_store */
      $pool_store = $this->dependencyManager->getDependency('acquia.wip.storage.wippool');
      $task->setClaimedTimestamp(Task::NOT_CLAIMED);
      $pool_store->save($task);
    } else {
      $message = sprintf('Failed to lock wip_pool: %s before calling %s', $task->getId(), __METHOD__);
      $this->getWipLog()->log(WipLogLevel::FATAL, $message, 0);
    }
  }

  /**
   * Does the necessary operations when a task is complete.
   *
   * This is called via runAtomic and should not be called directly.
   *
   * @param IteratorResultInterface $result
   *   The results.
   * @param WipInterface $wip
   *   The WIP.
   * @param StateTableIteratorInterface $iterator
   *   The iterator.
   */
  public function processComplete(
    IteratorResultInterface $result,
    WipInterface $wip,
    StateTableIteratorInterface $iterator
  ) {
    $task_id = $this->getTaskId();
    $task = $this->getTask();
    $this->callCompleteLifecycleMethod($task, $result, $wip);
    $task->setStatus(TaskStatus::COMPLETE);
    $task->setExitStatus(TaskExitStatus::fromIteratorStatus($result->getStatus()));
    $task->setCompletedTimestamp(time());
    // Set the exit message. If the result does not have a message, try
    // finding one from the iterator or the wip object itself.
    $exit_message = $result->getMessage();
    if (empty($exit_message)) {
      if (!is_null($wip->getExitMessage())) {
        $exit_message = $wip->getExitMessage()->getExitMessage();
      }
      if (empty($exit_message)) {
        $exit_message = $wip->getIterator()->getExitMessage();
      }
    }
    $task->setExitMessage(empty($exit_message) ? '' : $exit_message);
    $wip->onStatusChange($task);
    if (WipFactory::getBool('$acquia.wip.wiplog.prune', TRUE)) {
      // Prune the logs to save space since the operation was successful.
      if (!empty($task_id) && is_int($task_id) && $result->getStatus()->getValue() === IteratorStatus::OK) {
        $this->getWipLog()->getStore()->prune($task_id, $this->getMinimumLogLevel($wip));
      }
    }
    $this->updateData($task, $iterator);
  }

  /**
   * Does the necessary operations when a task is not complete.
   *
   * This is called via runAtomic and should not be called directly.
   *
   * @param IteratorResultInterface $result
   *   The results.
   * @param WipInterface $wip
   *   The WIP.
   * @param StateTableIteratorInterface $iterator
   *   The iterator.
   */
  public function processNotComplete(
    IteratorResultInterface $result,
    WipInterface $wip,
    StateTableIteratorInterface $iterator
  ) {
    $task = $this->getTask();
    $task->setStatus(TaskStatus::WAITING);
    // If the timestamp is still 0 no other process updated the value as per
    // normal.
    if ($task->getWakeTimestamp() == 0) {
      $task->setWakeTimestamp(time() + $result->getWaitTime());
    }
    $wip->onWait();
    $this->updateData($task, $iterator);
  }

  /**
   * Performs any cleanup tasks for completion of the thread in general.
   *
   * @param IteratorResultInterface $iterator_result
   *   Optional.  If the result indicates success, the thread row will be
   *   removed.  Otherwise the thread row will remain, but indicate the thread
   *   was completed.
   *
   * @throws NoTaskException
   *   If no task was loaded in the WipWorker object when this is called.
   * @throws RowLockException
   *   If the row lock could not be acquired.
   */
  public function complete(IteratorResultInterface $iterator_result = NULL) {
    $task_id = $this->getTaskId();
    if ($task_id === NULL) {
      $message = '  Ensure that a task is set into WipWorker before calling complete().';
      throw new NoTaskException($message);
    }
    WipPoolRowLock::getWipPoolRowLock($task_id, NULL, $this->getDependencyManager())
      ->setTimeout(30)
      ->runAtomic($this, 'atomicComplete', [$task_id]);
  }

  /**
   * Performs any cleanup tasks for completion of the thread.
   *
   * This method must be called only when the wip_pool row lock has been
   * acquired.
   *
   * @param int $task_id
   *   The task ID.
   *
   * @throws NoTaskException
   *   If the task ID has not been set.
   * @throws RowLockException
   *   If the wip_pool row update lock has not been acquired.
   */
  public function atomicComplete($task_id) {
    if ($task_id === NULL) {
      $message = 'No task ID was set. Ensure that a task is set into WipWorker before calling complete().';
      throw new NoTaskException($message);
    }
    $row_lock = WipPoolRowLock::getWipPoolRowLock($task_id, NULL, $this->getDependencyManager());
    if ($row_lock->hasLock()) {
      $task = $this->getTask();
      try {
        /** @var WipPoolInterface $wip_pool */
        $wip_pool = $this->dependencyManager->getDependency('acquia.wip.pool');
        $wip_pool->stopProgress($task);
      } catch (\Exception $e) {
        $this->getWipLog()->log(
          WipLogLevel::FATAL,
          sprintf(
            "Unexpected exception encountered while stopping progress on task %d:\n%s",
            $task->getId(),
            $e->getMessage()
          ),
          $task->getId()
        );
      }
    } else {
      $message = sprintf('Failed to lock the wip_pool row for task %s before calling %s', $task_id, __METHOD__);
      throw new RowLockException($message);
    }
  }

  /**
   * Calls the lifecycle method associated with the completion status.
   *
   * @param TaskInterface $task
   *   The task.
   * @param IteratorResultInterface $iterator_result
   *   The IteratorResultInterface instance that indicates what the completion
   *   status is.
   * @param WipInterface $wip
   *   The Wip associated with the given task.
   */
  protected function callCompleteLifecycleMethod(
    TaskInterface $task,
    IteratorResultInterface $iterator_result,
    WipInterface $wip
  ) {
    if ($iterator_result->isComplete()) {
      /** @var WipInterface $wip */
      if (!empty($wip)) {
        $wip->onStatusChange($task);
        switch ($iterator_result->getStatus()->getValue()) {
          case IteratorStatus::OK:
          case IteratorStatus::WARNING:
            $wip->onFinish();
            break;

          case IteratorStatus::ERROR_USER:
            $wip->onUserError();
            break;

          case IteratorStatus::ERROR_SYSTEM:
            $wip->onSystemError();
            break;

          case IteratorStatus::TERMINATED:
            $wip->onTerminate();
            break;

          default:
            $format = 'Unable to match complete lifecycle method to Iterator status %d';
            $error_message = sprintf($format, $iterator_result->getStatus()->getValue());
            $task->getWipIterator()->getWipLog()->log(WipLogLevel::ERROR, $error_message);
        }
      }
    }
  }

  /**
   * Returns the WipLog associated with this WipWorker instance.
   *
   * @return WipLogInterface
   *   The WipLog instance.
   *
   * @throws \Acquia\Wip\Exception\DependencyMissingException
   *   If the 'acquia.wip.wiplog' dependency is missing.
   */
  public function getWipLog() {
    if (NULL === $this->wipLog) {
      $this->wipLog = $this->dependencyManager->getDependency('acquia.wip.wiplog');
    }
    return $this->wipLog;
  }

  /**
   * Gets the log level requested by the associated Wip object.
   *
   * The minimum log level refers to the importance of the message, with
   * WipLogLevel::FATAL being the highest log level, and WipLogLevel::TRACE
   * being the lowest.
   *
   * @param WipInterface $wip
   *   The Wip object.
   *
   * @return int
   *   The log level.
   */
  private function getMinimumLogLevel(WipInterface $wip) {
    return $wip->getLogLevel();
  }

}
