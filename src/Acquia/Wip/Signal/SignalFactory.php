<?php

namespace Acquia\Wip\Signal;

use Acquia\Wip\WipFactory;

/**
 * Creates a domain-specific signal from a generic signal.
 */
class SignalFactory implements SignalFactoryInterface {

  /**
   * Returns a domain-specific signal for the specified signal.
   *
   * @param SignalInterface $signal
   *   The signal.
   *
   * @return SignalInterface
   *   The domain-specific signal.
   */
  public static function getDomainSpecificSignal(SignalInterface $signal) {
    // Note: Not using the dependency manager because we don't know beforehand
    // what signals are available.
    $signal_data = $signal->getData();
    if (empty($signal_data->classId)) {
      throw new \InvalidArgumentException('The classId field must be populated in the signal data.');
    }
    $signal_class = $signal_data->classId;

    /** @var SignalInterface $new_signal */
    $new_signal = NULL;
    $new_signal_type = WipFactory::getObject($signal_class);
    if (!empty($new_signal_type)) {
      $new_signal = new $new_signal_type();
      if ($new_signal instanceof SignalInterface) {
        $new_signal->initializeFromSignal($signal);
      }
    }
    return $new_signal;
  }

}
