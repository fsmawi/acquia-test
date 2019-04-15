<?php

namespace Acquia\WipService\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates allowed parameters.
 */
class AllowedParametersValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($request, Constraint $constraint) {
    $allowed_parameters = $constraint->routeParameters;

    $unexpected = array_diff_key($request->query->all(), $allowed_parameters);
    if (count($unexpected) > 0) {
      $this->context->addViolation($constraint->message, array(
        '{{ parameters }}' => implode(', ', array_keys($unexpected)),
      ));
    }
  }

}
