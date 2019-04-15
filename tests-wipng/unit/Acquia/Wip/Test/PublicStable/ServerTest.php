<?php

namespace Acquia\Wip\Test\PublicStable\Ssh;

use Acquia\Wip\Runtime\Server;
use Acquia\Wip\ServerStatus;

/**
 * Missing summary.
 */
class ServerTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testAddServerInvalidHostname() {
    $server = new Server(' ');
  }

  /**
   * Missing summary.
   */
  public function testConstructor() {
    $hostname = 'testhostname';
    $server = new Server($hostname);
    $this->assertTrue($server instanceof Server);
    $this->assertObjectHasAttribute('hostname', $server);
    $this->assertEquals($hostname, $server->getHostname());
  }

  /**
   * Missing summary.
   */
  public function testDefaults() {
    $server = new Server('testhostname');
    $this->assertGreaterThan(0, $server->getTotalThreads());
    $this->assertEquals(0, $server->getActiveThreads());
    $this->assertGreaterThan(0, $server->getFreeThreads());
    $this->assertTrue(ServerStatus::isValid($server->getStatus()));
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testStringId() {
    $server = new Server('testhostname');
    $server->setId('invalid');
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testNegativeId() {
    $server = new Server('testhostname');
    $server->setId(-1);
  }

  /**
   * Missing summary.
   */
  public function testIdMutator() {
    $server = new Server('testhostname');
    $id = rand();
    $server->setId($id);
    $this->assertEquals($server->getId(), $id);
  }

  /**
   * Missing summary.
   */
  public function testHostnameMutator() {
    $server = new Server('testhostname');
    $server->setHostname('test.server.example.com');
    $this->assertEquals($server->getHostname(), 'test.server.example.com');
  }

  /**
   * Missing summary.
   */
  public function testStatusMutator() {
    $server = new Server('testhostname');
    $server->setStatus(ServerStatus::NOT_AVAILABLE);
    $this->assertEquals($server->getStatus(), ServerStatus::NOT_AVAILABLE);
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidStatus() {
    $server = new Server('testhostname');
    $server->setStatus(-1);
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testStringTotalThreads() {
    $server = new Server('testhostname');
    $server->setTotalThreads('invalid');
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testZeroTotalThreads() {
    $server = new Server('testhostname');
    $server->setTotalThreads(0);
  }

  /**
   * Missing summary.
   */
  public function testTotalThreadsMutator() {
    $server = new Server('testhostname');
    $thread = rand();
    $server->setTotalThreads($thread);
    $this->assertEquals($server->getTotalThreads(), $thread);
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testStringActiveThreads() {
    $server = new Server('testhostname');
    $server->setActiveThreads('invalid');
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testNegativeActiveThreads() {
    $server = new Server('testhostname');
    $server->setActiveThreads(-1);
  }

  /**
   * Missing summary.
   */
  public function testActiveThreadsMutator() {
    $server = new Server('testhostname');
    $thread = rand();
    $server->setActiveThreads($thread);
    $this->assertEquals($server->getActiveThreads(), $thread);
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testStringFreeThreads() {
    $server = new Server('testhostname');
    $server->setFreeThreads('invalid');
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testNegativeFreeThreads() {
    $server = new Server('testhostname');
    $server->setFreeThreads(-1);
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testTooManyFreeThreads() {
    $server = new Server('testhostname');
    $server->setFreeThreads($server->getTotalThreads() + 1);
  }

  /**
   * Missing summary.
   */
  public function testFreeThreadsMutator() {
    $server = new Server('testhostname');
    $total_threads = rand(5, 10);
    $free_threads = rand(0, 5);
    $server->setTotalThreads($total_threads);
    $server->setFreeThreads($free_threads);
    $this->assertEquals($server->getFreeThreads(), $free_threads);
  }

}
