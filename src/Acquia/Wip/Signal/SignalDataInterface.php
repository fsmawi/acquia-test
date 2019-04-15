<?php

namespace Acquia\Wip\Signal;

/**
 * This interface provides a means of interacting with signal data generically.
 */
interface SignalDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getState();

  /**
   * {@inheritdoc}
   */
  public function getType();

}
