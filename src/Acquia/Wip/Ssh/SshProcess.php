<?php

namespace Acquia\Wip\Ssh;

use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\Signal\SshCompleteSignal;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipLogLevel;
use Acquia\Wip\WipProcess;
use Acquia\Wip\WipResultInterface;

/**
 * Basic implementation of SshProcessInterface.
 */
class SshProcess extends WipProcess implements SshProcessInterface {

  /**
   * Indicates whether the log files on the server require cleanup.
   *
   * @var bool
   */
  private $logsNeedCleanup = TRUE;

  /**
   * The result interpreter.
   *
   * @var SshResultInterpreterInterface
   */
  private $interpreter = NULL;

  /**
   * Maintains information about process progress.
   *
   * @var array
   */
  private $progressCache = array();

  /**
   * Indicates the timestamp when the initial call to get the result failed.
   *
   * @var int
   */
  private $missingSignalFailureTime = 0;

  /**
   * Creates a new instance of SshProcess.
   *
   * @param EnvironmentInterface $environment
   *   The environment this process is running in.
   * @param string $description
   *   The process description.
   * @param int $pid
   *   The process ID.
   * @param int $start_time
   *   The approximate Unix timestamp indicating when the process started.
   * @param int $id
   *   Optional. The ID associated with the Wip object that owns this process (if
   *   applicable).
   */
  public function __construct(EnvironmentInterface $environment, $description, $pid, $start_time, $id = 0) {
    $this->setEnvironment($environment);
    $this->setDescription($description);
    $this->setPid($pid);
    $this->setStartTime($start_time);
    $this->setWipId($id);
    $this->addSuccessExitCode(0);
  }

  /**
   * Sets the environment.
   *
   * @param EnvironmentInterface $environment
   *   The environment.
   */
  public function setEnvironment(EnvironmentInterface $environment) {
    // Do not allow a change to a new server to break this SshProcess instance.
    $environment = clone $environment;
    $server = $environment->getCurrentServer();
    if (!empty($server)) {
      $environment->setServers(array($server));
    }
    parent::setEnvironment($environment);
  }

  /**
   * Sets the process ID associated with this SshProcess instance.
   *
   * @param int $pid
   *   The process ID.
   *
   * @throws \InvalidArgumentException
   *   If the process ID is not a positive integer.
   */
  public function setPid($pid) {
    if ($pid <= 0) {
      throw new \InvalidArgumentException('The pid argument must be a positive integer.');
    }
    parent::setPid($pid);
  }

  /**
   * Sets the SSH service.
   *
   * @param SshServiceInterface $ssh_service
   *   An implementation of SshServiceInterface.
   */
  public function setSshService(SshServiceInterface $ssh_service) {
    $this->sshService = $ssh_service;
  }

  /**
   * Gets the SSH service.
   *
   * @return SshServiceInterface
   *   An implementation of SshServiceInterface.
   */
  public function getSshService() {
    if (empty($this->sshService)) {
      $this->sshService = new SshService();
    }
    return $this->sshService;
  }

  /**
   * Gets the SSH API instance.
   *
   * @return SshInterface
   *   An implementation of SshInterface.
   */
  public function getSsh() {
    $ssh = new Ssh();
    $environment = $this->getEnvironment();
    $ssh_service = $this->getSshService();
    $ssh_service->setEnvironment($environment);
    $ssh->setSshService($ssh_service);
    return $ssh;
  }

  /**
   * {@inheritdoc}
   */
  public function setResult(WipResultInterface $result) {
    if (!$result instanceof SshResultInterface) {
      throw new \InvalidArgumentException('The result parameter must implement the SshResultInterface.');
    }
    parent::setResult($result);
  }

  /**
   * {@inheritdoc}
   */
  public function getResult(WipLogInterface $wip_log, $fetch = FALSE) {
    $result = parent::getResult($wip_log);
    if (empty($result) && $fetch) {
      $result = NULL;
      $options = sprintf(' --no-logs --pid %s --start-time %s', $this->getPid(), $this->getStartTime());
      $description = sprintf('Get result from process %s [%s]', $this->getPid(), $this->getDescription());
      $environment = $this->getEnvironment();
      $ssh = $this->getSsh()->initialize($environment, $description, $wip_log, $this->getWipId());
      $ssh->setLogLevel($this->getLogLevel());
      $result = $ssh->invokeWrapperOperation('result', '', $options);

      // Getting the results of the asynchronous command clears the log files.
      $this->logsNeedCleanup = FALSE;

      // The result represents the result of getting the result from the
      // remote machine. The desired result is encoded in the stdout.
      $result = $ssh->parseResult($result);
      $result->setSecure($this->isSecure());
      $result->setSuccessExitCodes($this->getSuccessExitCodes());
      try {
        $result->setStartTime($this->getStartTime());
      } catch (\Exception $e) {
        // Ignore.
      }
      $interpreter = $this->getResultInterpreter();
      if (!empty($interpreter)) {
        $result->setResultInterpreter($interpreter);
      }
      $this->setResult($result);

      $log_level = WipLogLevel::WARN;
      if ($result->isSuccess()) {
        $log_level = $this->getLogLevel();
      }
      $time = 0;
      try {
        $time = $result->getRuntime();
      } catch (\Exception $e) {
        // Ignore.
      }
      $wip_log->multiLog(
        $this->getWipId(),
        $log_level,
        sprintf('Get result of asynchronous ssh process - %s completed in %s seconds', $this->getDescription(), $time),
        $this->getDebugLevel(),
        sprintf(
          ' - exit: %s; stdout: %s; stderr: %s, server: %s',
          $result->getExitCode(),
          $result->getSecureStdout(),
          $result->getSecureStderr(),
          $environment->getCurrentServer()
        )
      );
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getResultFromSignal(SshCompleteSignal $signal, WipLogInterface $logger) {
    $result = SshResult::fromObject($signal->getData());
    $result->populateFromProcess($this);
    $this->setResult($result);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function release(WipLogInterface $logger) {
    if (TRUE === $this->logsNeedCleanup) {
      $result = NULL;
      $options = sprintf(' --no-logs --pid %s --start-time %s', $this->getPid(), $this->getStartTime());
      $description = sprintf('Release log files for process %s [%s]', $this->getPid(), $this->getDescription());
      $environment = $this->getEnvironment();
      $ssh = $this->getSsh()->initialize($environment, $description, $logger, $this->getWipId());

      $ssh->invokeWrapperOperation('close', '', $options);
      $this->logsNeedCleanup = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasCompleted(WipLogInterface $logger) {
    $result = parent::hasCompleted($logger);
    $environment = $this->getEnvironment();
    if (!$result) {
      try {
        $options = sprintf(' --no-logs --pid %s --start-time %s', $this->getPid(), $this->getStartTime());
        $description = sprintf('Is process %s still running? [%s]', $this->getPid(), $this->getDescription());
        $ssh = $this->getSsh()->initialize($environment, $description, $logger, $this->getWipId());
        $is_running = $ssh->invokeWrapperOperation('is-running', '', $options);
        if ($is_running->getStdout() === 'yes') {
          $result = FALSE;
        }
        if ($is_running->getStdout() === 'no') {
          $result = TRUE;
        }
        $logger->multiLog(
          $this->getWipId(),
          $this->getLogLevel(),
          sprintf(
            'Asynchronous ssh - %s - Checking process %s, server: %s',
            $this->getDescription(),
            $this->getPid(),
            $environment->getCurrentServer()
          ),
          $this->getDebugLevel(),
          sprintf(
            ' - exit: %s; stdout: %s',
            $is_running->getExitCode(),
            $is_running->getStdout()
          )
        );
      } catch (\Exception $e) {
        $logger->log(
          WipLogLevel::ERROR,
          sprintf(
            'Failed to get status. Asynchronous ssh - %s - checking process %s, server: %s - %s',
            $this->getDescription(),
            $this->getPid(),
            $environment->getCurrentServer(),
            $e->getMessage()
          ),
          $this->getWipId()
        );
        $result = NULL;
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function kill(WipLogInterface $logger) {
    $completed = $this->hasCompleted($logger);
    if (!$completed) {
      $options = sprintf('--no-logs --pid %s --start-time %s', $this->getPid(), $this->getStartTime());
      $description = sprintf('Kill process %s [%s]', $this->getPid(), $this->getDescription());
      $environment = $this->getEnvironment();
      $ssh = $this->getSsh()->initialize($environment, $description, $logger, $this->getWipId());

      // Actually killing the process will take a little time.  This synchronous
      // operation will wait for the kill to complete before continuing.
      $ssh->invokeWrapperOperation('kill', '', $options);

      // Killing the process releases server-side resources.
      $this->logsNeedCleanup = FALSE;
    }
    return $this->hasCompleted($logger);
  }

  /**
   * {@inheritdoc}
   */
  public function getUniqueId() {
    $environment = $this->getEnvironment();
    return sprintf('%s:%d@%d', $environment->getCurrentServer(), $this->getPid(), $this->getStartTime());
  }

  /**
   * {@inheritdoc}
   */
  public function getResultInterpreter() {
    return $this->interpreter;
  }

  /**
   * {@inheritdoc}
   */
  public function setResultInterpreter(SshResultInterpreterInterface $interpreter) {
    $this->interpreter = $interpreter;
  }

  /**
   * Determines the log level for debug log messages.
   *
   * @return int
   *   The log level.
   */
  protected function getDebugLevel() {
    $result = WipLogLevel::DEBUG;
    if ($result < $this->getLogLevel()) {
      $result = $this->getLogLevel();
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function makingProgress(WipLogInterface $wip_log) {
    $result = TRUE;
    if (!$this->hasCompleted($wip_log)) {
      $log_sizes = $this->getLogSizes($wip_log);
      if (!empty($log_sizes)) {
        // Look at each log file size to determine if there is a difference.
        // If there is a difference then some progress is being made. Otherwise
        // assume no progress has been made. Note that this does not work for
        // commands that redirect their output to a file.
        $delta_found = FALSE;
        foreach ($log_sizes as $name => $size) {
          if (!isset($this->progressCache[$name]) ||
            $this->progressCache[$name] != $size) {
            $delta_found = TRUE;
            break;
          }
        }
        $this->progressCache = $log_sizes;
        $result = $delta_found;
      }
    }
    if ($result) {
      $wip_log->log(WipLogLevel::TRACE, 'Progress detected', $this->getWipId());
    } else {
      $wip_log->log(WipLogLevel::ALERT, 'No progress detected', $this->getWipId());
    }

    return $result;
  }

  /**
   * Fetches the log sizes for the associated Unix process.
   *
   * @param WipLogInterface $wip_log
   *   The logger.
   *
   * @return array
   *   An array of file sizes measured in bytes keyed by the file path.
   */
  private function getLogSizes(WipLogInterface $wip_log) {
    $result = array();
    $environment = $this->getEnvironment();
    $description = sprintf('Get the size of the log files for process %d', $this->getPid());
    $ssh = $this->getSsh()->initialize($environment, $description, $wip_log, $this->getWipId());
    $log_dir = sprintf('%s/logs', $environment->getWorkingDir());
    $command = sprintf(
      '\stat -L --format "%%s %%n" %s/%s.out %1$s/%2$s.err',
      $log_dir,
      $this->getPid()
    );
    $log_result = $ssh->execCommand($command);
    if ($log_result->isSuccess()) {
      // The log sizes are of the form "[size in bytes] [filename]".
      $log_sizes = explode("\n", trim($log_result->getStdout()));
      foreach ($log_sizes as $log_size) {
        $matches = array();
        if (1 === preg_match('/^([0-9]+)\s+(.*)$/', $log_size, $matches)) {
          $size = $matches[1];
          $file = $matches[2];
          $result[trim($file)] = intval($size);
        }
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getLogs(WipLogInterface $wip_log) {
    $options = sprintf('--no-logs --pid %s --start-time %s', $this->getPid(), $this->getStartTime());
    $description = sprintf('Get logs for process %s [%s]', $this->getPid(), $this->getDescription());
    $environment = $this->getEnvironment();
    $ssh = $this->getSsh()->initialize($environment, $description, $wip_log, $this->getWipId());
    return $ssh->invokeWrapperOperation('logs', '', $options);
  }

  /**
   * {@inheritdoc}
   */
  public function forceFail($reason, WipLogInterface $logger) {
    try {
      $this->setEndTime(time());
    } catch (\Exception $e) {
      // Ignore.
    };
    // Attempt to get the logs from the process. This will provide the output
    // thus far.
    $result = new SshResult();
    try {
      $log_result = $this->getLogs($logger);
      $log_result->populateFromProcess($this);
      $result->setStdout($log_result->getStdout());
      $result->setStderr($log_result->getStderr());
    } catch (\Exception $e) {
      $logger->log(
        WipLogLevel::ERROR,
        sprintf(
          'Failed to get the logs when force-failing process %s:%d.',
          $this->getPid()
        )
      );
    }
    $result->populateFromProcess($this);
    $result->setExitCode(self::FORCE_FAIL_EXIT_CODE);
    parent::forceFail($reason, $logger);

    $this->setResult($result);
  }

  /**
   * {@inheritdoc}
   */
  public function getMissingSignalFailureTime() {
    return $this->missingSignalFailureTime;
  }

  /**
   * {@inheritdoc}
   */
  public function setMissingSignalFailureTime($timestamp) {
    if (!is_int($timestamp) || $timestamp <= 0) {
      throw new \InvalidArgumentException('The "timestamp" argument must be a non-zero positive integer.');
    }
    $this->missingSignalFailureTime = $timestamp;
  }

}
