<?php

namespace Acquia\WipService\Validator\Constraints;

use Acquia\Wip\WipLogLevel;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates wip log level parameters.
 */
class WipLogLevelParameterValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (!$constraint instanceof WipLogLevelParameter) {
      throw new UnexpectedTypeException(
        $constraint,
        __NAMESPACE__ . '\WipLogLevelParameter'
      );
    }

    $valid = FALSE;
    try {
      if (!is_numeric($value)) {
        $valid = WipLogLevel::isValidLabel($value);
      } else {
        $valid = WipLogLevel::isValid((int) $value);
      }
    } catch (\InvalidArgumentException $e) {
      // Swallow the exception.
    }
    if (!$valid) {
      $this->context->addViolation($constraint->message, array(
        '{{ name }}' => $constraint->name,
        '{{ value }}' => $value === '' ? 'null' : $this->formatValue($value),
      ));
    }
  }

}
