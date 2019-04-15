<?php

namespace Acquia\Wip\Storage;

use Acquia\Wip\Runtime\WipApplication;

/**
 * Provides CRUD interface for WipApplication data storage.
 */
interface WipApplicationStoreInterface {

  /**
   * Saves the WipApplication data.
   *
   * @param WipApplication $wip_application
   *   The wip application object to be saved.
   *
   * @throws \InvalidArgumentException
   *   If the object does not have a handler set.
   */
  public function save(WipApplication $wip_application);

  /**
   * Gets the WipApplication with the specified id.
   *
   * @param int $id
   *   The id of the wip application to be found.
   *
   * @return WipApplication
   *   The WipApplication object or FALSE if it's not found.
   */
  public function get($id);

  /**
   * Gets the wip application with the specified textual id.
   *
   * @param string $handler
   *   The textual ID of the wip application.
   *
   * @return WipApplication
   *   The WipApplication object or FALSE if it's not found.
   */
  public function getByHandler($handler);

  /**
   * Removes the wip application data.
   *
   * @param WipApplication $wip_application
   *   The wip application to be removed.
   */
  public function remove(WipApplication $wip_application);

}
