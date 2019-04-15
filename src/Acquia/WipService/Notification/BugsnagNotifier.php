<?php

namespace Acquia\WipService\Notification;

use Acquia\WipService\App;
use Acquia\Wip\IteratorStatus;
use Acquia\Wip\Notification\NotificationInterface;

/**
 * Sends notifications to Bugsnag.
 */
class BugsnagNotifier implements NotificationInterface {

  /**
   * The notification service client.
   *
   * @var \Bugsnag_Client
   *   An instance of Bugsnag_Client.
   */
  private $client;

  /**
   * Creates a new instance of BugsnagNotifier.
   *
   * @param \Bugsnag_Client $client
   *   An instance of Bugsnag_Client.
   */
  public function __construct(\Bugsnag_Client $client = NULL) {
    if ($client === NULL) {
      $client = App::getApp()['bugsnag'];
    }
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public function notifyError($type, $message, $severity = 'error', array $metadata = array()) {
    // Do not alert for user errors.
    if (!in_array($type, array(
      IteratorStatus::getLabel(IteratorStatus::ERROR_USER),
      IteratorStatus::getLabel(IteratorStatus::TERMINATED),
      E_USER_NOTICE,
    ))) {
      $this->client->notifyError($type, $message, $metadata, $severity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function notifyException(\Exception $e, $severity = 'error', array $metadata = array()) {
    $this->client->notifyException($e, $metadata, $severity);
  }

  /**
   * Returns the Bugsnag_Client instance.
   *
   * @return \Bugsnag_Client
   *   An instance of Bugsnag_Client.
   */
  public function getClient() {
    return $this->client;
  }

}
