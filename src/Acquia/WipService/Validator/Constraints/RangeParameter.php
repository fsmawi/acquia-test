<?php

namespace Acquia\WipService\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Range;

/**
 * Defines a constraint for validating range parameters.
 */
class RangeParameter extends Range {

  public $name;
  public $minMessage = 'The {{ name }} parameter should be {{ limit }} or more, {{ value }} given.';
  public $maxMessage = 'The {{ name }} parameter should be {{ limit }} or less, {{ value }} given.';
  public $invalidNumberMessage = 'The {{ name }} parameter should be a valid number, {{ value }} given.';
  public $invalidWholeNumberMessage = 'The {{ name }} parameter should be a whole number, {{ value }} given.';
  public $allowDecimal = FALSE;
}
