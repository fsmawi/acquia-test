<?php

namespace Acquia\WipService\Resource\v1;

use Acquia\WipIntegrations\DoctrineORM\WipLogStore;
use Acquia\WipService\Test\AbstractFunctionalTest;
use Acquia\Wip\Storage\WipLogStoreInterface;

/**
 * Missing summary.
 */
class PingResourceTest extends AbstractFunctionalTest {

  /**
   * Missing summary.
   *
   * @var WipLogStoreInterface
   */
  private $storage = NULL;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    $this->storage = new WipLogStore($this->app);
  }

  /**
   * Tests that the ping resource responds appropriately.
   */
  public function testPing() {
    $start_time = time();
    $client = $this->createClient('ROLE_ADMIN');
    $client->request('GET', '/ping');
    $response = $client->getResponse();

    // Check that we got the expected 200 status code.
    $this->assertEquals(200, $response->getStatusCode());

    // Check that server_time exists in the response body.
    $content = json_decode($response->getContent());
    $this->assertNotEmpty($content->server_time);

    // Check that the time is greater than or equal to the start time above.
    $this->assertGreaterThanOrEqual($start_time, $content->server_time);

    // Check that a message was logged.
    $entries = $this->storage->load();
    $this->assertCount(2, $entries);

    // Check that the log message is correct.
    $entry = array_shift($entries);
    $message = $entry->getMessage();
    // Assumes IPv4 address.
    $regexp = '/Received ping request from \d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3} as user af7db973-21a5-40a7-9141-d6f9ae391161/';
    $this->assertRegExp($regexp, $message);
  }

}
