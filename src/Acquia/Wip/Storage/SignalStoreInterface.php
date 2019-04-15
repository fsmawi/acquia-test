<?php

namespace Acquia\Wip\Storage;

use Acquia\Wip\Signal\SignalInterface;

/**
 * Describes the interface for signal storage.
 */
interface SignalStoreInterface {

  /**
   * Sends the specified signal.
   *
   * @param SignalInterface $signal
   *   The signal to send.
   *
   * @return SignalInterface
   *   The modified signal.
   */
  public function send(SignalInterface $signal);

  /**
   * Flags the signal as consumed.
   *
   * @param SignalInterface $signal
   *   The signal to mark as consumed.
   */
  public function consume(SignalInterface $signal);

  /**
   * Deletes the specified signal.
   *
   * @param SignalInterface $signal
   *   The signal to delete.
   */
  public function delete(SignalInterface $signal);

  /**
   * Loads the signal with the specified signal ID.
   *
   * @param int $signal_id
   *   The signal ID.
   *
   * @return SignalInterface
   *   The signal.
   */
  public function load($signal_id);

  /**
   * Gets all signals associated with the specified object.
   *
   * @param int $object_id
   *   The object ID associated with the desired signals.
   *
   * @return SignalInterface[]
   *   The set of Signal instances associated with the specified object ID.
   */
  public function loadAll($object_id);

  /**
   * Gets all signals uuid associated with the specified objects.
   *
   * @param int[] $object_ids
   *   List of object ids.
   *
   * @return int[]
   *   The signal id for the specified objects.
   */
  public function getUuids(array $object_ids);

  /**
   * Deletes singals associated with specific objects.
   *
   * @param int[] $object_ids
   *   List of object ids.
   */
  public function pruneObjects(array $object_ids);

  /**
   * Gets all non-consumed signals associated with the specified object.
   *
   * @param int $object_id
   *   The object ID associated with the desired signals.
   *
   * @return SignalInterface[]
   *   The set of Signal instances associated with the specified object ID that
   *   have not yet been consumed.
   */
  public function loadAllActive($object_id);

}
