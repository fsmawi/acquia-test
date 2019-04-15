<?php

namespace Acquia\WipService\Validator\Constraints;

use Acquia\Wip\TaskStatus;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates task status parameters.
 */
class TaskStatusParameterValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (!$constraint instanceof TaskStatusParameter) {
      throw new UnexpectedTypeException(
        $constraint,
        __NAMESPACE__ . '\TaskStatusParameter'
      );
    }

    if (ctype_digit($value)) {
      $value = (int) $value;
    }
    $valid = TaskStatus::isValid($value);
    if (!$valid) {
      $this->context->addViolation($constraint->message, array(
        '{{ name }}' => $constraint->name,
        '{{ value }}' => $value === '' ? 'null' : $this->formatValue($value),
      ));
    }
  }

}
