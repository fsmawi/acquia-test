<?php

namespace Acquia\WipService\MySql;

use Acquia\WipService\App;
use Acquia\Wip\Environment;
use Symfony\Component\Process\Process;

/**
 * Provides mysql utility functions.
 */
class Utility implements MysqlUtilityInterface {

  const ON_DEMAND_DIR = 'on-demand';

  /**
   * Database configuration.
   *
   * @var array
   */
  protected $dbConfig;

  /**
   * Minimum backup counts.
   *
   * A default is always provided that can be overridden.
   *
   * @var array
   */
  protected $minimumCount = ['default' => 2];

  /**
   * The maximum age of a backup.
   *
   * Note if we do not have enough backups this is ignored.
   *
   * @var int
   */
  protected $maximumAge;

  /**
   * Creates a new instance of mysql utility class.
   */
  public function __construct() {
    $app = App::getApp();
    $this->dbConfig = $app['db.options'];
    // Override minimum number of backups to keep.
    if (isset($app['config.backups']['minimum_count'])) {
      $this->minimumCount = array_merge($this->minimumCount, $app['config.backups']['minimum_count']);
    }
    $limit = !empty($app['config.backups']['maximum_age']) ? $app['config.backups']['maximum_age'] : '14 days';
    $this->maximumAge = strtotime($limit, 0);
  }

  /**
   * Get the base path for backups.
   *
   * @return string
   *   The base path for backups.
   */
  protected function getBackUpBasePath() {
    $site_group = Environment::getRuntimeSitegroup();
    $environment = Environment::getRuntimeEnvironmentName();
    return "/mnt/files/$site_group.$environment/backups";
  }

  /**
   * Get the minimum number of backups we need to keep for a given directory.
   *
   * @param string $dir
   *   The directory name.
   *
   * @return int
   *   The minimum number of backups to keep.
   */
  private function getMinimumBackupCount($dir) {
    return isset($this->minimumCount[$dir]) ? $this->minimumCount[$dir] : $this->minimumCount['default'];
  }

  /**
   * Get the time the file was last modified.
   *
   * @param string $filename
   *   The file name.
   *
   * @return int
   *   The time the file was last modified.
   */
  protected function getFiletime($filename) {
    return filemtime($filename);
  }

  /**
   * Check if the file needs to be deleted.
   *
   * @param string $filename
   *   The file name.
   * @param int $check_time
   *   Unix timestamp.
   *
   * @return bool
   *   Is the file older than the allowed age.
   */
  private function fileIsOld($filename, $check_time) {
    return $check_time - $this->getFiletime($filename) > $this->maximumAge;
  }

  /**
   * Delete files older than the maximum file age.
   *
   * @param array $files
   *   List of file names.
   */
  private function deleteFiles($files) {
    $current_time = time();
    foreach ($files as $filename) {
      if ($this->fileIsOld($filename, $current_time)) {
        unlink($filename);
      }
    }
  }

  /**
   * Ensure that we keep the minimum number of backups.
   *
   * @param array $files
   *   List of file names.
   * @param int $minimum_count
   *   The minimum number of files to keep.
   *
   * @return array
   *   List of file names to process.
   */
  private function keepMinimumBackups($files, $minimum_count) {
    $files_time = [];
    foreach ($files as $file_name) {
      $files_time[$file_name] = $this->getFiletime($file_name);
    }
    asort($files_time);
    // Keep the latest n files no matter the age.
    $files_time = array_slice($files_time, 0, count($files_time) - $minimum_count, TRUE);
    return array_keys($files_time);
  }

  /**
   * Get a list of files from a given directory with a given mask.
   *
   * @param string $dir
   *   Directory name.
   * @param string $mask
   *   File mask.
   *
   * @return array
   *   List of files found in the directory with the specific mask.
   */
  private function getFileList($dir, $mask = '*.gz') {
    return glob($this->getBackUpBasePath() . '/' . $dir . '/' . $mask);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteBackups() {
    foreach (['.', self::ON_DEMAND_DIR] as $dir) {
      $files = $this->getFileList($dir);
      $minimum_count = $this->getMinimumBackupCount($dir);
      if (count($files) > $minimum_count) {
        if ($minimum_count > 0) {
          $files = $this->keepMinimumBackups($files, $minimum_count);
        }
        $this->deleteFiles($files);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function databaseDump() {
    $user = escapeshellarg($this->dbConfig['user']);
    $pass = escapeshellarg($this->dbConfig['password']);
    $name = escapeshellarg($this->dbConfig['dbname']);
    $host = escapeshellarg($this->dbConfig['host']);
    $base_path = $this->getBackUpBasePath();
    // Ensure the directory exists.
    if (!file_exists($base_path)) {
      mkdir($base_path, 0777, TRUE);
    }
    $path = $base_path . '/' . $this->dbConfig['dbname'] . '-' . date("Y-m-d_H-i-s") . '.gz';

    if (empty($this->dbConfig['password'])) {
      $command = sprintf(
        'mysqldump --user=%s --databases %s --single-transaction --host=%s | gzip > %s',
        $user,
        $name,
        $host,
        $path
      );
    } else {
      $command = sprintf(
        'mysqldump --user=%s --password=%s --databases %s --single-transaction --host=%s | gzip > %s',
        $user,
        $pass,
        $name,
        $host,
        $path
      );
    }

    $process = new Process($command);
    // Allow as much time as needed to dump the db.
    $process->setTimeout(NULL);
    $process->run();

    // We store zips which when empty are 20 bytes.
    if (!$process->isSuccessful() || !file_exists($path) || filesize($path) <= 20) {
      // In certain cases an invalid file will be created.
      if (file_exists($path)) {
        unlink($path);
      }
      throw new \Exception(sprintf('Database dump could not be created at: %s', $path));
    } else {
      return sprintf('Database dump created at: %s', $path);
    }
  }

}
