<?php

namespace Acquia\Wip\Signal;

/**
 * Describes the interface for creating domain-specific Signal instances.
 */
interface SignalFactoryInterface {

  /**
   * Returns a domain-specific signal for the specified signal.
   *
   * @param SignalInterface $signal
   *   The signal.
   *
   * @return SignalInterface
   *   The domain-specific signal.
   */
  public static function getDomainSpecificSignal(SignalInterface $signal);

}
