<?php

namespace Acquia\Wip\Iterators\BasicIterator;

use Acquia\Wip\ValidationResultInterface;

/**
 * The ValidationResult reveals the flaws encountered during validation.
 */
class ValidationResult implements ValidationResultInterface {

  /**
   * Indicates whether the validation included object validation.
   *
   * @var bool
   */
  private $usedValidationObject = FALSE;

  /**
   * The state table associated with this ValidationResult instance.
   *
   * @var string
   */
  private $stateTable = NULL;

  /**
   * Indicates missing transition blocks.
   *
   * @var string[]
   */
  private $missingBlocks = array();

  /**
   * Indicates states that have no path to the finish state.
   *
   * @var string[]
   */
  private $missingPaths = array();

  /**
   * Indicates transitions from a state to itself with no wait.
   *
   * @var array[]
   */
  private $spinTransitions = array();

  /**
   * States that are referenced in the state table but have no implementation.
   *
   * @var string[]
   */
  private $missingStateMethods = array();

  /**
   * Transition methods referenced in the state table with no implementation.
   *
   * @var string[]
   */
  private $missingTransitionMethods = array();

  /**
   * Indicates what transition values are used for each transition block.
   *
   * @var string[][]
   */
  private $usedTransitionValues = array();

  /**
   * Indicates what transition values are available for each transition block.
   *
   * @var string[][]
   */
  private $availableTransitionValues = array();

  /**
   * The state machine this validation result applies to.
   *
   * @var StateMachine
   */
  private $stateMachine;

  /**
   * Creates a new instance of ValidationResult.
   *
   * @param StateMachine $state_machine
   *   The state machine.
   */
  public function __construct(StateMachine $state_machine) {
    $this->stateMachine = $state_machine;
  }

  /**
   * Gets the state machine this result applies to.
   *
   * @return StateMachine
   *   the state machine.
   */
  public function getStateMachine() {
    return $this->stateMachine;
  }

  /**
   * {@inheritdoc}
   */
  public function hasFailures() {
    $missing_transition_values = $this->getAllMissingTransitionValues();
    $unknown_transition_values = $this->getAllUnknownTransitionValues();
    return (!empty($this->missingBlocks) ||
      !empty($this->missingPaths) ||
      !empty($this->spinTransitions) ||
      !empty($this->missingStateMethods) ||
      !empty($this->missingTransitionMethods) ||
      !empty($missing_transition_values) ||
      !empty($unknown_transition_values));
  }

  /**
   * {@inheritdoc}
   */
  public function getReport() {
    $generator = new ReportGenerator($this);
    return $generator->generate();
  }

  /**
   * Indicates whether a Wip object was used during validation or not.
   *
   * Validation can involve only static analysis of state table contents or it
   * can be used in conjunction with a Wip object so missing methods and misuse
   * of transition values can be reported.
   *
   * @param bool $used_validation_object
   *   TRUE if a validation object was used; FALSE otherwise.
   */
  public function setUsedValidationObject($used_validation_object) {
    if (!is_bool($used_validation_object)) {
      throw new \InvalidArgumentException('The $used_validation_object parameter must be of type boolean.');
    }
    $this->usedValidationObject = $used_validation_object;
  }

  /**
   * Indicates whether a Wip object was used during validation or not.
   *
   * Validation can involve only static analysis of state table contents or it
   * can be used in conjunction with a Wip object so missing methods and misuse
   * of transition values can be reported.
   *
   * @return bool
   *   TRUE if a Wip object was used during validation; FALSE otherwise.
   */
  public function getUsedValidationObject() {
    return $this->usedValidationObject;
  }

  /**
   * Sets the state table into this ValidationResult for reporting purposes.
   *
   * @param string $state_table
   *   The state table.
   */
  public function setStateTable($state_table) {
    $this->stateTable = $state_table;
  }

  /**
   * Gets the state table associated with this ValidationResult instance.
   *
   * @return string
   *   The state table.
   */
  public function getStateTable() {
    return $this->stateTable;
  }

  /**
   * Adds a missing block to the result.
   *
   * @param string $block_name
   *   The name of the missing block to add.
   *
   * @throws \InvalidArgumentException
   *   If the block_name argument is not a string.
   */
  public function addMissingBlock($block_name) {
    if (!is_string($block_name)) {
      throw new \InvalidArgumentException('The $block_name argument must be a string.');
    }
    if (!in_array($block_name, $this->missingBlocks)) {
      $this->missingBlocks[] = $block_name;
    }
  }

  /**
   * Gets missing blocks.
   *
   * @return \string[]
   *   The array of missing blocks.
   */
  public function getMissingBlocks() {
    return $this->missingBlocks;
  }

  /**
   * Adds a missing path to the result.
   *
   * A missing path is a state that has no path to the finish state.
   *
   * @param string $path
   *   The missing path.
   *
   * @throws \InvalidArgumentException
   *   If the path argument is not a string.
   */
  public function addMissingPath($path) {
    if (!is_string($path)) {
      throw new \InvalidArgumentException('The $path argument must be a string.');
    }
    if (!in_array($path, $this->missingPaths)) {
      $this->missingPaths[] = $path;
    }
  }

  /**
   * Gets missing paths.
   *
   * @return \string[]
   *   The array of states missing a path to the finish state.
   */
  public function getMissingPaths() {
    return $this->missingPaths;
  }

  /**
   * Adds a spin transition to the result.
   *
   * A spin transition is a transition that goes from a particular state to
   * itself with no wait value.  Generally this structure is used for polling,
   * but it is always best to wait between checks so other Wip objects can be
   * processed while the asynchronous process is busy.
   *
   * @param string $state
   *   The name of the state.
   * @param string $transition_value
   *   The transition value.
   */
  public function addSpinTransition($state, $transition_value) {
    if (!isset($this->spinTransitions[$state])) {
      $this->spinTransitions[$state] = array();
    }
    if (!in_array($transition_value, $this->spinTransitions[$state])) {
      $this->spinTransitions[$state][] = $transition_value;
    }
  }

  /**
   * Retrieves all detected spin transitions.
   *
   * @return \array[]
   *   The spin transitions.
   */
  public function getSpinTransitions() {
    return $this->spinTransitions;
  }

  /**
   * Adds a method referenced in the state table with no implementation.
   *
   * @param string $method_name
   *   The name of the state method that has no implementation.
   */
  public function addMissingStateMethod($method_name) {
    if (!in_array($method_name, $this->missingStateMethods)) {
      $this->missingStateMethods[] = $method_name;
    }
  }

  /**
   * Returns the set of state methods that have no implementation.
   *
   * @return \string[]
   *   The set of missing state methods.
   */
  public function getMissingStateMethods() {
    return $this->missingStateMethods;
  }

  /**
   * Adds a transition method that has no implementation.
   *
   * @param string $method_name
   *   The name of the missing transition method.
   */
  public function addMissingTransitionMethod($method_name) {
    if (!in_array($method_name, $this->missingTransitionMethods)) {
      $this->missingTransitionMethods[] = $method_name;
    }
  }

  /**
   * Returns the set of transition methods that have no implementation.
   *
   * @return \string[]
   *   The missing transition method names.
   */
  public function getMissingTransitionMethods() {
    return $this->missingTransitionMethods;
  }

  /**
   * Adds a transition value to the specified state.
   *
   * @param string $state_name
   *   The name of the state.
   * @param string $transition_value
   *   The transition value.
   */
  public function addUsedTransition($state_name, $transition_value) {
    if (!isset($this->usedTransitionValues[$state_name])) {
      $this->usedTransitionValues[$state_name] = array();
    }
    if (!in_array($transition_value, $this->usedTransitionValues[$state_name])) {
      $this->usedTransitionValues[$state_name][] = $transition_value;
    }
  }

  /**
   * Adds an available transition value to the specified state.
   *
   * @param string $state_name
   *   The name of the state.
   * @param string $transition_value
   *   The transition value.
   */
  public function addAvailableTransitionValue($state_name, $transition_value) {
    if (!isset($this->availableTransitionValues[$state_name])) {
      $this->availableTransitionValues[$state_name] = array();
    }
    if (!in_array($transition_value, $this->availableTransitionValues[$state_name])) {
      $this->availableTransitionValues[$state_name][] = $transition_value;
    }
  }

  /**
   * Gets the set of available transition values in the specified block.
   *
   * @param string $state_name
   *   The name of the state associated with the transition block.
   *
   * @return string[]
   *   An array of transition values available in the transition block.
   */
  public function getAvailableTransitionValues($state_name) {
    $result = array();
    if (!empty($this->availableTransitionValues[$state_name])) {
      $result = $this->availableTransitionValues[$state_name];
    }
    return $result;
  }

  /**
   * Gets the set of used transition values in the specified block.
   *
   * @param string $state_name
   *   The name of the state associated with the transition block.
   *
   * @return string[]
   *   An array of unknown transition values in the transition block.
   */
  public function getUsedTransitionValues($state_name) {
    $result = array();
    if (!empty($this->usedTransitionValues[$state_name])) {
      $result = $this->usedTransitionValues[$state_name];
    }
    return $result;
  }

  /**
   * Returns the set of available transition values that are not being used.
   *
   * @return string[]
   *   An array of state names that are not using all of the available
   *   transition values.
   */
  public function getAllMissingTransitionValues() {
    $result = array();
    foreach ($this->availableTransitionValues as $state => $available_values) {
      $used_values = $this->getUsedTransitionValues($state);
      $difference = array_diff($available_values, $used_values);
      if (!empty($difference) && !in_array('*', $used_values)) {
        $result[$state] = $difference;
      }
    }
    return $result;
  }

  /**
   * Returns the set of unknown transition values for the specified state.
   *
   * @param string $state
   *   The state name.
   *
   * @return string[]
   *   The set of unknown transition values.
   */
  public function getUnknownTransitionValues($state) {
    $result = array();
    $all_unknown_values = $this->getAllUnknownTransitionValues();
    if (isset($all_unknown_values[$state])) {
      $result = $all_unknown_values[$state];
    }
    return $result;
  }

  /**
   * Returns the set of used transition values that are not available.
   *
   * @return string[]
   *   An array of state names that have unknown transition values.
   */
  public function getAllUnknownTransitionValues() {
    $result = array();
    if (empty($this->availableTransitionValues)) {
      // The available transition methods have not been determined.
    } else {
      foreach ($this->usedTransitionValues as $state => $used_values) {
        $available_values = $this->getAvailableTransitionValues($state);
        $difference = array_diff($used_values, $available_values, array('*', '!'));
        if (!empty($difference)) {
          $result[$state] = $difference;
        }
      }
    }
    return $result;
  }

  /**
   * Gets the value of the '*' transition for each state that uses one.
   *
   * @return array
   *   An array in which the keys are state names and the values are arrays
   *   that indicate what value(s) the * transition replaces.
   */
  public function getAsteriskValues() {
    $result = array();
    foreach ($this->usedTransitionValues as $state => $used_values) {
      if (in_array('*', $used_values)) {
        $value_diff = array_diff($this->getAvailableTransitionValues($state), $used_values);
        if (!empty($value_diff)) {
          $result[$state] = $value_diff;
        }
      }
    }
    return $result;
  }

}
