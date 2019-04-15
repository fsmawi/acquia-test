<?php

namespace Acquia\WipService\Validator\Constraints;

use Acquia\Wip\State\GlobalPause;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates pause mode values.
 */
class GlobalPauseModeValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (!$constraint instanceof GlobalPauseMode) {
      throw new UnexpectedTypeException(
        $constraint,
        __NAMESPACE__ . '\GlobalPauseMode'
      );
    }

    $valid = FALSE;
    try {
      $valid = GlobalPause::isValidValue($value);
    } catch (\Exception $e) {
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
