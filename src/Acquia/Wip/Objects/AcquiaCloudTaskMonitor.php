<?php

namespace Acquia\Wip\Objects;

use Acquia\Wip\Environment;
use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\WipContextInterface;

/**
 * Wip object that monitors a Cloud task and sends a signal when completed.
 */
class AcquiaCloudtaskMonitor extends BasicWip {

  /**
   * The version associated with this Wip class.
   */
  const CLASS_VERSION = 1;

  /**
   * The state table that will be executed by this Wip object.
   *
   * @var string
   */
  protected $stateTable = <<<EOT
start {
  *               waitForTask
}

waitForTask:checkAcquiaCloudTaskStatus {
  success         signalSuccess
  wait            waitForTask wait=30 exec=false
  uninitialized   failure
  fail            failure
}

signalSuccess {
  *               finish
}

failure {
  *               finish
  !               finish
}

EOT;

  /**
   * Creates a new AcquiaCloudTaskMonitor instance.
   *
   * @param Environment $env
   *   The environment.
   * @param int $task_id
   *   The Acquia Cloud task ID that will be monitored.
   * @param int $interval
   *   Optional. The interval used to check on the running task, measured in
   *   seconds.
   *
   * @TODO: Need the signal info also.
   */
  public function construct(Environment $env, $task_id, $interval = 30) {
  }

  /**
   * TODO: Fill in the method description.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function waitForTask(WipContextInterface $wip_context) {
  }

  /**
   * TODO: Fill in the method description.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function signalSuccess(WipContextInterface $wip_context) {
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
   *
   * @throws \Exception
   *   Re-throw in the case that $exception is not null.
   */
  public function failure(WipContextInterface $wip_context, \Exception $exception = NULL) {
  }

}
