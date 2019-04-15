<?php

namespace Acquia\WipService\Console\Command;

use Acquia\WipService\Console\AbstractWipVersionTest;
use Acquia\WipService\Console\Commands\WipVersionGetHashCommand;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests that WipVersionGetHashCommand behaves as expected.
 */
class WipVersionGetHashCommandTest extends AbstractWipVersionTest {

  /**
   * The name of the VersionCheckTestWip class.
   *
   * @var string
   */
  private static $wipName = 'Acquia\Wip\Implementation\VersionCheckTestWip';
  
  /**
   * The short name of the VersionCheckTestWip class.
   *
   * @var string
   */
  private static $shortWipName = 'VersionCheckTestWip';

  /**
   * The name of the fingerprint file with its path.
   *
   * @var string
   */
  private static $fingerprintFileName = '';

  /**
   * The name of the fingerprint backup file with its path.
   *
   * @var string
   */
  private static $fingerprintFileBackup = '';

  /**
   * The timestamp of the fingerprint file's last update.
   *
   * @var int
   */
  private static $fileLastUpdated = 0;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->registerTestingConfig();
  }

  /**
   * {@inheritdoc}
   */
  public static function setUpBeforeClass() {
    self::$fingerprintFileName = sprintf("%s/%s", WipVersionGetHashCommand::FINGERPRINT_DIR, self::$shortWipName);
    self::$fingerprintFileBackup = self::$fingerprintFileName . '.bak';
    self::$fileLastUpdated = filemtime(self::$fingerprintFileName);

    // Make sure that we don't actually overwrite the fingerprint files that we
    // are using to test.
    if (file_exists(self::$fingerprintFileName)) {
      copy(self::$fingerprintFileName, self::$fingerprintFileBackup);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function tearDownAfterClass() {
    if (file_exists(self::$fingerprintFileBackup)) {
      copy(self::$fingerprintFileBackup, self::$fingerprintFileName);
      unlink(self::$fingerprintFileBackup);
    }
  }

  /**
   * Tests that a hash value is generated.
   */
  public function testGetHash() {
    $arguments = array('class' => self::$wipName);
    $tester = $this->executeCommand(new WipVersionGetHashCommand(), 'hash', $arguments);
    $display = $tester->getDisplay();

    $this->assertSame(0, $tester->getStatusCode());
    $this->assertContains(
      sprintf('Fingerprint hash value'),
      $display
    );
  }

  /**
   * Tests that an invalid wip name fails.
   *
   * @expectedException \InvalidArgumentException
   * @expectedExceptionMessage The "not/a/wip" class does not exist.
   */
  public function testGetHashInvalidWip() {
    $arguments = array('class' => 'not/a/wip');
    $this->executeCommand(new WipVersionGetHashCommand(), 'hash', $arguments);
  }

  /**
   * Tests the saving option.
   */
  public function testSave() {
    unlink(self::$fingerprintFileName);

    $arguments = array(
      'class' => self::$wipName,
      '--save' => TRUE,
    );

    $this->executeCommand(new WipVersionGetHashCommand(), 'hash', $arguments);
    $this->assertFileExists(self::$fingerprintFileName);
  }

}
