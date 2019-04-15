<?php

namespace Acquia\WipService\Console\Commands;

use Acquia\WipIntegrations\DoctrineORM\StateStore;
use Acquia\WipService\App;
use Acquia\WipService\Console\WipConsoleEndlessCommand;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\LockInterface;
use Acquia\Wip\Notification\NotificationInterface;
use Acquia\Wip\Notification\NotificationSeverity;
use Acquia\Wip\State\MonitorDaemonPause;
use Acquia\Wip\Storage\StateStoreInterface;
use Acquia\Wip\WipLogLevel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Defines a console command for starting the thread pool daemon.
 */
class ThreadPoolDaemonCommand extends WipConsoleEndlessCommand {

  /**
   * The name of the thread pool daemon process.
   */
  const PROCESS_NAME = 'threadpooldaemon';

  /**
   * The name of the thread pool daemon lock.
   */
  const LOCK_NAME = 'threadpool.daemon';

  /**
   * The longest interval of time in seconds to wait for the lock.
   */
  const LOCK_INTERVAL = 10;

  /**
   * Stores the PID file location of this process.
   *
   * @var string
   */
  private $pidfile;

  /**
   * Stores the connection ID used by the DB for this process.
   *
   * @var int
   */
  private $dbConnectionId;

  /**
   * Indicates the daemon should stop gracefully ASAP.
   *
   * @var bool
   */
  private $quit = FALSE;

  /**
   * The output interface.
   *
   * @var OutputInterface
   */
  private $output;

  /**
   * The entity manager.
   *
   * @var EntityManagerInterface
   */
  private $entityManager;

  /**
   * The process instance.
   *
   * @var Process
   */
  private $process;

  /**
   * The StateStore instance.
   *
   * @var StateStoreInterface
   */
  private $stateStore = NULL;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $help = <<<EOT
This command starts a 'daemon' to process wip tasks. This is an internal command and should not be run directly instead
the daemon should be started via the monitor-daemon start command.
EOT;

    $this->setName('run-daemon')
      ->setDescription('Start the Wip Thread pool daemon.')
      ->setHelp($help)
      ->addOption(
        'guid',
        NULL,
        InputOption::VALUE_REQUIRED,
        'A GUID by which to identify this process.'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    if ($this->isPaused() === TRUE) {
      return;
    }

    $this->output($output, 'Starting WIP thread pool daemon.');
    $this->output($output, sprintf(
      'DB connection ID is %s. Use this ID in MySQL if needed to kill DB locks held by this process.',
      $this->dbConnectionId
    ));

    $daemon_runtime = $this->getDaemonRuntime();
    $timeout = time() + $daemon_runtime;
    $task_process_timeout = App::getApp()['config.global']['threadpool.timeout'];

    /** @var NotificationInterface $notifier */
    $notifier = $this->dependencyManager->getDependency('acquia.wip.notification');

    // This is the main loop of this process: it is intended to run "forever"
    // (actually, just a long while before re-invoking).  It attempts to gain a
    // global lock for permission to dispatch some WIP tasks.  If it fails, it
    // will check again 5 seconds later.  If it succeeds, it will spawn a child
    // process, which will do the actual work.  Having a single instance of this
    // process responsible for dispatching tasks reduces the overhead of locking
    // individual tasks for processing.
    while (!$this->quit && time() < $timeout && $this->isPaused() === FALSE) {
      if ($this->getLock()) {
        try {
          $wip_recovery = new Process(
            'exec ' . $this->getAppDirectory() . '/bin/wipctl recover',
            NULL,
            NULL,
            NULL,
            20
          );
          $wip_recovery->start();
          $this->output($output, sprintf(
            'Started WipRecovery process: %d',
            $wip_recovery->getPid()
          ));
        } catch (\Exception $e) {
          $this->output($output, sprintf(
            'Exception detected in WipRecovery process: %s',
            $e->getMessage()
          ));
          $notifier->notifyException(
            $e,
            NotificationSeverity::WARNING
          );
          continue;
        }

        if ($this->isPaused() === TRUE) {
          $this->shutdown();
          return;
        }

        set_error_handler(array($this, 'handleError'));
        try {
          // Log which server and PID are currently actively processing tasks.
          $this->getStateStore()->set(StateStore::ACTIVE_THREAD_NAME, $state = array(
            'server' => gethostname(),
            'pid' => getmypid(),
            'guid' => $input->getOption('guid'),
          ));

          $this->output($output, sprintf(
            'Actively processing tasks on server %s pid %d guid %s',
            $state['server'],
            $state['pid'],
            $state['guid']
          ));

          if (!$this->process) {
            // The exec command should ensure that the process does not get
            // forked in a [grand]child process.
            $this->process = new Process(
              'exec ' . $this->getAppDirectory() . '/bin/wipctl process-tasks',
              NULL,
              NULL,
              NULL,
              $task_process_timeout * 2 + 10
            );
            $this->process->start();
            $this->output($output, sprintf(
              'Started child: %d',
              $this->process->getPid()
            ));
          } else {
            // Restarted processes have been verified as getting a new PID on
            // restart(). It is critical to assign the result of restart() here
            // to $process to avoid spawning infinite clones.
            $this->process = $this->process->restart();
            $this->output($output, sprintf(
              'Restarted child: %d',
              $this->process->getPid()
            ));
          }
          try {
            while ($this->process->isRunning()) {
              sleep(5);
              // Ensure that we keep our own database connection alive.
              $this->keepAlive();
            }
            $exitcode = $this->process->getExitCode();
          } catch (RuntimeException $e) {
            $exitcode = 1;
            $this->output($output, sprintf(
              'Exception detected in child process: %s',
              $e->getMessage()
            ));
            $notifier->notifyException(
              $e,
              NotificationSeverity::WARNING,
              array('state' => $state)
            );
          }
          if ($exitcode !== 0) {
            // @todo Clear up the pid file for the child. The lock should be
            // handled automatically by MySQL.
            $this->output($output, "Error in child process with exit code $exitcode.");
            $this->output($output, $this->process->getErrorOutput());
          }

          $this->output($output, $this->process->getOutput());

          // We got the lock last time, we can try again immediately.
          $this->releaseLock();
          restore_error_handler();
          continue;
        } catch (\Exception $e) {
          $this->releaseLock();
          $this->output($output, sprintf(
            'An error occurred in the thread pool daemon - cleaning up locks and exiting - %s.',
            $e->getMessage()
          ));
          $notifier->notifyException(
            $e,
            NotificationSeverity::ERROR,
            array('state' => $state)
          );
          restore_error_handler();
          break;
        }
      }

      // 5 seconds pause between checking for availability of the lock.
      sleep(5);
      // Ensure our DB connection doesn't die.
      $this->keepAlive();
    }

    $this->shutdown();
    $this->output($output, 'Exiting WIP thread pool daemon.');
  }

  /**
   * Gets the StateStore instance.
   *
   * @return StateStoreInterface
   *   The StateStore instance.
   */
  private function getStateStore() {
    if ($this->stateStore === NULL) {
      $this->stateStore = $this->dependencyManager->getDependency('acquia.wip.storage.state');
    }
    return $this->stateStore;
  }

  /**
   * Gets whether the monitor daemon is paused.
   *
   * @return bool
   *   Whether the monitor daemon is paused.
   */
  private function isPaused() {
    $pause = $this->getStateStore()->get(
      MonitorDaemonPause::STATE_NAME,
      MonitorDaemonPause::$defaultValue
    );
    return $pause === MonitorDaemonPause::ON;
  }

  /**
   * Catches any errors that occur during the execute loop.
   *
   * This method throws an exception that can break the execute loop and provide
   * for rapid cleanup and exit.  This can prevent a broken process from holding
   * a lock that would prevent tasks from being processed.
   *
   * @param int $error_number
   *   The level of the error raised.
   * @param string $message
   *   The error message.
   * @param string $filename
   *   The filename where the error occurred.
   * @param int $line_number
   *   The line number where the error occurred.
   *
   * @throws \ErrorException
   *   An exception that contains all of the error information.
   */
  public function handleError($error_number, $message, $filename, $line_number) {
    $stacks = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 10);
    $wip_id = 0;
    foreach ($stacks as $stack) {
      if ($stack['function'] == 'dispatch') {
        $args = $stack['args'];
        foreach ($args as $arg) {
          if (get_class($arg) == 'Acquia\Wip\Task') {
            /** @var \Acquia\Wip\Task $arg */
            $wip_id = $arg->getId();
          }
          break;
        }
        break;
      }
    }
    $recoverable = array(
      E_RECOVERABLE_ERROR,
      E_WARNING,
      E_NOTICE,
      E_USER_NOTICE,
      E_USER_WARNING,
    );
    if (in_array($error_number, $recoverable)) {
      WipLog::getWipLog()->log(
        WipLogLevel::DEBUG,
        sprintf(
          "Recoverable error encountered in %s: %s in %s:%d [code: %d]",
          __CLASS__,
          $message,
          $filename,
          $line_number,
          $error_number
        ),
        $wip_id
      );
      return;
    } else {
      $this->output($this->output, sprintf(
        "Non-recoverable error encountered in %s: %s in %s:%d [code: %d]\n",
        __CLASS__,
        $message,
        $filename,
        $line_number,
        $error_number
      ));
    }
    $this->quit = TRUE;

    throw new \ErrorException($message, $error_number, 0, $filename, $line_number);
  }

  /**
   * Sends a message to the output interface.
   *
   * @param OutputInterface $output
   *   The output interface.
   * @param string $message
   *   The message to send to the output interface.
   */
  protected function output(OutputInterface $output, $message) {
    $formatter = $this->getHelper('formatter');
    $date = date('r');
    $section = self::PROCESS_NAME . " $date " . getmypid();
    $output->writeln($formatter->formatSection($section, $message));
  }

  /**
   * {@inheritdoc}
   */
  protected function initialize(InputInterface $input, OutputInterface $output) {
    // Just making sure this is available for use in the signal handler.
    $this->output = $output;

    // Store the PID of this process in a file for other processes to monitor.
    $this->pidfile = App::getApp()['config.global']['pidfile.threadpooldaemon'];
    file_put_contents($this->pidfile, getmypid());

    $this->entityManager = App::getEntityManager();
    $rsm = new ResultSetMapping();
    $rsm->addScalarResult('id', 'id');
    $query = $this->entityManager
      ->createNativeQuery('SELECT CONNECTION_ID() AS id', $rsm);
    $result = $query->getResult();
    $this->dbConnectionId = reset($result)['id'];

    $this->setUpSignalHandling();
  }

  /**
   * Sets up signal handling so simple aborts will not prevent proper cleanup.
   */
  private function setUpSignalHandling() {
    declare(ticks = 1);
    if (function_exists('pcntl_signal')) {
      pcntl_signal(SIGINT, array($this, 'handleSignal'));
      pcntl_signal(SIGTERM, array($this, 'handleSignal'));
      pcntl_signal(SIGQUIT, array($this, 'handleSignal'));
      pcntl_signal(SIGABRT, array($this, 'handleSignal'));
    }
  }

  /**
   * Handles the specified signal.
   *
   * @param int $signal
   *   The signal type.
   */
  public function handleSignal($signal) {
    if (isset($this->output)) {
      $this->output($this->output, sprintf(
        'WIP thread pool daemon received signal: %d',
        $signal
      ));
    }
    switch ($signal) {
      case SIGTERM:
      case SIGINT:
      case SIGQUIT:
      case SIGABRT:
        // Indicate that processing should stop when possible.
        $this->quit = TRUE;
        if (isset($this->process) && $this->process->isRunning()) {
          if (isset($this->output)) {
            $this->output($this->output, sprintf(
              'Passing on signal %d to child PID %d.',
              $signal,
              $this->process->getPid()
            ));
          }

          try {
            $this->process->signal($signal);
          } catch (\Exception $e) {
            // This can happen if the process is no longer running. Ignore.
          }
        }
        break;
    }
  }

  /**
   * Defines shutdown behavior to perform at the end of processing.
   */
  public function shutdown() {
    unlink($this->pidfile);

    // Remove server and PID from the state store.
    $this->getStateStore()->delete(StateStore::ACTIVE_THREAD_NAME);
    // @TODO - work out if this works error-free if we didn't have the lock.
    $this->releaseLock();
    parent::shutdown();
  }

  /**
   * Acquires a lock.
   *
   * @return bool
   *   If the lock was successfully acquired.
   */
  private function getLock() {
    $lock_manager = $this->getLockManager();
    $lock_timeout = $this->getDaemonLockTimeout();
    $loops = ceil($lock_timeout / self::LOCK_INTERVAL);
    $result = FALSE;
    // Only wait for the lock in smaller increments so that signals will be handled faster.
    for ($iteration = 0; $iteration < $loops; $iteration++) {
      $result = $lock_manager->acquire(self::LOCK_NAME, self::LOCK_INTERVAL);
      if ($this->isPaused() === TRUE || $this->quit === TRUE || $result === TRUE) {
        break;
      }
    }
    return $result;
  }

  /**
   * Releases a lock.
   */
  private function releaseLock() {
    $lock_manager = $this->getLockManager();
    // @todo - handle fails: warn.
    $lock_manager->release(self::LOCK_NAME);
  }

  /**
   * Issues a query to keep the database connection alive.
   */
  private function keepAlive() {
    // @todo - this should work most of the time, but if we still see fails
    // attempting to retain a connection, we can detect here that it's gone,
    // reconnect and attempt to get the lock back also.
    static $rsm;

    if (empty($rsm)) {
      $rsm = new ResultSetMapping();
      $rsm->addScalarResult('keepalive', 'keepalive');
    }
    $query = $this->entityManager
      ->createNativeQuery('SELECT 1 AS keepalive', $rsm);
    $query->getResult();
  }

  /**
   * Gets the number of seconds the daemon should run before exiting.
   *
   * @return int
   *   The number of seconds the daemon should run.
   */
  private function getDaemonRuntime() {
    $result = intval(App::getApp()['config.global']['threadpooldaemon.runtime']);

    // The daemon runtime must be larger than the ThreadPool lock duration.
    $thread_pool_lock_duration = $this->getThreadPoolLockTimeout();
    if ($result <= $thread_pool_lock_duration) {
      // Create a safe value.
      $result = intval($thread_pool_lock_duration * 1.5);
    }
    return $result;
  }

  /**
   * Gets the daemon lock timeout.
   *
   * @return int
   *   The maximum number of seconds to wait for the lock.
   */
  private function getDaemonLockTimeout() {
    $result = intval(App::getApp()['config.global']['threadpooldaemon.timeout']);

    // The daemon lock duration must be larger than the ThreadPool lock
    // duration.
    $thread_pool_lock_duration = $this->getThreadPoolLockTimeout();
    if ($result <= $thread_pool_lock_duration) {
      // Create a safe value.
      $result = intval($thread_pool_lock_duration * 1.5);
    }
    return $result;
  }

  /**
   * Gets the thread pool lock timeout.
   *
   * @return int
   *   The maximum number of seconds to wait for the lock.
   */
  private function getThreadPoolLockTimeout() {
    return intval(App::getApp()['config.global']['threadpool.timeout']);
  }

  /**
   * Gets the lock manager.
   *
   * @return LockInterface
   *   The lock manager.
   */
  private function getLockManager() {
    /** @var LockInterface $lock_manager */
    return $this->dependencyManager->getDependency('acquia.wip.lock.global');
  }

}
