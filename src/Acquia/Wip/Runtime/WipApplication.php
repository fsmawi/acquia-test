<?php

namespace Acquia\Wip\Runtime;

use Acquia\Wip\WipApplicationStatus;

/**
 * Manages the applications that want to use Wip.
 */
class WipApplication implements WipApplicationInterface {

  /**
   * The Wip application's numeric ID.
   *
   * @var int
   */
  private $id;

  /**
   * The Wip application's textual ID.
   *
   * @var string
   */
  private $handler;

  /**
   * The Wip application's status.
   *
   * @var int
   */
  private $status = WipApplicationStatus::DISABLED;

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    if (!is_int($id) || $id <= 0) {
      throw new \InvalidArgumentException('Invalid application ID provided.');
    }
    if ($this->id) {
      $message = "The application's ID has been already specified.";
      throw new \Acquia\Wip\Exception\WipApplicationOverwriteException($message);
    }
    $this->id = $id;
  }

  /**
   * {@inheritdoc}
   */
  public function getHandler() {
    return $this->handler;
  }

  /**
   * {@inheritdoc}
   */
  public function setHandler($handler) {
    if (!is_string($handler) || !($handler = trim($handler))) {
      throw new \InvalidArgumentException('Invalid application handler provided.');
    }
    $this->handler = $handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    if (!WipApplicationStatus::isValid($status)) {
      throw new \InvalidArgumentException('Invalid application status provided.');
    }
    $this->status = $status;
  }

}
