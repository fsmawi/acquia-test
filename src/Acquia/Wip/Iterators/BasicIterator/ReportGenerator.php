<?php

namespace Acquia\Wip\Iterators\BasicIterator;

/**
 * Generates a report from a ValidationResult.
 */
class ReportGenerator {

  /**
   * The string used to separate sections.
   */
  const SEPARATOR = '=';

  /**
   * The line length of section separators.
   */
  const SEPARATOR_LENGTH = 80;

  /**
   * The section name for the state table.
   */
  const STATE_TABLE_SECTION = 'STATE TABLE';

  /**
   * The section name for a missing state table.
   */
  const MISSING_STATE_TABLE_SECTION = 'ERROR - MISSING STATE TABLE';

  /**
   * The section name for missing transition blocks.
   */
  const MISSING_TRANSITION_BLOCKS_SECTION = 'ERROR - MISSING TRANSITION BLOCKS';

  /**
   * The section name for transition blocks with no path to the finish state.
   */
  const NO_PATH_TO_FINISH_STATE = 'ERROR - NO PATH TO FINISH STATE';

  /**
   * The section name for missing state and transition methods.
   */
  const MISSING_METHODS = 'ERROR - MISSING METHODS';

  /**
   * The section name for available transition values that are not used.
   */
  const UNUSED_TRANSITION_VALUES = 'ERROR - UNUSED TRANSITION VALUES';

  /**
   * The section name for used transition values that are not documented.
   */
  const UNRECOGNIZED_TRANSITION_VALUES = 'ERROR - USING UNRECOGNIZED TRANSITION VALUES';

  /**
   * The section name for states that transition to themselves with no wait.
   */
  const SPIN_TRANSITIONS = 'ERROR - SELF REFERENTIAL TRANSITIONS MISSING WAIT VALUE';

  /**
   * The section name for identifying asterisk values.
   */
  const ASTERISK_VALUES = 'ASTERISK VALUES';

  /**
   * The section name for automatically generated source code.
   */
  const SOURCE_CODE = 'SOURCE CODE';

  /**
   * The section name for the summary on failure.
   */
  const FAIL = 'ERRORS FOUND';

  /**
   * The section name for the summary on success when a Wip object was used..
   */
  const SUCCESS = 'NO ERRORS FOUND';

  /**
   * The section name for the summary on success when no Wip object was used.
   */
  const SUCCESS_BUT_NO_OBJECT = 'NO ERRORS FOUND, BUT WIP OBJECT WAS NOT USED TO VALIDATE';

  /**
   * The validation result.
   *
   * @var ValidationResult
   */
  private $validationResult = NULL;

  /**
   * Creates a new ReportGenerator instance for the specified validation result.
   *
   * @param ValidationResult $validation_result
   *   The validation result.
   */
  public function __construct(ValidationResult $validation_result) {
    $this->validationResult = $validation_result;
  }

  /**
   * Generates the report.
   *
   * @return string
   *   The report.
   */
  public function generate() {
    $report = $this->generateStateTableSection() .
      $this->generateMissingBlocksSection() .
      $this->generateNoFinishPathSection() .
      $this->generateMissingMethodsSection() .
      $this->generateUnusedTransitionValuesSection() .
      $this->generateUnrecognizedTransitionValuesSection() .
      $this->generateSpinTransitionsSection() .
      $this->generateAsteriskValuesSection();
    // Only include source code if no Wip object was used during validation or
    // if a Wip object was used during validation and there were errors in the
    // validation. Mostly we prefer to not encourage the copying of source code
    // for a broken Wip object.
    if ((!$this->validationResult->hasFailures() && !$this->validationResult->getUsedValidationObject()) ||
      ($this->validationResult->hasFailures() && $this->validationResult->getUsedValidationObject())
    ) {
      $report .= self::generateSectionSeparator(self::SOURCE_CODE) .
        $this->generateClassCode();
    }
    $report .= $this->generateSummary();
    return $report;
  }

  /**
   * Generates the state table section of the report.
   *
   * @return string
   *   The state table section.
   */
  private function generateStateTableSection() {
    // Check for the state table.
    $state_table = $this->validationResult->getStateTable();
    if (empty($state_table)) {
      $report = self::generateSectionSeparator(self::MISSING_STATE_TABLE_SECTION);
    } else {
      $report = sprintf("%s%s\n", self::generateSectionSeparator(self::STATE_TABLE_SECTION), $state_table);
    }
    return $report;
  }

  /**
   * Generates the missing blocks section of the report.
   *
   * @return string
   *   The missing blocks section.
   */
  private function generateMissingBlocksSection() {
    $report = '';
    $missing_transition_blocks = implode("\n    ", $this->validationResult->getMissingBlocks());
    if (!empty($missing_transition_blocks)) {
      $report = self::generateSectionSeparator(self::MISSING_TRANSITION_BLOCKS_SECTION);
      $report .= <<<EOT
  The following transition blocks are referenced in the state table, but are
  not defined anywhere:

    $missing_transition_blocks

EOT;
    }
    return $report;
  }

  /**
   * Generates the section of the report that identifies missing paths.
   *
   * @return string
   *   The missing path section.
   */
  private function generateNoFinishPathSection() {
    $report = '';
    $no_finish_path = implode("\n    ", $this->validationResult->getMissingPaths());
    if (!empty($no_finish_path)) {
      $report = self::generateSectionSeparator(self::NO_PATH_TO_FINISH_STATE);
      $report .= <<<EOT
  The following transition blocks have no path to the finish state:

    $no_finish_path

EOT;
    }
    return $report;
  }

  /**
   * Generates the missing methods section of the report.
   *
   * @return string
   *   The missing methods section.
   */
  private function generateMissingMethodsSection() {
    $report = '';
    $all_missing_methods = array_merge(
      $this->validationResult->getMissingStateMethods(),
      $this->validationResult->getMissingTransitionMethods()
    );
    if (!empty($all_missing_methods)) {
      $report = self::generateSectionSeparator(self::MISSING_METHODS);
    }
    $missing_methods = implode("\n    ", $this->validationResult->getMissingStateMethods());
    if (!empty($missing_methods)) {
      $report .= <<<EOT
  The following state methods must be defined:

    $missing_methods


EOT;
    }
    $missing_methods = implode("\n    ", $this->validationResult->getMissingTransitionMethods());
    if (!empty($missing_methods)) {
      $report .= <<<EOT
  The following transition methods must be defined:

    $missing_methods


EOT;
    }
    return $report;
  }

  /**
   * Generates the report section that identifies unused transition values.
   *
   * @return string
   *   The unused transition values section.
   */
  private function generateUnusedTransitionValuesSection() {
    $report = '';
    $all_unused_values = $this->validationResult->getAllMissingTransitionValues();
    if (!empty($all_unused_values)) {
      $all_unused_values_report = '';
      foreach ($all_unused_values as $state => $values) {
        $transition_values = implode('", "', $values);
        $all_unused_values_report .= <<<EOT
  State "$state" is not handling transition values ["$transition_values"]

EOT;
      }
      $report = self::generateSectionSeparator(self::UNUSED_TRANSITION_VALUES);
      $report .= <<<EOT
  The following transition values are defined but not handled:

    $all_unused_values_report


EOT;
    }
    return $report;
  }

  /**
   * Generates the report section that identifies unrecognized transition values.
   *
   * @return string
   *   The unrecognized transition value section.
   */
  private function generateUnrecognizedTransitionValuesSection() {
    $report = '';
    $all_unrecognized_values = $this->validationResult->getAllUnknownTransitionValues();
    if (!empty($all_unrecognized_values)) {
      $all_unrecognized_values_report = '';
      foreach ($all_unrecognized_values as $state => $values) {
        $transition_values = implode('", "', $values);
        $all_unrecognized_values_report .= <<<EOT
  State "$state" is using unverified transition values ["$transition_values"]
EOT;
      }
      $report = self::generateSectionSeparator(self::UNRECOGNIZED_TRANSITION_VALUES);
      $report .= <<<EOT
  The following transition values are used but not documented:

    $all_unrecognized_values_report


EOT;
    }
    return $report;
  }

  /**
   * Generates the section of the report that identifies spin transitions.
   *
   * @return string
   *   The spin transition section.
   */
  private function generateSpinTransitionsSection() {
    $report = '';
    $spin_transitions = $this->validationResult->getSpinTransitions();
    if (!empty($spin_transitions)) {
      $spin_transitions_report = '';
      foreach ($spin_transitions as $state => $values) {
        $transition_values = implode('", "', $values);
        $spin_transitions_report .= <<<EOT
    State "$state" transitions to itself with value ["$transition_values"] without a wait value.

EOT;
      }
      $report = self::generateSectionSeparator(self::SPIN_TRANSITIONS);
      $report .= <<<EOT
  The following transitions require a wait value.

$spin_transitions_report


EOT;
    }
    return $report;
  }

  /**
   * Generates the section of the report that identifies what '*' means.
   *
   * @return string
   *   The asterisk identification section.
   */
  private function generateAsteriskValuesSection() {
    $report = '';
    $asterisk_report = '';
    $asterisk_values = $this->validationResult->getAsteriskValues();
    if (!empty($asterisk_values)) {
      foreach ($asterisk_values as $state => $values) {
        $values_string = implode('", "', $values);
        if (!empty($values_string)) {
          $asterisk_report .= <<<EOT
    State "$state" * value includes "$values_string"

EOT;
        }
      }
      if (!empty($asterisk_report)) {
        $report = self::generateSectionSeparator(self::ASTERISK_VALUES);
        $report .= <<<EOT
  The following states use grouped transition values:

$asterisk_report


EOT;
      }
    }
    return $report;
  }

  /**
   * Generates skeleton code for the associated state table.
   *
   * @param string $classname
   *   Optional. The name of the class that is generated from the state table.
   *
   * @return string
   *   The class source code.
   *
   * @throws \Acquia\Wip\Exception\MissingTransitionBlockException
   *   If an internal error occurs.
   */
  public function generateClassCode($classname = 'MyWipClass') {
    $state_methods = $this->validationResult->getStateMachine()->getAllStates();
    $transition_methods = array();
    if (!$this->validationResult->getUsedValidationObject()) {
      $missing_state_methods = $this->validationResult->getStateMachine()->getAllStates();
      $missing_transition_methods = array();
    } else {
      $missing_state_methods = $this->validationResult->getMissingStateMethods();
      $missing_transition_methods = $this->validationResult->getMissingTransitionMethods();
    }
    $state_table = $this->validationResult->getStateTable();
    $state_code = '';
    $transition_code = '';
    foreach ($state_methods as $state) {
      if (in_array($state, $missing_state_methods)) {
        $state_code .= $this->generateStateMethod($state);
      }
      $transition_block = $this->validationResult->getStateMachine()->getTransitionBlock($state);
      $transition = $transition_block->getTransitionMethod();
      if (('emptyTransition' !== $transition &&
          empty($missing_transition_methods)) &&
        (!in_array($transition, $transition_methods)) ||
        in_array($transition, $missing_transition_methods)
      ) {
        $transition_code .= $this->generateTransitionMethod($transition, $transition_block->getAllTransitionValues());
        $transition_methods[] = $transition;
      }
    }
    $state_code = trim($state_code);
    $transition_code = trim($transition_code);
    $eot = 'EOT';
    $report = <<<EOT
<?php

/**
 * Contains the $classname class.
 */

use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\WipContextInterface;

/**
 * TODO: Fill in description
 */
class $classname extends BasicWip {

  /**
   * The state table that will be executed by this Wip object.
   */
  protected \$stateTable = <<<EOT
$state_table
$eot;

  $state_code

  $transition_code

}

EOT;
    return $report;
  }

  /**
   * Generates source code for the specified state method.
   *
   * @param string $state_method
   *   The name of the state method to generate code for.
   *
   * @return string
   *   The source code.
   */
  private function generateStateMethod($state_method) {
    $method_contents = '';
    if ($state_method === 'start') {
      $method_contents = <<<EOT
    parent::start(\$wip_context);
EOT;
    }
    if ($state_method !== 'failure') {
      $result = <<<EOT
  /**
   * TODO: Fill in the method description.
   *
   * @param WipContextInterface \$wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function $state_method(WipContextInterface \$wip_context) {
$method_contents
  }


EOT;
    } else {
      $result = <<<EOT
  /**
   * The default failure state in the FSM.
   *
   * @param WipContextInterface \$wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   *
   * @param \Exception \$exception
   *   The exception that caused the failure (assuming the failure was caused
   *   by an exception.
   *
   * @throws \Exception
   */
  public function failure(WipContextInterface \$wip_context, \Exception \$exception = NULL) {
$method_contents
  }


EOT;
    }
    return $result;
  }

  /**
   * Generates source code for the specified transition method.
   *
   * @param string $transition_method
   *   The name of the transition method.
   * @param string[] $transition_values
   *   The set of legal transition values this method may return.
   *
   * @return string
   *   The source code for the transition method.
   */
  private function generateTransitionMethod($transition_method, $transition_values) {
    $values_comment = '';
    $first_value = 'unknown';
    if (count($transition_values) > 0) {
      $first_value = $transition_values[0];
    }
    foreach ($transition_values as $value) {
      $values_comment .= <<<EOT
   *   '$value' - TODO - add description here

EOT;
    }
    $result = <<<EOT
  /**
   * TODO: Fill in the method description.
   *
   * @param WipContextInterface \$wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   *
   * @return string
$values_comment   */
  public function $transition_method(WipContextInterface \$wip_context) {
    \$result = '$first_value';
    return \$result;
  }


EOT;
    return $result;
  }

  /**
   * Generates a report summary that indicates whether there are errors.
   *
   * @return string
   *   The report summary.
   */
  private function generateSummary() {
    if ($this->validationResult->hasFailures()) {
      $report = self::generateSectionSeparator(self::FAIL);
    } elseif ($this->validationResult->getUsedValidationObject()) {
      $report = self::generateSectionSeparator(self::SUCCESS);
    } else {
      $report = self::generateSectionSeparator(self::SUCCESS_BUT_NO_OBJECT);
    }
    return $report;
  }

  /**
   * Generates a section separator with the specified section name.
   *
   * @param string $section_name
   *   The name of the section.
   *
   * @return string
   *   The section separator string.
   */
  public static function generateSectionSeparator($section_name) {
    $separator_bar = str_repeat(self::SEPARATOR, self::SEPARATOR_LENGTH);
    $section_name = sprintf(' %s ', $section_name);
    $bisect = strlen($section_name) / 2;
    $section_separator = substr_replace(
      $separator_bar,
      $section_name,
      (self::SEPARATOR_LENGTH / 2) - $bisect,
      strlen($section_name)
    );
    return sprintf("\n%s\n", $section_separator);
  }

}
