<?php

namespace Acquia\WipService\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates required parameters.
 */
class RequiredParametersValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($request, Constraint $constraint) {
    $route_parameters = $constraint->routeParameters;

    $missing_parameters = array();
    foreach ($route_parameters as $name => $parameter) {
      if (!empty($parameter['required'])) {
        switch ($parameter['location']) {
          case 'json':
            // @todo Make this work hierarchically.
            $request_entity = json_decode($request->getContent(), TRUE);
            if (!isset($request_entity[$name])) {
              $missing_parameters[] = $name;
            }
            break;

          case 'query':
            if (!$request->query->has($name)) {
              $missing_parameters[] = $name;
            }
            break;
        }
      }
    }

    if (count($missing_parameters) > 0) {
      $this->context->addViolation($constraint->message, array(
        '{{ parameters }}' => implode(', ', $missing_parameters),
      ));
    }
  }

}
