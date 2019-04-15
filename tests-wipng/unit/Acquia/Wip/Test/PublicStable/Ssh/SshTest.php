<?php

namespace Acquia\Wip\Test\PublicStable\Ssh;

use Acquia\Wip\Environment;
use Acquia\Wip\Implementation\SqliteWipLogStore;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\Ssh\Ssh;
use Acquia\Wip\Ssh\SshFileCommands;
use Acquia\Wip\Ssh\SshKeys;
use Acquia\Wip\Ssh\SshResult;
use Acquia\Wip\Ssh\SshService;
use Acquia\Wip\Ssh\StatResultInterpreter;
use Acquia\Wip\Test\Integration\PublicStable\AcquiaCloud\AcquiaCloudTestSetup;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogEntryInterface;
use SshWrapper;

/**
 * Missing summary.
 */
class SshTest extends \PHPUnit_Framework_TestCase {

  /**
   * Set up for ssh_wrapper tests.
   */
  public static function setUpBeforeClass() {
    include_once dirname(__FILE__) . '/SshTestGlobals.php';
    $unit_test_ssh_wrapper = TRUE;
    $env = SshTestSetup::setUpLocalSsh();
    $ssh_wrapper_path = Ssh::getSshWrapper($env);
    include_once $ssh_wrapper_path;
  }

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    Environment::setRuntimeSitegroup('testing');
    Environment::setRuntimeEnvironmentName('prod');
    SshKeys::setBasePath(sys_get_temp_dir());
  }

  /**
   * Missing summary.
   */
  public function tearDown() {
    parent::tearDown();
    Environment::setRuntimeSitegroup(NULL);
    Environment::setRuntimeEnvironmentName(NULL);
    SshKeys::setBasePath(NULL);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testInstantiation() {
    $env = $this->createEnvironment();
    (new Ssh())->initialize($env, 'testing', $this->createWipLog(), 15);
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
    (new Ssh())->initialize($env, 'testing', $this->createWipLog(), 15);
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
    (new Ssh())->initialize($env, 'testing', $this->createWipLog(), 15);
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
    (new Ssh())->initialize($env, 'testing', $this->createWipLog(), 15);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testGetEnvironment() {
    $description = 'testing';
    $env = $this->createEnvironment();
    $ssh = (new Ssh())->initialize($env, $description, $this->createWipLog(), 15);
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
  public function testGetDescription() {
    $description = 'testing';
    $env = $this->createEnvironment();
    $ssh = (new Ssh())->initialize($env, $description, $this->createWipLog(), 15);
    $this->assertEquals($description, $ssh->getDescription());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInstantiateDescriptionNull() {
    $description = NULL;
    $env = $this->createEnvironment();
    $ssh = (new Ssh())->initialize($env, $description, $this->createWipLog(), 15);
    $this->assertEquals($description, $ssh->getDescription());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInstantiateDescriptionEmpty() {
    $description = '';
    $env = $this->createEnvironment();
    $ssh = (new Ssh())->initialize($env, $description, $this->createWipLog(), 15);
    $this->assertEquals($description, $ssh->getDescription());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testGetLogger() {
    $description = 'testing';
    $env = $this->createEnvironment();
    $logger = $this->createWipLog();
    $ssh = (new Ssh())->initialize($env, $description, $logger, 15);
    $result = $ssh->getLogger();
    $this->assertNotEmpty($result);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testGetId() {
    $id = 15;
    $env = $this->createEnvironment();
    $logger = $this->createWipLog();
    $ssh = (new Ssh())->initialize($env, 'Hello', $logger, $id);
    $this->assertEquals($id, $ssh->getWipId());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \InvalidArgumentException
   */
  public function testIdNotInt() {
    $id = NULL;
    $env = $this->createEnvironment();
    $logger = $this->createWipLog();
    $ssh = (new Ssh())->initialize($env, 'Hello', $logger, $id);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \RuntimeException
   */
  public function testSetWipIdTwice() {
    $ssh = new Ssh();
    $ssh->setWipId(1);
    $ssh->setWipId(2);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testSetCommand() {
    $command = 'ls -l';
    $ssh = $this->createSsh();
    $ssh->setCommand($command);
    $this->assertEquals($command, $ssh->getCommand());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testGetSshService() {
    $environment = $this->createEnvironment();
    $ssh = $this->createSsh();
    $service = $ssh->getSshService($environment);
    $this->assertNotEmpty($service);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testGetCommandWrapper() {
    $ssh = $this->createSsh();
    $wrapper = $ssh->getCommandWrapper('exec', 'ls -l', '');
    $this->assertNotEmpty($wrapper);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testAddSuccessExitCode() {
    $ssh = $this->createSsh();
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
    $ssh = $this->createSsh();
    $ssh->addSuccessExitCode(NULL);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testSetSuccessExitCodes() {
    $expected_codes = array(12, 93, 124);
    $ssh = $this->createSsh();
    $ssh->setSuccessExitCodes($expected_codes);
    $codes = $ssh->getSuccessExitCodes();
    $this->assertEquals($expected_codes, $codes);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \Exception
   */
  public function testExecNoKey() {
    $ssh = $this->createSsh();
    $ssh_keys = new SshKeys();
    $ssh_keys->deleteKey($ssh->getEnvironment());
    $ssh->setCommand('ls -l');
    $result = $ssh->exec();
    $this->assertFalse($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \RuntimeException
   */
  public function testExecNoCommand() {
    $ssh = $this->createSsh();
    $ssh->exec();
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \RuntimeException
   */
  public function testExecAsyncNoCommand() {
    $ssh = $this->createSsh();
    $ssh->execAsync();
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testExecWithInterpreter() {
    $env = SshTestSetup::setUpLocalSsh();
    $exception = NULL;
    $ssh_process = NULL;
    $ssh_result = NULL;
    $logger = SshTestSetup::createWipLog();
    try {
      $ssh_keys = new SshKeys();
      $ssh_service = new SshService();
      $ssh_service->setKeyPath($ssh_keys->getPrivateKeyPath($env));
      $tools = new SshFileCommands($env, 0, $logger, $ssh_service);
      $ssh = $tools->getFilePermissions('/');
      $ssh_result = $ssh->exec();
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
    /** @var StatResultInterpreter $interpreter */
    $interpreter = $ssh_result->getResultInterpreter();
    $this->assertInstanceOf('Acquia\Wip\Ssh\SshResultInterpreter', $interpreter);
    $this->assertNotEmpty($interpreter->getPermissions());
    $this->assertEquals(0, $interpreter->getModifiers());
    $this->assertTrue($interpreter->isExecutable());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testGetSshWrapper() {
    $test_wrapper = '/tmp/testWrapper';
    $env = $this->createEnvironment();
    $ssh = $this->createSsh();
    $ssh->setSshWrapper(NULL);
    $wrapper = $ssh->getSshWrapper($env);
    $this->assertStringEndsWith('ssh_wrapper', $wrapper);
    $ssh->setSshWrapper($test_wrapper);
    $this->assertEquals($test_wrapper, $ssh->getSshWrapper($env));
  }

  /**
   * Provides data to test the formatOutput method.
   *
   * @return array
   *   A multi-dimensional array of properties.
   */
  public function formatOutputProvider() {
    return [
      [
        // A single \b will not be replace.
        "Hello \b world!",
        "Hello \b world!",
      ],
      [
        // Multiple sequential \b characters will be replaced.
        "Hello \b\b world!",
        "Hell world!",
      ],
      [
        "My text\b\b\b\bimproved output.\n",
        "My improved output.\n",
      ],
      [
        "My\ttext" . chr(8) . chr(8) . chr(8) . chr(8) . "improved output.\n",
        "My\timproved output.\n",
      ],
      [
        "Loading composer repositories with package information
Installing dependencies (including require-dev) from lock file
  - Installing psr/http-message (1.0)
    Downloading: Connecting...\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b    Downloading: 0%           \b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b    Downloading: 25%\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b    Downloading: 95%\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b    Downloading: 100%

  - Installing guzzlehttp/psr7 (1.3.0)
    Downloading: Connecting...\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b    Downloading: 0%           \b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b    Downloading: 10%\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b    Downloading: 15%\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b    Downloading: 25%\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b    Downloading: 35%\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b    Downloading: 45%\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b    Downloading: 50%\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b    Downloading: 60%\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b    Downloading: 75%\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b    Downloading: 85%\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b    Downloading: 90%\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b    Downloading: 100%",
        "Loading composer repositories with package information
Installing dependencies (including require-dev) from lock file
  - Installing psr/http-message (1.0)
    Downloading: 100%

  - Installing guzzlehttp/psr7 (1.3.0)
    Downloading: 100%",
      ],
    ];
  }

  /**
   * Tests that we only return the intended output.
   *
   * @param string $input
   *   The input string.
   * @param string $output
   *   The expected output.
   *
   * @group Ssh
   *
   * @dataProvider formatOutputProvider
   */
  public function testFormatOutput($input, $output) {
    $wrapper = new SshWrapper(new \stdClass());
    $this->assertEquals($wrapper->formatOutput($input), $output);
  }

  /**
   * Tests that we only return the intended output.
   *
   * @group Ssh
   */
  public function testFormatOutputComplex() {
    $wrapper = new SshWrapper(new \stdClass());
    $data = __DIR__ . '/data/output.txt';
    $input = file_get_contents($data);
    $input = json_decode($input);
    $this->assertNotEquals(mb_strlen($wrapper->formatOutput($input)), mb_strlen($input));
    $this->assertEquals(35081, mb_strlen($wrapper->formatOutput($input)));
    $this->assertContains('Total time: 1 minutes  18.74 seconds', $wrapper->formatOutput($input));
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testExecMock() {
    $ssh = $this->createSsh();
    $ssh_service = $this->getMock(
      'Acquia\Wip\Ssh\SshService',
      $methods = array('exec'),
      array(),
      '',
      FALSE
    );
    $environment = AcquiaCloudTestSetup::getEnvironment();
    $end_time = time();
    $start_time = $end_time - mt_rand(10, 45);
    $stdout = json_encode((object) array(
      'pid' => 155,
      'exitCode' => 0,
      'stdout' => 'Hello',
      'stderr' => '',
      'startTime' => $start_time,
      'endTime' => $end_time,
    ));
    $exec_result = new SshResult(0, $stdout, 'stderr');
    $exec_result->setEnvironment($environment);
    $ssh_service->expects($this->any())
      ->method('exec')
      ->will($this->returnValue($exec_result));
    $ssh->setSshService($ssh_service);

    $ssh->setCommand('ls -l');
    $ssh->exec();
  }

  /**
   * Ensure that we can exec with suppressed output.
   *
   * @group Ssh
   */
  public function testSecureExec() {
    $exception = NULL;
    $description = 'testing';
    $logger = $this->createWipLog();
    $env = SshTestSetup::setUpLocalSsh();

    try {
      $ssh = new Ssh();
      $ssh->initialize($env, $description, $logger, 15);
      $ssh->setSecure(TRUE);
      $this->assertTrue($ssh->isSecure());
      $stdout = 'hello world';
      $stderr = '';
      $result = $ssh->execCommand("echo $stdout");
      $this->assertTrue($result->isSecure());
      $this->assertEquals(WipLogEntryInterface::OUTPUT_SUPPRESSED_MESSAGE, $result->getSecureStdout());
      $this->assertEquals(WipLogEntryInterface::OUTPUT_SUPPRESSED_MESSAGE, $result->getSecureStderr());
      WipFactory::addConfiguration('$acquia.wip.secure.debug => TRUE');
      $this->assertEquals($stdout, $result->getSecureStdout());
      $this->assertEquals($stderr, $result->getSecureStderr());
      WipFactory::addConfiguration('$acquia.wip.secure.debug => FALSE');
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
   * Ensure that we can exec asynchronously with suppressed output.
   *
   * @group Ssh
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSecureBadArgument() {
    $description = 'testing';
    $logger = $this->createWipLog();
    $env = SshTestSetup::setUpLocalSsh();

    $ssh = new Ssh();
    $ssh->initialize($env, $description, $logger, 15);
    $ssh->setSecure('true');
  }

  /**
   * Ensure that we can exec asynchronously with suppressed output.
   *
   * @group Ssh
   */
  public function testSecureAsyncExec() {
    $max_run_time = 5;
    $exception = NULL;
    $description = 'testing';
    $logger = $this->createWipLog();
    $env = SshTestSetup::setUpLocalSsh();

    try {
      $ssh = new Ssh();
      $ssh->initialize($env, $description, $logger, 15);
      $ssh->setSecure(TRUE);
      $this->assertTrue($ssh->isSecure());
      $ssh_process = $ssh->execAsyncCommand("ls -l /");
      while (!$ssh_process->hasCompleted($logger) && time() - $ssh_process->getStartTime() < $max_run_time) {
        sleep(1);
      }

      $result = $ssh_process->getResult($logger, TRUE);
      $this->assertTrue($ssh_process->isSecure());
      $this->assertTrue($result->isSecure());
      $this->assertEquals(WipLogEntryInterface::OUTPUT_SUPPRESSED_MESSAGE, $result->getSecureStdout());
      $this->assertEquals(WipLogEntryInterface::OUTPUT_SUPPRESSED_MESSAGE, $result->getSecureStderr());
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
   *
   * @expectedException \Exception
   */
  public function testExecAsyncNoKey() {
    $ssh = $this->createSsh();
    $ssh_keys = new SshKeys();
    $ssh_keys->deleteKey($ssh->getEnvironment());
    $ssh->setCommand('ls -l');
    $process = $ssh->execAsync();
    $result = $process->getResult(WipLog::getWipLog());
    $this->assertFalse($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testExecAsyncMock() {
    $ssh = $this->createSsh();
    $ssh_service = $this->getMock(
      'Acquia\Wip\Ssh\SshService',
      $methods = array('exec'),
      array(),
      '',
      FALSE
    );

    $exec_result = new SshResult(0, '15', '');
    $ssh_service->expects($this->any())
      ->method('exec')
      ->will($this->returnValue($exec_result));
    $ssh->setSshService($ssh_service);

    $ssh->setCommand('ls -l');
    $ssh_process = $ssh->execAsync();
    $this->assertNotEmpty($ssh_process->getPid());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testResultHasPid() {
    $env = SshTestSetup::setUpLocalSsh();
    $exception = NULL;
    $ssh_result = NULL;
    $is_running = NULL;
    $logger = SshTestSetup::createWipLog();
    try {
      // This is the actual call.
      $ssh = (new Ssh())->initialize($env, 'Verify pid is part of the result.', $logger, 1);
      $ssh_result = $ssh->execCommand('echo "Hi"');
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
    $this->assertNotNull($ssh_result);
    $this->assertNotEmpty($ssh_result->getPid());
  }

  /**
   * Test that we can execute as a specified user.
   */
  public function testSwitchUser() {
    $user = posix_getpwuid(posix_geteuid())['name'];
    $command = 'whoami';
    $ssh = $this->createSsh();
    $ssh->setCommand($command);
    $ssh->switchUser($user);
    $this->assertEquals($user, $ssh->getUser());
    $wrapper = $ssh->getCommandWrapper('exec', $command);
    $this->assertContains("--switch-user $user", $wrapper);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \RuntimeException
   */
  public function testInitializeTwice() {
    $ssh = $this->createSsh();
    $env = $this->createEnvironment();
    $ssh->initialize($env, 'dupe', $this->createWipLog(), 15);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testCallback() {
    // In order to test the callback mechanism, we're mocking the SshService,
    // and checking that its exec() method gets called with a command argument
    // that contains the callback hostname and the --report option.
    $handler = WipFactory::getObject('acquia.wip.handler.signal');
    $callback_hostname = 'https://my_test_host.com/';
    $handler->setCallbackUrl($callback_hostname);

    $ssh = $this->createSsh();
    $ssh_service = $this->getMock('Acquia\Wip\Ssh\SshService', array('exec'), array(), '', FALSE);
    $ssh->setSshService($ssh_service);

    // We're just verifying here that when requesting an async request, the
    // resulting command on the SshService contains the callback hostname, the
    // 'report' argument, and the expected classId for an SSH completion signal.
    $exec_result = new SshResult(0, '15', '');
    $ssh_service->expects($this->any())
      ->method('exec')
      ->with($this->callback(function ($arg) use ($callback_hostname) {
        $matches = array();
        if (preg_match('/--data=([^\s]+)/', $arg, $matches) === 1) {
          $decoded_value = base64_decode($matches[1]);
        }
        return !empty($decoded_value)
          && strpos($arg, '--report') !== FALSE
          && strpos($decoded_value, $callback_hostname) !== FALSE
          && strpos($decoded_value, '"classId";s:31:"$acquia.wip.signal.ssh.complete"') !== FALSE;
      }))
      ->will($this->returnValue($exec_result));

    $ssh->setCommand('ls -l');

    $data = new \stdClass();
    $data->report = TRUE;
    $ssh->execAsync('', $data);

    // This time do not request a callback, and verify that none is specified.
    $data = new \stdClass();
    $ssh_service = $this->getMock('Acquia\Wip\Ssh\SshService', array('exec'), array(), '', FALSE);
    $ssh->setSshService($ssh_service);

    $ssh_service->expects($this->any())
      ->method('exec')
      ->with($this->callback(function ($arg) use ($callback_hostname) {
        // Verify that the invoked command on the SSH service contains neither
        // 'report', nor the callback hostname.
        return strpos($arg, '--report') === FALSE && strpos($arg, $callback_hostname) === FALSE;
      }))
      ->will($this->returnValue($exec_result));

    $ssh->execAsync('', $data);

    // Test this time with an explicit callback URL specified.
    $data = new \stdClass();
    $data->report = TRUE;
    $data->callbackUrl = 'https://explicit_callback_url.com';
    $ssh_service = $this->getMock('Acquia\Wip\Ssh\SshService', array('exec'), array(), '', FALSE);
    $ssh->setSshService($ssh_service);

    $ssh_service->expects($this->any())
      ->method('exec')
      ->with($this->callback(function ($arg) use ($callback_hostname, $data) {
        $matches = array();
        if (preg_match('/--data=([^\s]+)/', $arg, $matches) === 1) {
          $decoded_value = base64_decode($matches[1]);
        }
        return !empty($decoded_value)
          && strpos($arg, '--report') !== FALSE
          && strpos($decoded_value, $callback_hostname) === FALSE
          && strpos($decoded_value, $data->callbackUrl) !== FALSE
          && strpos($decoded_value, '"classId";s:31:"$acquia.wip.signal.ssh.complete"') !== FALSE;
      }))
      ->will($this->returnValue($exec_result));

    $ssh->execAsync('', $data);
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
  private function createEnvironment() {
    $result = AcquiaCloudTestSetup::getEnvironment();
    $this->addServers($result);
    $result->selectNextServer();
    return $result;
  }

  /**
   * Missing summary.
   */
  private function createWipLog() {
    $result = new WipLog(new SqliteWipLogStore());
    return $result;
  }

  /**
   * Missing summary.
   */
  private function createSsh() {
    $description = 'testing';
    $env = $this->createEnvironment();
    $logger = $this->createWipLog();
    return (new Ssh())->initialize($env, $description, $logger, 15);
  }

}
