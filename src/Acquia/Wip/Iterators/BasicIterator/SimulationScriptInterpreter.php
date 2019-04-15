<?php

namespace Acquia\Wip\Iterators\BasicIterator;

/**
 * Interprets a simulation script.
 *
 * Simulations scripts are meant to exercise the finite state machine in a Wip
 * object.
 */
class SimulationScriptInterpreter implements SimulationInterpreterInterface {

  /**
   * The instructions in the script.
   *
   * @var object[]
   */
  private $instructions = array();

  /**
   * The index into the instructions representing the next step.
   *
   * @var int
   */
  private $instructionPointer = 0;

  /**
   * Creates a new parser for the specified script.
   *
   * @param string $script
   *   The simulation script.
   *
   * @throws \InvalidArgumentException
   *   If the script cannot be parsed.
   */
  public function __construct($script) {
    $this->instructions = $this->parse($script);
    if (empty($this->instructions)) {
      throw new \InvalidArgumentException('Unable to parse the specified simulation.');
    }
  }

  /**
   * Parses the given script.
   *
   * @param string $script
   *   The script to parse.
   *
   * @return array
   *   The parsed script.
   *
   * @throws \InvalidArgumentException
   *   If the script is not a string.
   */
  private function parse($script) {
    if (is_string($script)) {
      $result = array();
      $matches = array();
      // Break the script into state blocks using a regex. Remove all comments.
      $script = preg_replace("/(\\s*\\#[^\\n]*\\n)/", '', $script);

      // Find all of the state blocks.
      preg_match_all("/\\s*(([a-zA-z0-9]+)\\s+{([^}]*)\\s*})/m", $script, $matches, PREG_PATTERN_ORDER);

      foreach ($matches[3] as $index => $transitions) {
        $state = $matches[2][$index];
        $transitions_matches = array();
        preg_match_all("/\\s*['\"]([^'\"]*)['\"]\\s*/m", $transitions, $transitions_matches);

        $obj = new \stdClass();
        $obj->state = $state;
        $obj->instructionPointer = 0;
        $obj->transitions = array();
        foreach ($transitions_matches[1] as $transition_value) {
          $obj->transitions[] = $transition_value;
        }
        $result[$state] = $obj;
      }

      return $result;
    } else {
      throw new \InvalidArgumentException('The script must be a string.');
    }
  }

  /**
   * Resets the instruction pointer to start the simulation over.
   */
  public function reset() {
    foreach ($this->instructions as $state => $obj) {
      $obj->instructionPointer = 0;
    }
  }

  /**
   * Gets the next transition from the simulation.
   *
   * @param string $state
   *   The state for which the transition value should be returned.  If the
   *   state does not match that expected in the instructions a mismatch is
   *   detected and an exception will be thrown.
   *
   * @return string
   *   The transition value.
   *
   * @throws \DomainException
   *   If the next transition value can not be determined.
   * @throws \InvalidArgumentException
   *   If the state name is not a string.
   */
  public function getNextTransitionValue($state) {
    if (is_string($state)) {
      if (!isset($this->instructions[$state])) {
        throw new \DomainException(sprintf('Cannot get the next transition for unknown state %s.', $state));
      }

      $obj = $this->instructions[$state];
      if (count($obj->transitions) >= $obj->instructionPointer) {
        $result = $obj->transitions[$obj->instructionPointer++];
      } else {
        // Repeat the last value in the sequence.
        $result = end($obj->transitions);
      }
      return $result;
    } else {
      throw new \InvalidArgumentException('The state name must be a string.');
    }
  }

}
