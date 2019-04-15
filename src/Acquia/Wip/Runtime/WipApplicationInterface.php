<?php

namespace Acquia\Wip\Runtime;

/**
 * Provides a basic API for managing applications that want to use Wip.
 */
interface WipApplicationInterface {

  /**
   * Retrieves the application's numeric ID.
   *
   * @return int
   *   The numeric ID of the application.
   */
  public function getId();

  /**
   * Sets the application's numeric ID.
   *
   * @param int $id
   *   The numeric ID of the application.
   *
   * @throws \InvalidArgumentException
   *   If the argument is not a non-negative integer.
   * @throws \Acquia\Wip\Exception\WipApplicationOverwriteException
   *   If the ID has been already set previously.
   */
  public function setId($id);

  /**
   * Retrieves the application's textual ID.
   *
   * @return string
   *   The textual ID of the application.
   */
  public function getHandler();

  /**
   * Sets the application's textual ID.
   *
   * @param string $handler
   *   The textual ID of the application.
   *
   * @throws \InvalidArgumentException
   *   If the argument is empty.
   */
  public function setHandler($handler);

  /**
   * Retrieves the application's status.
   *
   * @return int
   *   The status of the application.
   */
  public function getStatus();

  /**
   * Sets the application's status.
   *
   * @param string $status
   *   The status of the application.
   *
   * @throws \InvalidArgumentException
   *   If the argument is not a valid status.
   */
  public function setStatus($status);

}
