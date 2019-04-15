<?php

namespace Acquia\Wip\Ssh;

use Acquia\Wip\Signal\SshCompleteSignal;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipProcessInterface;

/**
 * This interface is responsible for interacting with an Ssh process.
 *
 * Instances of this interface result from asynchronous processes only.
 */
interface SshProcessInterface extends WipProcessInterface {

  /**
   * Creates an instance of SshResult from the specified signal.
   *
   * @param SshCompleteSignal $signal
   *   The signal.
   * @param WipLogInterface $logger
   *   The logger.
   *
   * @return SshResultInterface
   *   The SshResult instance.
   */
  public function getResultFromSignal(SshCompleteSignal $signal, WipLogInterface $logger);

  /**
   * Returns the result interpreter for this instance, if provided.
   *
   * @return SshResultInterpreterInterface
   *   The interpreter.
   */
  public function getResultInterpreter();

  /**
   * Sets the result interpreter for this instance.
   *
   * @param SshResultInterpreterInterface $interpreter
   *   The interpreter.
   */
  public function setResultInterpreter(SshResultInterpreterInterface $interpreter);

  /**
   * Checks to see if progress is being made.
   *
   * @param WipLogInterface $wip_log
   *   The logger.
   *
   * @return bool
   *   TRUE if progress can be detected; FALSE otherwise.
   */
  public function makingProgress(WipLogInterface $wip_log);

  /**
   * Fetches the log sizes for the associated Unix process.
   *
   * @param WipLogInterface $wip_log
   *   The logger.
   *
   * @return SshResultInterface
   *   A result instance containing the stdout and stderr from the associated
   *   process.
   */
  public function getLogs(WipLogInterface $wip_log);

  /**
   * Gets the time that the initial failing call to get the request occurred.
   *
   * @return int
   *   The timestamp of the request failure. If the timestamp has not been set
   *   then 0 will be returned.
   */
  public function getMissingSignalFailureTime();

  /**
   * Sets the time that the initial failing call to get the result occurred.
   *
   * It is possible that after a signal has been sent and before it has been
   * processed that there are no logs on the server to indicate the result of
   * the process. When this happens, the only successful policy is to wait
   * until the signal is processed. The signal will contain the result of the
   * SSH call.
   *
   * If a call to get the SshResult from this process fails, this method should
   * be called to indicate the timestamp of the failure. From there an upper
   * limit can be established such that a failure to see the signal within that
   * window would be grounds to fail out the process.
   *
   * @param int $timestamp
   *   The Unix timestamp indicating when the call to fetch the request failed.
   */
  public function setMissingSignalFailureTime($timestamp);

}
