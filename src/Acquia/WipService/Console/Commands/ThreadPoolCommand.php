<?php

namespace Acquia\WipService\Console\Commands;

use Acquia\WipService\App;
use Acquia\WipService\Console\WipConsoleCommand;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\LockInterface;
use Acquia\Wip\Notification\NotificationInterface;
use Acquia\Wip\Notification\NotificationSeverity;
use Acquia\Wip\Runtime\ThreadPool;
use Acquia\Wip\WipLogLevel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Defines a console command for processing tasks.
 */
class ThreadPoolCommand extends WipConsoleCommand {

  /**
   * The name of this process - only used for making logging more readable.
   */
  const PROCESS_NAME = 'threadpoolprocess';

  /**
   * The name of the lock for this process.
   */
  const LOCK_NAME = 'threadpool.process';

  /**
   * Number of seconds to keep the lock beyond the process lifetime.
   *
   * This should be a very small number of seconds that we'll continue to hold
   * the lock beyond the amount of time we expect (the expectation is the
   * lifetime of this process), just for safety so that we don't lose the lock
   * whilst still processing.  We need to allow just a small amount of time in
   * case we had just checked the lock, and proceeded to process as the lock was
   * about to expire.  This is the amount of time that it might take to complete
   * one loop.  It is also ok to add more time here, as we will explicitly
   * release the lock when this process finishes.
   */
  const LOCK_TIMEOUT_BUFFER = 5;

  /**
   * Connect id.
   *
   * @var string
   */
  private $dbConnectionId;

  /**
   * The output interface.
   *
   * @var Output
   */
  private $output;

  /**
   * The ThreadPool object this process is running.
   *
   * @var ThreadPool
   */
  private $pool;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $help = <<<EOT
This command processes tasks in the wip_pool table that are not currently in a finished state. Note this triggers a
single iteration of processing tasks.
EOT;

    $this->setName('process-tasks')
      ->setHelp($help)
      ->setDescription('Process tasks in the wip pool.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    set_error_handler(array($this, 'handleError'));

    /** @var NotificationInterface $notifier */
    $notifier = $this->dependencyManager->getDependency('acquia.wip.notification');

    try {
      $this->output($output, 'Processing WIP tasks...');
      $this->output($output, sprintf(
        'DB connection ID of this process is %s. Use this if needed to kill locks held by this process in MySQL.',
        $this->dbConnectionId
      ));

      // @todo - need to handle signals during the main loop so that we can
      // exit gracefully.  This means that stopping the daemon would also stop this
      // child proc.
      $this->pool = $this->dependencyManager->getDependency('acquia.wip.threadpool');
      $this->pool->setDirectoryPrefix($this->getAppDirectory());
      $this->pool->setTimeLimit(App::getApp()['config.global']['threadpool.timeout']);
      $this->pool->setStatusCheckCallback(array($this, 'checkLockStatus'));
      $this->pool->process();
    } catch (\Exception $e) {
      $this->output($output, sprintf(
        'Exception thrown while processing tasks: Message: %s; Stacktrace: %s',
        $e->getMessage(),
        $e->getTraceAsString()
      ));
      $notifier->notifyException($e, NotificationSeverity::ERROR);
    }
    $this->releaseLock();
    restore_error_handler();
    $this->output($output, 'WIP tasks done. Exiting.');
  }

  /**
   * Sends messages to the output interface.
   *
   * @param OutputInterface $output
   *   The output interface.
   * @param string $message
   *   The message to send.
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
    $this->getLock();
    $this->output = $output;

    /** @var EntityManagerInterface $em */
    $em = App::getEntityManager();
    $rsm = new ResultSetMapping();
    $rsm->addScalarResult('id', 'id');
    $query = $em->createNativeQuery('SELECT CONNECTION_ID() AS id', $rsm);
    $result = $query->getResult();
    $this->dbConnectionId = reset($result)['id'];

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
        'WIP thread pool task process received signal: %d',
        $signal
      ));
    }
    switch ($signal) {
      case SIGTERM:
      case SIGINT:
      case SIGQUIT:
      case SIGABRT:
        // Indicate that processing should stop when possible.
        if (isset($this->pool)) {
          $this->pool->stop();
        }
        break;
    }
  }

  /**
   * Catches any errors that occur during the execute method.
   *
   * This method throws an exception that can break the execute method and
   * provide for rapid cleanup and exit.  This can prevent a broken process from
   * holding a lock that would prevent tasks from being processed.
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
    throw new \ErrorException($message, $error_number, 0, $filename, $line_number);
  }

  /**
   * Checks the status of the lock that was acquired for processing.
   *
   * This is intended only for use as a callback function implementation for
   * ThreadPool::systemStatusOk().
   *
   * @return bool
   *   TRUE if a previously held lock is still held by this process.
   */
  public function checkLockStatus() {
    /** @var LockInterface $lock_manager */
    $lock_manager = $this->dependencyManager->getDependency('acquia.wip.lock.global');
    return $lock_manager->isMine(self::LOCK_NAME);
  }

  /**
   * Attempts to obtain a global exclusive lock for processing some WIP tasks.
   */
  private function getLock() {
    /** @var LockInterface $lock_manager */
    $lock_manager = $this->dependencyManager->getDependency('acquia.wip.lock.global');
    // Lock timeout is 5 seconds longer than the threadpool timeout, to allow
    // for one more loop before we lose the lock.  We manually release the lock
    // at the end of a run anyway, so we want to ensure as far as possible that
    // we don't lose the lock.
    $timeout = App::getApp()['config.global']['threadpool.timeout'] + self::LOCK_TIMEOUT_BUFFER;
    $lock = $lock_manager->acquire(self::LOCK_NAME, $timeout);

    if (!$lock) {
      // @todo - attempt to recover from this,
      throw new \RuntimeException(
        'Unable to acquire mutex lock to run WIP thread pool - aborting.'
      );
    }
  }

  /**
   * Releases the lock held by this process.
   */
  private function releaseLock() {
    /** @var LockInterface $lock_manager */
    $lock_manager = $this->dependencyManager->getDependency('acquia.wip.lock.global');
    // @todo - handle fails: warn.
    $lock_manager->release(self::LOCK_NAME);
  }

}
