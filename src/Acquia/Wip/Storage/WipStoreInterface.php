<?php

namespace Acquia\Wip\Storage;

use Acquia\Wip\Exception\WipStoreSaveException;
use Acquia\Wip\StateTableIteratorInterface;

/**
 * The WipStoreInterface handles Wip data storage related tasks.
 *
 * The WipStoreInterface provides access point to save (insert or update),
 * delete or get Wip related data.
 */
interface WipStoreInterface {

  /**
   * Saves the Wip data.
   *
   * @param int $wip_id
   *   The ID of the Wip object. The ID is being hidden so customers may not
   *   query it and overwrite it. The ThreadPool will know it. An empty $wip_id
   *   means saving a new Wip.
   * @param StateTableIteratorInterface $wip_iterator
   *   The Wip iterator object.
   *
   * @throws WipStoreSaveException
   *   If the Wip iterator object could not be saved.
   */
  public function save($wip_id, StateTableIteratorInterface $wip_iterator);

  /**
   * Gets the data for a Wip object.
   *
   * @param int $id
   *   The Wip's ID to be loaded.
   *
   * @return StateTableIteratorInterface
   *   The Wip iterator's data or FALSE if it's not found.
   */
  public function get($id);

  /**
   * Gets the timestamp of the last update to the Wip by the given ID.
   *
   * @param int $wip_id
   *   The ID of a Wip object.
   *
   * @return int|null
   *   If the Wip ID is valid, returns timestamp, else NULL.
   */
  public function getTimestampByWipId($wip_id);

  /**
   * Removes the stored data for a Wip object.
   *
   * @param int $id
   *   The ID of the Wip iterator that is being deleted.
   */
  public function remove($id);

  /**
   * Removes the objects from the wip store.
   *
   * @param int[] $object_ids
   *   The IDs of the Wip iterator that is being deleted.
   */
  public function pruneObjects(array $object_ids);

}
