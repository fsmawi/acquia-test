<?php

namespace Acquia\Wip;

use Acquia\Wip\Signal\SignalInterface;

/**
 * This is the interface used to interpret the state table.
 */
interface StateTableIteratorInterface {

  /**
   * Initializes this iterator with the specified state table.
   *
   * @param WipInterface $obj
   *   The Wip instance that will be iterated over.
   */
  public function initialize(WipInterface $obj);

  /**
   * Causes the state table to be complied.
   */
  public function compileStateTable();

  /**
   * Returns the name of the starting state.
   *
   * @return string
   *   The name of the state table's initial state.
   */
  public function getStartState();

  /**
   * Returns the name of the current state.
   *
   * @return string
   *   The name of the current state in the state table.
   */
  public function getCurrentState();

  /**
   * Returns the WipInterface object that the iterator was initialized with.
   *
   * @return WipInterface
   *   The Wip object.
   */
  public function getWip();

  /**
   * Moves to the next state.
   *
   * This method executes whatever transition method has been associated with
   * the current state and uses the result of that method to determine the new
   * state.
   *
   * A single execution of moveToNextState involves running a transition method
   * followed by executing a state method.
   *
   * @return IteratorResultInterface
   *   The result after moving to the next state.
   */
  public function moveToNextState();

  /**
   * Restarts the associated Wip object.
   *
   * This call causes the iterator to completely reset, and prepare for another
   * run.
   */
  public function restart();

  /**
   * Validates that the Wip object can be executed.
   *
   * @return bool
   *   TRUE if the Wip object can be executed; FALSE otherwise
   */
  public function validate();

  /**
   * Gets the WipLog instance that will be used to log messages.
   *
   * @return WipLogInterface
   *   The WipLog instance.
   */
  public function getWipLog();

  /**
   * Sets the WipLog instance that will be used for all logging.
   *
   * @param WipLogInterface $wip_log
   *   The WipLog instance.
   */
  public function setWipLog(WipLogInterface $wip_log);

  /**
   * Returns the Wip ID associated with this iterator.
   *
   * @return int
   *   The ID.
   */
  public function getId();

  /**
   * Sets the Wip ID associated with this iterator.
   *
   * @param int $id
   *   The Wip ID.
   */
  public function setId($id);

  /**
   * Retrieves any signals destined for the associated Wip instance.
   *
   * @return SignalInterface[]
   *   The signals.
   */
  public function getSignals();

  /**
   * Flags the specified signal as consumed.
   *
   * A consumed signal is considered completed and will not appear in the list
   * returned from the getSignals method.
   *
   * @param SignalInterface $signal
   *   The signal that will be marked as consumed.
   *
   * @return SignalInterface The modified signal.
   *   The modified signal.
   *
   * @throws \RuntimeException
   *   If the specified signal is not associated with this Wip object.
   */
  public function consumeSignal(SignalInterface $signal);

  /**
   * Deletes the specified signal.
   *
   * Deletion removes the signal completely from signal storage such that it
   * cannot be retrieved again.
   *
   * @param SignalInterface $signal
   *   The signal to delete.
   *
   * @return bool
   *   TRUE if the deletion was successful; FALSE otherwise.
   *
   * @throws \RuntimeException
   *   If the specified signal is not associated with this Wip object.
   */
  public function deleteSignal(SignalInterface $signal);

  /**
   * Applies all unconsumed signals for the specified context.
   *
   * Applying a signal results in the signal being marked as consumed.
   *
   * @param WipContextInterface $context
   *   The WipContext instance for which the signals are to be processed.
   *
   * @return int
   *   The number of signals that were applied to the specified context.
   */
  public function processSignals(WipContextInterface $context);

  /**
   * Returns the WipContext instance for the specified state.
   *
   * @param string $state
   *   Optional. The state name associated with the desired WipContext instance.
   *   If not provided, the WipContext associated with the current state will
   *   be returned.
   * @param bool $follow_link
   *   Optional. If TRUE and the associated context is linked to another
   *   context, that link will be followed. If FALSE, the link will not be
   *   followed.
   *
   * @return WipContextInterface
   *   The WipContext instance associated with the specified state.
   */
  public function getWipContext($state = NULL, $follow_link = TRUE);

  /**
   * Sets the final message that will be logged or displayed upon completion.
   *
   * @param string $message
   *   The exit message.
   *
   * @throws \InvalidArgumentException
   *   If the message is not a string.
   */
  public function setExitMessage($message);

  /**
   * Returns the final message that will be logged or displayed upon completion.
   *
   * @return string
   *   The exit message.
   */
  public function getExitMessage();

  /**
   * Sets the exit code, indicating success or failure of the Wip object.
   *
   * @param int $exit_code
   *   The exit code. Must be a value from IteratorResult.
   *
   * @throws \InvalidArgumentException
   *   If the specified exit code is not valid.
   */
  public function setExitCode($exit_code);

  /**
   * Returns the exit code of the Wip object.
   *
   * @return int
   *   The exit code.
   */
  public function getExitCode();

  /**
   * Gets the timer instance associated with this iterator.
   *
   * @return Timer
   *   The timer.
   */
  public function getTimer();

  /**
   * Blends the specified timer data into this instances's timer.
   *
   * @param Timer $timer
   *   The timer.
   */
  public function blendTimerData(Timer $timer);

  /**
   * Adds a recording.
   *
   * @param string $name
   *   The name associated with the recording.
   * @param RecordingInterface $recording
   *   The recording to add.
   *
   * @throws \InvalidArgumentException
   *   If the name is not a string.
   */
  public function addRecording($name, RecordingInterface $recording);

  /**
   * Gets the recordings.
   *
   * @return RecordingInterface[]
   *   The array of recordings
   */
  public function getRecordings();

  /**
   * Determines whether an update of the Wip object is needed.
   *
   * @return bool
   *   TRUE if an update is needed; FALSE otherwise.
   */
  public function needsUpdate();

  /**
   * Updates the Wip object.
   */
  public function update();

  /**
   * Gets all transition IDs for the specified state.
   *
   * @param string $state
   *   The state name.
   *
   * @return string[]
   *   The transition IDs.
   */
  public function getStateTransitionIds($state);

  /**
   * Gets the transition ID for the specified state and transition.
   *
   * @param string $state
   *   The state name.
   * @param string $transition_value
   *   The transition value that identifies the desired transition within the
   *   state.
   *
   * @return string
   *   The transition ID.
   */
  public function getTransitionId($state, $transition_value);

}
