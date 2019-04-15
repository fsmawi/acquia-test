<?php

namespace Acquia\Wip\Test\PublicStable\Drupal;

use Acquia\Wip\Drupal\DrupalSite;
use Acquia\Wip\Drupal\DrushSsh;
use Acquia\Wip\Environment;
use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\Implementation\SqliteWipLogStore;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\Ssh\Ssh;
use Acquia\Wip\Test\PublicStable\Ssh\SshTestSetup;
use Acquia\Wip\WipLogInterface;

/**
 * Missing summary.
 */
class DrushSshTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @group DrupalSsh
   */
  public function testConstructor() {
    $env = $this->createEnvironment();
    (new DrushSsh())->initialize($env, 'testing', $this->createWipLog(), 15);
  }

  /**
   * Missing summary.
   *
   * @group DrupalSsh
   */
  public function testSetDrushExecutable() {
    $executable = '\drush5';
    $env = $this->createEnvironment();
    $drush_ssh = (new DrushSsh())->initialize($env, 'testing', $this->createWipLog(), 15);
    $drush_ssh->setDrushExecutable($executable);
    $this->assertEquals($executable, $drush_ssh->getDrushExecutable());
  }

  /**
   * Missing summary.
   *
   * @group DrupalSsh
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetDrushExecutableNotString() {
    $executable = NULL;
    $env = $this->createEnvironment();
    $drush_ssh = (new DrushSsh())->initialize($env, 'testing', $this->createWipLog(), 15);
    $drush_ssh->setDrushExecutable($executable);
  }

  /**
   * Missing summary.
   *
   * @group DrupalSsh
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetDrushExecutableEmpty() {
    $executable = '';
    $env = $this->createEnvironment();
    $drush_ssh = (new DrushSsh())->initialize($env, 'testing', $this->createWipLog(), 15);
    $drush_ssh->setDrushExecutable($executable);
  }

  /**
   * Missing summary.
   *
   * @group DrupalSsh
   */
  public function testCreateCacheDirectory() {
    $env = SshTestSetup::setUpLocalSsh();
    $exception = NULL;
    $ssh_process = NULL;
    $is_running = NULL;
    $logger = SshTestSetup::createWipLog();
    $dir = sprintf('%s/drush_cache', sys_get_temp_dir());
    try {
      // Verify the cache directory does not exist.
      $ssh = (new Ssh())->initialize($env, 'Verify cache dir does not exist.', $logger, 1);
      $ssh_result = $ssh->execCommand(sprintf('ls -l %s', $dir));
      $this->assertFalse($ssh_result->isSuccess());

      $drush_ssh = (new DrushSsh())->initialize($env, 'testing', $logger, 15);
      $drush_ssh->setCacheDirectory($dir);
      $this->assertEquals($dir, $drush_ssh->getCacheDirectory());
      $drush_ssh->createCacheDir();

      // Verify the cache directory does exist.
      $ssh = (new Ssh())->initialize($env, 'Verify cache dir does exist.', $logger, 1);
      $ssh_result = $ssh->execCommand(sprintf('ls -l %s', $dir));
      $this->assertTrue($ssh_result->isSuccess());

      // Delete the cache directory.
      $ssh = (new Ssh())->initialize($env, 'Delete the drupal cache dir.', $logger, 1);
      $ssh_result = $ssh->execCommand(sprintf('rm -rf %s', $dir));
      $this->assertTrue($ssh_result->isSuccess());
    } catch (\Exception $e) {
      $exception = $e;
    }
    // Now remove the key.
    SshTestSetup::clearLocalSsh();

    if (isset($exception)) {
      throw $exception;
    }
  }

  /**
   * Missing summary.
   *
   * @group DrupalSsh
   */
  public function testGetCacheDir() {
    $env = $this->createEnvironment();
    $drush_ssh = (new DrushSsh())->initialize($env, 'testing', $this->createWipLog(), 15);
    $dir = $drush_ssh->getCacheDirectory();
    $this->assertEquals('/mnt/tmp/sitefactory.prod', $dir);
  }

  /**
   * Missing summary.
   *
   * @group DrupalSsh
   */
  public function testUseTemporaryCache() {
    $env = $this->createEnvironment();
    $drush_ssh = (new DrushSsh())->initialize($env, 'testing', $this->createWipLog(), 15);
    $drush_ssh->useTemporaryCache();
    $dir = $drush_ssh->getCacheDirectory();
    $this->assertNotEquals('/mnt/tmp/sitefactory.prod', $dir);
    $this->assertStringStartsWith('/mnt/tmp/sitefactory.prod', $dir);
  }

  /**
   * Missing summary.
   *
   * @group DrupalSsh
   */
  public function testUseTemporaryCacheWithDomain() {
    $domains = array('domain1');
    $drupal_site = new DrupalSite($domains);
    $env = $this->createEnvironment($drupal_site);
    $drush_ssh = (new DrushSsh())->initialize($env, 'testing', $this->createWipLog(), 15);
    $drush_ssh->useTemporaryCache();
    $dir = $drush_ssh->getCacheDirectory();
    $this->assertNotEquals('/mnt/tmp/sitefactory.prod', $dir);
    $this->assertStringStartsWith('/mnt/tmp/sitefactory.prod', $dir);
  }

  /**
   * Missing summary.
   *
   * @group DrupalSsh
   */
  public function testCreateDrushCommand() {
    $domains = array('domain1');
    $drupal_site = new DrupalSite($domains);
    $env = $this->createEnvironment($drupal_site);
    $drush_ssh = (new DrushSsh())->initialize($env, 'testing', $this->createWipLog(), 15);
    $command = 'cache-clear all';
    $ssh_command = $drush_ssh->createDrushCommand($command);
    $this->assertStringMatchesFormat("CACHE_PREFIX=%s \drush6 --root=%s -l 'domain1' cache-clear all", $ssh_command);
  }

  /**
   * Missing summary.
   *
   * @group DrupalSsh
   */
  public function testCreateDrushCommandWithPresetCommand() {
    $domains = array('domain1');
    $drupal_site = new DrupalSite($domains);
    $env = $this->createEnvironment($drupal_site);
    $drush_ssh = (new DrushSsh())->initialize($env, 'testing', $this->createWipLog(), 15);
    $drush_ssh->setCommand('cache-clear all');
    $ssh_command = $drush_ssh->createDrushCommand();
    $this->assertStringMatchesFormat("CACHE_PREFIX=%s \drush6 --root=%s -l 'domain1' cache-clear all", $ssh_command);
  }

  /**
   * Missing summary.
   *
   * @group DrupalSsh
   */
  public function testCreateDrushCommandNoDomain() {
    $env = $this->createEnvironment();
    $drush_ssh = (new DrushSsh())->initialize($env, 'testing', $this->createWipLog(), 15);
    $drush_ssh->setCommand('cache-clear all');
    $ssh_command = $drush_ssh->createDrushCommand();
    $this->assertStringMatchesFormat("CACHE_PREFIX=%s \drush6 --root=%s cache-clear all", $ssh_command);
  }

  /**
   * Missing summary.
   *
   * @group DrupalSsh
   *
   * @expectedException \RuntimeException
   */
  public function testCreateDrushCommandNoCommandSet() {
    $env = $this->createEnvironment();
    $drush_ssh = (new DrushSsh())->initialize($env, 'testing', $this->createWipLog(), 15);
    $drush_ssh->createDrushCommand();
  }

  /**
   * Missing summary.
   */
  private function addServers(Environment $environment) {
    $environment->setServers(array('server1', 'server2', 'server3', 'server4'));
  }

  /**
   * Missing summary.
   */
  public function createEnvironment(EnvironmentInterface $environment = NULL) {
    if (empty($environment)) {
      $result = new Environment();
    } else {
      $result = $environment;
    }
    $result->setSitegroup('sitefactory');
    $result->setEnvironmentName('prod');
    $this->addServers($result);
    $result->selectNextServer();
    return $result;
  }

  /**
   * Missing summary.
   *
   * @return WipLogInterface
   *   The log
   */
  public function createWipLog() {
    $result = new WipLog(new SqliteWipLogStore());
    return $result;
  }

  /**
   * Missing summary.
   */
  public function createDrushSsh() {
    $description = 'testing';
    $env = $this->createEnvironment();
    $logger = $this->createWipLog();
    return (new DrushSsh())->initialize($env, $description, $logger, 15);
  }

}
