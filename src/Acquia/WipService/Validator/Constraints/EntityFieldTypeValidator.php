<?php

namespace Acquia\WipService\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that an entity field exists and is of the expected type.
 */
class EntityFieldTypeValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (!$constraint instanceof EntityFieldType) {
      throw new UnexpectedTypeException(
        $constraint,
        __NAMESPACE__ . '\EntityFieldType'
      );
    }

    if (is_array($value) && !isset($value[$constraint->name])) {
      $this->context->addViolation($constraint->requiredMessage, array(
        '{{ name }}' => $constraint->name,
      ));
      $value = $value[$constraint->name];
    } elseif (is_object($value) && !isset($value->{$constraint->name})) {
      $this->context->addViolation($constraint->requiredMessage, array(
        '{{ name }}' => $constraint->name,
      ));
      $value = $value->{$constraint->name};
    }

    if ($constraint->type === 'mixed') {
      return;
    }

    $type = strtolower($constraint->type);
    $type = $type === 'boolean' ? 'bool' : $constraint->type;
    $is_function = 'is_' . $type;
    $ctype_function = 'ctype_' . $type;

    if (function_exists($is_function) && $is_function($value)) {
      return;
    } elseif (function_exists($ctype_function) && $ctype_function($value)) {
      return;
    } elseif ($value instanceof $constraint->type) {
      return;
    }

    $this->buildViolation($constraint->typeMessage)
      ->setParameter('{{ name }}', $constraint->name)
      ->setParameter('{{ value }}', $this->formatValue($value))
      ->setParameter('{{ type }}', $constraint->type)
      ->addViolation();
  }

}
