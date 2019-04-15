<?php

namespace Acquia\WipService\Console;

use Acquia\WipService\Console\Commands\WipVersionDetailCommand;
use Acquia\WipService\Test\AbstractFunctionalTest;

/**
 * Tests the application for wipversions.
 */
class WipVersionApplicationTest extends AbstractWipVersionTest {

  /**
   * Tests getting the console application.
   */
  protected function getConsoleApp() {
    $application = $this->app['console'];
    $application->add(new WipVersionDetailCommand());
    return $application;
  }

  /**
   * Tests getting the base application.
   */
  public function testGetBaseApplication() {
    $app = $this->getConsoleApp();
    $this->assertNotEmpty($app->getBaseApplication());
  }

  /**
   * Tests getting the console application's directory.
   */
  public function testGetAppDirectory() {
    $app = $this->getConsoleApp();
    $this->assertNotEmpty($app->getAppDirectory());
  }

}
