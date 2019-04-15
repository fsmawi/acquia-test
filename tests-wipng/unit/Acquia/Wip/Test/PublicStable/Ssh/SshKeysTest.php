<?php

namespace Acquia\Wip\Test\PublicStable\Ssh;

use Acquia\Wip\Environment;
use Acquia\Wip\Ssh\SshKeys;
use Acquia\Wip\Test\Integration\PublicStable\AcquiaCloud\AcquiaCloudTestSetup;

/**
 * Missing summary.
 */
class SshKeysTest extends \PHPUnit_Framework_TestCase {
  private $originalSitegroup = NULL;
  private $originalEnvironmentName = NULL;
  private $keyPath = NULL;
  private $logger = NULL;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    try {
      $this->originalSitegroup = Environment::getRuntimeSitegroup();
    } catch (\Exception $e) {
      Environment::setRuntimeSitegroup('testing');
    }
    try {
      $this->originalEnvironmentName = Environment::getRuntimeEnvironmentName();
    } catch (\Exception $e) {
      Environment::setRuntimeEnvironmentName('prod');
    }
    $this->logger = AcquiaCloudTestSetup::createWipLog();

    $this->keyPath = sprintf('%s/keyPath', sys_get_temp_dir());
    SshKeys::setBasePath($this->keyPath);
  }

  /**
   * Missing summary.
   */
  public function tearDown() {
    parent::tearDown();
    if (!isset($this->originalSitegroup)) {
      Environment::setRuntimeSitegroup(NULL);
    }
    if (!isset($this->originalEnvironmentName)) {
      Environment::setRuntimeEnvironmentName(NULL);
    }
    SshKeys::setBasePath(NULL);
    exec(sprintf('rm -rf %s', escapeshellarg($this->keyPath)));
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testGetKeyPath() {
    $env = AcquiaCloudTestSetup::getEnvironment();
    $ssh_keys = new SshKeys();
    $private_key_path = $ssh_keys->getPrivateKeyPath($env);
    $this->assertNotEmpty($private_key_path);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testHasKeyNoKey() {
    $env = AcquiaCloudTestSetup::getEnvironment();
    $ssh_keys = new SshKeys();
    $ssh_keys->deleteKey($env);
    $this->assertFalse($ssh_keys->hasKey($env));
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testCreateKey() {
    $env = AcquiaCloudTestSetup::getEnvironment();
    $ssh_keys = new SshKeys();
    $ssh_keys->createKey($env);
    $this->assertTrue($ssh_keys->hasKey($env));
  }

  /**
   * Test that we can create a key for a specified user.
   *
   * @group Ssh
   */
  public function testCreateKeyAsUser() {
    $user = posix_getpwuid(posix_geteuid())['name'];
    $env = AcquiaCloudTestSetup::getEnvironment();
    $ssh_keys = new SshKeys();
    $ssh_keys->createKey($env, $user);
    $this->assertTrue($ssh_keys->hasKey($env));
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testBasePath() {
    SshKeys::setBasePath(NULL);
    $ssh_keys = new SshKeys();
    $path = $ssh_keys->getBasePath();
    $this->assertNotEmpty($path);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \Exception
   */
  public function testKeyFailure() {
    SshKeys::setBasePath('/proc');
    $env = AcquiaCloudTestSetup::getEnvironment();
    $ssh_keys = new SshKeys();
    $ssh_keys->createKey($env);
  }

  /**
   * Missing summary.
   *
   * @param string $base_path
   *   The base path.
   * @param string $relative_path
   *   The relative path.
   * @param string $expected
   *   The expected result.
   *
   * @group Ssh
   *
   * @dataProvider relativePathProvider
   */
  public function testRealivePath($base_path, $relative_path, $expected) {
    $env = AcquiaCloudTestSetup::getEnvironment();
    $ssh_keys = new SshKeys();
    $ssh_keys::setBasePath($base_path);
    $ssh_keys->setRelativeKeyPath($relative_path);
    $private_key = $ssh_keys->getPrivateKeyPath($env);
    $public_key = $ssh_keys->getPublicKeyPath($env);
    $this->assertEquals($expected, $private_key);
    $this->assertEquals(sprintf('%s.pub', $expected), $public_key);
  }

  /**
   * Missing summary.
   */
  public function relativePathProvider() {
    return array(
      array('/base/path', 'relative/key', '/base/path/relative/key'),
      array('/base', '/relative/key', '/base/relative/key'),
      array('/base/path', 'key', '/base/path/key'),
      array('/home/local/.ssh', 'id_rsa', '/home/local/.ssh/id_rsa'),
    );
  }

}
