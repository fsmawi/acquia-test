<?php

namespace Acquia\WipService\Console;

use Acquia\WipService\Test\AbstractFunctionalTest;

/**
 * An abstract class that sets up tests for wipversion's commands.
 */
abstract class AbstractWipVersionTest extends AbstractFunctionalTest {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->app->register(new WipConsoleServiceProvider(), array(
      'console.name' => 'Wip Version Tool',
      'console.version' => '0.0.1',
      'console.app_directory' => $this->app['root_dir'],
    ));
  }

}
