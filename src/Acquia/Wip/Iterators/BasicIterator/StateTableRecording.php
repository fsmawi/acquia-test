<?php

namespace Acquia\Wip\Iterators\BasicIterator;

use Acquia\Wip\RecordingInterface;

/**
 * Encapsulates the transcript of a Wip execution.
 *
 * This object records the states and transition methods visited by the
 * execution of a Wip object.  This data can be used to validate that the Wip
 * state table results in the expected sequence.
 */
class StateTableRecording implements RecordingInterface {

  /**
   * Indicates a transcript element that represents a transition between states.
   */
  const TYPE_TRANSITION = 1;

  /**
   * Indicates a transcript element that represents a state.
   */
  const TYPE_STATE = 2;

  /**
   * Contains the set of transitions within the transcript.
   *
   * @var array
   */
  private $transitions = array();

  /**
   * Indicates the time the associated Wip object was added.
   *
   * @var int
   */
  private $addTime = NULL;

  /**
   * Indicates the time the associated Wip object was started.
   *
   * @var int
   */
  private $startTime = NULL;

  /**
   * Indicates the time the associated Wip object completed.
   *
   * @var int
   */
  private $endTime = NULL;

  /**
   * Gets the transcript.
   *
   * @return string
   *   The transcript.
   */
  public function getTranscript() {
    $result = '';
    $previous = NULL;
    $current = NULL;
    $last_transition = NULL;
    if (!empty($this->transitions)) {
      $add = $this->getAddTime();
      if ($add !== NULL) {
        $result = $this->append($result, sprintf('Added: %s', $this->formatDate($add)));
      }
      $start = $this->getStartTime();
      if ($start !== NULL) {
        $result = $this->append($result, sprintf('Started: %s', $this->formatDate($start)));
      }
      $end = $this->getEndTime();
      if ($end !== NULL) {
        $result = $this->append($result, sprintf('Completed: %s', $this->formatDate($end)));
      }

      foreach ($this->transitions as $transition) {
        if ($transition instanceof \stdClass && !empty($transition->type)) {
          if ($transition->type === self::TYPE_STATE) {
            $previous = $current;
            $current = $transition;
            if (!empty($previous) &&
              !empty($current) &&
              NULL !== $last_transition &&
              isset($last_transition->timestamp) &&
              isset($last_transition->value) &&
              isset($previous->state)
            ) {
              $result = $this->append(
                $result,
                sprintf(
                  "%s%s => '%s' => %s",
                  $this->formatElapsedTime($last_transition->timestamp),
                  $previous->state,
                  $last_transition->value,
                  $this->renderState($current)
                )
              );
              $last_transition = NULL;
            }
          } elseif ($transition->type === self::TYPE_TRANSITION) {
            if (NULL !== $last_transition && isset($last_transition->timestamp)) {
              $result = $this->append(
                $result,
                sprintf(
                  "%s => '%s' => %s",
                  $this->formatElapsedTime($last_transition->timestamp),
                  $current->state,
                  $last_transition->value,
                  $current->state
                )
              );
              $last_transition = $transition;
            } else {
              $last_transition = $transition;
            }
          }
        }
      }
    }
    return $result;
  }

  /**
   * Appends new text to the specified text.
   *
   * This method deals with optionally prefixing a newline character if needed.
   *
   * @param string $text
   *   The existing text.
   * @param string $new_text
   *   The text to append.
   *
   * @return string
   *   The resulting text.
   *
   * @throws \InvalidArgumentException
   *   If the text and/or new text are not empty or strings.
   */
  private function append($text, $new_text) {
    if ((is_string($text) || empty($text)) && (is_string($new_text) || empty($new_text))) {
      $separator = '';
      if (!empty($text)) {
        $separator = "\n";
      }
      return $text . $separator . $new_text;
    } else {
      throw new \InvalidArgumentException(
        'The text and new text to append must be empty or strings.'
      );
    }
  }

  /**
   * Formats the specified timestamp as a date.
   *
   * @param int $timestamp
   *   The timestamp.
   *
   * @return string
   *   The formatted date string.
   *
   * @throws \InvalidArgumentException
   *   If the timestamp is not an integer.
   */
  private function formatDate($timestamp) {
    if (is_int($timestamp)) {
      return date(DATE_COOKIE, $timestamp);
    } else {
      throw new \InvalidArgumentException('The timestamp must be an integer.');
    }
  }

  /**
   * Formats the difference between the specified timestamp and the start time.
   *
   * @param int $timestamp
   *   The time.
   *
   * @return string
   *   The formatted elapsed time, with appended spaces.
   *
   * @throws \InvalidArgumentException
   *   If the timestamp is not an integer.
   */
  private function formatElapsedTime($timestamp) {
    if (is_int($timestamp)) {
      $result = '';
      $start = $this->getStartTime();
      if (NULL !== $start) {
        $elapsed_seconds = $timestamp - $start;
        $time = $this->breakdownTime($elapsed_seconds);
        $result = sprintf('%02d:%02d:%02d  ', $time->hours, $time->minutes, $time->seconds);
      }
      return $result;
    } else {
      throw new \InvalidArgumentException('The timestamp must be an integer.');
    }
  }

  /**
   * Breaks elapsed seconds into hours, minutes, seconds.
   *
   * @param int $seconds
   *   The number of seconds.
   *
   * @return object
   *   An object containing hours, minutes, seconds.
   *
   * @throws \InvalidArgumentException
   *   If the $seconds argument is not an integer.
   */
  private function breakdownTime($seconds) {
    if (is_int($seconds)) {
      $remaining_time = $seconds;
      $result = new \stdClass();
      $result->seconds = $remaining_time % 60;
      $remaining_time = ($remaining_time - $result->seconds) / 60;
      $result->minutes = $remaining_time % 60;
      $remaining_time = ($remaining_time - $result->minutes) / 60;
      $result->hours = $remaining_time;
      return $result;
    } else {
      throw new \InvalidArgumentException(
        'The number of seconds must be an integer.'
      );
    }
  }

  /**
   * Returns a displayable state name from the specified state object.
   *
   * @param object $state
   *   The state instance.
   *
   * @return string
   *   The displayable state string.
   *
   * @throws \InvalidArgumentException
   *   If the state is not an object.
   */
  private function renderState($state) {
    if (is_object($state)) {
      $result = $state->state;
      if (isset($state->exec) && FALSE === $state->exec) {
        $result = sprintf('[%s]', $state->state);
      }
      return $result;
    } else {
      throw new \InvalidArgumentException('The state must be an object.');
    }
  }

  /**
   * Gets the simulation script.
   *
   * @return string
   *   the simulation script.
   */
  public function getSimulationScript() {
    $script_data = $this->getScriptData();
    $result = '';
    foreach ($script_data as $state => $transitions) {
      if (!empty($transitions)) {
        if (!empty($result)) {
          $result .= "\n";
        }
        $result .= "{$state} {";
        foreach ($transitions as $value) {
          $result .= "\n  '{$value}'";
        }
        $result .= "\n}\n";
      }
    }
    return $result;
  }

  /**
   * Adds the specified transition to this transcript.
   *
   * @param string $transition_method
   *   The name of the transition method.
   * @param string $transition_value
   *   The value returned from the transition method.
   *
   * @throws \InvalidArgumentException
   *   If the method and/or values are not strings.
   */
  public function addTransition($transition_method, $transition_value) {
    if (is_string($transition_method) && is_string($transition_value)) {
      $transition = new \stdClass();
      $transition->type = self::TYPE_TRANSITION;
      $transition->method = $transition_method;
      $transition->value = $transition_value;
      $transition->timestamp = time();
      $this->transitions[] = $transition;
    } else {
      throw new \InvalidArgumentException(
        'The transition method name and value must be strings.'
      );
    }
  }

  /**
   * Adds the specified state to this transcript.
   *
   * @param string $state
   *   The name of the state.
   * @param bool $exec
   *   Optional. Indicates whether the state was executed.
   *
   * @throws \InvalidArgumentException
   *   If the method and/or values are not strings.
   */
  public function addState($state, $exec = TRUE) {
    if (is_string($state)) {
      $transition = new \stdClass();
      $transition->type = self::TYPE_STATE;
      $transition->state = $state;
      $transition->exec = $exec;
      $transition->timestamp = time();
      $this->transitions[] = $transition;
    } else {
      throw new \InvalidArgumentException('The state name must be a string.');
    }
  }

  /**
   * Gets script data in an internal format.
   *
   * This internal format aggregates all of the transition values in order,
   * grouped by state name.
   *
   * @return array
   *   The script data.
   */
  private function getScriptData() {
    $result = array();
    $current_state = '';
    foreach ($this->transitions as $transition) {
      if ($transition->type === self::TYPE_STATE) {
        $current_state = $transition->state;
        if (!isset($result[$current_state])) {
          $result[$current_state] = array();
        }
      } elseif ($transition->type === self::TYPE_TRANSITION && !empty($current_state)) {
        $result[$current_state][] = $transition->value;
      }
    }
    return $result;
  }

  /**
   * Sets the add time to the given timestamp.
   *
   * @param int $timestamp
   *   The timestamp.
   */
  public function setAddTime($timestamp) {
    if (!is_int($timestamp) || $timestamp <= 0) {
      throw new \InvalidArgumentException('The timestamp parameter must be a positive integer.');
    }
    $this->addTime = $timestamp;
  }

  /**
   * Gets the add time.
   *
   * @return int
   *   The add time.
   */
  public function getAddTime() {
    return $this->addTime;
  }

  /**
   * Sets the start time.
   *
   * @param int $timestamp
   *   The start time timestamp.
   */
  public function setStartTime($timestamp) {
    if (!is_int($timestamp) || $timestamp <= 0) {
      throw new \InvalidArgumentException('The timestamp parameter must be a positive integer.');
    }
    $this->startTime = $timestamp;
  }

  /**
   * Gets the start time.
   *
   * @return int
   *   The start time.
   */
  public function getStartTime() {
    return $this->startTime;
  }

  /**
   * Sets the end time.
   *
   * @param int $timestamp
   *   The end time timestamp.
   */
  public function setEndTime($timestamp) {
    if (!is_int($timestamp) || $timestamp <= 0) {
      throw new \InvalidArgumentException('The timestamp parameter must be a positive integer.');
    }
    $this->endTime = $timestamp;
  }

  /**
   * Gets the end time.
   *
   * @return int
   *   The end time.
   */
  public function getEndTime() {
    return $this->endTime;
  }

  /**
   * Performs a diff on a given transcript and this object's transcript.
   *
   * @param string $transcript
   *   The transcript to diff.
   *
   * @return string
   *   The diff between the files.
   *
   * @throws \InvalidArgumentException
   *   If the transcript is not a string.
   */
  public function diff($transcript) {
    if (is_string($transcript)) {
      $transcript = $this->removeTimeData($transcript);
      $orig_file = $this->writeTmpFile($transcript);
      $this_transcript = $this->removeTimeData($this->getTranscript());
      $new_file = $this->writeTmpFile($this_transcript);
      $diff = shell_exec(sprintf('diff %s %s', escapeshellarg($orig_file), escapeshellarg($new_file)));
      unlink($orig_file);
      unlink($new_file);
      return $diff;
    } else {
      throw new \InvalidArgumentException('The transcript must be a string.');
    }
  }

  /**
   * Writes the specified transcript to a temporary file.
   *
   * @param string $transcript
   *   The transcript.
   *
   * @return string
   *   The path to the resulting file.
   *
   * @throws \InvalidArgumentException
   *   If the transcript is not a string.
   */
  private function writeTmpFile($transcript) {
    if (is_string($transcript)) {
      $result = tempnam(sys_get_temp_dir(), 'transcript');
      file_put_contents($result, trim($transcript));
      return $result;
    } else {
      throw new \InvalidArgumentException('The transcript must be a string.');
    }
  }

  /**
   * Removes the time data from the specified transcript text.
   *
   * The removal of time data is critical for being able to identify the
   * differences between two transcripts.  The time data will always be
   * different and thus should not be considered important.
   *
   * @param string $transcript
   *   The transcript.
   *
   * @return string
   *   The transcript without time data.
   *
   * @throws \InvalidArgumentException
   *   If the transcript is not a string.
   */
  public function removeTimeData($transcript) {
    if (is_string($transcript)) {
      $result = preg_replace('/^\s*[A-Za-z]+:.*\n/m', '', $transcript);
      $result = preg_replace('/^\d+:\d+:\d+  /m', '', $result);
      return $result;
    } else {
      throw new \InvalidArgumentException('The transcript must be a string.');
    }
  }

  /**
   * Gets the transition count.
   *
   * @return int
   *   The transition count.
   */
  public function getTransitionCount() {
    $result = 0;
    foreach ($this->transitions as $transition) {
      if (isset($transition->type) && self::TYPE_TRANSITION === $transition->type) {
        $result++;
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviousStates() {
    return $this->getPreviousElements(self::TYPE_STATE);
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviousTransitions() {
    return $this->getPreviousElements(self::TYPE_TRANSITION);
  }

  /**
   * Gets all previous elements of the specified type.
   *
   * @param int $element_type
   *   The type of elements to fetch. Must be self::TYPE_STATE or
   *   self::TYPE_TRANSITION.
   *
   * @return object[]
   *   Previous elements of the specified type arranged in reverse time order.
   */
  private function getPreviousElements($element_type) {
    if ($element_type !== self::TYPE_STATE && $element_type !== self::TYPE_TRANSITION) {
      throw new \InvalidArgumentException('The "element_type" parameter must indicate a state or a transition.');
    }
    $result = array();
    for ($index = count($this->transitions) - 1; $index >= 0; $index--) {
      $transition = $this->transitions[$index];
      if (is_object($transition) && isset($transition->type) && $transition->type == $element_type) {
        $result[] = clone $this->transitions[$index];
      }
    }
    return $result;
  }

}
