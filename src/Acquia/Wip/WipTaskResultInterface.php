<?php

namespace Acquia\Wip;

/**
 * The interface for all classes that reveal the result of a Wip object.
 */
interface WipTaskResultInterface extends WipResultInterface {

  /**
   * Produces an ID that uniquely identifies a Task.
   *
   * @param int $id
   *   The WIP ID of the Task.
   *
   * @return mixed
   *   A unique identifier for the passed Wip ID.
   */
  public static function createUniqueId($id);

}
