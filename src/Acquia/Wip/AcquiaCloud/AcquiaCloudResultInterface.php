<?php

namespace Acquia\Wip\AcquiaCloud;

use Acquia\Wip\WipResultInterface;

/**
 * This class encapsulates a completed result from an AcquiaCloud call.
 */
interface AcquiaCloudResultInterface extends WipResultInterface {

  /**
   * Produces an ID that uniquely represents a process.
   *
   * @param int $tid
   *   The task ID.
   *
   * @return string
   *   A unique ID built from the passed parameters.
   */
  public static function createUniqueId($tid);

  /**
   * Sets the error message associated with this result.
   *
   * @param string|\Exception $e
   *   The error.
   */
  public function setError($e);

  /**
   * Gets the error message associated with this result instance.
   *
   * @return string
   *   The exception.
   */
  public function getError();

}
