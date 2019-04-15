<?php

namespace Acquia\Wip;

use Acquia\Wip\Signal\SshCompleteSignal;
use Acquia\Wip\Signal\SshSignalInterface;
use Acquia\Wip\Ssh\SshProcessInterface;
use Acquia\Wip\Ssh\SshResultInterface;

/**
 * The WipSshInterface describes the integration of Ssh into Wip tasks.
 */
interface WipSshInterface {

  /**
   * Clears all Ssh results from the specified context.
   *
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the Ssh results are recorded.
   * @param WipLogInterface $logger
   *   The logger.
   */
  public function clearSshResults(WipContextInterface $context, WipLogInterface $logger);

  /**
   * Sets the specified Ssh result as the only result in the context.
   *
   * @param SshResultInterface $result
   *   The result.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where Ssh results are recorded.
   * @param WipLogInterface $logger
   *   The logger.
   */
  public function setSshResult(SshResultInterface $result, WipContextInterface $context, WipLogInterface $logger);

  /**
   * Adds the specified Ssh result.
   *
   * @param SshResultInterface $result
   *   The Ssh result.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the Ssh result will be recorded.
   */
  public function addSshResult(SshResultInterface $result, WipContextInterface $context);

  /**
   * Removes the specified Ssh result from the context.
   *
   * @param SshResultInterface $result
   *   The Ssh result to remove.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the Ssh result will be recorded.
   */
  public function removeSshResult(SshResultInterface $result, WipContextInterface $context);

  /**
   * Returns the set of Ssh results in the specified context.
   *
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the Ssh results are recorded.
   *
   * @return SshResultInterface[]
   *   The array of results.
   */
  public function getSshResults(WipContextInterface $context);

  /**
   * Clears all Ssh processes from the specified context.
   *
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the Ssh processes are recorded.
   * @param WipLogInterface $logger
   *   The logger.
   */
  public function clearSshProcesses(WipContextInterface $context, WipLogInterface $logger);

  /**
   * Sets the specified Ssh process as the only process in the context.
   *
   * @param SshProcessInterface $process
   *   The process.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the Ssh processes are recorded.
   * @param WipLogInterface $logger
   *   The logger.
   */
  public function setSshProcess(SshProcessInterface $process, WipContextInterface $context, WipLogInterface $logger);

  /**
   * Adds the specified Ssh process.
   *
   * As the processes complete, they will be removed and converted into results.
   *
   * @param SshProcessInterface $process
   *   The Ssh process.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the Ssh process will be recorded.
   */
  public function addSshProcess(SshProcessInterface $process, WipContextInterface $context);

  /**
   * Removes the specified Ssh process from the context.
   *
   * @param SshProcessInterface $process
   *   The Ssh process to remove.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the Ssh result will be recorded.
   * @param WipLogInterface $logger
   *   The logger.
   */
  public function removeSshProcess(SshProcessInterface $process, WipContextInterface $context, WipLogInterface $logger);

  /**
   * Returns the set of Ssh processes in the specified context.
   *
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the Ssh processes are recorded.
   *
   * @return SshProcessInterface[]
   *   The array of processes.
   */
  public function getSshProcesses(WipContextInterface $context);

  /**
   * Returns the Ssh process associated with the specified ID.
   *
   * @param int $id
   *   The process ID.
   * @param WipContextInterface $context
   *   The WipContextInterface instance where the Ssh processes are recorded.
   *
   * @return SshProcessInterface|null
   *   The Ssh process, or NULL if there is no process associated with the
   *   specified ID.
   */
  public function getSshProcess($id, WipContextInterface $context);

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
   */
  public function getSshStatus(WipContextInterface $context, WipLogInterface $logger);

  /**
   * Processes the specified signal.
   *
   * @param SshSignalInterface $signal
   *   The signal.
   * @param WipContextInterface $context
   *   The context.
   * @param WipLogInterface $logger
   *   The logger.
   */
  public function processSignal(SshSignalInterface $signal, WipContextInterface $context, WipLogInterface $logger);

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
  );

}
