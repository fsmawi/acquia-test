<?php

namespace Acquia\Wip\Storage;

use Acquia\WipIntegrations\DoctrineORM\Entities\WipStoreEntry;
use Acquia\Wip\Exception\WipStoreSaveException;
use Acquia\Wip\IncludeFileInterface;
use Acquia\Wip\StateTableIteratorInterface;

/**
 * Provides a base class to test Wip storage.
 */
class BasicWipStore implements WipStoreInterface {

  /**
   * The state table iterators.
   *
   * @var WipStoreEntry[]
   */
  private $wipEntries = array();

  /**
   * Resets the basic implementation's storage.
   */
  public function initialize() {
    $this->wipEntries = array();
  }

  /**
   * {@inheritdoc}
   */
  public function save($wip_id, StateTableIteratorInterface $wip_iterator) {
    if ($wip_id > 0) {
      $wip = $wip_iterator->getWip();
      if (empty($wip)) {
        throw new WipStoreSaveException('The iterator is missing a Wip object.');
      }

      $wip_entry = new WipStoreEntry();
      $wip_entry->setWid($wip_id);
      $wip_entry->setTimestamp(time());
      $wip_entry->setObj(serialize($wip_iterator));
      $wip_entry->setRequires(serialize($wip_iterator->getWip()->getIncludes()));
      $this->wipEntries[$wip_id] = $wip_entry;
    } else {
      throw new \InvalidArgumentException('Invalid Wip id specified.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($id) {
    $result = FALSE;
    // Ensure that the required files are included before we unserialize the
    // Wip iterator and its associated Wip object.
    if (isset($this->wipEntries[$id])) {
      $wip_entry = $this->wipEntries[$id];
      /** @var IncludeFileInterface[] $includes */
      $includes = unserialize($wip_entry->getRequires());
      foreach ($includes as $include) {
        $path = $include->getFullPath();
        if (is_readable($path)) {
          require_once $path;
        }
      }
      $result = unserialize($wip_entry->getObj());
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function remove($id) {
    unset($this->wipEntries[$id]);
  }

  /**
   * {@inheritdoc}
   */
  public function pruneObjects(array $object_ids) {
    foreach ($object_ids as $id) {
      unset($this->wipEntries[$id]);
    }
  }

  /**
   * Gets the timestamp of the last update to the Wip by the given ID.
   *
   * @param int $wip_id
   *   The ID of a Wip object.
   *
   * @return int|null
   *   If the Wip ID is valid, returns timestamp, else NULL.
   */
  public function getTimestampByWipId($wip_id) {
    $result = NULL;
    if (isset($this->wipEntries[$wip_id])) {
      $wip_entry = $this->wipEntries[$wip_id];
      /** @var IncludeFileInterface[] $includes */
      $result = $wip_entry->getTimestamp();
    }
    return $result;
  }

}
