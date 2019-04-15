<?php

namespace Acquia\Wip\Modules\NativeModule;

use Acquia\WipIntegrations\DoctrineORM\WipLogStore;
use Acquia\Wip\Environment;
use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\ExitMessage;
use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\IteratorStatus;
use Acquia\Wip\Storage\BasicServerStore;
use Acquia\Wip\WipContextInterface;
use Acquia\Wip\WipLogEntryInterface;
use Acquia\Wip\WipLogLevel;

/**
 * A simple object to test timing and locking behavior.
 *
 * Some timing issues can manifest as a task being processed more than once.
 * This condition is induced with a very fast, asynchronous task and delaying
 * the status checking and stage completion.
 */
class WipEchoTest extends BasicWip {

  /**
   * The state table that will be executed by this Wip object.
   *
   * @var string
   */
  protected $stateTable = <<<EOT
start {
  *               callEcho
}



callEcho:checkSshStatus {
  success         repeat
  wait            callEcho wait=10 exec=false
  fail            repeat
  uninitialized   callEcho wait=2 max=25
  *               errorFound
  !               errorFound
}



repeat:checkForError {
  success         callEcho wait=1
  complete_count  finish
  complete_time   finish
  fail            errorFound
  !               finish
}

errorFound {
  *               failure
}



failure {
  *               finish
}

terminate {
  *               failure
}
EOT;

  /**
   * The Environment instance, used for SSH calls.
   *
   * @var EnvironmentInterface
   */
  protected $environment = NULL;

  /**
   * The maximum number of iterations that will be executed.
   *
   * @var int
   */
  private $maxIterations = NULL;

  /**
   * The maximum number of seconds this process will execute.
   *
   * @var int
   */
  private $maxRuntime = 300;

  /**
   * The number of iterations that have been executed.
   *
   * @var int
   */
  private $iterations = 0;

  /**
   * The Unix timestamp indicating the time this Wip instance started.
   *
   * @var int
   */
  private $startTime;

  /**
   * Initiates the start state with time, environment, and server info.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function start(WipContextInterface $wip_context) {
    $this->startTime = time();
    $this->environment = Environment::getRuntimeEnvironment();
    $server_store = BasicServerStore::getServerStore($this->dependencyManager);
    $active = $server_store->getActiveServers();
    $servers = [];
    foreach ($active as $web) {
      $servers[] = $web->getHostname();
    }
    $this->environment->setServers($servers);
    parent::start($wip_context);
  }

  /**
   * Makes a short asynchronous SSH call to stress Wip processing.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function callEcho(WipContextInterface $wip_context) {
    $ssh_api = $this->getSshApi();
    $ssh = $this->getSsh('race condition test', $this->environment);
    $process = $ssh->execAsyncCommand("echo 'hello, world!' && sleep 1");
    $ssh_api->setSshProcess($process, $wip_context, $this->getWipLog());
  }

  /**
   * Transitions quickly to callEcho after a successful asynchronous SSH.
   */
  public function repeat() {
    // Execute callEcho again right away to encourage race condition.
    $this->iterations++;
  }

  /**
   * The default failure state in the FSM.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   * @param \Exception $exception
   *   The exception that caused the failure (assuming the failure was caused
   *   by an exception.
   */
  public function failure(WipContextInterface $wip_context, \Exception $exception = NULL) {
    // Dropping the exception because this is not the interesting failure.
  }

  /**
   * Checks for an error in the log.
   *
   * @return string
   *   'success' - No errors in the log.
   *   'fail' - An error was found in the log.
   *   'complete_count' - The number of iterations has been reached with no
   *                      error.
   *   'complete_time' - The execution duration has been reached with no error.
   */
  public function checkForError() {
    $result = 'success';
    $error_log = $this->getErrorLogEntry();
    if (NULL !== $error_log) {
      $result = 'fail';
    } elseif (NULL !== $this->maxIterations && $this->iterations >= $this->maxIterations) {
      $result = 'complete_count';
    } elseif (NULL !== $this->maxRuntime) {
      if (time() - $this->startTime >= $this->maxRuntime) {
        $result = 'complete_time';
      }
    }
    return $result;
  }

  /**
   * Called if an error has been detected.
   */
  public function errorFound() {
    $error_log = $this->getErrorLogEntry();
    $this->setExitMessage(new ExitMessage($error_log->getMessage(), WipLogLevel::FATAL));
    $this->setExitCode(IteratorStatus::ERROR_SYSTEM);
  }

  /**
   * Gets the last error log entry, if it exists.
   *
   * @return WipLogEntryInterface
   *   The last error log entry or NULL if there is no such entry.
   */
  private function getErrorLogEntry() {
    $result = NULL;
    $log_store = WipLogStore::getWipLogStore($this->getDependencyManager());
    $log_entries = $log_store->load($this->getId(), 0, 1, 'DESC', WipLogLevel::ERROR);
    if (count($log_entries) > 0) {
      $result = reset($log_entries);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions($options) {
    if (isset($options->maxIterations)) {
      $this->maxIterations = intval($options->maxIterations);
    }
    if (isset($options->maxRuntime)) {
      $this->maxRuntime = intval($options->maxRuntime);
    }
  }

}
