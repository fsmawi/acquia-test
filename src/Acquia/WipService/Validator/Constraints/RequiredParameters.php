<?php

namespace Acquia\WipService\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Defines a constraint for validating required parameters.
 */
class RequiredParameters extends Constraint {

  /**
   * The parameters defined for the route.
   *
   * @var array
   */
  public $routeParameters = array();

  /**
   * The violation message.
   *
   * @var string
   */
  public $message = 'Missing required parameters: {{ parameters }}.';

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions() {
    return array('routeParameters');
  }

}
