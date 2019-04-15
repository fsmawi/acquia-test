<?php

namespace Acquia\WipService\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Choice;

/**
 * Defines a constraint for validating choice parameters.
 */
class ChoiceParameter extends Choice {
  public $name;
  public $message = 'The value of the {{ name }} parameter is not a valid choice.';
  public $multipleMessage = 'One or more of the given values for the {{ name }} parameter is invalid.';
  public $minMessage = 'You must select at least {{ limit }} choice for the {{ name }} parameter.|You must select at least {{ limit }} choices for the {{ name }} parameter.';
  public $maxMessage = 'You must select at most {{ limit }} choice for the {{ name }} parameter.|You must select at most {{ limit }} choices for the {{ name }} parameter.';
}
