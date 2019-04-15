<?php

namespace Acquia\WipService\Console;

use Silex\Application;
use Symfony\Component\Console\Application as ConsoleApplication;

/**
 * Provides super-class functionality for the console application.
 */
class WipConsoleApplication extends ConsoleApplication {

  /**
   * The default command version.
   */
  const DEFAULT_VERSION = 'UNKNOWN';

  /**
   * The default command name.
   */
  const DEFAULT_NAME = 'UNKNOWN';

  /**
   * The Silex application that is bootstrapped.
   *
   * @var Application
   */
  protected $baseApplication;

  /**
   * The Directory in which the app is contained.
   *
   * @var string
   */
  protected $appDirectory;

  /**
   * Creates a new WipConsoleApplication.
   *
   * @param Application $application
   *   The application instance.
   * @param string $app_dir
   *   The root directory of the application.
   * @param string $name
   *   The name of the application.
   * @param string $version
   *   The version of the application.
   */
  public function __construct(
    Application $application,
    $app_dir,
    $name = self::DEFAULT_NAME,
    $version = self::DEFAULT_VERSION
  ) {
    parent::__construct($name, $version);

    $this->baseApplication = $application;
    $this->appDirectory = $app_dir;

    $application->boot();
  }

  /**
   * Retrieves the silex application.
   *
   * @return Application
   *   The application instance.
   */
  public function getBaseApplication() {
    return $this->baseApplication;
  }

  /**
   * Retrieves the project directory.
   *
   * @return string
   *   The root directory of the application.
   */
  public function getAppDirectory() {
    return $this->appDirectory;
  }

}
