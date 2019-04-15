<?php

namespace Acquia\Wip;

use Acquia\Wip\Exception\InvalidOperationException;
use Acquia\Wip\Objects\Modules\WipModuleConfigReader;

/**
 * Describes all relevant values of a wip module residing in the Wip project.
 */
class NativeWipModule extends WipModule {

  /**
   * The directory in which the module files reside.
   *
   * @var string
   */
  private $directory = 'src/Acquia/Wip/Modules/NativeModule';

  /**
   * The absolute path to the directory with all the modules in it.
   *
   * @var string
   */
  private $moduleDirectory = NULL;

  /**
   * The directory prefix.
   *
   * @var string
   */
  private $directoryPrefix = '';

  /**
   * The path to the module's config file.
   *
   * @var string
   */
  private $configFilePath = '';

  /**
   * Creates an instance of NativeWipModule.
   *
   * @param string $name
   *   The name of this module.
   */
  public function __construct($name = 'NativeModule') {
    parent::__construct($name);

    // A lot of the config for the native module should stay constant between
    // instances- set those here.
    $directory_prefix = WipFactory::getString('$wip.modules.native_module.directory_prefix');
    if (empty($directory_prefix)) {
      $env = Environment::getRuntimeEnvironment();
      $directory_prefix = sprintf(
        '/var/www/html/%s.%s',
        $env->getSitegroup(),
        $env->getEnvironmentName()
      );
    }
    if (!is_readable($directory_prefix)) {
      // This may be a test environment.
      $directory_prefix = getcwd();
    }
    $this->directoryPrefix = $directory_prefix;
    $this->moduleDirectory = $this->directoryPrefix . DIRECTORY_SEPARATOR . $this->directory;

    $this->configFilePath = sprintf('%s/module.ini', $this->moduleDirectory);
    $config_file_contents = file_get_contents($this->configFilePath);
    WipModuleConfigReader::populateModule($this, $config_file_contents);
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function enable() {
    // No-op.
  }

  /**
   * {@inheritdoc}
   */
  public function disable() {
    throw new InvalidOperationException('NativeModule cannot be disabled.');
  }

  /**
   * {@inheritdoc}
   */
  public function isReady() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function setReady($ready) {
    // No-op.
  }

  /**
   * {@inheritdoc}
   */
  public function getModuleDirectory() {
    return $this->moduleDirectory;
  }

  /**
   * {@inheritdoc}
   */
  public function setVcsUri($vcs_uri) {
    throw new \DomainException('Cannot set the VCS URI on a native Wip module.');
  }

  /**
   * {@inheritdoc}
   */
  public function getVcsUri() {
    throw new \DomainException('Cannot get the VCS URI on a native Wip module.');
  }

  /**
   * {@inheritdoc}
   */
  public function setVcsPath($vcs_path) {
    throw new \DomainException('Cannot set the VCS path on a native Wip module.');
  }

  /**
   * {@inheritdoc}
   */
  public function getVcsPath() {
    throw new \DomainException('Cannot get the VCS path on a native Wip module.');
  }

}
