<?php

namespace Acquia\WipService\Console\Commands;

use Acquia\WipService\App;
use Acquia\WipService\Console\WipConsoleCommand;
use Acquia\Wip\Exception\RowLockException;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\IteratorStatus;
use Acquia\Wip\Iterators\BasicIterator\StateTableRecording;
use Acquia\Wip\Lock\WipPoolRowLock;
use Acquia\Wip\Notification\NotificationInterface;
use Acquia\Wip\Notification\NotificationSeverity;
use Acquia\Wip\Runtime\ExecutionTranscriptElement;
use Acquia\Wip\Runtime\Thread;
use Acquia\Wip\Runtime\WipWorker;
use Acquia\Wip\Storage\ThreadStoreInterface;
use Acquia\Wip\Storage\WipPoolStoreInterface;
use Acquia\Wip\TaskExitStatus;
use Acquia\Wip\TaskInterface;
use Acquia\Wip\TaskStatus;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipLogLevel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Defines a console command for executing an individual task.
 */
class WipExecCommand extends WipConsoleCommand {

  /**
   * Indicates no failure will be simulated.
   */
  const SIMULATE_FAILURE_NONE = 0;

  /**
   * Indicates a failure will be simulated before the parent starts.
   */
  const SIMULATE_FAILURE_BEFORE_PARENT = 1;

  /**
   * Indicates a failure will be simulated in the parent process.
   */
  const SIMULATE_FAILURE_WITHIN_PARENT = 2;

  /**
   * Indicates a failure will be simulated before the child starts.
   */
  const SIMULATE_FAILURE_BEFORE_CHILD = 3;

  /**
   * Indicates a failure will be simulated within the child.
   */
  const SIMULATE_FAILURE_WITHIN_CHILD = 4;

  /**
   * Indicates a failure will be simulated before the task is processed.
   */
  const SIMULATE_FAILURE_BEFORE_TASK = 5;

  /**
   * Indicates a failure will be simulated during the processing of the task.
   */
  const SIMULATE_FAILURE_WITHIN_TASK = 6;

  /**
   * Indicates a failure will be simulated after the task has been processed.
   */
  const SIMULATE_FAILURE_AFTER_TASK = 7;

  /**
   * Indicates a failure will be simulated after the child completes.
   */
  const SIMULATE_FAILURE_AFTER_CHILD = 8;

  /**
   * Indicates a failure will be simulated before task cleanup.
   */
  const SIMULATE_FAILURE_BEFORE_CLEANUP = 9;

  /**
   * Indicates a failure will be simulated during task cleanup.
   */
  const SIMULATE_FAILURE_WITHIN_CLEANUP = 10;

  /**
   * Indicates a failure will be simulated after task cleanup.
   */
  const SIMULATE_FAILURE_AFTER_CLEANUP = 11;

  /**
   * Indicates a failure will be simulated before thread cleanup.
   */
  const SIMULATE_FAILURE_BEFORE_THREAD_CLEANUP = 12;

  /**
   * Indicates a failure will be simulated during thread cleanup.
   */
  const SIMULATE_FAILURE_WITHIN_THREAD_CLEANUP = 13;

  /**
   * Indicates a failure will be simulated after thread cleanup.
   */
  const SIMULATE_FAILURE_AFTER_THREAD_CLEANUP = 14;

  /**
   * Indicates a failure will be simulated after the parent is complete.
   */
  const SIMULATE_FAILURE_AFTER_PARENT = 15;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $help = <<<EOT
This is an internal-use command for running a wip task.
EOT;
    $this->setName('exec')
      ->setDescription('Run a given WIP task.')
      ->setHelp($help)
      ->addOption(
        'id',
        NULL,
        InputOption::VALUE_REQUIRED,
        'ID of the WIP task to run.'
      )
      ->addOption(
        'thread-id',
        NULL,
        InputOption::VALUE_REQUIRED,
        'ID of the thread to run this WIP task.'
      )
      ->addOption(
        'child',
        NULL,
        InputOption::VALUE_NONE,
        '(internal use only - do not set)'
      )
      ->addOption(
        'cleanup',
        NULL,
        InputOption::VALUE_REQUIRED,
        'Exit code of the child process.',
        NULL
      )
      ->addOption(
        'timeout',
        NULL,
        InputOption::VALUE_OPTIONAL,
        'Number of seconds to run the task for before moving on.'
      )
      ->addOption(
        'config',
        NULL,
        InputOption::VALUE_OPTIONAL,
        'Path to a factory config file.'
      )
      ->addOption(
        'simulate-failure-type',
        NULL,
        InputOption::VALUE_REQUIRED,
        'An integer indicating where the failure will occur.',
        self::SIMULATE_FAILURE_NONE
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $result = 1;
    $id = $this->safeStringToInt($input->getOption('id'));
    $start = microtime(TRUE);
    try {
      $result = $this->invokeCommand($input, $output);
    } catch (\Exception $e) {
      WipLog::getWipLog()->log(
        WipLogLevel::FATAL,
        sprintf('Fatal error in WipExecCommand: %s', $e->getMessage()),
        $id
      );
      return 1;
    } finally {
      if (WipFactory::getBool('$acquia.command.duration.logs', FALSE)) {
        global $argv;
        $duration = microtime(TRUE) - $start;
        $command_line = implode(' ', $argv);
        WipLog::getWipLog()->log(
          WipLogLevel::DEBUG,
          sprintf('WipExecCommand took %0.3f seconds: %s', $duration, $command_line),
          $id
        );
      }
    }
    return $result;
  }

  /**
   * Performs the exec command.
   *
   * @param InputInterface $input
   *   An InputInterface instance.
   * @param OutputInterface $output
   *   An OutputInterface instance.
   *
   * @return int
   *   0 if everything went fine, or an error code.
   */
  private function invokeCommand(InputInterface $input, OutputInterface $output) {
    $result = 0;
    $logger = WipLog::getWipLog($this->dependencyManager);
    $id = NULL;
    $thread_id = NULL;

    try {
      $id = $this->safeStringToInt($input->getOption('id'));
      if ($id <= 0) {
        throw new \InvalidArgumentException('The "--id" option value must be an integer that is greater than zero.');
      }
      $thread_id = $this->safeStringToInt($input->getOption('thread-id'));
      if ($thread_id <= 0) {
        throw new \InvalidArgumentException(
          'The "--thread-id" option value must be an integer that is greater than zero.'
        );
      }
    } catch (\Exception $e) {
      // Try to get the original command.
      global $argv;
      $command = implode(' ', $argv);
      $logger->log(
        WipLogLevel::FATAL,
        sprintf(
          "Fatal error in WipExecCommand:\n%s\nCommand: %s",
          $e->getMessage(),
          $command
        )
      );
      return $result;
    }

    $simulate_failure_type = intval($input->getOption('simulate-failure-type'));
    if ($input->getOption('child')) {
      // The child process executes the Wip task.
      $result = $this->executeChild($id, $simulate_failure_type);
    } elseif ($input->getOption('cleanup') !== NULL) {
      // The cleanup process verifies the process has completed successfully
      // and performs repairs if required.
      try {
        // A simple cast to int or a call to intval() will convert a non-numeric
        // string to 0, indicating success even if the string was nonsense.
        $exit_code = $this->safeStringToInt($input->getOption('cleanup'));
        $result = WipPoolRowLock::getWipPoolRowLock($id, NULL, $this->dependencyManager)
          ->setTimeout(30)
          ->runAtomic(
            $this,
            'executeCleanup',
            [$id, $thread_id, $exit_code, $simulate_failure_type]
          );
      } catch (\Exception $e) {
        $logger->log(
          WipLogLevel::FATAL,
          sprintf(
            "Failed to cleanup after running task %d:\n%s",
            $id,
            $e->getMessage()
          )
        );
        $result = 1;
      }
    } else {
      // This is the parent process. This process is responsible for holding
      // the wip_pool row execute lock and delegates work to child processes.
      try {
        $result = WipPoolRowLock::getWipPoolRowLock(
          $id,
          WipPoolRowLock::LOCK_PREFIX_EXECUTE,
          $this->dependencyManager
        )
          // Do not wait at all for this lock. If the lock is not available
          // this task is already being executed.
          ->setTimeout(0)
          ->runAtomic(
            $this,
            'executeParent',
            array($input, $id, $thread_id, $logger)
          );
      } catch (RowLockException $rle) {
        // The execute lock is being held by a separate process. This should not
        // happen as the ThreadPool takes the execute lock into account before
        // dispatch. In case it does happen, handle it gracefully and retry
        // because there is no harm or side effects for task execution.
        $logger->log(
          WipLogLevel::ERROR,
          sprintf(
            "Task %d in process %s:%d, thread %d appears to be executing in another process; will clean up and retry",
            $id,
            gethostname(),
            getmypid(),
            $thread_id,
            $id
          ),
          $id
        );
        // Do minimal clean up so the task will be picked up again and progress can be made.
        WipPoolRowLock::getWipPoolRowLock($id, NULL, $this->dependencyManager)
          ->setTimeout(5)
          ->runAtomic(
            $this,
            'executeQuickCleanup',
            array($id, $thread_id, $logger)
          );
        $result = 1;
      } catch (\Exception $e) {
        /** @var WipPoolStoreInterface $wip_pool_storage */
        $wip_pool_storage = $this->dependencyManager->getDependency('acquia.wip.storage.wippool');
        $task = $wip_pool_storage->get($id);
        $log_message = sprintf(
          "Aborting execution of task %d in process %s:%d, thread %d:\n%s\n%s",
          $id,
          gethostname(),
          getmypid(),
          $thread_id,
          $e->getMessage(),
          $e->getTraceAsString()
        );
        $exit_message = 'Fatal error occurred. Please try again.';
        $result = 1;
        WipPoolRowLock::getWipPoolRowLock($id, NULL, $this->dependencyManager)
          ->setTimeout(5)
          ->runAtomic(
            $this,
            'exitWithError',
            array($task, $thread_id, $exit_message, $log_message)
          );
      }
    }
    return $result;
  }

  /**
   * Represents the parent process.
   *
   * @param InputInterface $input
   *   An InputInterface instance.
   * @param int $id
   *   The Wip task ID.
   * @param int $thread_id
   *   The thread ID.
   * @param WipLogInterface $logger
   *   The logger.
   *
   * @return int
   *   The exit code: 0 indicates success; non-zero indicates failure.
   *
   * @throws RowLockException
   *   If the execute lock has not been acquired for the associated wip_pool
   *   row.
   */
  public function executeParent(InputInterface $input, $id, $thread_id, WipLogInterface $logger) {
    // Figure out whether to simulate a failure.
    $simulate_failure = $this->willSimulateFailure();
    $simulate_failure_type = self::SIMULATE_FAILURE_NONE;
    if ($simulate_failure) {
      // Figure out where to simulate a failure.
      $simulate_failure_type = $this->getSimulatedFailurePosition();
    }
    if ($simulate_failure_type === self::SIMULATE_FAILURE_BEFORE_PARENT) {
      $this->simulateFailure($id);
    }
    $this->statusChange(ExecutionTranscriptElement::PROCESS_PARENT, ExecutionTranscriptElement::START);
    $result = 0;
    $row_lock = WipPoolRowLock::getWipPoolRowLock(
      $id,
      WipPoolRowLock::LOCK_PREFIX_EXECUTE,
      $this->dependencyManager
    );
    if (!$row_lock->hasLock()) {
      $message = sprintf(
        'Failed to acquire execute lock on wip_pool row %s before calling %s',
        $id,
        __METHOD__
      );
      throw new RowLockException($message);
    }

    // The child command is responsible for executing the Wip task. The child
    // does not hold a lock during the entire course of its execution; only
    // when updating the associated wip_pool row.
    $child_command = $this->getChildCommand($input, $simulate_failure_type);
    $process = new Process($child_command);
    // Verify the execute lock  before and after running the task. Be sure to
    // write the output after running the task because verification of the
    // execute lock can throw an exception.
    $this->verifyExecuteLock($id, __METHOD__);
    if ($simulate_failure_type === self::SIMULATE_FAILURE_WITHIN_PARENT) {
      $this->simulateFailure($id);
    }
    $process->run();
    // Print the output of the child process because it contains elements of
    // the transcript.
    print($process->getOutput());
    $exit_code = $process->getExitCode();
    if ($exit_code !== 0) {
      // Exit without cleaning up after the child so that another process will
      // not begin work on this task until after the completion signal is
      // received. The signal will contain all of the information required to
      // clean up after the failure.
      exit($exit_code);
    }

    // The cleanup process is responsible for verifying that the child process
    // has exited cleanly. For example, it verifies that the child has moved
    // the task out of the 'PROCESSING' run_state. This process holds the
    // wip_pool update lock during the entire duration of its execution, which
    // is why it was broken out into a separate process.
    $cleanup_command = $this->getCleanupCommand($input, $process->getExitCode());
    $cleanup_process = new Process($cleanup_command);
    $this->verifyExecuteLock($id, __METHOD__);
    $cleanup_process->run();

    // Print the output of the child process because it contains elements of
    // the transcript.
    print($cleanup_process->getOutput());
    $cleanup_exit_code = $cleanup_process->getExitCode();
    if ($cleanup_exit_code !== 0) {
      // Exit without cleaning up the thread so that another process will not
      // begin work on this task until after the completion signal is received.
      // The signal will contain all of the information required to clean up
      // after the failure.
      exit($cleanup_exit_code);
    }
    $this->verifyExecuteLock($id, __METHOD__);
    try {
      if ($simulate_failure_type === self::SIMULATE_FAILURE_BEFORE_THREAD_CLEANUP) {
        $this->simulateFailure($id);
      }
      $this->statusChange(ExecutionTranscriptElement::PROCESS_THREAD_CLEANUP, ExecutionTranscriptElement::START);
      $this->releaseThreadById($id, $thread_id, $logger, $simulate_failure_type);
      $this->statusChange(ExecutionTranscriptElement::PROCESS_THREAD_CLEANUP, ExecutionTranscriptElement::COMPLETE);
      if ($simulate_failure_type === self::SIMULATE_FAILURE_AFTER_THREAD_CLEANUP) {
        $this->simulateFailure($id);
      }
    } catch (\Exception $e) {
      $logger->log(
        WipLogLevel::FATAL,
        sprintf(
          "Failed to release the thread associated with task %d:\n%s",
          $id,
          $e->getMessage()
        )
      );
      $result = 1;
    }

    $this->statusChange(ExecutionTranscriptElement::PROCESS_PARENT, ExecutionTranscriptElement::COMPLETE);
    if ($simulate_failure_type === self::SIMULATE_FAILURE_AFTER_PARENT) {
      $this->simulateFailure($id);
    }

    // Only log dispatch completion if no problems were encountered. If any
    // problems were encountered, the log message will be written when the
    // completion signal is received.
    if ($result === 0) {
      $this->dispatchComplete($id, $thread_id, $logger);

      // This comment and sleep is now obsolete?
      // It is possible that the above log message does not get committed to
      // the database by the time the ThreadPool picks up this same task. It is
      // fair to pick it up again as soon as the status is set to WAITING and
      // the thread has been completed. If the dispatch completed log and the
      // dispatch started log messages get out of order, the transcript
      // validation code will interpret this as a dispatch that occurred during
      // an existing dispatch. Prevent that from happening by holding the
      // execute lock for at least a second after the log has been committed.
      // That will prevent the task from starting for the same period of time.
      sleep(1);
    }
    return $result;
  }

  /**
   * Verifies the execute lock is being held by this process.
   *
   * @param int $id
   *   The task ID.
   * @param string $method
   *   The calling method.
   *
   * @throws RowLockException
   *   If the lock is not currently being held by this process.
   */
  private function verifyExecuteLock($id, $method) {
    $row_lock = WipPoolRowLock::getWipPoolRowLock(
      $id,
      WipPoolRowLock::LOCK_PREFIX_EXECUTE,
      $this->dependencyManager
    );
    if (!$row_lock->hasLock()) {
      $message = sprintf(
        'Execute lock for task %d lost in %s.',
        $id,
        $method
      );
      throw new RowLockException($message);
    }
  }

  /**
   * Creates a command to invoke the child process.
   *
   * The child process is responsible for executing the task.
   *
   * @param InputInterface $input
   *   An InputInterface instance.
   * @param int $simulate_failure_type
   *   Optional. Indicates where a failure should be simulated.
   *
   * @return string
   *   The command.
   */
  private function getChildCommand(InputInterface $input, $simulate_failure_type = self::SIMULATE_FAILURE_NONE) {
    $command = $this->getAppDirectory() . '/' . WipFactory::getObject('$acquia.wip.exec.path');
    $arguments = $input->getArguments();
    // Important that we don't duplicate the command arg from config and the
    // arguments supplied to this command.
    if (isset($arguments['command'])) {
      unset($arguments['command']);
    }
    $command .= ' ' . implode(' ', $arguments);

    // White-list of options to pass on.
    $options = array('id', 'timeout', 'config', 'thread-id');
    foreach ($options as $key) {
      $value = $input->getOption($key);
      if (isset($value)) {
        $command .= " --$key=$value";
      }
    }
    $command .= ' --child';
    if (App::getApp()['config.global']['debug']) {
      $command .= ' --verbose';
    }
    if ($simulate_failure_type !== self::SIMULATE_FAILURE_NONE) {
      $command .= sprintf(' --simulate-failure-type=%d', $simulate_failure_type);
    }
    return $command;
  }

  /**
   * Creates a command to perform cleanup.
   *
   * The cleanup process is responsible for updating the wip_pool row.
   *
   * @param InputInterface $input
   *   An InputInterface instance.
   * @param int $exit_code
   *   The exit code from the execution of the child command.
   * @param int $simulate_failure_type
   *   Optional. Indicates when a failure should be simulated.
   *
   * @return string
   *   The command.
   */
  private function getCleanupCommand(
    InputInterface $input,
    $exit_code,
    $simulate_failure_type = self::SIMULATE_FAILURE_NONE
  ) {
    $command = $this->getAppDirectory() . '/' . WipFactory::getObject('$acquia.wip.exec.path');
    $arguments = $input->getArguments();
    // Important that we don't duplicate the command arg from config and the
    // arguments supplied to this command.
    if (isset($arguments['command'])) {
      unset($arguments['command']);
    }
    $command .= ' ' . implode(' ', $arguments);

    // White-list of options to pass on.
    $options = array('id', 'config', 'thread-id');
    foreach ($options as $key) {
      $value = $input->getOption($key);
      if (isset($value)) {
        $command .= " --$key=$value";
      }
    }
    $command .= sprintf(' --cleanup=%d', $exit_code);
    if (App::getApp()['config.global']['debug']) {
      $command .= ' --verbose';
    }
    if ($simulate_failure_type !== self::SIMULATE_FAILURE_NONE) {
      $command .= sprintf(' --simulate-failure-type=%d', $simulate_failure_type);
    }
    return $command;
  }

  /**
   * Represents the child process.
   *
   * @param int $id
   *   The Wip task ID.
   * @param int $simulate_failure_type
   *   Optional. Indicates when to simulate a failure.
   *
   * @return int
   *   The exit code: 0 indicates success; non-zero indicates failure.
   */
  public function executeChild($id, $simulate_failure_type = self::SIMULATE_FAILURE_NONE) {
    if ($simulate_failure_type !== self::SIMULATE_FAILURE_NONE) {
      WipLog::getWipLog()->log(WipLogLevel::ALERT, sprintf("simulate-failure-type: %d", $simulate_failure_type));
    }
    if ($simulate_failure_type === self::SIMULATE_FAILURE_BEFORE_CHILD) {
      WipLog::getWipLog()->log(WipLogLevel::ALERT, sprintf("Simulating failure"));
      $this->simulateFailure($id);
    }
    $this->statusChange(ExecutionTranscriptElement::PROCESS_CHILD, ExecutionTranscriptElement::START);
    // What happens if the database connection fails?
    // Just load it up and run it.
    if ($simulate_failure_type === self::SIMULATE_FAILURE_WITHIN_CHILD) {
      $this->simulateFailure($id);
    }
    try {
      $worker = new WipWorker();
      $worker->setTaskId($id);
      if ($simulate_failure_type === self::SIMULATE_FAILURE_BEFORE_TASK) {
        $this->simulateFailure($id);
      }
      $this->statusChange(ExecutionTranscriptElement::PROCESS_TASK, ExecutionTranscriptElement::START);
      if ($simulate_failure_type === self::SIMULATE_FAILURE_WITHIN_TASK) {
        $this->simulateFailure($id);
      }
      $result = $worker->process();
      if ($simulate_failure_type === self::SIMULATE_FAILURE_AFTER_TASK) {
        $this->simulateFailure($id);
      }

      $this->statusChange(ExecutionTranscriptElement::PROCESS_TASK, ExecutionTranscriptElement::COMPLETE);
      $worker->complete($result);
      // Share the IteratorResult instance with the parent process in a way
      // that can co-exist with other output.
      printf("\n<IteratorResult>\n%s\n</IteratorResult>\n", serialize($result));
      $this->statusChange(ExecutionTranscriptElement::PROCESS_CHILD, ExecutionTranscriptElement::COMPLETE);
      $result = 0;
    } catch (\Exception $e) {
      WipLog::getWipLog($this->dependencyManager)
        ->log(
          WipLogLevel::ERROR,
          sprintf(
            "%s - Failed to execute task %d:\n%s",
            __METHOD__,
            $id,
            $e->getMessage()
          ),
          $id
        );
      $result = 1;
    }
    if ($simulate_failure_type === self::SIMULATE_FAILURE_AFTER_CHILD) {
      $this->simulateFailure($id);
    }
    return $result;
  }

  /**
   * Releases the thread associated with this task.
   *
   * This method is called in the parent process, which holds the execute lock.
   *
   * @param int $task_id
   *   The task ID.
   * @param int $thread_id
   *   The thread ID.
   * @param WipLogInterface $logger
   *   The logger.
   * @param int $simulate_failure_type
   *   Optional. If a failure is being simulated, this value indicates when.
   *
   * @throws \Acquia\Wip\Exception\DependencyMissingException
   *   If there is a missing dependency.
   */
  public function releaseThreadById(
    $task_id,
    $thread_id,
    WipLogInterface $logger,
    $simulate_failure_type = self::SIMULATE_FAILURE_NONE
  ) {
    $row_lock = WipPoolRowLock::getWipPoolRowLock(
      $task_id,
      WipPoolRowLock::LOCK_PREFIX_EXECUTE,
      $this->dependencyManager
    );
    if (!$row_lock->hasLock()) {
      $message = sprintf(
        'Failed to acquire execute lock on wip_pool row %s before calling %s',
        $task_id,
        __METHOD__
      );
      $logger->log(WipLogLevel::FATAL, $message, 0);
      return;
    }

    if ($simulate_failure_type === self::SIMULATE_FAILURE_WITHIN_THREAD_CLEANUP) {
      $this->simulateFailure($task_id);
    }
    /** @var ThreadStoreInterface $thread_store */
    $thread_store = $this->dependencyManager->getDependency('acquia.wip.storage.thread');
    try {
      $thread = $thread_store->get($thread_id);
      if (!($thread instanceof Thread)) {
        return;
      }

      // As expected, the thread still exists. Remove it from the thread_store.
      $thread_store->remove($thread);
    } catch (\Exception $e) {
      // Unexpected.
      $logger->log(
        WipLogLevel::FATAL,
        sprintf(
          "Unexpected error encountered releasing thread for task %d:\n%s",
          $task_id,
          $e->getMessage()
        ),
        $task_id
      );
    }
  }

  /**
   * Cleans up after the child process.
   *
   * This method ensures that the child process took the task out of the
   * PROCESSING state, and exits the task with an error if there was a problem.
   *
   * This method is called in its own process, and must have the wip_pool row
   * update lock for the duration of the call. A different process is used
   * because of a limitation of Mysql versions older than 5.7.5 in which only a
   * single lock can be held per database connection.
   *
   * @param int $id
   *   The task ID.
   * @param int $thread_id
   *   The thread ID.
   * @param int $exit_code
   *   The child process exit code.
   * @param int $simulate_failure_type
   *   When a failure should be simulated.
   *
   * @return int
   *   The exit code: 0 indicates success; non-zero indicates failure.
   *
   * @throws RowLockException
   *   If the wip pool row lock has not been acquired.
   */
  public function executeCleanup($id, $thread_id, $exit_code, $simulate_failure_type = self::SIMULATE_FAILURE_NONE) {
    if ($simulate_failure_type === self::SIMULATE_FAILURE_BEFORE_CLEANUP) {
      $this->simulateFailure($id);
    }
    $this->statusChange(ExecutionTranscriptElement::PROCESS_CLEANUP, ExecutionTranscriptElement::START);
    if ($simulate_failure_type === self::SIMULATE_FAILURE_WITHIN_CLEANUP) {
      $this->simulateFailure($id);
    }
    $result = 0;
    if (!WipPoolRowLock::getWipPoolRowLock($id, NULL, $this->dependencyManager)->hasLock()) {
      $message = sprintf('%s must only be called with the wip pool row lock.', __METHOD__);
      throw new RowLockException($message);
    }
    $logger = WipLog::getWipLog($this->dependencyManager);
    /** @var WipPoolStoreInterface $wip_pool_storage */
    $wip_pool_storage = $this->dependencyManager->getDependency('acquia.wip.storage.wippool');
    $task = $wip_pool_storage->get($id);
    if (FALSE === $task) {
      $message = sprintf(
        'Fatal error in WIP child process task %d - task not found in the database.',
        $id
      );
      $logger->log(WipLogLevel::FATAL, $message, $id);
      throw new \RuntimeException($message);
    }
    if ($exit_code !== 0) {
      $message = sprintf(
        'Fatal error in WIP child process for %s %d. exit: %d.',
        $task->getName(),
        $id,
        $exit_code
      );
      $this->exitWithError($task, $thread_id, $message, $message);
      $result = 1;
    } elseif ($task->getStatus() === TaskStatus::PROCESSING) {
      $task->loadWipIterator();
      $iterator = $task->getWipIterator();
      $current_state = $iterator->getCurrentState();
      if (empty($current_state)) {
        $current_state = $iterator->getStartState();
      }
      $exit_message = 'Failed to move the run status from "PROCESSING".';
      $log_message = sprintf(
        '%s This error occurred on or after state "%s".',
        $exit_message,
        $current_state
      );
      $this->exitWithError($task, $thread_id, $exit_message, $log_message);

      $recordings = $iterator->getRecordings();
      if (!empty($recordings)) {
        /** @var StateTableRecording $recording */
        $recording = reset($recordings);
        $transcript = $recording->getTranscript();
        if (empty($transcript)) {
          $transcript = 'Empty transcript';
        }
        $logger->log(WipLogLevel::ALERT, $transcript, $task->getId());
      }
      $result = 1;
    }
    if ($result === 0) {
      $this->statusChange(ExecutionTranscriptElement::PROCESS_CLEANUP, ExecutionTranscriptElement::COMPLETE);
    }
    if ($simulate_failure_type === self::SIMULATE_FAILURE_AFTER_CLEANUP) {
      $this->simulateFailure($id);
    }
    return $result;
  }

  /**
   * Logs that a dispatch is complete.
   *
   * @param int $task_id
   *   The task ID.
   * @param int $thread_id
   *   The thread ID.
   * @param WipLogInterface $logger
   *   The logger instance.
   */
  private function dispatchComplete($task_id, $thread_id, WipLogInterface $logger) {
    $message = sprintf(
      'INTERNAL: WIP dispatch completed for task %d on thread %d.',
      $task_id,
      $thread_id
    );
    $logger->log(WipLogLevel::TRACE, $message, $task_id);
  }

  /**
   * Cleans up the task and thread minimally so execution will continue smoothly.
   *
   * @param int $task_id
   *   The ID of the task.
   * @param int $thread_id
   *   The ID of the thread.
   * @param WipLogInterface $logger
   *   The logger.
   *
   * @throws RowLockException
   *   If the appropriate update lock is not held.
   */
  public function executeQuickCleanup($task_id, $thread_id, WipLogInterface $logger) {
    if (!WipPoolRowLock::getWipPoolRowLock($task_id, NULL, $this->dependencyManager)->hasLock()) {
      $message = sprintf('%s must only be called with the wip pool row lock.', __METHOD__);
      throw new RowLockException($message);
    }

    try {
      /** @var ThreadStoreInterface $thread_store */
      $thread_store = $this->dependencyManager->getDependency('acquia.wip.storage.thread');
      $thread = $thread_store->get($thread_id);
      if (!($thread instanceof Thread)) {
        return;
      }

      // As expected, the thread still exists. Remove it from the thread_store.
      $thread_store->remove($thread);
    } catch (\Exception $e) {
      // Ignore.
    }
    $this->dispatchComplete($task_id, $thread_id, $logger);

    try {
      /** @var WipPoolStoreInterface $wip_pool_storage */
      $wip_pool_storage = $this->dependencyManager->getDependency('acquia.wip.storage.wippool');
      $task = $wip_pool_storage->get($task_id);

      // Set the task back to waiting.
      $task->setStatus(TaskStatus::WAITING);
      $wip_pool_storage->save($task);
    } catch (\Exception $e) {
      $logger->log(WipLogLevel::ERROR, sprintf('Unable to set task %d back to WAITING', $task_id), $task_id);
    }
  }

  /**
   * Exits the task with the specified error message.
   *
   * The wip_pool row update lock must be held during the execution of this
   * method.
   *
   * @param TaskInterface $task
   *   The task.
   * @param int $thread_id
   *   The thread ID.
   * @param string $exit_message
   *   The exit message that will be set into the wip_pool row.
   * @param string $log_message
   *   The log message.
   *
   * @throws \Acquia\Wip\Exception\DependencyMissingException
   *   If a dependency is missing.
   * @throws RowLockException
   *   If the wip_pool row update lock has not been acquired.
   */
  public function exitWithError(TaskInterface $task, $thread_id, $exit_message, $log_message) {
    if (!WipPoolRowLock::getWipPoolRowLock($task->getId(), NULL, $this->dependencyManager)
      ->hasLock()) {
      $message = sprintf('%s must only be called with the wip pool row lock.', __METHOD__);
      throw new RowLockException($message);
    }
    $logger = WipLog::getWipLog();
    /** @var WipPoolStoreInterface $wip_pool_storage */
    $wip_pool_storage = $this->dependencyManager->getDependency('acquia.wip.storage.wippool');
    /** @var NotificationInterface $notifier */
    $notifier = $this->dependencyManager->getDependency('acquia.wip.notification');

    // Set the task as complete.
    $task->setStatus(TaskStatus::COMPLETE);
    $task->setExitStatus(TaskExitStatus::ERROR_SYSTEM);
    $task->setExitMessage($exit_message);
    $task->setCompletedTimestamp(time());
    $wip_pool_storage->save($task);
    $wip_pool_storage->stopProgress($task);

    $logger->log(WipLogLevel::ERROR, $log_message, $task->getId());
    $notification_type = IteratorStatus::getLabel(IteratorStatus::ERROR_SYSTEM);
    $notifier->notifyError(
      $notification_type,
      $exit_message,
      NotificationSeverity::ERROR,
      array('task' => $task)
    );
    // Also log a user-readable message with less detail.
    $user_message = sprintf('A fatal error occurred while processing the task. Aborting. (%s)', __METHOD__);
    $logger->log(WipLogLevel::ERROR, $user_message, $task->getId(), TRUE);
    try {
      // Set the thread as complete, but keep it in case it's useful for
      // debugging. Note that this may have been released already.
      WipLog::getWipLog()->log(
        WipLogLevel::FATAL,
        sprintf('%s - Process failed, setting thread status to failed for task %d.', __METHOD__, $task->getId())
      );

      // Delete the thread from the thread store.
      $this->releaseThreadById($task->getId(), $thread_id, $logger);
    } catch (\Exception $e) {
      // Ignore.
      WipLog::getWipLog()->log(
        WipLogLevel::FATAL,
        sprintf('%s - Failed to finish thread for task %d.', __METHOD__, $task->getId())
      );
    }
  }

  /**
   * Converts the specified string to an integer.
   *
   * Validation is performed to ensure that the entire string was considered,
   * not just the first character.
   *
   * @param string $str
   *   The string to convert to an integer.
   *
   * @return int
   *   The integer value.
   *
   * @throws \InvalidArgumentException
   *   If the provided string contains anything but a string representation of
   *   an integer.
   */
  private function safeStringToInt($str) {
    $result = intval($str, 10);
    if ((string) $result !== $str) {
      throw new \InvalidArgumentException(
        sprintf(
          'The "str" parameter must be a base 10 integer value; %s was provided instead.',
          $str
        )
      );
    }
    return $result;
  }

  /**
   * Called when the status changes.
   *
   * @param string $process
   *   An indication of which process.
   * @param string $status_change
   *   The status change of the associated process.
   */
  private function statusChange($process, $status_change) {
    printf("<%s %s [%0.3f]>\n", $process, $status_change, microtime(TRUE));
  }

  /**
   * Indicates whether a failure will be simulated during this execution.
   *
   * This will be called only by the parent process and failure instructions
   * will be passed to the child processes.
   *
   * @return bool
   *   TRUE if a failure will be simulated; FALSE otherwise.
   */
  private function willSimulateFailure() {
    $result = FALSE;
    $debug = WipFactory::getBool('$acquia.wip.secure.debug', FALSE);
    $percent_failure = $this->getFailureRate();
    if ($debug && $percent_failure > 0) {
      $value = mt_rand(0, 100);
      if ($value <= $percent_failure) {
        $result = TRUE;
      }
    }
    return $result;
  }

  /**
   * Gets the rate at which failures are forced.
   *
   * @return int
   *   The failure rate expressed as a percent between 0 and 100 inclusive.
   */
  private function getFailureRate() {
    return WipFactory::getInt('$acquia.wip.dispatch.simulate.failure', 0);
  }

  /**
   * Indicates the point at which a failure will be simulated.
   *
   * @return int
   *   A value indicating the failure type.
   */
  private function getSimulatedFailurePosition() {
    return mt_rand(1, 15);
  }

  /**
   * Simulates a failure.
   *
   * The failure will always be fatal.
   *
   * @param int $task_id
   *   The task ID.
   */
  private function simulateFailure($task_id) {
    $wip_log = WipLog::getWipLog();
    $type = mt_rand(1, 3);
    $message = sprintf(
      "Failure rate is set to %d%%.",
      $this->getFailureRate()
    );
    switch ($type) {
      case 1:
        $wip_log->log(
          WipLogLevel::ALERT,
          $message . ' Forcing an out of memory failure.',
          $task_id
        );
        $this->forceOutOfMemoryFailure();
        break;

      case 2:
        $wip_log->log(
          WipLogLevel::ALERT,
          $message . ' Forcing an infinite recursion failure.',
          $task_id
        );
        $this->forceInfiniteRecursionFailure();
        break;

      default:
        $wip_log->log(
          WipLogLevel::ALERT,
          $message . ' Forcing an undefined function call failure.',
          $task_id
        );
        $this->forceCallUndefinedFunctionFailure();
    }
  }

  /**
   * Forces a failure by running out of memory.
   */
  private function forceOutOfMemoryFailure() {
    $str = 'DEADBEEF';
    for ($i = 0; $i < 100; $i++) {
      $str .= $str;
    }
  }

  /**
   * Forces a fatal error through infinite recursion.
   */
  private function forceInfiniteRecursionFailure() {
    $this->forceInfiniteRecursionFailure();
  }

  /**
   * Forces a fatal error by calling a method that does not exist.
   */
  private function forceCallUndefinedFunctionFailure() {
    // Yes, I know. It doesn't exist. The call_user_func doesn't work for this
    // sort of failure because it doesn't force a fatal error.
    $this->doesNotExist();
  }

}
