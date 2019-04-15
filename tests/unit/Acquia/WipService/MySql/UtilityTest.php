<?php

namespace Acquia\WipService\Test;

use Acquia\WipService\MySql\UtilityOverride;

/**
 * Test the mysql utility class.
 */
class UtilityTest extends \PHPUnit_Framework_TestCase {

  /**
   * Provide data for backup count checking.
   *
   * @return array
   *   Directory to test and expected backup count.
   */
  public function backupCountDataProvider() {
    return [
      ['', 2],
      ['random-dir', 2],
      // Overriden in config.
      [UtilityOverride::ON_DEMAND_DIR, 2],
    ];
  }

  /**
   * Test backup counts.
   *
   * @param string $dir
   *   The directory we are checking limits for.
   * @param int $limit
   *   The excepted number of backups to keep.
   *
   * @dataProvider backupCountDataProvider
   */
  public function testMinimumBackupCounts($dir, $limit) {
    // Use the test class as it provides some sane overrides.
    $utility = new UtilityOverride();
    $result = $this->invokeMethod($utility, 'getMinimumBackupCount', ['dir' => $dir]);
    $this->assertEquals($result, $limit);
  }

  /**
   * Provide data for backups to be kept.
   *
   * @return array
   *   Items to keep and files that should remain.
   */
  public function keepBackupDataProvider() {
    return [
      [0, ['file3.gz', 'file2.gz', 'file1.gz']],
      [1, ['file3.gz', 'file2.gz']],
      [2, ['file3.gz']],
      [3, []],
    ];
  }

  /**
   * Test backup counts.
   *
   * @param int $minimum_count
   *   The count of items to keep.
   * @param array $results
   *   The expected response from the method.
   *
   * @dataProvider keepBackupDataProvider
   */
  public function testKeepMinimumBackups($minimum_count, $results) {
    $utility = new UtilityOverride();
    $options = [
      'files' => [
        'file1.gz',
        'file2.gz',
        'file3.gz',
      ],
      'minimum_count' => $minimum_count,
    ];
    $result = $this->invokeMethod($utility, 'keepMinimumBackups', $options);
    $this->assertEquals($result, $results);
  }

  /**
   * Provide data for backups to be kept.
   *
   * @return array
   *   Items to keep and files that should remain.
   */
  public function ageDataProvider() {
    return [
      [time(), TRUE],
      [0, FALSE],
    ];
  }

  /**
   * Test backup counts.
   *
   * @param int $time
   *   Unix timestamp.
   * @param bool $expected_result
   *   The result excepted from the method being called.
   *
   * @dataProvider ageDataProvider
   */
  public function testFileIsOld($time, $expected_result) {
    $utility = new UtilityOverride();
    $result = $this->invokeMethod($utility, 'fileIsOld', ['filename' => 'test', 'check_time' => $time]);
    $this->assertEquals($result, $expected_result);
  }

  /**
   * Call protected/private method of a class.
   *
   * @param object &$object
   *   Instantiated object that we will run method on.
   * @param string $method_name
   *   Method name to call.
   * @param array $parameters
   *   Array of parameters to pass into method.
   *
   * @return mixed
   *   Method return.
   */
  public function invokeMethod(&$object, $method_name, array $parameters = []) {
    $reflection = new \ReflectionClass(get_class($object));
    $method = $reflection->getMethod($method_name);
    $method->setAccessible(TRUE);
    return $method->invokeArgs($object, $parameters);
  }

}
