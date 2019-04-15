<?php

namespace Acquia\WipService\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that JSON data can be parsed and is not empty.
 */
class JsonDecodeValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (!$constraint instanceof JsonDecode) {
      throw new UnexpectedTypeException(
        $constraint,
        __NAMESPACE__ . '\JsonDecode'
      );
    }

    if (NULL === json_decode($value)) {
      $error_message = $constraint->message;
      $json_error_code = json_last_error();
      $json_error_message = json_last_error_msg();
      if ($json_error_code !== JSON_ERROR_NONE) {
        $error_message = sprintf(
          '%s Error code: %d; Reason: %s',
          $error_message,
          $json_error_code,
          $json_error_message
        );
        $this->context->addViolation($error_message);
      } else {
        $this->context->addViolation($constraint->message);
      }
    }
  }

}
