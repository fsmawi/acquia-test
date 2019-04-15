<?php

namespace Acquia\Wip;

/**
 * Exposes the results of iterator validation.
 */
interface ValidationResultInterface {

  /**
   * Indicates whether failures were detected.
   *
   * @return bool
   *   TRUE if there were failures detected; FALSE otherwise.
   */
  public function hasFailures();

  /**
   * Provides a text report of warnings and failures.
   *
   * @return string
   *   The report.
   */
  public function getReport();

}
