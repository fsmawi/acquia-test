<?php

namespace Acquia\Wip\Modules\NativeModule;

use Acquia\Wip\ExitMessage;
use Acquia\Wip\Implementation\ContainerWip;
use Acquia\Wip\IteratorStatus;
use Acquia\Wip\WipContextInterface;
use Acquia\Wip\WipLogLevel;

/**
 * A class that tests ContainerWip with basic arguments.
 *
 * This class provides enough arguments to exercise the state table segments
 * provided by the ContainerWip object.
 */
class ContainerCanary extends ContainerWip {

  /**
   * The version associated with this Wip class.
   */
  const CLASS_VERSION = 1;

  /**
   * The group title for the class.
   */
  const GROUP_TITLE = 'ContainerCanary';

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return self::GROUP_TITLE;
  }

  /**
   * {@inheritdoc}
   */
  public function generateWorkId() {
    return sha1(uniqid());
  }

  /**
   * {@inheritdoc}
   */
  public function getStateTable() {
    // The ContainerWip provides 2 chunks that should be inserted into the
    // state table. The first chunk is used to start the container. In this
    // example, the 'containerStarted' state will be called if starting the
    // container is successful, and the 'containerFailed' state will be called
    // if starting the container is not successful.
    $start_container = $this->getContainerStartTable('containerStarted', 'containerFailed', 'containerTerminated');
    $start_container_state = $this->getContainerStartState();

    // Similarly, this example shows how to get the state table chunk that is
    // responsible for stopping the container.
    $stop_container = $this->getContainerStopTable('containerStopped', 'containerStopFailed');
    $stop_container_state = $this->getContainerStopState();

    // Notice how the start and stop chunks are integrated into the state
    // table. This allows our state table startup or shutdown logic to change
    // without having to rewrite the state table for each subclass.
    return <<<EOT

start {
  *                  $start_container_state
}

$start_container

containerStarted {
  *                  $stop_container_state
}

containerTerminated {
  *                  failure
}

# All failures caused by container launch and initialization and tasks
# performed in this Wip object will end up here. Failure to release a container
# will not invoke this state because that would form an infinite loop. Note
# that after this state all failures must bypass this state to avoid a loop.
containerFailed {
  *                  $stop_container_state
  !                  $stop_container_state
}

$stop_container

failure {
  *                  finish
}

containerStopped {
  *                  finish
  !                  finish
}

containerStopFailed {
  *                  finish
  !                  finish
}

terminate {
  *                  failure
}

EOT;
  }

  /**
   * Logs a user-readable fail message.
   *
   * Note that we must handle logging the generic fail message here instead
   * of in onFinish() like the success case does, because the ContainerDelegate
   * will take over as soon as a fail occurs and we will never reach the finish
   * state in this wip object.
   */
  public function onFail() {
    $message = $this->getExitMessage();
    if (empty($message)) {
      $message = new ExitMessage('The ContainerCanary task has failed.', WipLogLevel::FATAL);
      $this->setExitMessage($message);
    }
    $this->log($message->getLogLevel(), $message->getLogMessage(), TRUE);
    $this->cleanUp();
  }

  /**
   * The state in the FSM that indicates a successful run.
   */
  public function containerStarted() {
    $this->setExitCode(IteratorStatus::OK);
    $this->setExitMessage(new ExitMessage('Successfully completed the ContainerCanary task.'));
  }

  /**
   * Handles the failure state.
   *
   * @param WipContextInterface $wip_context
   *   The current WIP context.
   * @param \Exception|null $exception
   *   The received exception.
   */
  public function containerFailed(WipContextInterface $wip_context, \Exception $exception = NULL) {
    $this->setExitCode(IteratorStatus::ERROR_SYSTEM);
    $message = $detailed_message = 'Failed to complete the ContainerCanary task.';
    if ($exception !== NULL) {
      $detailed_message .= ' Exception message: ' . $exception->getMessage();
    }
    $this->setExitMessage(
      new ExitMessage(
        $message,
        WipLogLevel::FATAL,
        $detailed_message
      )
    );
  }

  /**
   * Called when the container has stopped.
   */
  public function containerStopped() {
    $this->log(WipLogLevel::INFO, 'The container stopped successfully.');
  }

  /**
   * Called when the container failed to stop.
   */
  public function containerStopFailed() {
    if (!IteratorStatus::isError($this->getExitCode())) {
      $this->setExitCode(IteratorStatus::ERROR_SYSTEM);
      $this->setExitMessage(new ExitMessage('Failed to stop the container.', WipLogLevel::ERROR));
    }
  }

  /**
   * Called when the container has been terminated.
   */
  public function containerTerminated() {
    $this->setExitCode(IteratorStatus::TERMINATED);
    $this->setExitMessage(new ExitMessage('The ContainerCanary task was terminated.', WipLogLevel::FATAL));
  }

}
