<?php

namespace Acquia\Wip\Test\PublicStable\Ssh;

use Acquia\Wip\Ssh\SshFileCommands;
use Acquia\Wip\Ssh\SshKeys;
use Acquia\Wip\Ssh\SshService;
use Acquia\Wip\Ssh\StatResultInterpreter;
use Acquia\Wip\WipFactory;

/**
 * Missing summary.
 */
class SshFileCommandsTest extends \PHPUnit_Framework_TestCase {

  /**
   * The temp dir.
   *
   * @var string
   */
  private $tmpDir;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setup();
    WipFactory::reset();
    $this->tmpDir = sprintf('%s/wip_ssh', sys_get_temp_dir());
    if (!file_exists($this->tmpDir)) {
      mkdir($this->tmpDir, 0777, TRUE);
    }
  }

  /**
   * Test that SSH file operations can be secured.
   *
   * @group Ssh
   */
  public function testSecureSsh() {
    $env = SshTestSetup::setUpLocalSsh();
    $logger = SshTestSetup::createWipLog();

    try {
      $ssh_keys = new SshKeys();
      $ssh_service = new SshService();
      $ssh_service->setKeyPath($ssh_keys->getPrivateKeyPath($env));
      $file = new SshFileCommands($env, 0, $logger, $ssh_service);
      $file->setSecure(TRUE);
      $this->assertTrue($file->isSecure());
      $process = $file->exists($this->tmpDir);
      $this->assertTrue($process->isSecure());
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
   * @group Ssh
   */
  public function testWriteFile() {
    $contents = <<<EOT
    Contents of the file.
    Includes a newline to be sure.
EOT;
    $path = sprintf('%s/testFile1.txt', $this->tmpDir);

    $env = SshTestSetup::setUpLocalSsh();
    $logger = SshTestSetup::createWipLog();
    $exception = NULL;
    $result = NULL;
    $md5_result = NULL;
    $unlink_result = NULL;

    try {
      $ssh_keys = new SshKeys();
      $ssh_service = new SshService();
      $ssh_service->setKeyPath($ssh_keys->getPrivateKeyPath($env));
      $file = new SshFileCommands($env, 0, $logger, $ssh_service);
      $result = $file->writeFile($contents, $path)->exec();
      $md5_result = $file->getMd5Sum($path)->exec();
      $unlink_result = $file->unlink($path)->exec();
    } catch (\Exception $e) {
      $exception = $e;
    }

    // Now remove the key.
    SshTestSetup::clearLocalSsh();

    if (isset($exception)) {
      throw $exception;
    }
    // This is done at the end so the above cleanup leaves the machine in the
    // same state as before the test.
    $this->assertTrue($result->isSuccess());
    $this->assertEquals(md5($contents), trim($md5_result->getStdout()));
    $this->assertTrue($unlink_result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testMkdir() {
    $path = sprintf('%s/testDir1', $this->tmpDir);

    $env = SshTestSetup::setUpLocalSsh();
    $logger = SshTestSetup::createWipLog();
    $exception = NULL;
    $result = NULL;
    $chmod_result = NULL;
    $permissions_result = NULL;
    $rmdir_result = NULL;
    $dir_exists_result = NULL;

    try {
      $ssh_keys = new SshKeys();
      $ssh_service = new SshService();
      $ssh_service->setKeyPath($ssh_keys->getPrivateKeyPath($env));
      $file = new SshFileCommands($env, 0, $logger, $ssh_service);
      $result = $file->mkdir($path)->exec();
      $chmod_result = $file->chmod(0700, $path)->exec();
      $permissions_result = $file->getFilePermissions($path)->exec();
      $chown_result = $file->chown('nobody', $path, 'nogroup', FALSE, 'test')->exec();
      $owner_result = $file->getFileOwner($path)->exec();
      $group_result = $file->getFileGroup($path)->exec();
      $rmdir_result = $file->rmdir($path)->exec();
      $dir_exists_result = $file->getFilePermissions($path)->exec();
    } catch (\Exception $e) {
      $exception = $e;
    }

    // Now remove the key.
    SshTestSetup::clearLocalSsh();

    if (isset($exception)) {
      throw $exception;
    }
    // This is done at the end so the above cleanup leaves the machine in the
    // same state as before the test.
    $this->assertTrue($result->isSuccess());
    $this->assertTrue($chmod_result->isSuccess());

    /** @var StatResultInterpreter $interpreter */
    $interpreter = $permissions_result->getResultInterpreter();
    $this->assertEquals(0700, $interpreter->getPermissions());
    $this->assertTrue($rmdir_result->isSuccess());
    $this->assertFalse($dir_exists_result->isSuccess());

    // Ensure file ownership changes.
    // @todo travis currently does not allow this operation, so these will fail.
    // $this->assertTrue($chown_result->isSuccess());
    // $this->assertEquals('nobody', $owner_result->getStdout());
    // $this->assertEquals('nogroup', $group_result->getStdout());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testCreateSshKey() {
    $key_path = sprintf('%s/testKey', $this->tmpDir);

    $env = SshTestSetup::setUpLocalSsh();
    $logger = SshTestSetup::createWipLog();
    $exception = NULL;
    $result = NULL;
    $del_result = NULL;
    $key_exists_result = NULL;
    $key_missing_result = NULL;

    try {
      $ssh_keys = new SshKeys();
      $ssh_service = new SshService();
      $ssh_service->setKeyPath($ssh_keys->getPrivateKeyPath($env));
      $file = new SshFileCommands($env, 0, $logger, $ssh_service);
      $result = $file->createSshKey($key_path)->exec();
      $key_exists_result = $file->exists($key_path)->exec();
      $del_result = $file->deleteSshKey($key_path)->exec();
      $key_missing_result = $file->exists($key_path)->exec();
    } catch (\Exception $e) {
      $exception = $e;
    }

    // Now remove the key.
    SshTestSetup::clearLocalSsh();

    if (isset($exception)) {
      throw $exception;
    }
    // This is done at the end so the above cleanup leaves the machine in the
    // same state as before the test.
    $this->assertTrue($result->isSuccess());
    $this->assertTrue($key_exists_result->isSuccess());
    $this->assertTrue($del_result->isSuccess());
    $this->assertFalse($key_missing_result->isSuccess());
  }

  /**
   * Verifies rsync behavior.
   *
   * @group Ssh
   */
  public function testRsync() {
    $file = NULL;
    $text_file = <<<EOT
File contents.
EOT;

    $dir1 = sprintf('%s/dir1', $this->tmpDir);
    $dir2 = sprintf('%s/dir1', $this->tmpDir);
    $file1_path = $dir1 . '/file.txt';
    $file2_path = $dir2 . '/file.txt';

    $env = SshTestSetup::setUpLocalSsh();
    $logger = SshTestSetup::createWipLog();
    $exception = NULL;
    $rsync_result = NULL;
    $dir_exists_result = NULL;
    $file_exists_result = NULL;
    $file_contents_result = NULL;

    try {
      $ssh_service = new SshService();
      $file = new SshFileCommands($env, 0, $logger, $ssh_service);
      $file->mkdir($dir1)->exec();
      $file->writeFile($text_file, $file1_path)->exec();
      $rsync_result = $file->rsync($dir1 . '/', $dir2)->exec();
      $dir_exists_result = $file->exists($dir2)->exec();
      $file_exists_result = $file->exists($file2_path)->exec();
      $file_contents_result = $file->cat($file2_path)->exec();
    } catch (\Exception $e) {
      $exception = $e;
    }

    // Now remove the directories.
    $file->forceRemove($dir1)->exec();
    $file->forceRemove($dir2)->exec();

    if (isset($exception)) {
      throw $exception;
    }
    // This is done at the end so the above cleanup leaves the machine in the
    // same state as before the test.
    $this->assertTrue($rsync_result->isSuccess());
    $this->assertTrue($dir_exists_result->isSuccess());
    $this->assertTrue($file_exists_result->isSuccess());
    $this->assertTrue($file_contents_result->isSuccess());
    $this->assertEquals($text_file, $file_contents_result->getStdout());
  }

  /**
   * Tests the ensureTrailingSeparator method.
   *
   * @param string $dir
   *   The directory name.
   * @param string $expected_result
   *   The directory name with the trailing slash.
   *
   * @group Ssh
   *
   * @dataProvider directoryProvider
   */
  public function testEnsureTrailingSeparator($dir, $expected_result) {
    $env = SshTestSetup::setUpLocalSsh();
    $logger = SshTestSetup::createWipLog();

    $ssh_service = new SshService();
    $file = new SshFileCommands($env, 0, $logger, $ssh_service);
    $this->assertEquals($expected_result, $file->ensureTrailingSeparator($dir));
  }

  /**
   * Provides a list of directory names.
   *
   * @return array
   *   The array of values provided.
   */
  public function directoryProvider() {
    return array(
      array('/tmp', '/tmp/'),
      array('/tmp/', '/tmp/'),
    );
  }

}
