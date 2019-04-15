<?php

namespace Acquia\Wip\Runtime;

use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\DependencyManager;
use Acquia\Wip\Environment;
use Acquia\Wip\Exception\ThreadIncompleteException;
use Acquia\Wip\Exception\ThreadOverwriteException;
use Acquia\Wip\Notification\NotificationInterface;
use Acquia\Wip\Notification\NotificationSeverity;
use Acquia\Wip\Ssh\SshInterface;
use Acquia\Wip\Ssh\SshProcess;
use Acquia\Wip\Ssh\SshProcessInterface;
use Acquia\Wip\Storage\ThreadStoreInterface;
use Acquia\Wip\Storage\WipStoreInterface;
use Acquia\Wip\TaskInterface;
use Acquia\Wip\ThreadStatus;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogLevel;

/**
 * Represents a thread of execution.
 *
 * A thread is a process tied to a specific server.
 */
class Thread implements DependencyManagedInterface {

  /**
   * Flag to indicate that a thread should dispatch locally via exec.
   */
  const WIP_WORKER_EXEC_LOCAL = 'local';

  /**
   * Indicates that a thread should dispatch over SSH.
   */
  const WIP_WORKER_EXEC_SSH = 'ssh';

  /**
   * The thread ID.
   *
   * @var int
   */
  private $id;

  /**
   * The server's ID associated with this thread.
   *
   * @var int
   */
  private $serverId;

  /**
   * The Wip object ID assigned to this thread for processing.
   *
   * @var int
   */
  private $wipId;

  /**
   * The process ID associated with this thread.
   *
   * @var int
   */
  private $pid = 0;

  /**
   * The SSH Process object currently running this thread.
   *
   * @var SshProcess
   */
  private $process;

  /**
   * The Unix timestamp when this thread started.
   *
   * @var int
   */
  private $created;

  /**
   * The Unix timestamp when this thread finished.
   *
   * @var int
   */
  private $completed = 0;

  /**
   * The thread status.
   *
   * @var int
   */
  private $status = ThreadStatus::RESERVED;

  /**
   * Output from the ssh command.
   *
   * @var string
   */
  private $sshOutput = '';

  /**
   * A directory prefix to the configured Ssh command endpoint.
   *
   * @var string
   */
  private $directoryPrefix = '';

  /**
   * The DependencyManager instance responsible for verifying dependencies.
   *
   * @var DependencyManager
   */
  public $dependencyManager;

  /**
   * Instantiates a new Thread.
   */
  public function __construct() {
    $this->dependencyManager = new DependencyManager();
    $this->dependencyManager->addDependencies($this->getDependencies());
    $this->setCreated(time());
  }

  /**
   * Implements DependencyManagedInterface::getDependencies().
   */
  public function getDependencies() {
    return array(
      'acquia.wip.ssh.client'        => 'Acquia\Wip\Ssh\SshInterface',
      'acquia.wip.storage.thread'    => 'Acquia\Wip\Storage\ThreadStoreInterface',
      'acquia.wip.storage.server'    => 'Acquia\Wip\Storage\ServerStoreInterface',
      'acquia.wip.wiplog'            => 'Acquia\Wip\WipLogInterface',
      'acquia.wip.ssh_service.local' => 'Acquia\Wip\Ssh\SshServiceInterface',
    );
  }

  /**
   * Sends a task to this thread for processing.
   *
   * @param TaskInterface $task
   *   The task object to process (typically on a remote server).
   *
   * @throws \Acquia\Wip\Exception\DependencyMissingException
   *   If a missing dependency will cause this method to fail.
   * @throws \Acquia\Wip\Exception\ThreadIncompleteException
   *   If this Thread instance has not been fully configured.
   */
  public function dispatch(TaskInterface $task) {
    if (!$this->getServerId()) {
      $message = 'The thread needs an associated server to be able to dispatch a task.';
      throw new ThreadIncompleteException($message);
    }
    /** @var \Acquia\Wip\Storage\ServerStoreInterface $server_store */
    $server_store = $this->dependencyManager->getDependency('acquia.wip.storage.server');
    $server = $server_store->get($this->getServerId());
    if (!$server) {
      $message = 'The thread needs an associated server to be able to dispatch a task.';
      throw new ThreadIncompleteException($message);
    }

    $env = $this->getEnvironment($server);
    /** @var SshInterface $ssh */
    $ssh = $this->dependencyManager->getDependency('acquia.wip.ssh.client');
    $ssh->setLogLevel(WipLogLevel::TRACE);
    $wiplog = $this->dependencyManager->getDependency('acquia.wip.wiplog');
    $ssh->initialize($env, sprintf('INTERNAL: WIP thread dispatch for task %d.', $task->getId()), $wiplog, 0);
    if (WipFactory::getObject('$acquia.wip.worker_exec_method') === Thread::WIP_WORKER_EXEC_LOCAL) {
      $ssh_service = $this->dependencyManager->getDependency('acquia.wip.ssh_service.local');
      $ssh->setSshService($ssh_service);
    }

    $ssh->setLogLevel(WipLogLevel::TRACE);
    $ssh->getSshService($env)->setKeyPath(WipFactory::getObject('$acquia.wip.service.private_key_path'));

    // Exec async, don't store the PID because the thread will talk to the DB
    // directly.
    $ssh->setCommand($this->generateCommand($task));

    /** @var ThreadStoreInterface $thread_store */
    $thread_store = $this->dependencyManager->getDependency('acquia.wip.storage.thread');
    $thread_id = $this->getId();
    if (!empty($thread_id)) {
      $thread = $thread_store->get($thread_id);
    }
    if (empty($thread)) {
      $thread = $this;
    }

    // Request a callback with information that makes it possible to evaluate
    // whether the dispatch was successful.
    $process = $ssh->execAsync(
      '--no-logs',
      $this->getDispatchCallData($task->getId(), $thread_id)
    );

    try {
      $thread->setPid($process->getPid());
    } catch (\Exception $e) {
      // Ignore.
    }

    $thread->setProcess($process);
    $thread_store->save($thread);
  }

  /**
   * Gets the signal call data for system SSH calls.
   *
   * @param int $task_id
   *   The task ID.
   * @param int $thread_id
   *   The thread ID.
   *
   * @return object
   *   The object used for system signals.
   */
  private function getDispatchCallData($task_id, $thread_id) {
    $data = new \stdClass();
    try {
      // Figure out if the certification should not be verified within the
      // ssh_wrapper script. This behavior is required in the vast majority
      // of our test environments because we use self-signed certificates.
      $verify = WipFactory::getBool('$acquia.wip.ssl.verifyCertificate', TRUE);
      if (!$verify) {
        $data->noVerifyCert = TRUE;
      }
    } catch (\Exception $e) {
      // Nothing to do here; the default is to always verify.
    }
    $data->report = TRUE;
    $data->threadId = $thread_id;
    $data->taskId = $task_id;

    // Add the timestamp from the wip_store table. This will indicate whether
    // the object has been updated or not. This is important because if the
    // process that executes the task fails during the processing of the task
    // but before the Wip object has been saved, it probably isn't possible to
    // clear the failure. In this case, any state that is not fully idempotent
    // will fail.
    /** @var WipStoreInterface $object_store */
    $object_store = $this->dependencyManager->getDependency('acquia.wip.storage.wip');
    $data->wipStoreTimestamp = $object_store->getTimestampByWipId($task_id);

    return $data;
  }

  /**
   * Obtains an environment to use for executing a WIP task.
   *
   * The Environment object returned here is somewhat fake: we need to force a
   * single server, so we don't provide the full list.
   *
   * @param Server $server
   *   The server object to add to the environment.
   *
   * @return Environment
   *   An Environment object that describes the WIP hosting environment itself.
   */
  private function getEnvironment(Server $server) {
    $env = Environment::getRuntimeEnvironment();
    $env->setServers(array($server->getHostname()));
    $env->setCurrentServer($server->getHostname());
    return $env;
  }

  /**
   * Produces a command string to run the WIP on a remote server.
   *
   * @param TaskInterface $task
   *   The task to dispatch on remote server.
   *
   * @return string
   *   A command string
   */
  private function generateCommand(TaskInterface $task) {
    // @todo - do we pass the timeout value here? or just allow that to be loaded from the Task on the other end?
    $ssh_endpoint = $this->getDirectoryPrefix() . '/' . WipFactory::getObject('$acquia.wip.exec.path');
    $config_path = escapeshellarg(WipFactory::getConfigPath());
    $command = sprintf(
      "$ssh_endpoint --id=%d --thread-id=%d --config=%s",
      $task->getId(),
      $this->getId(),
      $config_path
    );
    return $command;
  }

  /**
   * Gets the ID this Thread instance.
   *
   * @return int
   *   The thread's ID.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Sets the ID of this Thread instance.
   *
   * @param int $id
   *   The thread's ID.
   *
   * @throws ThreadOverwriteException
   */
  public function setId($id) {
    if (!is_int($id) || $id <= 0) {
      throw new \InvalidArgumentException('Invalid thread ID provided.');
    }
    if ($this->id) {
      throw new ThreadOverwriteException("The thread's ID has been already specified.");
    }
    $this->id = $id;
  }

  /**
   * Gets the server's ID associated with this Thread instance.
   *
   * @return int
   *   The server's ID.
   */
  public function getServerId() {
    return $this->serverId;
  }

  /**
   * Sets the server's ID associated with this Thread instance.
   *
   * @param int $id
   *   The server's ID.
   *
   * @throws \InvalidArgumentException
   *   If the argument is not a positive integer.
   * @throws ThreadOverwriteException
   *   If the server ID has already been set.
   */
  public function setServerId($id) {
    if (!is_int($id) || $id <= 0) {
      throw new \InvalidArgumentException('Invalid server ID provided.');
    }
    if ($this->serverId) {
      throw new ThreadOverwriteException("The thread's server ID has been already specified.");
    }
    $this->serverId = $id;
  }

  /**
   * Gets the Wip's ID associated with this Thread instance.
   *
   * @return int
   *   The Wip task's ID.
   */
  public function getWipId() {
    return $this->wipId;
  }

  /**
   * Sets the Wip's ID associated with this Thread instance.
   *
   * @param int $id
   *   The Wip task's ID.
   *
   * @throws \InvalidArgumentException
   *   If the argument is not a positive integer.
   * @throws ThreadOverwriteException
   *   If the Wip ID has already been set.
   */
  public function setWipId($id) {
    if (!is_int($id) || $id <= 0) {
      throw new \InvalidArgumentException('Invalid Wip task ID provided.');
    }
    if ($this->wipId && $this->wipId !== $id) {
      /** @var NotificationInterface $notifier */
      $notifier = $this->dependencyManager->getDependency('acquia.wip.notification');
      $message = sprintf('Attempt to reassign thread from Wip ID %d to %d.', $this->wipId, $id);
      $e = new ThreadOverwriteException($message);
      $notifier->notifyException($e, NotificationSeverity::ERROR);
      throw new ThreadOverwriteException($message);
    }
    $this->wipId = $id;
  }

  /**
   * Gets the process' ID associated with this Thread instance.
   *
   * @return int
   *   The process' ID.
   */
  public function getPid() {
    return $this->pid;
  }

  /**
   * Sets the process' ID associated with this Thread instance.
   *
   * @param int $id
   *   The process' ID.
   *
   * @throws \InvalidArgumentException
   *   If the argument is not a non-negative integer.
   * @throws ThreadOverwriteException
   *   If the process ID has already been set.
   */
  public function setPid($id) {
    if (!is_int($id) || $id < 0) {
      throw new \InvalidArgumentException('Invalid process ID provided.');
    }
    if ($this->pid) {
      throw new ThreadOverwriteException("The thread's process ID has been already specified.");
    }
    $this->pid = $id;
  }

  /**
   * Gets the UNIX timestamp when this Thread instance was created.
   *
   * @return int
   *   The creation timestamp.
   */
  public function getCreated() {
    return $this->created;
  }

  /**
   * Sets the UNIX timestamp when this Thread instance was created.
   *
   * @param int $timestamp
   *   The creation timestamp.
   *
   * @throws \InvalidArgumentException
   *   If the argument is not a positive integer.
   */
  public function setCreated($timestamp) {
    if (!is_int($timestamp) || $timestamp <= 0) {
      throw new \InvalidArgumentException('Invalid creation timestamp provided.');
    }
    $this->created = $timestamp;
  }

  /**
   * Gets the UNIX timestamp when this Thread instance was completed.
   *
   * @return int
   *   The completion timestamp.
   */
  public function getCompleted() {
    return $this->completed;
  }

  /**
   * Sets the UNIX timestamp when this Thread instance was completed.
   *
   * @param int $timestamp
   *   The completion timestamp.
   *
   * @throws \InvalidArgumentException
   *   If the argument is not a non-negative integer.
   */
  public function setCompleted($timestamp) {
    if (!is_int($timestamp) || $timestamp < 0) {
      throw new \InvalidArgumentException('Invalid completed timestamp provided.');
    }
    $this->completed = $timestamp;
  }

  /**
   * Gets the status of this Thread.
   *
   * The status defines the state of the Task. Can be one of:
   * ```` php
   * ThreadStatus::RESERVED
   * ThreadStatus::RUNNING
   * ThreadStatus::FINISHED
   * ````
   * The default status is ThreadStatus::RESERVED.
   *
   * @return int
   *   The Thread's status.
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * Sets the status of this Thread.
   *
   * @param int $status
   *   The Thread's status.
   *
   * @throws \InvalidArgumentException
   *   If the argument is not a valid status.
   */
  public function setStatus($status) {
    if (!ThreadStatus::isValid($status)) {
      throw new \InvalidArgumentException('Invalid thread status provided.');
    }
    $this->status = $status;
  }

  /**
   * Gets the ssh output of this Thread.
   *
   * @return string
   *   The Thread's ssh output.
   */
  public function getSshOutput() {
    return $this->sshOutput;
  }

  /**
   * Sets the ssh output of this Thread.
   *
   * @param string $ssh_output
   *   The Thread's ssh output.
   */
  public function setSshOutput($ssh_output) {
    if (!is_string($ssh_output)) {
      throw new \InvalidArgumentException('Invalid thread ssh output provided.');
    }
    $this->sshOutput = $ssh_output;
  }

  /**
   * Gets the directory prefix for dispatching commands.
   *
   * @return string
   *   The directory prefix.
   */
  public function getDirectoryPrefix() {
    return $this->directoryPrefix;
  }

  /**
   * Sets the directory prefix for dispatching commands.
   *
   * @param string $directory_prefix
   *   The directory prefix.
   */
  public function setDirectoryPrefix($directory_prefix) {
    $this->directoryPrefix = $directory_prefix;
  }

  /**
   * Gets the SSH process object.
   *
   * @return SshProcessInterface
   *   The SSH process object.
   */
  public function getProcess() {
    return $this->process;
  }

  /**
   * Sets the SSH process object.
   *
   * @param SshProcessInterface $process
   *   An instance of SshProcess.
   */
  public function setProcess(SshProcessInterface $process) {
    $this->process = $process;
  }

}
