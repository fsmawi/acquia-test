<?php

namespace Acquia\Wip;

use Acquia\Wip\Signal\WipCompleteSignal;

/**
 * This interface is responsible for interacting with a running Wip task.
 */
interface WipTaskProcessInterface extends WipProcessInterface {

  /**
   * Returns the Wip Task instance associated with the running process.
   *
   * @return Task
   *   The task.
   */
  public function getTask();

  /**
   * Creates an instance of WipTaskResult from the specified signal.
   *
   * @param WipCompleteSignal $signal
   *   The signal.
   * @param WipLogInterface $logger
   *   The logger.
   *
   * @return WipTaskResultInterface
   *   The SshResult instance.
   */
  public function getResultFromSignal(WipCompleteSignal $signal, WipLogInterface $logger);

}
