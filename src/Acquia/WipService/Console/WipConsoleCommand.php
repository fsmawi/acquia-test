<?php

namespace Acquia\WipService\Console;

use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\DependencyManager;
use Symfony\Component\Console\Command\Command;

/**
 * Provides super-class functionality common to all console commands.
 */
class WipConsoleCommand extends Command implements DependencyManagedInterface {
  use WipConsoleCommandTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    parent::__construct();
    $this->dependencyManager = new DependencyManager();
    $this->dependencyManager->addDependencies($this->getDependencies());
  }

}
