<?php

namespace Acquia\Wip\Iterators\BasicIterator;

use Acquia\Wip\Exception\WipParseException;
use Acquia\Wip\WipInterface;

/**
 * The StateTableParser is a simple DSL parser.
 */
class StateTableParser {

  /**
   * The state table.
   *
   * @var string
   */
  private $stateTable = NULL;

  /**
   * Creates a new instance of StateTableParser.
   *
   * @param string $state_table
   *   The state table.
   *
   * @throws \InvalidArgumentException
   *   If the state_table parameter is empty or not a string.
   */
  public function __construct($state_table) {
    if (!is_string($state_table) || empty($state_table)) {
      throw new \InvalidArgumentException('The $state_table parameter must be a non-empty string.');
    }
    $this->stateTable = $state_table;
  }

  /**
   * Parses the state table and provides transition blocks.
   *
   * @return StateMachine
   *   The state machine that can be validated and interpreted.
   */
  public function parse() {
    $state_machine = new StateMachine($this->stateTable);
    self::separateStateTable($state_machine, $this->stateTable);
    return $state_machine;
  }

  /**
   * Separates the components of the state table.
   *
   * Unless there is a syntax error in the state table, the structures
   * identified in the state table will be applied to the specified
   * state machine.
   *
   * @param StateMachine $state_machine
   *   The StateMachine into which the transition blocks will be set.
   * @param string $state_table
   *   The state table.
   *
   * @throws WipParseException
   *   If the specified state table could not be parsed.
   */
  public static function separateStateTable(StateMachine $state_machine, $state_table) {
    $result = new \stdClass();
    $result->lines = array();
    $result->variables = array();
    $result->transitionBlocks = array();
    /** @var TransitionBlock $current_transition_block */
    $current_transition_block = NULL;
    $lines = explode("\n", $state_table);
    $line_count = count($lines);
    $current_transition_block = NULL;
    for ($line_number = 1; $line_number <= $line_count; $line_number++) {
      $line = trim($lines[$line_number - 1]);
      // Remove any comments.
      if (strpos($line, '#') !== FALSE) {
        $line = trim(strstr($line, '#', TRUE));
      }
      $result->lines[] = $line;
      if (empty($line)) {
        continue;
      }
      $matches = array();
      if (empty($current_transition_block)) {
        // Could open a transition block or configure a variable.
        if (1 === preg_match('/^\s*([a-zA-Z0-9_-]+)\s*=(.+)\s*$/', $line, $matches)) {
          $var = new \stdClass();
          $var->lineNumber = $line_number;
          $var->name = trim($matches[1]);
          $var->value = trim($matches[2]);
          $result->variables[] = $var;
        } elseif (1 === preg_match(
          '/^([^\s:\{\}]+)\s*(?:[:]\s*([^\s\[\{\}]+))?\s*(?:\[([a-zA-Z0-9_-]+)\])?\s*\{$/',
          $line,
          $matches
        )) {
          // Format: "state_name(:transition_name) ([timer_name]) {".
          $state = $matches[1];
          $transition = 'emptyTransition';
          if (count($matches) > 2 && !empty($matches[2])) {
            $transition = $matches[2];
          }
          $timer_name = 'system';
          if (count($matches) > 3 && !empty($matches[3])) {
            $timer_name = $matches[3];
          }
          $current_transition_block = new TransitionBlock($state, $transition, $timer_name);
          $current_transition_block->setLineNumber($line_number);
        } else {
          throw new WipParseException(
            'Unrecognized state table input, possibly missing an opening brace',
            $line_number
          );
        }
      } else {
        $matches = array();
        if (1 === preg_match('/^\}$/', $line, $matches)) {
          $result->transitionBlocks[] = $current_transition_block;
          $current_transition_block = NULL;
        } elseif (1 === preg_match('/^([^\s]+)\s+([^\s\{\}]+)\s*(.+)?$/', $line, $matches)) {
          $value = trim($matches[1]);
          $new_state = trim($matches[2]);
          $wait = 0;
          $max = 0;
          $exec = TRUE;
          // Find any other properties for the transition.
          if (count($matches) > 3) {
            $option_matches = array();
            $option_string = trim($matches[3]);
            do {
              if (1 === preg_match('/\s*([^=]+)=([^\s]+)\s*/', trim($option_string), $option_matches)) {
                $option_string = substr($option_string, strlen($option_matches[0]));
                $property_name = trim($option_matches[1]);
                $property_value = trim($option_matches[2]);
                switch ($property_name) {
                  case 'wait':
                    if (intval($property_value) . '' !== $property_value) {
                      throw new WipParseException(
                        sprintf('Transition property "%s" must have an integer value', $property_name),
                        $line_number
                      );
                    }
                    $wait = intval($property_value);
                    break;

                  case 'max':
                    if (intval($property_value) . '' !== $property_value) {
                      throw new WipParseException(
                        sprintf('Transition property "%s" must have an integer value', $property_name),
                        $line_number
                      );
                    }
                    $max = intval($property_value);
                    break;

                  case 'exec':
                    switch ($property_value) {
                      case 'true':
                        $exec = TRUE;
                        break;

                      case 'false':
                        $exec = FALSE;
                        break;

                      default:
                        throw new WipParseException(
                          'Illegal value for the "exec" option; must be "true" or "false"',
                          $line_number
                        );
                    }
                    break;

                  default:
                    throw new WipParseException(
                      sprintf('Unrecognized transition option "%s"', $property_name),
                      $line_number
                    );
                }
              } else {
                throw new WipParseException('Syntax error', $line_number);
              }
            } while (strlen(trim($option_string)) > 0);
          }
          $transition = new Transition($value, $new_state, $wait, $max, $exec);
          $current_transition_block->addTransition($transition);
          $transition->setLineNumber($line_number);
        } else {
          throw new WipParseException(
            sprintf('Missing end brace on state %s, starting', $current_transition_block->getState()),
            $current_transition_block->getLineNumber()
          );
        }
      }
    }
    if (!empty($current_transition_block)) {
      throw new WipParseException(
        sprintf('Missing end brace on state %s', $current_transition_block->getState()),
        $current_transition_block->getLineNumber()
      );
    }
    foreach ($result->transitionBlocks as $transition_block) {
      $state_machine->addTransitionBlock($transition_block);
    }
  }

  /**
   * Extracts the transition values and descriptions from the specified method.
   *
   * @param WipInterface $wip
   *   The Wip object.
   * @param string $transition_method
   *   The name of the transition method associated with these transition
   *   values.
   *
   * @return array
   *   An array containing the values and descriptions that can be returned from
   *   the specified transition method.
   */
  public static function getAvailableTransitionValues(WipInterface $wip, $transition_method) {
    $result = array();

    $class = new \ReflectionClass(get_class($wip));
    if (!method_exists($wip, $transition_method)) {
      throw new \RuntimeException(
        sprintf(
          'The specified Wip object does not contain transition method %s.',
          $transition_method
        )
      );
    }
    $method = $class->getMethod($transition_method);

    $rm = new \ReflectionMethod($method->class, $method->name);
    $doc = $rm->getDocComment();
    $doc = str_replace(array('*', '/'), '', $doc);

    $matches = array();
    if (1 == preg_match('/(\@return.*)/s', $doc, $matches)) {
      $lines = explode("\n", $matches[1]);
      foreach ($lines as $line) {
        $val_matches = array();
        if (1 == preg_match('/[^\'\"]*[\'\"]([^\'\"]*)[\'\"]\s*\-\s*(.*)/', $line, $val_matches)) {
          $value = trim($val_matches[1]);
          if (!empty($value)) {
            $result[] = $val_matches[1];
          }
        }
      }
    }
    return $result;
  }

}
