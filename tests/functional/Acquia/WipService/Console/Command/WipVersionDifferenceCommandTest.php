<?php

namespace Acquia\WipService\Console\Command;

use Acquia\WipService\Console\AbstractWipVersionTest;
use Acquia\WipService\Console\Commands\WipVersionDifferenceCommand;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests that WipVersionDifferenceCommand behaves as expected.
 */
class WipVersionDifferenceCommandTest extends AbstractWipVersionTest {

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
    self::$detailsFileName = sprintf("%s/%s", WipVersionDifferenceCommand::DETAIL_DIR, self::$shortWipName);
    self::$detailsFileBackup = self::$detailsFileName . '.bak';
    self::$fingerprintFileName = sprintf("%s/%s", WipVersionDifferenceCommand::FINGERPRINT_DIR, self::$shortWipName);
    self::$fingerprintFileBackup = self::$fingerprintFileName . '.bak';

    // Make sure that we don't actually overwrite the detail and fingerprint
    // files that we are using to test.
    if (file_exists(self::$detailsFileName)) {
      copy(self::$detailsFileName, self::$detailsFileBackup);
    }
    if (file_exists(self::$fingerprintFileName)) {
      copy(self::$fingerprintFileName, self::$fingerprintFileBackup);
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
    if (file_exists(self::$fingerprintFileBackup)) {
      copy(self::$fingerprintFileBackup, self::$fingerprintFileName);
      unlink(self::$fingerprintFileBackup);
    }
  }

  /**
   * Tests that the current hash has not changed from the details file's.
   */
  public function testHashHasNotChanged() {
    $arguments = array('class' => self::$wipName);
    $tester = $this->executeCommand(new WipVersionDifferenceCommand(), 'diff', $arguments);
    $display = $tester->getDisplay();

    $this->assertSame(0, $tester->getStatusCode());
    $this->assertContains(
      sprintf('The fingerprint hash values match.'),
      $display
    );
  }

  /**
   * Tests that an invalid wip name fails.
   *
   * @expectedException \InvalidArgumentException
   * @expectedExceptionMessage The "not/a/wip" class does not exist.
   */
  public function testGetDiffInvalidWip() {
    $arguments = array('class' => 'not/a/wip');
    $this->executeCommand(new WipVersionDifferenceCommand(), 'diff', $arguments);
  }

  /**
   * Tests the logic that handles when hash values have changed.
   */
  public function testHashHasChanged() {
    $arguments = array('class' => self::$wipName);

    // Modify the detail and fingerprint files.
    file_put_contents(self::$detailsFileName, 'Changed', FILE_APPEND);
    file_put_contents(self::$fingerprintFileName, '12345');

    $tester = $this->executeCommand(new WipVersionDifferenceCommand(), 'diff', $arguments);
    $display = $tester->getDisplay();

    $this->assertContains(
      sprintf('The fingerprint hash values do not match.'),
      $display
    );
    $this->assertContains(
      sprintf("New fingerprint hash value: %s", file_get_contents(self::$fingerprintFileBackup)),
      $display
    );
    $this->assertContains(
      sprintf("Old fingerprint hash value: %s", '12345'),
      $display
    );
    $diff_header = <<<EOT
19c19
< }Changed
---
> }
EOT;

    $this->assertContains(
      $diff_header,
      $display
    );
  }

  /**
   * Tests the output for a new wip object.
   *
   * The new wip object would not have a fingerprint or details file that was
   * previously saved.
   */
  public function testNewWip() {
    // To simulate a new wip, we remove the test wip's fingerprint and
    // details files.
    unlink(self::$detailsFileName);
    unlink(self::$fingerprintFileName);

    $arguments = array('class' => self::$wipName);
    $tester = $this->executeCommand(new WipVersionDifferenceCommand(), 'diff', $arguments);
    $display = $tester->getDisplay();

    $this->assertContains(
      sprintf('The fingerprint hash values do not match.'),
      $display
    );
    $this->assertContains(
      sprintf("New fingerprint hash value: %s", file_get_contents(self::$fingerprintFileBackup)),
      $display
    );
    $this->assertContains(
      sprintf("Old fingerprint hash value: %s", 'N/A'),
      $display
    );
  }

}
