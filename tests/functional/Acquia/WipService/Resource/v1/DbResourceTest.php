<?php

namespace Acquia\WipService\Resource\v1;

use Acquia\WipService\Test\AbstractFunctionalTest;
use Acquia\Wip\WipFactory;

/**
 * Missing summary.
 */
class DbResourceTest extends AbstractFunctionalTest {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    WipFactory::setConfigPath('config/config.factory.test.cfg');
  }

  /**
   * Tests that we can call the db dumper.
   */
  public function testDumpRoute() {
    $client = $this->createClient('ROLE_ADMIN');
    $client->request('POST', '/dbbackup');
    $response = $client->getResponse();

    // Check that the route exists.
    $this->assertNotEquals(404, $response->getStatusCode());
    $this->assertEquals(201, $response->getStatusCode());
    $json = json_decode($response->getContent());
    $this->assertNotContains('mysqldump', $json->message);
    $this->assertContains('Database dump created at:', $json->message);
  }

  /**
   * Tests that only admins can dump the db.
   */
  public function testDumpAccess() {
    $client = $this->createClient('ROLE_USER');
    $client->request('POST', '/dbbackup');
    $response = $client->getResponse();
    $this->assertEquals(403, $response->getStatusCode());
  }

}
