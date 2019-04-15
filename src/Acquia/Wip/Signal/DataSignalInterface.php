<?php

namespace Acquia\Wip\Signal;

/**
 * Describes signal data with a simple data payload.
 */
interface DataSignalInterface {

  /**
   * Sets the data payload.
   *
   * @param object $payload
   *   The data payload.
   *
   * @throws \InvalidArgumentException
   *   If the payload parameter is not an object.
   */
  public function setPayload($payload);

  /**
   * Gets the data payload.
   *
   * @return object
   *   The data payload.
   */
  public function getPayload();

}
