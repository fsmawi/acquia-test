<?php

namespace Acquia\WipService\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates string parameters.
 */
class StringParameterValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (!$constraint instanceof StringParameter) {
      throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\StringParameter');
    }

    $valid = self::isValid($value);
    if (!$valid) {
      $this->context->addViolation($constraint->message, array(
        '{{ name }}' => $constraint->name,
        '{{ value }}' => $value === '' ? 'null' : $this->formatValue($value),
      ));
    }
  }

  /**
   * Validates the string value.
   *
   * @param mixed $value
   *   The value to validate.
   *
   * @return bool
   *   Whether the value is a valid string.
   */
  public static function isValid($value) {
    $valid = FALSE;
    if ($value !== NULL && is_string($value)) {
      $valid = TRUE;
    }
    return $valid;
  }

}
