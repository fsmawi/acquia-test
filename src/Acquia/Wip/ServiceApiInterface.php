<?php

namespace Acquia\Wip;

/**
 * Contains method signatures common to all services.
 */
interface ServiceApiInterface {

  /**
   * Records the process runtime for the specified category.
   *
   * @param string $name
   *   The category name.
   * @param string $client_id
   *   The client identifier.
   * @param int $runtime
   *   The process run time measured in seconds.
   */
  public function recordProcessRuntime($name, $client_id, $runtime);

  /**
   * Indicates whether a process has been running too long.
   *
   * @param string $name
   *   The category name.
   * @param string $client_id
   *   The client identifier.
   * @param int $start_time
   *   The Unix timestamp indicating when the process started.
   *
   * @return bool
   *   TRUE if the process has been running too long; FALSE otherwise.
   *
   * @throws \Exception
   *   If there is insufficient data to know if the process has been running
   *   too long.
   */
  public function hasProcessRunTooLong($name, $client_id, $start_time);

  /**
   * Indicates which process category names should be omitted from force fail.
   *
   * Force fail is the act of indicating failure on a task because it is taking
   * significantly longer than expected.  The underlying process may not
   * literally be killed, but for the purposes of Wip execution it is considered
   * to have failed.
   *
   * @return string[]
   *   The category names that should be omitted.
   */
  public function getProcessNamesToOmit();

}
