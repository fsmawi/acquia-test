<?php

namespace Acquia\WipService\Validator\Constraints;

use Acquia\Wip\TaskPriority;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates task priority parameters.
 */
class TaskPriorityParameterValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (!$constraint instanceof TaskPriorityParameter) {
      throw new UnexpectedTypeException(
        $constraint,
        __NAMESPACE__ . '\TaskPriorityParameter'
      );
    }

    if (ctype_digit($value)) {
      $value = (int) $value;
    }
    $valid = TaskPriority::isValid($value);
    if (!$valid) {
      $this->context->addViolation($constraint->message, array(
        '{{ name }}' => $constraint->name,
        '{{ value }}' => $value === '' ? 'null' : $this->formatValue($value),
      ));
    }
  }

}
