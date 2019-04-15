<?php

namespace Acquia\WipService\Notification;

use Acquia\Wip\Implementation\NullNotifier;

/**
 * Missing summary.
 */
class NullNotifierTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @var NotificationInterface
   *   The notifier.
   */
  private $notifier;

  /**
   * Missing summary.
   */
  public function setUp() {
    $this->notifier = new NullNotifier();
  }

  /**
   * Missing summary.
   */
  public function testNotifyError() {
    $this->notifier->notifyError('error', 'message', 'error', array());
  }

  /**
   * Missing summary.
   */
  public function testNotifyException() {
    $this->notifier->notifyException(new \Exception(), 'error', array());
  }

}
