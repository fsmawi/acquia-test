<?php

namespace Acquia\WipService\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates fuzzy integer parameters.
 */
class FuzzyIntegerParameterValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (!$constraint instanceof FuzzyIntegerParameter) {
      throw new UnexpectedTypeException(
        $constraint,
        __NAMESPACE__ . '\FuzzyIntegerParameter'
      );
    }

    $valid = is_int($value) || ctype_digit($value);
    if ($valid) {
      $value = (int) $value;
    }
    if ($valid && $constraint->nonNegative) {
      $valid = $value >= 0;
    }
    if ($valid && $constraint->nonZero) {
      $valid = $value !== 0;
    }
    if (!$valid) {
      $this->context->addViolation($constraint->message, array(
        '{{ name }}' => $constraint->name,
        '{{ value }}' => $value === '' ? 'null' : $this->formatValue($value),
      ));
    }
  }

}
