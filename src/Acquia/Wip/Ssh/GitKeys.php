<?php

namespace Acquia\Wip\Ssh;

/**
 * Manages a set of SSH keys used to work with git.
 */
class GitKeys {

  /**
   * The set of keys.
   *
   * @var GitKey[]
   */
  private $keys = array();

  /**
   * Adds the specified key.
   *
   * @param GitKey $key
   *   The key to add.
   */
  public function addKey(GitKey $key) {
    $name = $key->getName();
    if (empty($name)) {
      throw new \InvalidArgumentException('The "key" parameter must have an associated name.');
    }
    $this->keys[$name] = $key;
  }

  /**
   * Indicates whether there is a key associated with the specified name.
   *
   * @param string $name
   *   The name associated with the key.
   *
   * @return bool
   *   TRUE if the associated key exists; FALSE otherwise.
   */
  public function hasKey($name) {
    return array_key_exists($name, $this->keys);
  }

  /**
   * Gets the key associated with the specified name.
   *
   * @param string $name
   *   The name associated with the desired key.
   *
   * @return GitKey
   *   The GitKey instance.
   */
  public function getKey($name) {
    $result = NULL;
    if ($this->hasKey($name)) {
      $result = $this->keys[$name];
    }
    return $result;
  }

  /**
   * Removes the key associated with the specified name.
   *
   * @param string $name
   *   The key name.
   */
  public function removeKey($name) {
    if ($this->hasKey($name)) {
      unset($this->keys[$name]);
    }
  }

  /**
   * Gets all key names.
   *
   * @return string[]
   *   The key names.
   */
  public function getAllKeyNames() {
    return array_keys($this->keys);
  }

  /**
   * Returns the entire set of SSH keys.
   *
   * @return GitKey[]
   *   All of the keys.
   */
  public function getAllKeys() {
    $array_object = new \ArrayObject($this->keys);
    return $array_object->getArrayCopy();
  }

}
