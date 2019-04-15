<?php

namespace Acquia\WipService\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Defines a constraint for validating that JSON can be decoded.
 */
class JsonDecode extends Constraint {

  public $message = 'The JSON data was empty or could not be decoded.';

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption() {
    return 'message';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions() {
    return array('message');
  }

}
