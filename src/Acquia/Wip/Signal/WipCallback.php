<?php

namespace Acquia\Wip\Signal;

use Acquia\Wip\Storage\SignalStoreInterface;
use Acquia\Wip\WipFactory;

/**
 * The WipCallback is used to send signals within the Wip runtime.
 */
class WipCallback extends CallbackBase implements CallbackInterface {

  /**
   * The ID of the Wip object that will receive the signal.
   *
   * @var int
   */
  private $wipId;

  /**
   * Creates a new instance that signals the specified Wip object.
   *
   * @param int $wip_id
   *   The ID of the Wip object that will receive the signal.
   */
  public function __construct($wip_id) {
    $this->wipId = $wip_id;
  }

  /**
   * {@inheritdoc}
   */
  public function send(SignalInterface $signal) {
    $signal->setObjectId($this->wipId);
    /** @var SignalStoreInterface $signal_storage */
    $signal_storage = WipFactory::getObject('acquia.wip.storage.signal');
    $signal_storage->send($signal);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $base = parent::getDescription();
    return sprintf('%s: Target WIP ID: %d.', $base, $this->wipId);
  }

}
