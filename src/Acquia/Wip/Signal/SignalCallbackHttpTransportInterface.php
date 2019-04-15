<?php

namespace Acquia\Wip\Signal;

/**
 * Interface SignalCallbackHttpTransportInterface.
 *
 * Defines methods that must be implemented by a HTTP signal callback handler.
 *
 * This class includes methods for generating and calling callback URLs.
 */
interface SignalCallbackHttpTransportInterface {

  /**
   * Gets a callback URL that can be used for sending signals to a WIP.
   *
   * @param int $wip_id
   *   The ID of the WIP object that the signal will apply to.
   * @param int $type
   *   The type of signal for which to generate a callback.
   *
   * @return string
   *   A callback path that can be used to send a signal back to the WIP or SSH
   *   process.
   */
  public function getCallbackUrl($wip_id, $type = SignalType::COMPLETE);

  /**
   * Produces a signal based on callback URL parameters.
   *
   * The signal produced here is a general Signal, not a domain-specific signal
   * type.
   *
   * @param string $uuid
   *   The UUID obtained from the URL path.
   *
   * @return SignalInterface
   *   A full Signal instance that can be sent to a WIP object.
   */
  public function resolveSignal($uuid);

  /**
   * Calls the given callback URL.
   *
   * @param string $url
   *   The URL to call.
   * @param object $data
   *   Any additional data to pass to the callback URL.
   */
  public function callUrl($url, $data);

  /**
   * Releases a callback once it is no longer needed.
   *
   * The purpose of this is for implementations that use a mapping in storage -
   * this allows those implementations to free up storage that is no longer
   * needed.
   *
   * @param string $uuid
   *   The UUID of the callback.
   */
  public function releaseCallback($uuid);

}
