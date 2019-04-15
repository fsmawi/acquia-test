<?php

namespace Acquia\Wip\Implementation;

use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\DependencyManager;
use Acquia\Wip\Exception\ContainerInfoUnavailableException;
use Acquia\Wip\ServiceApi;
use Acquia\Wip\Signal\SignalType;
use Acquia\Wip\Signal\SshCompleteSignal;
use Acquia\Wip\Signal\SshSignalInterface;
use Acquia\Wip\Ssh\SshProcessInterface;
use Acquia\Wip\Ssh\SshResult;
use Acquia\Wip\Ssh\SshResultInterface;
use Acquia\Wip\Storage\SignalStoreInterface;
use Acquia\Wip\WipContextInterface;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipLogLevel;
use Acquia\Wip\WipSshInterface;

/**
 * The SshApi represents the integration of Ssh into Wip tasks.
 */
class SshApi extends ServiceApi implements WipSshInterface, DependencyManagedInterface {

  /**
   * Time to wait for the signal, otherwise get the result directly.
   */
  const WAIT_FOR_SIGNAL = 15;

  /**
   * The time after which to force fail if the result is not available.
   */
  const SIGNAL_TIMEOUT = 120;

  /**
   * The DependencyManager instance.
   *
   * @var DependencyManager
   */
  public $dependencyManager;

  /**
   * Initializes the SshApi instance.
   *
   * @throws \Acquia\Wip\Exception\DependencyTypeException
   *   If any dependencies are not satisfied.
   */
  public function __construct() {
    $this->dependencyManager = new DependencyManager();
    $this->dependencyManager->addDependencies($this->getDependencies());
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.storage.signal' => 'Acquia\Wip\Storage\SignalStoreInterface',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function clearSshResults(WipContextInterface $context, WipLogInterface $logger) {
    $this->clearSshProcesses($context, $logger);
    if (isset($context->ssh)) {
      unset($context->ssh);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setSshResult(SshResultInterface $result, WipContextInterface $context, WipLogInterface $logger) {
    $this->clearSshResults($context, $logger);
    $this->addSshResult($result, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function addSshResult(SshResultInterface $result, WipContextInterface $context) {
    if (!isset($context->ssh)) {
      $context->ssh = new \stdClass();
    }
    if (!isset($context->ssh->results) || !is_array($context->ssh->results)) {
      $context->ssh->results = array();
    }
    $unique_id = $result->getUniqueId();
    if (!in_array($unique_id, $context->ssh->results)) {
      $context->ssh->results[$unique_id] = $result;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSshResults(WipContextInterface $context) {
    $result = array();
    if (isset($context->ssh) && isset($context->ssh->results) && is_array($context->ssh->results)) {
      $result = $context->ssh->results;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function removeSshResult(SshResultInterface $result, WipContextInterface $context) {
    if (isset($context->ssh) && isset($context->ssh->results) && is_array($context->ssh->results)) {
      $unique_id = $result->getUniqueId();
      if (array_key_exists($unique_id, $context->ssh->results)) {
        unset($context->ssh->results[$unique_id]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clearSshProcesses(WipContextInterface $context, WipLogInterface $logger) {
    if (isset($context->ssh) && isset($context->ssh->processes)) {
      if (is_array($context->ssh->processes)) {
        foreach ($context->ssh->processes as $process) {
          $this->releaseServerSideResources($process, $logger);
        }
      }
    }
    if (isset($context->ssh)) {
      unset($context->ssh);
    }
  }

  /**
   * Releases server side resources held by the specified process.
   *
   * @param SshProcessInterface $process
   *   The process.
   * @param WipLogInterface $logger
   *   The logger.
   */
  private function releaseServerSideResources(SshProcessInterface $process, WipLogInterface $logger) {
    if ($process instanceof SshProcessInterface) {
      if (!$process->hasCompleted($logger)) {
        $process->kill($logger);
      } else {
        $process->release($logger);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setSshProcess(SshProcessInterface $process, WipContextInterface $context, WipLogInterface $logger) {
    $this->clearSshProcesses($context, $logger);
    $this->addSshProcess($process, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function addSshProcess(SshProcessInterface $process, WipContextInterface $context) {
    if (!isset($context->ssh)) {
      $context->ssh = new \stdClass();
    }
    if (!isset($context->ssh->processes) || !is_array($context->ssh->processes)) {
      $context->ssh->processes = array();
    }
    $unique_id = $process->getUniqueId();
    if (!in_array($unique_id, $context->ssh->processes)) {
      $context->ssh->processes[$unique_id] = $process;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function removeSshProcess(
    SshProcessInterface $process,
    WipContextInterface $context,
    WipLogInterface $logger
  ) {
    if (isset($context->ssh) && isset($context->ssh->processes) && is_array($context->ssh->processes)) {
      $unique_id = $process->getUniqueId();
      if (array_key_exists($unique_id, $context->ssh->processes)) {
        try {
          $this->releaseServerSideResources($process, $logger);
        } catch (\Exception $e) {
          // Always clear the process so subsequent calls to check process
          // status will succeed despite any cleanup issues.
        }
        unset($context->ssh->processes[$unique_id]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSshProcesses(WipContextInterface $context) {
    $result = array();
    if (isset($context->ssh) && isset($context->ssh->processes) && is_array($context->ssh->processes)) {
      $result = $context->ssh->processes;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getSshProcess($id, WipContextInterface $context) {
    $result = NULL;
    if (isset($context->ssh) && isset($context->ssh->processes) && is_array($context->ssh->processes)) {
      if (array_key_exists($id, $context->ssh->processes)) {
        $result = $context->ssh->processes[$id];
      }
    }
    return $result;
  }

  /**
   * Returns the status of Ssh results and processes in the specified context.
   *
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the Ssh results and/or processes
   *   are stored.
   * @param WipLogInterface $logger
   *   The logger.
   *
   * @return string
   *   'success' - All Ssh calls were completed successfully.
   *   'wait' - One or more Ssh processes are still running.
   *   'uninitialized' - No Ssh results or processes have been added.
   *   'fail' - At least one Ssh call failed.
   *   'ssh_fail' - The Ssh call could not connect.
   *   'no_progress' - An call is still running but no progress detected.
   */
  public function getSshStatus(WipContextInterface $context, WipLogInterface $logger) {
    $result = 'uninitialized';
    // Processing signals will automatically convert any completed process
    // objects into result objects.
    $context->processSignals();
    // Verify all processes have completed.
    $processes = $this->getSshProcesses($context);
    foreach ($processes as $id => $process) {
      if ($process instanceof SshProcessInterface) {
        if (!$process->hasCompleted($logger)) {
          if (!$this->runningTooLong($process, $logger)) {
            try {
              if ($context->getReportOnNoProgress() && !$process->makingProgress($logger)) {
                $result = 'no_progress';
                break;
              }
            } catch (\Exception $e) {
              throw new ContainerInfoUnavailableException($e->getMessage(), $e->getCode(), $e);
            }
            $result = 'wait';
            break;
          }
          // @todo: Fail this process out; it has taken too long.
          // $process->forceFail($logger);
        }

        // The process has completed and the signal has not been received. Wait a bit.
        $signal_missing_timestamp = $process->getMissingSignalFailureTime();
        $signal_timeout = WipFactory::getInt('$acquia.wip.ssh.signal.wait', self::WAIT_FOR_SIGNAL);
        if ($signal_missing_timestamp === 0) {
          $logger->log(
            WipLogLevel::DEBUG,
            sprintf("Missing signal failure - starting to wait for the signal."),
            $process->getWipId()
          );
          $process->setMissingSignalFailureTime(time());
          $result = 'wait';
          break;
        } elseif (time() - $signal_missing_timestamp < $signal_timeout) {
          $logger->log(
            WipLogLevel::DEBUG,
            sprintf(
              "Missing signal failure - have been waiting %d seconds now for the signal.",
              time() - $signal_missing_timestamp
            ),
            $process->getWipId()
          );
          $result = 'wait';
          break;
        }

        /** @var SshResult $ssh_result */
        $ssh_result = NULL;
        $result_timeout = WipFactory::getInt('$acquia.wip.ssh.result.timeout', self::SIGNAL_TIMEOUT);
        try {
          $ssh_result = $process->getResult($logger, TRUE);
        } catch (\Exception $e) {
          // The signal may still have not been received. When the signal is
          // sent the logs are removed, making it impossible to get the data.
          // Try waiting for the signal to come in.
          if (time() - $signal_missing_timestamp < $result_timeout) {
            $logger->log(
              WipLogLevel::DEBUG,
              sprintf(
                "Missing signal failure and tried to get the result - have been waiting %d seconds now for the signal.",
                time() - $signal_missing_timestamp
              ),
              $process->getWipId()
            );
            $result = 'wait';
            break;
          }
          $message = sprintf(
            'Requested the result of the asynchronous SSH process but the result was unavailable. Possibly the asynchronous process sent a signal and removed its results. Error: %s',
            $e->getMessage()
          );
          $logger->log(
            WipLogLevel::ERROR,
            $message,
            $process->getWipId()
          );
          $process->forceFail($message, $logger);
          $ssh_result = $process->getResult($logger, FALSE);
        }
        if (!empty($ssh_result)) {
          if ($ssh_result->isSuccess()) {
            // Record the run length of all successful tasks.
            $environment = $ssh_result->getEnvironment();
            if (!empty($environment)) {
              $run_time = $ssh_result->getRuntime();
              $this->recordProcessRuntime('ssh', $environment->getSitegroup(), $run_time);
            }
          }
          // This process completed; convert it to a result.
          $this->addSshResult($ssh_result, $context);
          $this->removeSshProcess($process, $context, $logger);
          $log_level = WipLogLevel::WARN;
          if ($ssh_result->isSuccess()) {
            $log_level = $ssh_result->getLogLevel();
          }
          $logger->multiLog(
            $context->getObjectId(),
            $log_level,
            sprintf(
              'Requested the result of asynchronous SSH process - %s completed in %s seconds',
              $process->getDescription(),
              $ssh_result->getRuntime()
            ),
            WipLogLevel::DEBUG,
            sprintf(
              ' - exit: %s; stdout: %s; stderr: %s, server: %s',
              $ssh_result->getExitCode(),
              $ssh_result->getSecureStdout(),
              $ssh_result->getSecureStderr(),
              $process->getEnvironment()->getCurrentServer()
            )
          );
        }
      }
    }
    // Have all of the processes completed?
    $processes = $this->getSshProcesses($context);
    if (count($processes) == 0) {
      // Inspect all results. Note this only happens if there are no processes
      // still running.
      $ssh_results = $this->getSshResults($context);
      if (count($ssh_results) > 0) {
        $result = 'success';
        foreach ($ssh_results as $id => $ssh_result) {
          if ($ssh_result instanceof SshResultInterface) {
            if (!$ssh_result->isSuccess()) {
              $result = 'fail';
              if ($ssh_result->getExitCode() === 255) {
                $result = 'ssh_fail';
              }
              break;
            }
          }
        }
      }
    }
    return $result;
  }

  /**
   * Gets the signal storage instance to use.
   *
   * @return SignalStoreInterface
   *   The signal store.
   *
   * @throws \Acquia\Wip\Exception\DependencyMissingException
   *   If the SignalStoreInterface implementation could not be found.
   */
  private function getSignalStore() {
    return $this->dependencyManager->getDependency('acquia.wip.storage.signal');
  }

  /**
   * {@inheritdoc}
   */
  public function processSignal(SshSignalInterface $signal, WipContextInterface $context, WipLogInterface $logger) {
    $result = 0;
    if ($signal instanceof SshCompleteSignal && $signal->getType() === SignalType::COMPLETE) {
      $result += $this->processCompletionSignal($signal, $context, $logger);
    }
    return $result;
  }

  /**
   * Processes the specified SshCompleteSignal instance.
   *
   * @param SshCompleteSignal $signal
   *   The signal.
   * @param WipContextInterface $context
   *   The context.
   * @param WipLogInterface $logger
   *   The logger.
   *
   * @return int
   *   0 if the specified signal was not processed; 1 otherwise.
   */
  public function processCompletionSignal(
    SshCompleteSignal $signal,
    WipContextInterface $context,
    WipLogInterface $logger
  ) {
    $result = 0;
    $obj = $signal->getData();
    if (!empty($obj) && isset($obj->server) && isset($obj->pid) && isset($obj->startTime)) {
      $unique_id = SshResult::createUniqueId($obj->server, $obj->pid, $obj->startTime);
      $process = $this->getSshProcess($unique_id, $context);
      if (!empty($process) && $process instanceof SshProcessInterface) {
        // This process completed; convert it to a result.
        $ssh_result = $process->getResultFromSignal($signal, $logger);
        $this->addSshResult($ssh_result, $context);
        $this->removeSshProcess($process, $context, $logger);
        $result = 1;
        $log_level = WipLogLevel::WARN;
        if ($ssh_result->isSuccess()) {
          $log_level = WipLogLevel::TRACE;
        }
        $logger->multiLog(
          $context->getObjectId(),
          $log_level,
          sprintf(
            'Signaled result of asynchronous ssh process - %s completed in %s seconds',
            $process->getDescription(),
            $ssh_result->getRuntime()
          ),
          WipLogLevel::TRACE,
          sprintf(
            ' - exit: %s; stdout: %s; stderr: %s, server: %s',
            $ssh_result->getExitCode(),
            $ssh_result->getSecureStdout(),
            $ssh_result->getSecureStderr(),
            $process->getEnvironment()->getCurrentServer()
          )
        );
        $signal_store = $this->getSignalStore();
        $signal_store->consume($signal);
      } else {
        // Check to see if the result has already been obtained. This will
        // happen for asynchronous SSH calls that take very little time to
        // execute.
        $ssh_results = $this->getSshResults($context);
        foreach ($ssh_results as $ssh_result) {
          if ($ssh_result->getUniqueId() === $unique_id) {
            // Found the result, consume the signal.
            $signal_store = $this->getSignalStore();
            $signal_store->consume($signal);
          }
        }
      }
    }
    return $result;
  }

  /**
   * Determines whether the specified process has been running too long.
   *
   * @param SshProcessInterface $process
   *   The process.
   * @param WipLogInterface $logger
   *   The logger.
   *
   * @return bool
   *   TRUE if the process has been running too long; FALSE otherwise.
   */
  private function runningTooLong(SshProcessInterface $process, WipLogInterface $logger) {
    // @todo: For now don't kill ssh processes due to runtime.
    $result = FALSE;
    return $result;
  }

}
