<?php

namespace Acquia\Wip\Objects;

/**
 * Provides an interface for conversion of types within a ParameterDocument.
 */
interface ParameterConverterInterface {

  /**
   * Converts the specified value to an appropriate object.
   *
   * @param mixed $value
   *   The value to convert.
   * @param array $context
   *   An array of additional data to append to the returned object.
   *
   * @return object
   *   The value converted into an appropriate object.
   */
  public static function convert($value, $context = array());

  /**
   * Extracts a part from the parameter document.
   *
   * @param array $keys
   *   An associative array of keys to use to locate the part to extract.
   * @param array $context
   *   An array of additional data to append to the returned object.
   *
   * @return mixed
   *   The requested document part, which may have been delegated further down
   *   the hierarchy.
   */
  public function extract($keys, $context = array());

  /**
   * Checks a document part for validity.
   *
   * @return bool
   *   TRUE if the object is valid.
   */
  public function validate();

}
