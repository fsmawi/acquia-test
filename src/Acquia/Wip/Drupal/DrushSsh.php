<?php

namespace Acquia\Wip\Drupal;

use Acquia\Wip\Ssh\Ssh;

/**
 * The DrushSsh class is responsible for executing a single drush command.
 */
class DrushSsh extends Ssh {

  /**
   * The cache directory to use for this command.
   *
   * This will only be populated if a request is made to use a temporary
   * drush cache directory.
   *
   * @var string
   */
  private $cacheDir = NULL;

  /**
   * The drush executable to use.
   *
   * @var string
   */
  private $drushExecutable = '\drush6';

  /**
   * {@inheritdoc}
   */
  public function execCommand($command, $options = '--no-logs') {
    // Executing drush commands in the unit test environment isn't practical
    // because it requires a Drupal installation.
    // @codeCoverageIgnoreStart
    $this->createCacheDir();
    $drush_command = $this->createDrushCommand($command);
    $result = parent::execCommand($drush_command, $options);
    return $result;
    // @codeCoverageIgnoreEnd
  }

  /**
   * {@inheritdoc}
   */
  public function execAsyncCommand($command, $options = '', $data = NULL) {
    // Executing drush commands in the unit test environment isn't practical
    // because it requires a Drupal installation.
    // @codeCoverageIgnoreStart
    $this->createCacheDir();
    $drush_command = $this->createDrushCommand($command);
    $result = parent::execAsyncCommand($drush_command, $options, $data);
    return $result;
    // @codeCoverageIgnoreEnd
  }

  /**
   * Sets the drush executable to use.
   *
   * @param string $drush_executable
   *   The drush executable.
   *
   * @throws \InvalidArgumentException
   *   If the drush_executable argument is empty or not a string.
   */
  public function setDrushExecutable($drush_executable = '\drush6') {
    if (!is_string($drush_executable) || empty($drush_executable)) {
      throw new \InvalidArgumentException('The drush_executable argument must be a non-empty string.');
    }
    $this->drushExecutable = $drush_executable;
  }

  /**
   * Gets the drush executable that will be used.
   *
   * @return string
   *   The drush executable.
   */
  public function getDrushExecutable() {
    return $this->drushExecutable;
  }

  /**
   * Creates the cache directory.
   */
  public function createCacheDir() {
    $cache_dir = $this->getCacheDirectory();
    $command = sprintf('mkdir -p %s', escapeshellarg($cache_dir));
    $ssh = new Ssh();
    $create_dir = $ssh->initialize(
      $this->getEnvironment(),
      'Create drush cache directory.',
      $this->getLogger(),
      $this->getWipId()
    );
    $create_dir->execCommand($command);
  }

  /**
   * Retrieves the directory to be used for the drush cache.
   *
   * @return string
   *   The directory.
   */
  public function getCacheDirectory() {
    $cache_dir = $this->cacheDir;
    if (empty($cache_dir)) {
      // Not using a temporary directory.
      $environment = $this->getEnvironment();
      $cache_dir = $environment->getWorkingDir();
    }
    return $cache_dir;
  }

  /**
   * Sets the directory to be used for the drush cache.
   *
   * @param string $cache_dir
   *   The cache directory.
   */
  public function setCacheDirectory($cache_dir) {
    $this->cacheDir = $cache_dir;
  }

  /**
   * Indicates a temporary cache directory should be used for the drush command.
   *
   * In situations such as SiteUpdate in which many sites try to clear the drush
   * cache simultaneously, a conflict can arise causing some of the commands to
   * fail.
   */
  public function useTemporaryCache() {
    $drupal_site = $this->getDrupalSite();
    if (!empty($drupal_site)) {
      $domain_name = $drupal_site->getCurrentDomain();
    } else {
      $domain_name = '';
    }
    $environment = $this->getEnvironment();
    $this->cacheDir = sprintf('%s/%s', $environment->getWorkingDir(), md5($domain_name));
  }

  /**
   * Returns a fully formed drush command.
   *
   * @param string $command
   *   The drush operation and options e.g. "vget theme_default".
   *
   * @return string
   *   The complete drush command string that can be run from any directory on
   *   the remote server.
   */
  public function createDrushCommand($command = NULL) {
    if (empty($command)) {
      $command = $this->getCommand();
      if (empty($command)) {
        throw new \RuntimeException('Drush command not set.');
      }
    }
    $drush_executable = $this->getDrushExecutable();
    $cache_prefix = $this->getCacheDirectory();
    $drupal_site = $this->getDrupalSite();
    $environment = $this->getEnvironment();
    if (!empty($drupal_site)) {
      $drush_command = sprintf(
        'CACHE_PREFIX=%s %s --root=%s -l %s %s',
        escapeshellarg($cache_prefix),
        $drush_executable,
        escapeshellarg($environment->getDocrootDir()),
        escapeshellarg($drupal_site->getCurrentDomain()),
        $command
      );
    } else {
      $drush_command = sprintf(
        'CACHE_PREFIX=%s %s --root=%s %s',
        escapeshellarg($cache_prefix),
        $drush_executable,
        escapeshellarg($environment->getDocrootDir()),
        $command
      );
    }
    return $drush_command;
  }

  /**
   * Gets the DrupalSite instance, if provided.
   *
   * @return DrupalSite
   *   The DrupalSite, if it has been provided.
   */
  private function getDrupalSite() {
    $result = NULL;
    $environment = $this->getEnvironment();
    if ($environment instanceof DrupalSite) {
      $result = $environment;
    }
    return $result;
  }

}
