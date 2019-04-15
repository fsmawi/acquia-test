<?php

namespace Acquia\Wip\Test\Integration\PublicStable\AcquiaCloud;

use Acquia\Wip\AcquiaCloud\AcquiaCloud;
use Acquia\Wip\AcquiaCloud\AcquiaCloudResult;
use Acquia\Wip\Environment;
use Acquia\Wip\Test\Utility\DataProviderTrait;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipLogLevel;
use InvalidArgumentException;

/**
 * Missing summary.
 */
class AcquiaCloudFetchResultTest extends \PHPUnit_Framework_TestCase {

  use DataProviderTrait;

  /**
   * The Wip ID.
   *
   * @var int
   */
  private $wipId = NULL;

  /**
   * Missing summary.
   *
   * @var AcquiaCloud
   */
  private $cloud = NULL;

  /**
   * Missing summary.
   *
   * @var Environment
   */
  private $environment = NULL;

  /**
   * Missing summary.
   *
   * @var WipLogInterface
   */
  private $logger = NULL;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    $this->wipId = mt_rand(1, PHP_INT_MAX);
    $this->logger = AcquiaCloudTestSetup::createWipLog();
    $this->environment = AcquiaCloudTestSetup::getEnvironment();
    $this->cloud = new AcquiaCloud($this->environment, $this->logger, $this->wipId);
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testServers() {
    $result = $this->cloud->listServers($this->environment->getSitegroup(), $this->environment->getEnvironmentName());
    if (!$result->isSuccess()) {
      $this->fail(sprintf('Failed to list servers: %s', $result->getExitMessage()));
    }
    $servers = $result->getData();
    foreach ($servers as $server) {
      $this->assertStringStartsWith($server->getName(), $server->getFullyQualifiedDomainName());
    }
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testServersBadCreds() {
    $cloud = $this->getBadCredsCloud();
    $result = $cloud->listServers(
      $this->environment->getFullyQualifiedSitegroup(),
      $this->environment->getEnvironmentName()
    );
    $this->assertFalse($result->isSuccess());
    $error = $result->getError();
    $this->assertNotEmpty($error);
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testSitesNotAuthorized() {
    $env = AcquiaCloudTestSetup::getBadCredsEnvironment();
    $cloud = new AcquiaCloud($env, AcquiaCloudTestSetup::createWipLog(), $this->wipId);
    $result = $cloud->listSites();
    $this->assertFalse($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testGetEnvironment() {
    $result = new AcquiaCloudResult();
    $result->setEnvironment(AcquiaCloudTestSetup::getEnvironment());
    $this->assertNotEmpty($result->getEnvironment());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   *
   * @expectedException \RuntimeException
   */
  public function testSetEnvironmentTwice() {
    $result = new AcquiaCloudResult();
    $result->setEnvironment(AcquiaCloudTestSetup::getEnvironment());
    $result->setEnvironment(AcquiaCloudTestSetup::getEnvironment());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testExitCode() {
    $exit_code = 15;
    $result = new AcquiaCloudResult();
    $result->setExitCode($exit_code);
    $this->assertEquals($exit_code, $result->getExitCode());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testSuccess() {
    $exit_code = 15;
    $result = new AcquiaCloudResult();
    $result->setExitCode($exit_code);
    $result->setData(new \stdClass());
    $result->addSuccessExitCode($exit_code);
    $this->assertTrue(in_array($exit_code, $result->getSuccessExitCodes()));
    $this->assertTrue($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testSuccessFailureExitCode() {
    $exit_code = 15;
    $result = new AcquiaCloudResult();
    $result->setExitCode($exit_code + 1);
    $result->setData(new \stdClass());
    $result->addSuccessExitCode($exit_code);
    $this->assertTrue(in_array($exit_code, $result->getSuccessExitCodes()));
    $this->assertFalse($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   *
   * @expectedException InvalidArgumentException
   */
  public function testSuccessExitCodeInvalid() {
    $exit_code = 'hello';
    $result = new AcquiaCloudResult();
    $result->setData(new \stdClass());
    $result->addSuccessExitCode($exit_code);
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testSetSuccessExitCode() {
    $exit_code = 15;
    $result = new AcquiaCloudResult();
    $result->setPid(mt_rand(1, PHP_INT_MAX));
    $result->setData(new \stdClass());
    $result->setSuccessExitCodes(array($exit_code));
    $this->assertEquals(array($exit_code), $result->getSuccessExitCodes());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testGetUniqueId() {
    $result = new AcquiaCloudResult(TRUE);
    $result->setEnvironment(AcquiaCloudTestSetup::getEnvironment());
    $result->setData(new \stdClass());
    $id = $result->getUniqueId();
    $this->assertNotEmpty($id);
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testLogLevel() {
    $log_level = WipLogLevel::ALERT;
    $result = new AcquiaCloudResult();
    $result->setEnvironment(AcquiaCloudTestSetup::getEnvironment());
    $result->setLogLevel($log_level);
    ;
    $this->assertEquals($log_level, $result->getLogLevel());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   *
   * @expectedException \InvalidArgumentException
   */
  public function testLogLevelBadValue() {
    $log_level = 'hello';
    $result = new AcquiaCloudResult();
    $result->setEnvironment(AcquiaCloudTestSetup::getEnvironment());
    $result->setLogLevel($log_level);
    ;
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   *
   * @expectedException \InvalidArgumentException
   *
   * @dataProvider nonIntegerDataProvider
   */
  public function testSetPidBadValues($pid) {
    $result = new AcquiaCloudResult();
    $result->setEnvironment(AcquiaCloudTestSetup::getEnvironment());
    $result->setPid($pid);
  }

  /**
   * Missing summary.
   */
  private function getBadCredsCloud() {
    return new AcquiaCloud(AcquiaCloudTestSetup::getBadCredsEnvironment(), $this->logger, $this->wipId);
  }

}
