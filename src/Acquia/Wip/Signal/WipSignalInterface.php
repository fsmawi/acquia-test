<?php

namespace Acquia\Wip\Signal;

/**
 * This is a tag interface used to identify the source of a signal.
 *
 * This particular interface should be added to any signal implementation that
 * is used for asynchronous Wip tasks.
 */
interface WipSignalInterface {

  /**
   * Gets the state this signal is associated with.
   *
   * @return string
   *   The name of the associated state.
   */
  public function getState();

}
