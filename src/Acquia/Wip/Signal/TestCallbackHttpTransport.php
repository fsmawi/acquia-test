<?php

namespace Acquia\Wip\Signal;

/**
 * Missing summary.
 */
class TestCallbackHttpTransport implements SignalCallbackHttpTransportInterface {

  /**
   * This test implementation will always return whatever signal was last set.
   *
   * @var SignalInterface
   */
  private $signal;

  /**
   * A string to use as the callback host during unit testing.
   *
   * @var string
   */
  private $callbackUrl = '';

  /**
   * {@inheritdoc}
   */
  public function getCallbackUrl($wip_id, $type = SignalType::COMPLETE) {
    return $this->callbackUrl;
  }

  /**
   * {@inheritdoc}
   */
  public function resolveSignal($uuid) {
    return $this->signal;
  }

  /**
   * {@inheritdoc}
   */
  public function callUrl($url, $data) {
    // Stub - does nothing, goes nowhere.
  }

  /**
   * Missing summary.
   */
  public function setSignal(SignalInterface $signal) {
    $this->signal = $signal;
  }

  /**
   * {@inheritdoc}
   */
  public function releaseCallback($uuid) {
    // Stub - does nothing, goes nowhere.
  }

  /**
   * Sets the callback host to use during testing.
   *
   * @param string $url
   *   The callback URL.
   */
  public function setCallbackUrl($url) {
    $this->callbackUrl = $url;
  }

}
