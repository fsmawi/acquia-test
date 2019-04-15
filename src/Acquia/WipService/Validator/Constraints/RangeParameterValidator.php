<?php

namespace Acquia\WipService\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates range parameters.
 */
class RangeParameterValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (!$constraint instanceof RangeParameter) {
      throw new UnexpectedTypeException(
        $constraint,
        __NAMESPACE__ . '\RangeParameter'
      );
    }

    if (NULL === $value) {
      return;
    }

    $message = $constraint->invalidNumberMessage;
    if (!$constraint->allowDecimal) {
      $message = $constraint->invalidWholeNumberMessage;
    }

    if (!$this->isValid($value, $constraint->allowDecimal)) {
      $this->buildViolation($message)
        ->setParameter('{{ name }}', $constraint->name)
        ->setParameter('{{ value }}', empty($value) ? 'null' : $this->formatValue($value))
        ->addViolation();

      return;
    }

    if (NULL !== $constraint->max && $value > $constraint->max) {
      $this->buildViolation($constraint->maxMessage)
        ->setParameter('{{ name }}', $constraint->name)
        ->setParameter('{{ value }}', $value)
        ->setParameter('{{ limit }}', $constraint->max)
        ->addViolation();

      return;
    }

    if (NULL !== $constraint->min && $value < $constraint->min) {
      $this->buildViolation($constraint->minMessage)
        ->setParameter('{{ name }}', $constraint->name)
        ->setParameter('{{ value }}', $value)
        ->setParameter('{{ limit }}', $constraint->min)
        ->addViolation();
    }
  }

  /**
   * Checks whether the provided value is a valid fuzzy integer.
   *
   * @param mixed $value
   *   The value to check.
   * @param bool $allow_decimal
   *   Whether to allow decimal numbers.
   *
   * @return bool
   *   Whether the provided value is a valid fuzzy integer.
   */
  public static function isValid($value, $allow_decimal) {
    $result = TRUE;
    if (!is_numeric($value)) {
      $result = FALSE;
    }
    if (!$allow_decimal && fmod((double) $value, 1) !== (double) 0) {
      $result = FALSE;
    }
    return $result;
  }

}
