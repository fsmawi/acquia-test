<?php

namespace Acquia\WipService\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Defines a constraint for validating allowed parameters.
 */
class AllowedParameters extends Constraint {

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
  public $message = 'Invalid parameters given: {{ parameters }}.';

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions() {
    return array('routeParameters');
  }

}
