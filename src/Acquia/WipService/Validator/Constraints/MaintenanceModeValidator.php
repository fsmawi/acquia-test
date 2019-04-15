<?php

namespace Acquia\WipService\Validator\Constraints;

use Acquia\Wip\State\Maintenance;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates maintenance mode values.
 */
class MaintenanceModeValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (!$constraint instanceof MaintenanceMode) {
      throw new UnexpectedTypeException(
        $constraint,
        __NAMESPACE__ . '\MaintenanceMode'
      );
    }

    $valid = FALSE;
    try {
      $valid = Maintenance::isValidValue($value);
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
