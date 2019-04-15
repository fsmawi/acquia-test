<?php

namespace Acquia\Wip\Iterators\SimulationIterator;

/**
 * The interface that describes the common functions of simulation script parsers.
 */
interface SimulationScriptParserInterface {

  /**
   * Parses the input string into a StateTableScript that can be run by the test tool.
   *
   * @param string $text_to_parse
   *   Input text to parse.
   *
   * @return StateTableScript The resulting parsed StateTableScript object.
   *   The resulting parsed StateTableScript object.
   */
  public function parse($text_to_parse);

}
