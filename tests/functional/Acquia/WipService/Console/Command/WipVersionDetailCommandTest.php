<?php

namespace Acquia\WipService\Console\Command;

use Acquia\WipService\Console\AbstractWipVersionTest;
use Acquia\WipService\Console\Commands\WipVersionDetailCommand;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests that WipVersionDetailCommand behaves as expected.
 */
class WipVersionDetailCommandTest extends AbstractWipVersionTest {

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
   * The name of the details file with its path.
   *
   * @var string
   */
  private static $detailsFileName = '';

  /**
   * The name of the details backup file with its path.
   *
   * @var string
   */
  private static $detailsFileBackup = '';

  /**
   * The timestamp of the details file's last update.
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
    self::$detailsFileName = sprintf("%s/%s", WipVersionDetailCommand::DETAIL_DIR, self::$shortWipName);
    self::$detailsFileBackup = self::$detailsFileName . '.bak';
    self::$fileLastUpdated = filemtime(self::$detailsFileName);

    // Make sure that we don't actually overwrite the detail file that we are
    // using to test the save option.
    if (file_exists(self::$detailsFileName)) {
      copy(self::$detailsFileName, self::$detailsFileBackup);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function tearDownAfterClass() {
    if (file_exists(self::$detailsFileBackup)) {
      copy(self::$detailsFileBackup, self::$detailsFileName);
      unlink(self::$detailsFileBackup);
    }
  }

  /**
   * Tests that the detail contains both "Properties" and "State table".
   */
  public function testGetVersionDetail() {
    $arguments = array('class' => self::$wipName);
    $tester = $this->executeCommand(new WipVersionDetailCommand(), 'detail', $arguments);
    $display = $tester->getDisplay();

    $this->assertSame(0, $tester->getStatusCode());
    $this->assertContains(
      sprintf('Properties'),
      $display
    );
    $this->assertContains(
      sprintf('State table'),
      $display
    );
  }

  /**
   * Tests that an invalid wip name fails.
   *
   * @expectedException \InvalidArgumentException
   * @expectedExceptionMessage The "not/a/wip" class does not exist.
   */
  public function testGetVersionDetailInvalidWip() {
    $arguments = array('class' => 'not/a/wip');
    $this->executeCommand(new WipVersionDetailCommand(), 'detail', $arguments);
  }

  /**
   * Tests the saving option.
   */
  public function testSave() {
    unlink(self::$detailsFileName);

    $arguments = array(
      'class' => self::$wipName,
      '--save' => TRUE,
    );

    $this->executeCommand(new WipVersionDetailCommand(), 'detail', $arguments);
    $this->assertFileExists(self::$detailsFileName);
  }

}
