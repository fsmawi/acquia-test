<?php

namespace Acquia\WipService\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\ChoiceValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates choice parameters.
 */
class ChoiceParameterValidator extends ChoiceValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (!$constraint instanceof ChoiceParameter) {
      throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\ChoiceParameter');
    }

    if (!$constraint->choices && !$constraint->callback) {
      throw new ConstraintDefinitionException('Either "choices" or "callback" must be specified on constraint Choice');
    }

    if (NULL === $value) {
      return;
    }

    if ($constraint->multiple && !is_array($value)) {
      throw new UnexpectedTypeException($value, 'array');
    }

    if ($constraint->callback) {
      if (is_callable(array($this->context->getClassName(), $constraint->callback))) {
        $choices = call_user_func(array($this->context->getClassName(), $constraint->callback));
      } elseif (is_callable($constraint->callback)) {
        $choices = call_user_func($constraint->callback);
      } else {
        throw new ConstraintDefinitionException('The Choice constraint expects a valid callback');
      }
    } else {
      $choices = $constraint->choices;
    }

    if ($constraint->multiple) {
      foreach ($value as $_value) {
        if (!in_array($_value, $choices, $constraint->strict)) {
          $this->buildViolation($constraint->multipleMessage)
            ->setParameter('{{ name }}', $constraint->name)
            ->setParameter('{{ value }}', $this->formatValue($_value))
            ->setInvalidValue($_value)
            ->addViolation();

          return;
        }
      }

      $count = count($value);

      if ($constraint->min !== NULL && $count < $constraint->min) {
        $this->buildViolation($constraint->minMessage)
          ->setParameter('{{ name }}', $constraint->name)
          ->setParameter('{{ limit }}', $constraint->min)
          ->setPlural((int) $constraint->min)
          ->addViolation();

        return;
      }

      if ($constraint->max !== NULL && $count > $constraint->max) {
        $this->buildViolation($constraint->maxMessage)
          ->setParameter('{{ name }}', $constraint->name)
          ->setParameter('{{ limit }}', $constraint->max)
          ->setPlural((int) $constraint->max)
          ->addViolation();

        return;
      }
    } elseif (!in_array($value, $choices, $constraint->strict)) {
      $this->buildViolation($constraint->message)
        ->setParameter('{{ name }}', $constraint->name)
        ->setParameter('{{ value }}', $this->formatValue($value))
        ->addViolation();
    }
  }

}
