<?php

namespace Acquia\WipService\Console;

use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\DependencyManager;
use Wrep\Daemonizable\Command\EndlessCommand;

/**
 * Provides base functionality to console commands that run for ever.
 */
class WipConsoleEndlessCommand extends EndlessCommand implements DependencyManagedInterface {
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
