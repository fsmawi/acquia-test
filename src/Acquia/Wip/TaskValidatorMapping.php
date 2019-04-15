<?php

namespace Acquia\Wip;

/**
 * Maps each valid TaskType value to a list of validators.
 */
class TaskValidatorMapping {

  /**
   * List of validators for each task type.
   *
   * @var array[]
   */
  private static $validatorMapping = array(
    TaskType::BUILDSTEPS => array(
      'ParameterDocumentValidator' => 'Acquia\Wip\Validators\ParameterDocumentValidator',
    ),
  );

  /**
   * Provides a list of validators for the specified task type.
   *
   * @param string $type
   *   The task type to return the validators for.
   *
   * @return array
   *   The list of validators.
   *
   * @throws \InvalidArgumentException
   *   If the specified task type is invalid.
   */
  public static function getValidators($type) {
    if (!TaskType::isValid($type)) {
      throw new \InvalidArgumentException(sprintf('Unknown task type "%s"', $type));
    }
    return self::$validatorMapping[strtolower($type)];
  }

  /**
   * Returns an array containing all possible validator mappings.
   *
   * @return array[]
   *   An array keyed by task types with an array of validator(s) as values.
   */
  public static function getAll() {
    return self::$validatorMapping;
  }

}
