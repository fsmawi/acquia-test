<?php

namespace Acquia\Wip\Implementation;

use Acquia\Wip\Notification\NotificationInterface;

/**
 * An implementation of the NotificationInterface for testing that does nothing.
 */
class NullNotifier implements NotificationInterface {

  /**
   * {@inheritdoc}
   */
  public function notifyError($type, $message, $severity, array $metadata = array()) {
  }

  /**
   * {@inheritdoc}
   */
  public function notifyException(\Exception $e, $severity, array $metadata = array()) {
  }

}
