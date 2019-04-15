<?php

namespace Acquia\WipService\Validator\Constraints;

use Acquia\Wip\State\MonitorDaemonPause;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates pause mode values.
 */
class MonitorDaemonPauseModeValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (!$constraint instanceof MonitorDaemonPauseMode) {
      throw new UnexpectedTypeException(
        $constraint,
        __NAMESPACE__ . '\MonitorDaemonPauseMode'
      );
    }

    $valid = FALSE;
    try {
      $valid = MonitorDaemonPause::isValidValue($value);
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
