<?php

namespace Acquia\WipService\Console;

use Acquia\WipService\Console\Commands\WipProcessCommand;
use Acquia\WipService\Test\AbstractFunctionalTest;

/**
 * Missing summary.
 */
class WipConsoleApplicationTest extends AbstractWipToolTest {

  /**
   * Missing summary.
   */
  protected function getConsoleApp() {
    $application = $this->app['console'];
    $application->add(new WipProcessCommand());
    return $application;
  }

  /**
   * Missing summary.
   */
  public function testGetBaseApplication() {
    $app = $this->getConsoleApp();
    $this->assertNotEmpty($app->getBaseApplication());
  }

  /**
   * Missing summary.
   */
  public function testGetAppDirectory() {
    $app = $this->getConsoleApp();
    $this->assertNotEmpty($app->getAppDirectory());
  }

}
