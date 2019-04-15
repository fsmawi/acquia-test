<?php

namespace Acquia\WipService\Test;

use Acquia\WipInterface\TaskDefinitionStoreInterface;

/**
 * A basic storage implementation to be used in testing.
 */
class BasicTaskDefinitionStore implements TaskDefinitionStoreInterface {

  /**
   * Local storage of task definition data.
   *
   * @var array
   */
  private $data;

  /**
   * Missing summary.
   */
  public function get($name, $region, $revision = NULL) {
    if (!isset($revision)) {
      if (empty($this->data[$region][$name])) {
        return NULL;
      }
      $revisions = array_keys($this->data[$region][$name]);
      $revision = max($revisions);
    }

    if (empty($revision)) {
      return NULL;
    }

    if (isset($this->data[$region][$name][$revision])) {
      $result = $this->data[$region][$name][$revision];
      $result['revision'] = $revision;
      return $result;
    }
    return NULL;
  }

  /**
   * Missing summary.
   */
  public function save($name, $region, $definition, $revision) {
    $this->data[$region][$name][$revision] = $definition;
  }

  /**
   * Missing summary.
   */
  public function delete($name, $region, $revision) {
    if (isset($this->data[$region][$name][$revision])) {
      unset($this->data[$region][$name][$revision]);
    }
  }

}
