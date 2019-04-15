<?php

namespace Acquia\Wip\Test\PrivateStable\Storage;

use Acquia\Wip\Runtime\Server;
use Acquia\Wip\ServerStatus;

/**
 * Missing summary.
 */
class ServerStoreTest extends \PHPUnit_Framework_TestCase {

  /**
   * The ServerStore instance.
   *
   * @var \Acquia\Wip\Storage\ServerStore
   */
  private $serverStore;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    $this->serverStore = \Acquia\Wip\WipFactory::getObject('acquia.wip.storage.server');
    $this->serverStore->initialize();
  }

  /**
   * Missing summary.
   */
  public function testAddServer() {
    $server = new Server('test.server.example.com');

    // Ensure that the server is not yet stored.
    $server_check = $this->serverStore->getServerByHostname($server->getHostname());
    $this->assertTrue(empty($server_check));

    // Save the server.
    $this->serverStore->save($server);

    // Ensure that the server was properly stored.
    $server_check = $this->serverStore->getServerByHostname($server->getHostname());
    $this->assertEquals($server->getId(), $server_check->getId());
  }

  /**
   * Missing summary.
   */
  public function testUpdateServer() {
    // Save the server.
    $server = new Server('test.server.example.com');
    $this->serverStore->save($server);

    // Ensure that the server was properly stored.
    $server_check = $this->serverStore->getServerByHostname($server->getHostname());
    $this->assertEquals($server->getId(), $server_check->getId());

    // Update the server.
    $server->setTotalThreads(6);
    $this->serverStore->save($server);

    // Ensure that the server was properly stored.
    $server_check = $this->serverStore->getServerByHostname($server->getHostname());
    $this->assertEquals($server->getId(), $server_check->getId());
    $this->assertEquals($server->getTotalThreads(), $server_check->getTotalThreads());
  }

  /**
   * Missing summary.
   *
   * @expectedException \Acquia\Wip\Exception\ServerStoreSaveException
   */
  public function testAddServerDuplicateHostname() {
    $server1 = new Server('test.server.example.com');
    $this->serverStore->save($server1);

    $server2 = new Server('test.server.example.com');
    $this->serverStore->save($server2);
  }

  /**
   * Missing summary.
   */
  public function testGetServerById() {
    // Add servers.
    $server1 = new Server('test1.server.example.com');
    $this->serverStore->save($server1);
    $server2 = new Server('test2.server.example.com');
    $this->serverStore->save($server2);
    $server3 = new Server('test3.server.example.com');
    $this->serverStore->save($server3);

    // Find an existing server.
    $server_check = $this->serverStore->get($server2->getId());
    $this->assertTrue(!empty($server_check));
    $this->assertEquals($server2->getId(), $server_check->getId());
  }

  /**
   * Missing summary.
   */
  public function testRemoveServer() {
    // Add the server.
    $server = new Server('test.server.example.com');
    $this->serverStore->save($server);

    // Verify the the server is there.
    $server = $this->serverStore->getServerByHostname('test.server.example.com');
    $this->assertTrue(!empty($server));

    // Remove the server.
    $this->serverStore->remove($server);

    // Verify that the server has been removed.
    $server = $this->serverStore->getServerByHostname('test.server.example.com');
    $this->assertTrue(empty($server));
  }

  /**
   * Missing summary.
   */
  public function testGetActiveServerList() {
    // Add servers.
    $server1 = new Server('test1.server.example.com');
    $this->serverStore->save($server1);
    $server2 = new Server('test2.server.example.com');
    $this->serverStore->save($server2);
    $server3 = new Server('test3.server.example.com');
    $server3->setStatus(ServerStatus::NOT_AVAILABLE);
    $this->serverStore->save($server3);

    // Verify that the servers are there.
    $server_list = $this->serverStore->getActiveServers();
    $server1_id = $server_list[$server1->getHostname()]->getId();
    $this->assertTrue(!empty($server_list[$server1->getHostname()]) && $server1_id == $server1->getId());
    $server2_id = $server_list[$server2->getHostname()]->getId();
    $this->assertTrue(!empty($server_list[$server2->getHostname()]) && $server2_id == $server2->getId());
  }

  /**
   * Missing summary.
   */
  public function testGetAllServerList() {
    // Add servers.
    $server1 = new Server('test1.server.example.com');
    $this->serverStore->save($server1);
    $server2 = new Server('test2.server.example.com');
    $this->serverStore->save($server2);
    $server3 = new Server('test3.server.example.com');
    $server3->setStatus(ServerStatus::NOT_AVAILABLE);
    $this->serverStore->save($server3);

    // Verify that the servers are there.
    $server_list = $this->serverStore->getAllServers();
    $server1_id = $server_list[$server1->getHostname()]->getId();
    $this->assertTrue(!empty($server_list[$server1->getHostname()]) && $server1_id == $server1->getId());
    $server2_id = $server_list[$server2->getHostname()]->getId();
    $this->assertTrue(!empty($server_list[$server2->getHostname()]) && $server2_id == $server2->getId());
    $server3_id = $server_list[$server3->getHostname()]->getId();
    $this->assertTrue(!empty($server_list[$server3->getHostname()]) && $server3_id == $server3->getId());
  }

}
