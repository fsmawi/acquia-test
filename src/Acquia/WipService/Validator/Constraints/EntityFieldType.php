<?php

namespace Acquia\WipService\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Defines a constraint for validating that entity fields are of a given type.
 */
class EntityFieldType extends Constraint {

  public $name;
  public $type = 'mixed';
  public $requiredMessage = 'The {{ name }} field is required.';
  public $typeMessage = 'The {{ name }} field must be of type {{ type }}, {{ value }} given.';

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
