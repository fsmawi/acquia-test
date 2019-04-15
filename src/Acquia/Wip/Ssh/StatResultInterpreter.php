<?php

namespace Acquia\Wip\Ssh;

/**
 * The StatResultInterpreter provides an easy interface for file permissions.
 */
class StatResultInterpreter extends SshResultInterpreter {

  /**
   * The file path.
   *
   * @var string
   */
  private $path;

  /**
   * The ID of the associated Wip object.
   *
   * @var int
   *   The ID.
   */
  private $id;

  /**
   * The file modifiers.
   *
   * @var int
   */
  private $modifiers;

  /**
   * The file permissions.
   *
   * @var int
   */
  private $permissions;

  /**
   * Creates a new instance of StatResultInterpreter for the specified file.
   *
   * @param string $path
   *   The path to the file being inspected.
   * @param int $wip_id
   *   The ID of the wip object associated with this instance.
   */
  public function __construct($path, $wip_id) {
    $this->path = $path;
    $this->id = $wip_id;
  }

  /**
   * Gets the file permissions.
   *
   * @return int
   *   The file permissions.
   */
  public function getPermissions() {
    if (NULL === $this->permissions) {
      $this->interpretResults();
    }
    return $this->permissions;
  }

  /**
   * Gets the file modifiers.
   *
   * @return int
   *   The file modifiers.
   */
  public function getModifiers() {
    if (NULL === $this->modifiers) {
      $this->interpretResults();
    }
    return $this->modifiers;
  }

  /**
   * Indicates whether the file is executable.
   *
   * @return bool
   *   TRUE if the file is executable; FALSE otherwise.
   */
  public function isExecutable() {
    $permissions = $this->getPermissions();
    return ($permissions & 011) != 0;
  }

  /**
   * Interprets the associated SshResult instance.
   *
   * @throws \Exception
   *   If the SshResult has not been set or if the specified path does not
   *   exist.
   */
  private function interpretResults() {
    $stat_result = $this->getSshResult();
    if (!$stat_result->isSuccess()) {
      $message = sprintf('The file %s does not exist.', $this->path);
      throw new \RuntimeException($message);
    }
    $permission_string = $stat_result->getStdout();

    // The last 3 characters represent the octal form of the permissions (owner,
    // user, group. Before that are any modifiers that might indicate a
    // directory, link, etc.
    $this->modifiers = intval(substr($permission_string, 0, strlen($permission_string) - 3), 8);
    $this->permissions = intval(substr($permission_string, strlen($permission_string) - 3), 8);
  }

}
