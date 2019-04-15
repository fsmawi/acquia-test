<?php

namespace Acquia\Wip\Test\PublicStable\Resource;

use Acquia\Wip\DependencyManagedInterface;

/**
 * Class to test interdependence in DependencyManager.
 */
class InterdependentA implements DependencyManagedInterface {

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.phpunit.interdependentb' => 'Acquia\Wip\Test\PublicStable\Resource\InterdependentB',
    );
  }

}
