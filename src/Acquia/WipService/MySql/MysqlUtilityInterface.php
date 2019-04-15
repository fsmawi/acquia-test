<?php

namespace Acquia\WipService\MySql;

/**
 * Provides a common interface to build mysql commands.
 */
interface MysqlUtilityInterface {

  /**
   * Dump the wipservice database.
   *
   * @return string
   *   Message indicating where the dump was created.
   */
  public function databaseDump();

  /**
   * Delete database backups.
   */
  public function deleteBackups();

}
