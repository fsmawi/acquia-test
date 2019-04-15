<?php

namespace Acquia\Wip\Test\PublicStable\Ssh;

use Acquia\Wip\Environment;
use Acquia\Wip\Ssh\Ssh;
use Acquia\Wip\Ssh\SshProcess;
use Acquia\Wip\Ssh\SshResult;
use Acquia\Wip\Ssh\StatResultInterpreter;
use Acquia\Wip\WipResult;

/**
 * Missing summary.
 */
class SshProcessTest extends \PHPUnit_Framework_TestCase {

  /**
   * The SshProcess.
   *
   * @var SshProcess
   */
  private $sshProcess;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    $this->sshProcess = $this->createSshProcess();
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testInstantiation() {
    $this->assertNotEmpty($this->sshProcess);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testUniqueId() {
    $id = $this->sshProcess->getUniqueId();
    $this->assertNotEmpty($id);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testDescription() {
    $description = 'Whatever';
    $ssh_process = $this->createSshProcess(NULL, $description);
    $this->assertEquals($description, $ssh_process->getDescription());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \InvalidArgumentException
   */
  public function testDescriptionNull() {
    $this->createSshProcess(NULL, NULL);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testPid() {
    $pid = 27414;
    $ssh_process = $this->createSshProcess(NULL, 'Testing', $pid);
    $this->assertEquals($pid, $ssh_process->getPid());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \InvalidArgumentException
   */
  public function testPidNull() {
    $this->createSshProcess(NULL, 'Testing', NULL);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \InvalidArgumentException
   */
  public function testPidZero() {
    $this->createSshProcess(NULL, 'Testing', 0);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \InvalidArgumentException
   */
  public function testPidNegative() {
    $this->createSshProcess(NULL, 'Testing', -27);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testStartTime() {
    $time = time();
    $ssh_process = $this->createSshProcess(NULL, 'Testing', 58, $time);
    $this->assertEquals($time, $ssh_process->getStartTime());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \InvalidArgumentException
   */
  public function testStartTimeNull() {
    $this->createSshProcess(NULL, 'Testing', 58, NULL);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \InvalidArgumentException
   */
  public function testStartTimeZero() {
    $this->createSshProcess(NULL, 'Testing', 58, 0);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \InvalidArgumentException
   */
  public function testStartTimeNegative() {
    $this->createSshProcess(NULL, 'Testing', 58, -12);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \InvalidArgumentException
   */
  public function testDescriptionEmptyString() {
    $this->createSshProcess('', '');
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testSetResult() {
    $process = $this->createSshProcess();
    $process->setResult(new SshResult());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetResultWrongType() {
    $process = $this->createSshProcess();
    $process->setResult(new WipResult());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testEnvironment() {
    $ssh_process = $this->createSshProcess();
    $this->assertNotEmpty($ssh_process->getEnvironment());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testGetWipId() {
    $id = 42;
    $ssh_process = $this->createSshProcess($this->createEnvironment(), 'Test getWipId()', 57, time(), $id);
    $this->assertEquals($id, $ssh_process->getWipId());
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
    $this->createSshProcess($this->createEnvironment(), 'Test getPid()', 57, time(), $id);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \InvalidArgumentException
   */
  public function testIdNegativeInt() {
    $id = -1;
    $this->createSshProcess($this->createEnvironment(), 'Test getPid()', 57, time(), $id);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testAddSuccessExitCode() {
    $code = 15;
    $this->sshProcess->addSuccessExitCode($code);
    $codes = $this->sshProcess->getSuccessExitCodes();
    $this->assertEquals(array(0, $code), $codes);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \InvalidArgumentException
   */
  public function testAddSuccessExitCodeBadValue() {
    $code = NULL;
    $this->sshProcess->addSuccessExitCode($code);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testAsyncSshProcessGetsPid() {
    $env = SshTestSetup::setUpLocalSsh();
    $exception = NULL;
    $ssh_process = NULL;
    $logger = SshTestSetup::createWipLog();
    try {
      // This is the actual call.
      $ssh = (new Ssh())->initialize($env, 'Test async ls', $logger, 1);
      $ssh_process = $ssh->execAsyncCommand('ls -l /');
      sleep(1);
      $ssh_process->release($logger);
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
    $this->assertTrue(is_int($ssh_process->getPid()));
    $this->assertTrue($ssh_process->getPid() > 0);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testLongAsyncSshProcessIsRunning() {
    $env = SshTestSetup::setUpLocalSsh();
    $exception = NULL;
    $ssh_process = NULL;
    $is_running = NULL;
    $logger = SshTestSetup::createWipLog();
    try {
      // This is the actual call.
      $ssh = (new Ssh())->initialize($env, 'Test long async process is running', SshTestSetup::createWipLog(), 1);
      $ssh_process = $ssh->execAsyncCommand('sleep 120');
      $has_completed = $ssh_process->hasCompleted($logger);
      $ssh_process->kill($logger);
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
    $this->assertFalse($has_completed);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testKillLongProcess() {
    $env = SshTestSetup::setUpLocalSsh();
    $exception = NULL;
    $ssh_process = NULL;
    $is_running = NULL;
    $logger = SshTestSetup::createWipLog();
    try {
      // This is the actual call.
      $ssh = (new Ssh())->initialize($env, 'Test long async process is running', $logger, 1);
      $ssh_process = $ssh->execAsyncCommand('sleep 120');
      $has_completed = $ssh_process->kill($logger);
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
    $this->assertTrue($has_completed);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testKillLongProcessTwice() {
    $env = SshTestSetup::setUpLocalSsh();
    $exception = NULL;
    $ssh_process = NULL;
    $has_completed = NULL;
    $has_completed_1 = NULL;
    $has_completed_2 = NULL;
    try {
      // This is the actual call.
      $ssh = (new Ssh())->initialize($env, 'Test long async process is running', SshTestSetup::createWipLog(), 1);
      $ssh_process = $ssh->execAsyncCommand('sleep 120');
      $logger = SshTestSetup::createWipLog();
      $has_completed_1 = $ssh_process->kill($logger);
      $has_completed_2 = $ssh_process->kill($logger);
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
    $this->assertTrue($has_completed_1);
    $this->assertTrue($has_completed_2);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testGetAsyncResult() {
    $max_run_time = 5;
    $result = NULL;
    $env = SshTestSetup::setUpLocalSsh();
    $exception = NULL;
    $ssh_process = NULL;
    $is_running = NULL;
    $logger = SshTestSetup::createWipLog();
    try {
      // This is the actual call.
      $ssh = (new Ssh())->initialize($env, 'Test get result', $logger, 1);
      $ssh->setResultInterpreter(new StatResultInterpreter('/', 1));
      $ssh_process = $ssh->execAsyncCommand('echo "Hi"');
      while (!$ssh_process->hasCompleted($logger) && time() - $ssh_process->getStartTime() < $max_run_time) {
        sleep(1);
      }
      $result = $ssh_process->getResult($logger, TRUE);
      $has_completed = $ssh_process->hasCompleted($logger);
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
    $this->assertTrue($has_completed);
    $this->assertEquals('Hi', $result->getStdout());
    $this->assertEquals(0, $result->getExitCode());
    $this->assertEquals('', $result->getStderr());
    // If the start time is retrieved just before the next second, the runtime
    // could indicate 1 second.
    $this->assertLessThanOrEqual(1, $result->getRuntime());
    $this->assertGreaterThanOrEqual(0, $result->getRuntime());
    $this->assertInstanceOf('Acquia\Wip\Ssh\SshResultInterpreter', $result->getResultInterpreter());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testGetAsyncResultWithSuccessCodes() {
    $max_run_time = 5;
    $result = NULL;
    $env = SshTestSetup::setUpLocalSsh();
    $exception = NULL;
    $ssh_process = NULL;
    $is_running = NULL;
    $logger = SshTestSetup::createWipLog();
    try {
      // This is the actual call.
      $ssh = (new Ssh())->initialize($env, 'Test get result', $logger, 1);
      $ssh->addSuccessExitCode(15);
      $ssh->addSuccessExitCode(42);
      $ssh_process = $ssh->execAsyncCommand('echo "Hi"');
      $completed = FALSE;

      // Wait for the process to complete.  This shouldn't take long.
      do {
        try {
          $completed = $ssh_process->hasCompleted($logger);
        } catch (\Exception $e) {
        }
      } while (!$completed && time() - $ssh_process->getStartTime() < $max_run_time);
      $result = $ssh_process->getResult($logger, TRUE);
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
    $expected_success_codes = array(0, 15, 42);
    $success_codes = $result->getSuccessExitCodes();
    $this->assertEmpty(array_diff($expected_success_codes, $success_codes));
    $this->assertEquals(count($expected_success_codes), count($success_codes));
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testRelease() {
    $env = SshTestSetup::setUpLocalSsh();
    $exception = NULL;
    $ssh_process = NULL;
    $is_running = NULL;
    $logger = SshTestSetup::createWipLog();
    try {
      // This is the actual call.
      $ssh = (new Ssh())->initialize($env, 'Test release', $logger, 1);
      $ssh_process = $ssh->execAsyncCommand('echo "Hi"');

      // Don't care about the result; just release server resources.
      $ssh_process->release($logger);
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
  public function testAddInterpreter() {
    $proc = $this->createSshProcess();
    $proc->setResultInterpreter(new StatResultInterpreter('/tmp', 0));
    $interpreter = $proc->getResultInterpreter();
    $this->assertInstanceOf('Acquia\Wip\Ssh\SshResultInterpreter', $interpreter);
  }

  /**
   * Missing summary.
   */
  private function createSshProcess(
    $environment = NULL,
    $description = 'testing',
    $pid = 57,
    $start_time = 10,
    $id = 0
  ) {
    if (empty($environment)) {
      $environment = $this->createEnvironment();
    }
    return new SshProcess($environment, $description, $pid, $start_time, $id);
  }

  /**
   * Missing summary.
   */
  private function createEnvironment() {
    $result = new Environment();

    return $result;
  }

}
