<?php

namespace Acquia\Wip\Notification;

/**
 * Describes the API for sending notifications.
 */
interface NotificationInterface {

  /**
   * Sends an error notification.
   *
   * @param string $type
   *   The type of error that occurred.
   * @param string $message
   *   The error message describing the error state.
   * @param string $severity
   *   The severity of the error.
   * @param array $metadata
   *   Any additional data associated with the error to help with debugging.
   */
  public function notifyError($type, $message, $severity, array $metadata = array());

  /**
   * Sends an exception notification.
   *
   * @param \Exception $e
   *   The exception instance that was thrown when the error occurred.
   * @param string $severity
   *   The severity of the error.
   * @param array $metadata
   *   Any additional data associated with the error to help with debugging.
   */
  public function notifyException(\Exception $e, $severity, array $metadata = array());

}
