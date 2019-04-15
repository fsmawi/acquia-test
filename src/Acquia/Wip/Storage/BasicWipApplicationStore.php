<?php

namespace Acquia\Wip\Storage;

use Acquia\Wip\Runtime\WipApplication;

/**
 * Missing summary.
 */
class BasicWipApplicationStore implements WipApplicationStoreInterface {

  private $wipApplications = array();

  /**
   * Implements an "autoincrement" ID.
   *
   * @var int
   */
  private $id = 1;

  /**
   * Resets the basic implementation's storage.
   */
  public function initialize() {
    $this->wipApplications = array();
    $this->id = 1;
  }

  /**
   * {@inheritdoc}
   */
  public function save(WipApplication $wip_application) {
    if (!$wip_application->getHandler()) {
      throw new \InvalidArgumentException('The application must have its handler specified.');
    }
    foreach ($this->wipApplications as $wip_application_check) {
      $check_handler_equals_given_handler = $wip_application_check->getHandler() == $wip_application->getHandler();
      $check_id_equals_given_id = $wip_application->getId() != $wip_application_check->getId();
      if ($check_handler_equals_given_handler && $check_id_equals_given_id) {
        $message = 'There is already a wip application with the specified handler.';
        throw new \Acquia\Wip\Exception\WipApplicationStoreSaveException($message);
      }
    }
    if (!$wip_application->getId()) {
      $wip_application->setId($this->id++);
    }
    $this->wipApplications[$wip_application->getId()] = $wip_application;
  }

  /**
   * {@inheritdoc}
   */
  public function get($id) {
    return isset($this->wipApplications[$id]) ? $this->wipApplications[$id] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getByHandler($handler) {
    $application = FALSE;
    foreach ($this->wipApplications as $candidate) {
      if ($candidate->getHandler() == $handler) {
        $application = clone $candidate;
        break;
      }
    }
    return $application;
  }

  /**
   * {@inheritdoc}
   */
  public function remove(WipApplication $wip_application) {
    unset($this->wipApplications[$wip_application->getId()]);
  }

}
