<?php

namespace Acquia\Wip\Test\PublicStable\Resource;

use Acquia\Wip\DependencyManagedInterface;

/**
 * Class to test interdependence in DependencyManager.
 */
class InterdependentB implements DependencyManagedInterface {

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.phpunit.interdependenta' => 'Acquia\Wip\Test\PublicStable\Resource\InterdependentA',
    );
  }

}
