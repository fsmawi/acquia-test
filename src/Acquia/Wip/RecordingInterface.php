<?php

namespace Acquia\Wip;

/**
 * Provides a generic means of interacting with a Wip execution recording.
 */
interface RecordingInterface {

  /**
   * Generates a text format version of the recording.
   *
   * @return string
   *   The recording in text format.
   */
  public function getTranscript();

  /**
   * Returns a simulation script.
   *
   * This simulation script results in the same path through the Wip object that
   * was recorded, assuming the state table does not change.
   *
   * @return string
   *   The simulation script.
   */
  public function getSimulationScript();

  /**
   * Set the time the Wip object was added.
   *
   * @param int $timestamp
   *   The Unix timestamp indicating when the associated Wip object was added.
   */
  public function setAddTime($timestamp);

  /**
   * Gets the time the Wip object was added.
   *
   * @return int
   *   The Unix timestamp indicating when the associated Wip object was added.
   */
  public function getAddTime();

  /**
   * Set the time the Wip object was started.
   *
   * @param int $timestamp
   *   The Unix timestamp indicating when the associated Wip object was started.
   */
  public function setStartTime($timestamp);

  /**
   * Gets the time the Wip object was started.
   *
   * @return int
   *   The Unix timestamp indicating when the associated Wip object was started.
   */
  public function getStartTime();

  /**
   * Set the time the Wip object was completed.
   *
   * @param int $timestamp
   *   The Unix timestamp indicating when the associated Wip object was
   *   completed.
   */
  public function setEndTime($timestamp);

  /**
   * Gets the time the Wip object was completed.
   *
   * @return int
   *   The Unix timestamp indicating when the associated Wip object was
   *   completed.
   */
  public function getEndTime();

  /**
   * Indicates the differences between two transcripts.
   *
   * The difference will not include changes in start time or other time data;
   * only the difference in flow.
   *
   * @param string $transcript
   *   The transcript.
   *
   * @return string
   *   The differences between the transcript associated with this instance and
   *   the one passed in.
   */
  public function diff($transcript);

  /**
   * Indicates how many transitions were executed during the recording.
   *
   * @return int
   *   The number of transitions.
   */
  public function getTransitionCount();

  /**
   * Gets the states that executed previous to the current state.
   *
   * @return object[]
   *   An object array in which each element is populated with fields 'state',
   *   'exec', and 'timestamp' indicating the previous state.
   */
  public function getPreviousStates();

  /**
   * Gets the transition methods that brought execution to the current state.
   *
   * @return object[]
   *   An object array in which each element is populated with fields 'method',
   *   'value', and 'timestamp' indicating the previous transition method and
   *   the value it returned.
   */
  public function getPreviousTransitions();

}
