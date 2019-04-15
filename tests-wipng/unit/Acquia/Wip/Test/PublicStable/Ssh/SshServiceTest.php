<?php

namespace Acquia\Wip\Test\PublicStable\Ssh;

use Acquia\Wip\Environment;
use Acquia\Wip\Ssh\SshKeys;
use Acquia\Wip\Ssh\SshService;
use Acquia\Wip\Ssh\StatResultInterpreter;
use Acquia\Wip\Test\Integration\PublicStable\AcquiaCloud\AcquiaCloudTestSetup;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogEntryInterface;

/**
 * Missing summary.
 */
class SshServiceTest extends \PHPUnit_Framework_TestCase {

  private $keyFile;
  private $logger = NULL;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    if (!isset($this->keyFile)) {
      $this->keyFile = sprintf('%s.private', tempnam(sys_get_temp_dir(), 'id_rsa'));
    }
    if (!file_exists($this->keyFile)) {
      touch($this->keyFile);
    }
    $this->logger = AcquiaCloudTestSetup::createWipLog();
  }

  /**
   * Missing summary.
   */
  public function tearDown() {
    parent::tearDown();
    if (isset($this->keyFile)) {
      unlink($this->keyFile);
    }
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testInstantiation() {
    $env = $this->createEnvironment();
    new SshService($env, $this->keyFile);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInstantiationNoSitegroup() {
    $env = new Environment();
    $env->setEnvironmentName('prod');
    $this->addServers($env);
    $env->selectNextServer();
    new SshService($env, $this->keyFile);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInstantiationNoEnvironment() {
    $env = new Environment();
    $env->setSitegroup('sitegroup');
    $this->addServers($env);
    $env->selectNextServer();
    new SshService($env, $this->keyFile);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInstantiationNoCurrentServer() {
    $env = new Environment();
    $env->setSitegroup('sitegroup');
    $env->setEnvironmentName('env');
    $this->addServers($env);
    new SshService($env, $this->keyFile);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \RuntimeException
   */
  public function testCannotLogIn() {
    Environment::setRuntimeSitegroup('sitegroup');
    Environment::setRuntimeEnvironmentName('prod');
    $env = AcquiaCloudTestSetup::getEnvironment();
    $env->setSitegroup('sitegroup');
    $env->setEnvironmentName('env');
    $env->setServers(array('localhost'));
    $env->selectNextServer();

    // Create a key if required.
    SshKeys::setBasePath(sys_get_temp_dir());
    $keys = new SshKeys();
    if (!$keys->hasKey($env)) {
      $keys->createKey($env);
    }
    $sshService = new SshService($env, $keys->getPrivateKeyPath($env));
    $sshService->exec('ls -l /');
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testFullExec() {
    $message = 'Hello, world!';
    $env = SshTestSetup::setUpLocalSsh();
    $exception = NULL;
    $sshResult = NULL;
    try {
      // This is the actual call.
      $keys = new SshKeys();
      $sshService = new SshService($env, $keys->getPrivateKeyPath($env));
      $sshService->setResultInterpreter(new StatResultInterpreter('/tmp', 0));
      $sshResult = $sshService->exec(sprintf('echo %s', escapeshellarg($message)));
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
    $this->assertEquals(0, $sshResult->getExitCode());
    $this->assertEquals($message, trim($sshResult->getStdout()));
    $this->assertEquals('', $sshResult->getStderr());
  }

  /**
   * Test that we can exec securely and suppress the output.
   *
   * @group Ssh
   */
  public function testSecureExec() {
    $stdout = 'Secure test.';
    $stderr = '';
    $env = SshTestSetup::setUpLocalSsh();
    $exception = NULL;
    $result = NULL;
    try {
      $keys = new SshKeys();
      $sshService = new SshService($env, $keys->getPrivateKeyPath($env));
      $sshService->setResultInterpreter(new StatResultInterpreter('/tmp', 0));
      $sshService->setSecure(TRUE);
      $result = $sshService->exec(sprintf('echo %s', escapeshellarg($stdout)));
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
    $this->assertTrue($sshService->isSecure());
    $this->assertTrue($result->isSecure());
    $this->assertEquals(0, $result->getExitCode());
    $this->assertEquals(WipLogEntryInterface::OUTPUT_SUPPRESSED_MESSAGE, $result->getSecureStdout());
    $this->assertEquals(WipLogEntryInterface::OUTPUT_SUPPRESSED_MESSAGE, $result->getSecureStderr());
    WipFactory::addConfiguration('$acquia.wip.secure.debug => TRUE');
    $this->assertEquals($stdout, trim($result->getSecureStdout()));
    $this->assertEquals($stderr, $result->getSecureStderr());
    WipFactory::addConfiguration('$acquia.wip.secure.debug => FALSE');
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testKeyPath() {
    $env = SshTestSetup::setUpLocalSsh();
    $keys = new SshKeys();
    $keyPath = $keys->getPrivateKeyPath($env);
    $sshService = new SshService();
    $sshService->setKeyPath($keys->getPrivateKeyPath($env));
    $this->assertEquals($keyPath, $sshService->getKeyPath());
    SshTestSetup::clearLocalSsh();
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testGetEnvironment() {
    $env = $this->createEnvironment();
    $ssh = new SshService($env, $this->keyFile);
    $result = $ssh->getEnvironment();
    $this->assertEquals($env->getSitegroup(), $result->getSitegroup());
    $this->assertEquals($env->getEnvironmentName(), $result->getEnvironmentName());
    $this->assertEquals($env->getCurrentServer(), $result->getCurrentServer());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testAddInterpreter() {
    $env = $this->createEnvironment();
    $ssh = new SshService($env, $this->keyFile);
    $ssh->setResultInterpreter(new StatResultInterpreter('/tmp', 0));
    $interpreter = $ssh->getResultInterpreter();
    $this->assertInstanceOf('Acquia\Wip\Ssh\SshResultInterpreter', $interpreter);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testAddSuccessExitCode() {
    $env = $this->createEnvironment();
    $ssh = new SshService($env, $this->keyFile);
    $ssh->addSuccessExitCode(1);
    $codes = $ssh->getSuccessExitCodes();
    $this->assertEquals(array(0, 1), $codes);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \InvalidArgumentException
   */
  public function testAddSuccessExitCodeBadValue() {
    $env = $this->createEnvironment();
    $ssh = new SshService($env, $this->keyFile);
    $ssh->addSuccessExitCode(NULL);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testSetSuccessExitCodes() {
    $expectedCodes = array(12, 93, 124);
    $env = $this->createEnvironment();
    $ssh = new SshService($env, $this->keyFile);
    $ssh->setSuccessExitCodes($expectedCodes);
    $codes = $ssh->getSuccessExitCodes();
    $this->assertEquals($expectedCodes, $codes);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testUsername() {
    $env = $this->createEnvironment();
    $ssh = new SshService($env);
    $ssh2 = new SshService($env);
    $defaultUsername = 'sitefactory.prod';
    $testNameInstance = 'test-instance-override';
    $testNameStatic = 'test-static-override';
    $this->assertEquals($defaultUsername, $ssh->getUsername());
    $ssh->setUsername($testNameInstance);
    $this->assertEquals($testNameInstance, $ssh->getUsername());
    $this->assertEquals($defaultUsername, $ssh2->getUsername());
    SshService::setTestUsername($testNameStatic);
    $this->assertEquals($testNameStatic, $ssh->getUsername());
    $this->assertEquals($testNameStatic, $ssh2->getUsername());
    $this->assertEquals($testNameStatic, SshService::getTestUsername());
    SshService::setTestUsername(NULL);
    $this->assertEquals($testNameInstance, $ssh->getUsername());
    $this->assertEquals($defaultUsername, $ssh2->getUsername());
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
  public function createEnvironment() {
    $result = new Environment();
    $result->setSitegroup('sitefactory');
    $result->setEnvironmentName('prod');
    $this->addServers($result);
    $result->selectNextServer();
    return $result;
  }

}
