<?php

namespace Acquia\Wip\Iterators\BasicIterator;

use Acquia\Wip\Exception\MissingTransitionBlockException;
use Acquia\Wip\WipInterface;
use ReflectionMethod;

/**
 * The StateMachineValidator checks for errors within a StateMachine.
 */
class StateMachineValidator {

  /**
   * Holds any issues found during validation.
   *
   * @var ValidationResult
   */
  private $issues = NULL;

  /**
   * Creates a new instance of StateMachineValidator.
   */
  public function __construct() {
  }

  /**
   * Validates the specified state machine.
   *
   * @param StateMachine $state_machine
   *   The parsed state machine.
   * @param string $state_table
   *   Optional.  The state table.
   * @param WipInterface $wip_obj
   *   Optional. If provided, the state and transition methods will be verified.
   *
   * @return ValidationResult
   *   The set of issues found in the state table.
   */
  public function validate(StateMachine $state_machine, $state_table = NULL, WipInterface $wip_obj = NULL) {
    $this->issues = new ValidationResult($state_machine);
    if (!empty($state_table)) {
      $this->issues->setStateTable($state_table);
    }
    $this->validateAllPaths($state_machine);
    if ($wip_obj !== NULL) {
      $this->issues->setUsedValidationObject(TRUE);
      $this->validateAllMethods($state_machine, $wip_obj);

      // Find all of the legal transition values.
      $class = new \ReflectionClass(get_class($wip_obj));
      $all_states = $state_machine->getAllStates();
      foreach ($all_states as $state) {
        $transition_block = $state_machine->getTransitionBlock($state);
        $transition_method = $transition_block->getTransitionMethod();
        if (method_exists($wip_obj, $transition_method)) {
          $method = $class->getMethod($transition_method);
          $this->loadAvailableTransitionValues($state, $method);
        }
      }
    }
    return $this->issues;
  }

  /**
   * Ensures all referenced transition blocks exist and all states get to finish.
   *
   * @param StateMachine $state_machine
   *   The parsed state machine.
   */
  private function validateAllPaths(StateMachine $state_machine) {
    $states = $state_machine->getAllStates();
    if (!in_array('failure', $states)) {
      $this->issues->addMissingStateMethod('failure');
    }
    if (!in_array('terminate', $states)) {
      $this->issues->addMissingStateMethod('terminate');
    }
    foreach ($states as $state) {
      if (!$this->verifyPathToFinish($state_machine, $state)) {
        $this->issues->addMissingPath($state);
      }
      $this->identifySpinTransitions($state_machine, $state);
    }
  }

  /**
   * Verifies there is a path from the specified state to finish.
   *
   * @param StateMachine $state_machine
   *   The state machine.
   * @param string $start_state
   *   The starting state.
   * @param string[] $states_being_verified
   *   Optional. Used for internal processing to detect cycles and prevent them
   *   from preventing a successful path verification.
   *
   * @return bool
   *   TRUE if there is a path from the specified state to finish; FALSE
   *   otherwise.
   */
  private function verifyPathToFinish(StateMachine $state_machine, $start_state, $states_being_verified = array()) {
    $result = FALSE;
    if ($start_state === 'finish') {
      return TRUE;
    }
    $states_being_verified[] = $start_state;
    $transition_block = $state_machine->getTransitionBlock($start_state);
    foreach ($transition_block->getAllTransitionValues() as $value) {
      $this->issues->addUsedTransition($start_state, $value);
      $transition = $transition_block->getTransition($value);
      $next_state = $transition->getState();
      if ($next_state !== 'finish' && !in_array($next_state, $state_machine->getAllStates())) {
        $this->issues->addMissingBlock($next_state);
      }
      if (in_array($next_state, $states_being_verified)) {
        continue;
      }
      try {
        if ($this->verifyPathToFinish($state_machine, $next_state, $states_being_verified)) {
          $result = TRUE;
        }
      } catch (MissingTransitionBlockException $missing_block) {
        $this->issues->addMissingBlock($missing_block->getBlock());
      }
    }
    return $result;
  }

  /**
   * Identifies transitions from a state to itself with no wait.
   *
   * @param StateMachine $state_machine
   *   The state machine.
   * @param string $state
   *   The name of the state to test.
   */
  private function identifySpinTransitions(StateMachine $state_machine, $state) {
    $transition_block = $state_machine->getTransitionBlock($state);
    foreach ($transition_block->getAllTransitionValues() as $value) {
      $transition = $transition_block->getTransition($value);
      if ($transition->getState() === $state && $transition->getWait() === 0) {
        $this->issues->addSpinTransition($state, $value);
      }
    }
  }

  /**
   * Validates that all state and transition methods exist.
   *
   * @param StateMachine $state_machine
   *   The state machine.
   * @param WipInterface $wip_obj
   *   The wip object that should hold the state and transition methods.
   */
  private function validateAllMethods(StateMachine $state_machine, WipInterface $wip_obj) {
    $states = $state_machine->getAllStates();
    foreach ($states as $state) {
      if (!method_exists($wip_obj, $state)) {
        $this->issues->addMissingStateMethod($state);
      }
    }
    foreach ($state_machine->getAllTransitions() as $transition) {
      if (!method_exists($wip_obj, $transition)) {
        $this->issues->addMissingTransitionMethod($transition);
      }
    }
  }

  /**
   * Extracts the transition values and descriptions from the specified method.
   *
   * @param string $state_name
   *   The name of the state associated with these transition values.
   * @param ReflectionMethod $method
   *   The method object.
   */
  private function loadAvailableTransitionValues($state_name, ReflectionMethod $method) {
    $rm = new \ReflectionMethod($method->class, $method->name);
    $doc = $rm->getDocComment();
    $doc = str_replace(array('*', '/'), '', $doc);

    $matches = array();
    if (1 == preg_match('/(\@return.*)/s', $doc, $matches)) {
      $lines = explode("\n", $matches[1]);
      foreach ($lines as $line) {
        $val_matches = array();
        if (1 == preg_match('/[^\'\"]*[\'\"]([^\'\"]*)[\'\"]\s*-\s*(.*)/', $line, $val_matches)) {
          $this->issues->addAvailableTransitionValue($state_name, $val_matches[1]);
        }
      }
    }
  }

}
