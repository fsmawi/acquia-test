<?php

namespace Acquia\WipService\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Defines a constraint for validating fuzzy boolean parameters.
 */
class FuzzyBooleanParameter extends Constraint {

  public $name;
  public $message = 'Invalid boolean value for {{ name }} parameter, {{ value }} given.';

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
