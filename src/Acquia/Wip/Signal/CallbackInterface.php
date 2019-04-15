<?php

namespace Acquia\Wip\Signal;

/**
 * This interface is used to encapsulate Callback information.
 *
 * A callback is used to send a signal when a callback was requested. The
 * callback could post data to a URI, send an email, call an internal API, post
 * to Twitter, etc.
 */
interface CallbackInterface {

  /**
   * Sends the specified signal.
   *
   * @param SignalInterface $signal
   *   The signal to send.
   *
   * @return bool
   *   TRUE if the signal was successfully sent; FALSE otherwise.
   */
  public function send(SignalInterface $signal);

  /**
   * Retrieves any arbitrary data set on a callback object.
   *
   * @return object
   *   The data object.
   */
  public function getData();

  /**
   * Sets arbitrary data to merge into the signal data before sending.
   *
   * @param object $data
   *   The data object to store.
   */
  public function setData($data);

  /**
   * Produces a detailed description of the callback for use in logging.
   *
   * @return string
   *   The description.
   */
  public function getDescription();

}
