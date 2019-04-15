<?php

namespace Acquia\Wip\Iterators\BasicIterator;

/**
 * Interprets a simulation script.
 *
 * Simulations scripts are meant to exercise the finite state machine in a Wip
 * object.
 */
class RandomSimulationInterpreter implements SimulationInterpreterInterface {

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
   */
  public function __construct($script) {
    $this->instructions = $this->parse($script);
  }

  /**
   * Parses the specified script.
   *
   * @param string $script
   *   The simulation script in text form.
   *
   * @return object[]
   *   The simulation script in object form.
   *
   * @throws \InvalidArgumentException
   *   If the script is not a string.
   */
  private function parse($script) {
    if (is_string($script)) {
      $result = array();
      $lines = explode("\n", $script);
      for ($index = 0; $index < count($lines); $index++) {
        $line = trim($lines[$index]);
        if (empty($line) || strpos($line, '#') === 0) {
          // Ignore this line.
        } else {
          $matches = array();
          if (1 === preg_match('/^([^\s]+)(?:\s+([^\s]+))?$/s', $line, $matches)) {
            $obj = new \stdClass();
            $obj->state = $matches[1];
            if (count($matches) >= 3) {
              $obj->transitionValue = $matches[2];
            } else {
              $obj->transitionValue = ' ';
            }
            $result[] = $obj;
          } else {
            throw new \InvalidArgumentException(
              sprintf(
                'The script parameter does not include a valid script. Error parsing the script on line %d',
                $index + 1
              )
            );
          }
        }
      }
      if (empty($result)) {
        throw new \InvalidArgumentException('The script parameter does not include instructions.');
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
    $this->instructionPointer = 0;
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
   * @throws \InvalidArgumentException
   *   If the state name is not a string.
   */
  public function getNextTransitionValue($state) {
    if (is_string($state)) {
      $result = '';
      $instruction = $this->instructions[$this->instructionPointer++];
      if ($instruction->state === $state) {
        if (!empty($instruction->transitionValue)) {
          $result = $instruction->transitionValue;
        }
      } else {
        throw new \RuntimeException(sprintf('The simulation script does not match the state sequence.'));
      }
      return $result;
    } else {
      throw new \InvalidArgumentException('The state name must be a string.');
    }
  }

}
