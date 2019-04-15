<?php

namespace Acquia\WipService\MySql;

use Acquia\Wip\Environment;

/**
 * Provides mysql utility functions.
 */
class UtilityOverride extends Utility implements MysqlUtilityInterface {

  /**
   * Get the base path for backups.
   *
   * @return string
   *   The base path for backups.
   */
  protected function getBackUpBasePath() {
    // Travis can't write to mount and wip service stage can't write to anything in install
    // root. This allows us to have tests pass in both places.
    if (Environment::getRuntimeSitegroup() == 'travis') {
      return 'dumps';
    } else {
      return parent::getBackUpBasePath();
    }
  }

  /**
   * Provide a default value for tests.
   *
   * @param string $filename
   *   The file name.
   *
   * @return int
   *   The time the file was last modified.
   */
  protected function getFiletime($filename) {
    return 1000000;
  }

  /**
   * Avoid doing any file removal during tests.
   */
  public function deleteBackups() {
  }

}
