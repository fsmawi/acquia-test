<?php

namespace Acquia\Wip\Test\PublicStable\Ssh;

use Acquia\Wip\Environment;
use Acquia\Wip\Ssh\SshResult;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogEntryInterface;
use Acquia\Wip\WipResult;

/**
 * Missing summary.
 */
class SshResultTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testInstantiation() {
    $exit_code = 1;
    $stdout = 'out';
    $stderr = 'err';
    $result = new SshResult($exit_code, $stdout, $stderr);
    $this->assertEquals($exit_code, $result->getExitCode());
    $this->assertEquals($stdout, $result->getStdout());
    $this->assertEquals($stderr, $result->getStderr());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \InvalidArgumentException
   */
  public function testNonIntExitCode() {
    new SshResult('1', 'out', 'err');
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \InvalidArgumentException
   */
  public function testNegativeExitCode() {
    new SshResult(-1, 'out', 'err');
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \InvalidArgumentException
   */
  public function testNonStringStdout() {
    new SshResult(0, 1, 'err');
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \InvalidArgumentException
   */
  public function testNonStringStderr() {
    new SshResult(0, 'out', 1);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testSuccess() {
    $result = new SshResult(0, 'out', 'err');
    $this->assertTrue($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testFail() {
    $result = new SshResult(1, 'out', 'err');
    $this->assertFalse($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testGetSuccessExitCodes() {
    $result = new SshResult(0, 'out', 'err');
    $this->assertEquals(array(0), $result->getSuccessExitCodes());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testAddSuccessExitCode() {
    $result = new SshResult(0, 'out', 'err');
    $result->addSuccessExitCode(15);
    $this->assertEquals(array(0, 15), $result->getSuccessExitCodes());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \InvalidArgumentException
   */
  public function testAddNonIntSuccessExitCode() {
    $result = new SshResult(0, 'out', 'err');
    $result->addSuccessExitCode(NULL);
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testAddDuplicateSuccessExitCode() {
    $result = new SshResult(0, 'out', 'err');
    $result->addSuccessExitCode(15);
    $result->addSuccessExitCode(15);
    $this->assertEquals(array(0, 15), $result->getSuccessExitCodes());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testNonzeroSuccess() {
    $result = new SshResult(24, 'out', 'err');
    $result->addSuccessExitCode(24);
    $this->assertTrue($result->isSuccess());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testToJson() {
    $exit_code = 0;
    $stdout = 'out';
    $stderr = 'err';
    $original = new SshResult($exit_code, $stdout, $stderr);

    $json = $original->toJson();
    $this->assertNotEmpty($json);

    $object = SshResult::objectFromJson($json);
    $duplicate = SshResult::fromObject($object);
    $this->assertNotEmpty($duplicate);
    $this->assertEquals($exit_code, $duplicate->getExitCode());
    $this->assertEquals($stdout, $duplicate->getStdout());
    $this->assertEquals($stderr, $duplicate->getStderr());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testToJsonNoExitCode() {
    $obj = new \stdClass();
    $obj->stdout = 'out';
    $obj->stderr = 'err';
    $object = SshResult::objectFromJson(json_encode($obj));
    $result = SshResult::fromObject($object);
    $this->assertEquals($obj->stdout, $result->getStdout());
    $this->assertEquals($obj->stderr, $result->getStderr());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testToJsonNoStdout() {
    $obj = new \stdClass();
    $obj->exitCode = 1;
    $obj->stderr = 'err';
    $object = SshResult::objectFromJson(json_encode($obj));
    $result = SshResult::fromObject($object);
    $this->assertEquals($obj->exitCode, $result->getExitCode());
    $this->assertEquals($obj->stderr, $result->getStderr());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testToJsonNoStderr() {
    $obj = new \stdClass();
    $obj->exitCode = 1;
    $obj->stdout = 'out';
    $object = SshResult::objectFromJson(json_encode($obj));
    $result = SshResult::fromObject($object);
    $this->assertEquals($obj->exitCode, $result->getExitCode());
    $this->assertEquals($obj->stdout, $result->getStdout());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   *
   * @expectedException \InvalidArgumentException
   */
  public function testFromObjectWrongInterface() {
    $obj = new \stdClass();
    $obj->exitCode = 1;
    $obj->stderr = 'err';
    $result = new WipResult();
    $result = SshResult::fromObject($obj, $result);
    $this->assertEquals($obj->exitCode, $result->getExitCode());
    $this->assertEquals($obj->stderr, $result->getStderr());
  }

  /**
   * Missing summary.
   *
   * @group Ssh
   */
  public function testGetUniqueId() {
    $pid = mt_rand(1, PHP_INT_MAX);
    $now = time();
    $start_time = $now - mt_rand(60, 120);
    $end_time = $now - mt_rand(1, 10);
    $result = new SshResult(24, 'out', 'err');
    $result->setPid($pid);
    $result->setStartTime($start_time);
    $result->setEndTime($end_time);
    $environment = new Environment();
    $environment->setServers(array('localhost'));
    $environment->selectNextServer();
    $result->setEnvironment($environment);
    $id = $result->getUniqueId();
    $this->assertNotEmpty($id);
  }

  /**
   * Ensure that we can set the result as a secure object.
   *
   * @group Ssh
   */
  public function testSetSecure() {
    $exit_code = 1;
    $stdout = 'out';
    $stderr = 'err';
    $result = new SshResult($exit_code, $stdout, $stderr);
    $this->assertEquals($stdout, $result->getStdout());
    $this->assertEquals($stderr, $result->getStderr());
    $result->setSecure(TRUE);
    $this->assertTrue($result->isSecure());
    $this->assertEquals(WipLogEntryInterface::OUTPUT_SUPPRESSED_MESSAGE, $result->getSecureStdout());
    $this->assertEquals(WipLogEntryInterface::OUTPUT_SUPPRESSED_MESSAGE, $result->getSecureStderr());
    WipFactory::addConfiguration('$acquia.wip.secure.debug => TRUE');
    $this->assertEquals($stdout, $result->getSecureStdout());
    $this->assertEquals($stderr, $result->getSecureStderr());
    WipFactory::addConfiguration('$acquia.wip.secure.debug => FALSE');
  }

}
