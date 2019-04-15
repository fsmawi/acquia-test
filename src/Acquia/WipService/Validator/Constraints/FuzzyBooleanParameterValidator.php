<?php

namespace Acquia\WipService\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates fuzzy boolean parameters.
 */
class FuzzyBooleanParameterValidator extends ConstraintValidator {

  private static $truthyValues = array(TRUE, 1, '1', 'on', 'yes');
  private static $falseyValues = array(FALSE, 0, '0', 'off', 'no');

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (!$constraint instanceof FuzzyBooleanParameter) {
      throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\FuzzyBooleanParameter');
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
   * Validates the fuzzy boolean value.
   *
   * @param mixed $value
   *   The value to validate.
   *
   * @return bool
   *   Whether the value is a valid fuzzy boolean.
   */
  public static function isValid($value) {
    try {
      self::coerce($value);
    } catch (\InvalidArgumentException $e) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Attempts to coerce the provided value to a boolean.
   *
   * @param mixed $value
   *   The value to validate.
   *
   * @return bool
   *   The real boolean representation of the fuzzy boolean.
   *
   * @throws \InvalidArgumentException
   *   If the value was invalid and could not be coerced to a boolean.
   */
  public static function coerce($value) {
    if (in_array($value, self::$truthyValues, TRUE)) {
      $result = TRUE;
    } elseif (in_array($value, self::$falseyValues, TRUE)) {
      $result = FALSE;
    } else {
      throw new \InvalidArgumentException(sprintf('Could not coerce %s to a boolean.', var_export($value, TRUE)));
    }
    return $result;
  }

}
