<?php

namespace Acquia\Wip\Iterators\BasicIterator;

use Acquia\Wip\Exception\WipParseException;
use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\Implementation\TransitionSequence;

/**
 * Parses StateTable scripts into format accepted by the state table test tool.
 */
class TestScriptParser {
  /**
   * The state table.
   *
   * @var string
   */
  private $stateTable = NULL;

  /**
   * Creates a new instance of TestScriptParser.
   *
   * @param string $state_table
   *   The state table.
   *
   * @throws \InvalidArgumentException
   *   Thrown if an invalid state table is provided.
   */
  public function __construct($state_table) {
    if (!is_string($state_table) || empty($state_table)) {
      throw new \InvalidArgumentException('The $state_table parameter must be a non-empty string.');
    }
    $this->stateTable = $state_table;
  }

  /**
   * Parses the state table and returns a wip object containing transition sequences.
   *
   * @return BasicWip
   *   The wip object that contains transition sequences.
   */
  public function parse() {
    $ret = self::separateStateTable($this->stateTable);
    return $ret;
  }

  /**
   * Parses the transition sequence from the state table.
   *
   * @param string $state_table
   *   The state table.
   *
   * @return BasicWip
   *   The wip object.
   *
   * @throws WipParseException
   *   Thrown if an error occurred during parsing.
   * @throws \InvalidArgumentException
   *   If the state table is not a string.
   */
  public static function separateStateTable($state_table) {
    if (is_string($state_table)) {
      $wip = new BasicWip();

      /** @var TransitionSequence $current_transition_sequence */
      $current_transition_sequence = NULL;

      $lines = explode("\n", $state_table);
      $line_count = count($lines);
      for ($line_number = 1; $line_number <= $line_count; $line_number++) {
        $line = trim($lines[$line_number - 1]);

        // Remove any comments.
        if (strpos($line, '#') !== FALSE) {
          $line = trim(strstr($line, '#', TRUE));
        }
        if (empty($line)) {
          continue;
        }

        $matches = array();
        if (is_null($current_transition_sequence)) {
          // Matches the first line of a state in the format of
          // STATE:TRANSITION.
          if (1 === preg_match('/^([^\s:\{\}]+)\s*(?:[:]\s*([^\s\{\}]+)\s*)?\{$/', $line, $matches)) {
            $state = $matches[1];

            // Change the current TransitionSequence.
            $current_transition_sequence = new TransitionSequence($state);
            $current_transition_sequence->setLineNumber($line_number);
          } else {
            throw new WipParseException(
              'Unrecognized state table input, possibly missing an opening brace',
              $line_number
            );
          }
        } else {
          $matches = array();
          // End of block.
          if (1 === preg_match('/^\}$/', $line, $matches)) {
            $current_transition_sequence = NULL;
          } elseif (1 === preg_match('/^([^\s]+)\s+([^\s\{\}]+)$/', $line, $matches)) {
            // Transitions, which should be in the format of STATUS
            // NUM_TIMES_TO_REPEAT.
            $value = trim($matches[1]);
            $repeats = trim($matches[2]);

            if (!is_numeric($repeats)) {
              throw new WipParseException('Unrecognized argument- not a number', $line_number);
            }

            for ($i = 0; $i < intval($repeats); $i++) {
              $current_transition_sequence->addTransition($value);
            }
          } else {
            throw new WipParseException(
              sprintf(
                'Missing end brace on state %s, starting',
                $current_transition_sequence->getStateName()
              ),
              $current_transition_sequence->getLineNumber()
            );
          }
        }
      }
      if (!empty($current_transition_sequence)) {
        throw new WipParseException(
          sprintf(
            'Missing end brace on state %s',
            $current_transition_sequence->getStateName()
          ),
          $current_transition_sequence->getLineNumber()
        );
      }

      return $wip;
    } else {
      throw new \InvalidArgumentException('The state table must be a string.');
    }
  }

}
