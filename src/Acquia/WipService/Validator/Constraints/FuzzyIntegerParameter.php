<?php

namespace Acquia\WipService\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Defines a constraint for validating fuzzy integer parameters.
 */
class FuzzyIntegerParameter extends Constraint {

  public $name;
  public $message = 'Invalid integer value for {{ name }} parameter, {{ value }} given.';
  public $nonZero = FALSE;
  public $nonNegative = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption() {
    return 'name';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions() {
    return array('name');
  }

}
