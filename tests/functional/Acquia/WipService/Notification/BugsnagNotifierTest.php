<?php

namespace Acquia\WipService\Notification;

use Acquia\Wip\IteratorStatus;

/**
 * Missing summary.
 */
class BugsnagNotifierTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   */
  public function testConstructor() {
    $notifier = new BugsnagNotifier();
    $this->assertNotNull($notifier);
  }

  /**
   * Missing summary.
   */
  public function testNotifyError() {
    /** @var \Bugsnag_Client $mock_client */
    $mock_client = $this->getMockBuilder('\Bugsnag_Client')
      ->setConstructorArgs(array('api_key'))
      ->getMock();
    $mock_client->expects($this->once())->method('notifyError')->with('error', 'message');

    $notifier = new BugsnagNotifier($mock_client);
    $notifier->notifyError('error', 'message');
  }

  /**
   * Missing summary.
   */
  public function testNotifyException() {
    $exception = new \Exception();

    /** @var \Bugsnag_Client $mock_client */
    $mock_client = $this->getMockBuilder('\Bugsnag_Client')
      ->setConstructorArgs(array('api_key'))
      ->getMock();
    $mock_client->expects($this->once())->method('notifyException')->with($exception, array(), 'error');

    $notifier = new BugsnagNotifier($mock_client);
    $notifier->notifyException($exception, 'error');
  }

  /**
   * Missing summary.
   */
  public function testGetClient() {
    /** @var \Bugsnag_Client $mock_client */
    $mock_client = $this->getMock('\Bugsnag_Client', array(), array('api_key'));

    $notifier = new BugsnagNotifier($mock_client);
    $client = $notifier->getClient();
    $this->assertEquals($client, $mock_client);
  }

  /**
   * Tests that user errors do not trigger notification.
   */
  public function testNoNotificationForUserError() {
    /** @var \Bugsnag_Client $mock_client */
    $mock_client = $this->getMockBuilder('\Bugsnag_Client')
      ->setConstructorArgs(array('api_key'))
      ->getMock();
    $mock_client
      ->expects($this->never())
      ->method('notifyError')
      ->with(IteratorStatus::getLabel(IteratorStatus::ERROR_USER), 'message');

    $notifier = new BugsnagNotifier($mock_client);
    $notifier->notifyError(IteratorStatus::getLabel(IteratorStatus::ERROR_USER), 'message');
  }

}
