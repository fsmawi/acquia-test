<?php

namespace Acquia\Wip\Signal;

/**
 * The CallbackBase class contains commonly-used method implementations from the CallbackInterface.
 */
abstract class CallbackBase implements CallbackInterface {

  /**
   * Arbitrary data associated with this Callback.
   *
   * @var object
   */
  protected $data;

  /**
   * {@inheritdoc}
   */
  public function getData() {
    return $this->data;
  }

  /**
   * {@inheritdoc}
   */
  public function setData($data) {
    if (!is_object($data)) {
      throw new \InvalidArgumentException('The data argument must be an object.');
    }

    $this->data = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return sprintf('Callback class: %s.', get_class($this));
  }

}
