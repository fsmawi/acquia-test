<?php

namespace Acquia\WipService\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Defines a constraint for validating string parameters.
 */
class StringParameter extends Constraint {

  public $name;
  public $message = 'Invalid string value for {{ name }} parameter, {{ value }} given.';

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
