<?php

namespace Acquia\WipService\Console;

use Acquia\WipService\Test\AbstractFunctionalTest;

/**
 * An abstract class that sets up tests for wip's commands.
 */
abstract class AbstractWipToolTest extends AbstractFunctionalTest {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->app->register(new WipConsoleServiceProvider(), array(
      'console.name' => 'Wip Command-line Tool',
      'console.version' => '0.0.1',
      'console.app_directory' => $this->app['root_dir'],
    ));
  }

}
