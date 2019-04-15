<?php

namespace Acquia\WipIntegrations\DoctrineORM\Entities;

use Acquia\WipIntegrations\DoctrineORM\ServerStore;
use Acquia\WipService\Test\AbstractFunctionalTest;
use Acquia\Wip\Runtime\Server;
use Acquia\Wip\ServerStatus;

/**
 * Missing summary.
 *
 * @todo These tests seem eerily similar to those in ServerStoreTest in wipng.
 */
class ServerRepositoryFunctionalTest extends AbstractFunctionalTest {

  /**
   * The ServerStore instance.
   *
   * @var \Acquia\Wip\Storage\ServerStore
   */
  private $serverStore;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // For some reason the singleton produced by the WipFactory makes tests go
    // peculiar - after a few tests, it complains about the entity manager being
    // "closed". Leave this as a concrete object for now.
    $this->serverStore = new ServerStore();
  }

  /**
   * Missing summary.
   */
  public function testAddServer() {
    $server = new Server('test.server.example.com');
    $server->setTotalThreads(rand());
    $server->setStatus(ServerStatus::NOT_AVAILABLE);

    // Ensure that the server is not yet stored.
    $server_check = $this->serverStore->getServerByHostname($server->getHostname());
    $this->assertTrue(empty($server_check));

    // Save the server.
    $this->serverStore->save($server);

    // Ensure that the server was properly stored.
    $server_check = $this->serverStore->getServerByHostname($server->getHostname());
    $this->assertEquals($server->getId(), $server_check->getId());
    $this->assertEquals($server->getHostname(), $server_check->getHostname());
    $this->assertEquals($server->getTotalThreads(), $server_check->getTotalThreads());
    $this->assertEquals($server->getStatus(), $server_check->getStatus());
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
    $host1 = 'test1.server.example.com';
    $host2 = 'test2.server.example.com';
    $host3 = 'test3.server.example.com';

    // Add servers.
    $server1 = new Server($host1);
    $this->serverStore->save($server1);
    $server2 = new Server($host2);
    $this->serverStore->save($server2);
    $server3 = new Server($host3);
    $server3->setStatus(ServerStatus::NOT_AVAILABLE);
    $this->serverStore->save($server3);

    // Verify that the servers are there.
    $server_list = $this->serverStore->getActiveServers();
    $this->assertTrue(!empty($server_list[$host1]) && $server_list[$host1]->getId() == $server1->getId());
    $this->assertTrue(!empty($server_list[$host2]) && $server_list[$host2]->getId() == $server2->getId());
    $this->assertTrue(empty($server_list[$host3]));
  }

  /**
   * Missing summary.
   *
   * @todo These tests seem the same as in ServerStoreTest in wipng.
   */
  public function testGetAllServerList() {
    $host1 = 'test1.server.example.com';
    $host2 = 'test2.server.example.com';
    $host3 = 'test3.server.example.com';

    // Add servers.
    $server1 = new Server($host1);
    $this->serverStore->save($server1);
    $server2 = new Server($host2);
    $this->serverStore->save($server2);
    $server3 = new Server($host3);
    $server3->setStatus(ServerStatus::NOT_AVAILABLE);
    $this->serverStore->save($server3);

    // Verify that the servers are there.
    $server_list = $this->serverStore->getAllServers();
    $this->assertTrue(!empty($server_list[$host1]) && $server_list[$host1]->getId() == $server1->getId());
    $this->assertTrue(!empty($server_list[$host2]) && $server_list[$host2]->getId() == $server2->getId());
    $this->assertTrue(!empty($server_list[$host3]) && $server_list[$host3]->getId() == $server3->getId());
  }

  /**
   * Missing summary.
   *
   * @expectedException \Doctrine\DBAL\DBALException
   */
  public function testInvalidServerSave() {
    // Force our way through to cover the code that rethrows the DBALException.
    $server = new Server('test.example.com');
    $reflector = new \ReflectionClass($server);
    $status_field = $reflector->getProperty('status');
    $status_field->setAccessible(TRUE);
    $status_field->setValue($server, NULL);
    $this->serverStore->save($server);
  }

}
